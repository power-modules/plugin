# Use Case: CMS Content Filters Pipeline

A classic CMS pipeline: convert Markdown to HTML, sanitize unsafe tags, and linkify URLs — all via pluggable filters.

## Goal

- Define a `ContentFilter` contract
- Use autoprovisioned `GenericPluginRegistry`
- Implement `Markdown`, `Sanitizer`, and `Linkifier` plugins
- Export the registry and orchestrator from a core module; adapters live in separate modules (3rd‑party friendly)
- Provide each filter from its own module via `ProvidesPlugins` (3rd‑party friendly)
- Orchestrate them in order using the default `PluginRegistry`

## Modules

```php
declare(strict_types=1);

use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\Contract\ProvidesPlugins;
use Modular\Plugin\Contract\PluginRegistry;
use Modular\Plugin\PluginMetadata;

interface ContentFilter extends Plugin
{
    public function filter(string $htmlOrMarkdown): string;
}

// Orchestrator that applies a sequence of content filters
final class ContentFilterPipeline
{
    /** @param PluginRegistry<ContentFilter> $registry */
    public function __construct(private PluginRegistry $registry) {}

    /** @param array<class-string<ContentFilter>> $filters */
    public function run(string $input, array $filters): string
    {
        $result = $input;
        foreach ($filters as $filterClass) {
            $filter = $this->registry->makePlugin($filterClass);
            $result = $filter->filter($result);
        }
        return $result;
    }
}

// 1) Core module: exports the orchestrator; does not declare plugins
final class CmsFilterCoreModule implements PowerModule, ExportsComponents
{
    public static function exports(): array
    {
        return [ContentFilterPipeline::class];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        // Export orchestrator with the default PluginRegistry injected
        $c->set(ContentFilterPipeline::class, ContentFilterPipeline::class)
            ->addArguments([PluginRegistry::class]);
    }
}

// 2) Markdown plugin module: declares and registers only the Markdown filter
/** @implements ProvidesPlugins<ContentFilter> */
final class MarkdownFilter implements ContentFilter
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Markdown', '1.0.0', 'Converts Markdown to HTML');
    }

    public function filter(string $htmlOrMarkdown): string
    {
        // Tiny illustrative conversion; replace with league/commonmark or similar in real apps
        $html = $htmlOrMarkdown;
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html) ?? $html;
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html) ?? $html;
        $html = preg_replace('/\n/', '<br>', $html) ?? $html;
        return $html;
    }
}

final class MarkdownFilterModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [MarkdownFilter::class],
        ];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        $c->set(MarkdownFilter::class, MarkdownFilter::class);
    }
}

// 3) Sanitizer plugin module: declares and registers only the Sanitizer filter
/** @implements ProvidesPlugins<ContentFilter> */
final class SanitizerFilter implements ContentFilter
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Sanitizer', '1.0.0', 'Removes unsafe HTML');
    }

    public function filter(string $htmlOrMarkdown): string
    {
        // Naive sanitizer: strip script tags; real apps should use a library like HTML Purifier
        $safe = preg_replace('#<script\b[^<]*(?:(?!</script>)<[^<]*)*</script>#i', '', $htmlOrMarkdown) ?? $htmlOrMarkdown;
        return $safe;
    }
}

final class SanitizerFilterModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [SanitizerFilter::class],
        ];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        $c->set(SanitizerFilter::class, SanitizerFilter::class);
    }
}

// 4) Linkifier plugin module: declares and registers only the Linkifier filter
/** @implements ProvidesPlugins<ContentFilter> */
final class LinkifierFilter implements ContentFilter
{
    public static function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata('Linkifier', '1.0.0', 'Auto-links plain URLs');
    }

    public function filter(string $htmlOrMarkdown): string
    {
        // Very simple URL linkifier; avoid touching existing anchors
        return preg_replace(
            '~(?<!\")\bhttps?://[\w\-./?%&=#]+~i',
            '<a href="$0" rel="noopener noreferrer">$0</a>',
            $htmlOrMarkdown
        ) ?? $htmlOrMarkdown;
    }
}

final class LinkifierFilterModule implements PowerModule, ProvidesPlugins
{
    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [LinkifierFilter::class],
        ];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        $c->set(LinkifierFilter::class, LinkifierFilter::class);
    }
}
```

## App Wiring

```php
declare(strict_types=1);

use Modular\Framework\App\ModularAppBuilder;
use Modular\Plugin\PowerModule\Setup\PluginRegistrySetup;

$app = (new ModularAppBuilder(__DIR__))
    ->withPowerSetup(...PluginRegistrySetup::withDefaults())
    ->withModules(
        CmsFilterCoreModule::class, // provides the orchestrator
        MarkdownFilterModule::class,
        SanitizerFilterModule::class,
        LinkifierFilterModule::class,
    )
    ->build();

$pipeline = $app->get(ContentFilterPipeline::class);

$input = "Hello **world**! Visit https://example.com\n<script>alert('xss')</script>";
$output = $pipeline->run($input, [
    MarkdownFilter::class,
    SanitizerFilter::class,
    LinkifierFilter::class,
]);

// Example resulting HTML (formatted):
// Hello <strong>world</strong>! Visit <a href="https://example.com" rel="noopener noreferrer">https://example.com</a><br>
```

Note: When using `PluginRegistrySetup::withDefaults()`, the registry is also made injectable within modules via a convenience setup, so the orchestrator can depend on `PluginRegistry` without adding `ImportsComponents`.

Additional Notes:
- Third‑party filter modules (e.g., Markdown, Sanitizer, Linkifier) can live in separate repositories. They only depend on the shared `ContentFilter` contract and target the default `PluginRegistry` in `getPlugins()`.
- Only the core module should export the orchestrator. Plugin modules must not attempt to rebind the default registry; they simply declare their plugin classes against it.

## Edge Cases

- If a class isn’t registered, `PluginNotRegisteredException` is thrown
- Order matters: sanitize after markdown to remove unsafe tags produced during conversion

## Acceptance Check

- Given the input above, the pipeline returns safe, linkified HTML
- `getRegisteredPlugins()` lists the three filters with readable names from metadata
