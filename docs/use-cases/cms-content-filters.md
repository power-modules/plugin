# Use Case: CMS Content Filters Pipeline

A classic CMS pipeline: convert Markdown to HTML, sanitize unsafe tags, and linkify URLs — all via pluggable filters.

## Goal

- Define a `ContentFilter` contract
- Implement `Markdown`, `Sanitizer`, and `Linkifier` plugins
- Provide them from a module via `ProvidesPlugins`
- Orchestrate them in order using `PluginRegistry`

## Interfaces & Plugins

```php
declare(strict_types=1);

use Modular\Plugin\Contract\Plugin;
use Modular\Plugin\PluginMetadata;

interface ContentFilter extends Plugin
{
    public function filter(string $htmlOrMarkdown): string;
}

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
```

## Module

```php
declare(strict_types=1);

use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Plugin\Contract\ProvidesPlugins;
use Modular\Plugin\Contract\PluginRegistry;

/** @implements ProvidesPlugins<ContentFilter> */
final class CmsFilterModule implements PowerModule, ProvidesPlugins, ExportsComponents
{
    public static function exports(): array
    {
        // Export the orchestrator so it can be resolved from the root container
        return [ContentFilterPipeline::class];
    }

    public static function getPlugins(): array
    {
        return [
            PluginRegistry::class => [
                MarkdownFilter::class,
                SanitizerFilter::class,
                LinkifierFilter::class,
            ],
        ];
    }

    public function register(ConfigurableContainerInterface $c): void
    {
        $c->set(MarkdownFilter::class, MarkdownFilter::class);
        $c->set(SanitizerFilter::class, SanitizerFilter::class);
        $c->set(LinkifierFilter::class, LinkifierFilter::class);

        // Bind orchestrator and inject the default PluginRegistry
        $c->set(ContentFilterPipeline::class, ContentFilterPipeline::class)
            ->addArguments([PluginRegistry::class]);
    }
}
```

## Orchestrator

```php
declare(strict_types=1);

use Modular\Plugin\Contract\PluginRegistry;

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
```

## App Wiring

```php
declare(strict_types=1);

use Modular\Framework\App\ModularAppBuilder;
use Modular\Plugin\PowerModule\Setup\PluginRegistrySetup;
use Modular\Plugin\Contract\PluginRegistry;

$app = (new ModularAppBuilder(__DIR__))
    ->withPowerSetup(...PluginRegistrySetup::withDefaults())
    ->withModules(CmsFilterModule::class)
    ->build();

/** @var PluginRegistry<ContentFilter> $registry */
$registry = $app->get(PluginRegistry::class);

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

## Metadata-driven UI snippet

Use metadata without instantiating plugins to populate a picker:

```php
foreach ($registry->listPluginMetadata() as $class => $meta) {
    echo $meta->name . ' — ' . $meta->description . "\n";
}
```

## Edge Cases

- If a class isn’t registered, `PluginNotRegisteredException` is thrown
- Order matters: sanitize after markdown to remove unsafe tags produced during conversion

## Acceptance Check

- Given the input above, the pipeline returns safe, linkified HTML
- `getRegisteredPlugins()` lists the three filters with readable names from metadata
