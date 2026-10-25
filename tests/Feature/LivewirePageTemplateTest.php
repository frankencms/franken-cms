<?php

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Page;
use FrankenCms\Services\PageRouteService;
use FrankenCms\Services\TemplateResolver;
use FrankenCms\Settings\ReadingSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

const LIVE_TEMPLATE = <<<'BLADE'
<?php

use FrankenCms\Livewire\Concerns\InteractsWithFrankenPage;
use Livewire\Component;

new class extends Component {
    use InteractsWithFrankenPage;

    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
};
?>

<div>
    <h1>@frankenText('hero.title', ['default' => 'Default hero'])</h1>
    <p>Live page: {{ $page->post_title }}</p>
    <span>Count: {{ $count }}</span>
    <button wire:click="increment">+</button>
</div>
BLADE;

const LIVE_LAYOUT = <<<'BLADE'
<html><body><div id="layout">{{ $slot }}</div></body></html>
BLADE;

const PLAIN_TEMPLATE = <<<'BLADE'
<div>Plain page: {{ $page->post_title }}</div>
BLADE;

beforeEach(function () {
    Config::set('franken-cms.theme_folder', 'theme');

    $this->themePath = resource_path('views/theme');
    $this->createdThemeDir = ! File::isDirectory($this->themePath);
    $this->fixtures = [
        "{$this->themePath}/page-live.blade.php"   => LIVE_TEMPLATE,
        "{$this->themePath}/page-plain.blade.php"  => PLAIN_TEMPLATE,
        "{$this->themePath}/live-layout.blade.php" => LIVE_LAYOUT,
    ];

    File::ensureDirectoryExists($this->themePath);

    foreach ($this->fixtures as $path => $contents) {
        File::put($path, $contents);
    }

    Config::set('franken-cms.livewire.layout', 'theme.live-layout');
});

afterEach(function () {
    foreach (array_keys($this->fixtures) as $path) {
        File::delete($path);
    }

    if ($this->createdThemeDir) {
        File::deleteDirectory($this->themePath);
    }
});

function livePage(array $attributes = [], string $template = 'page-live'): Page
{
    $page = Page::factory()->create(array_merge([
        'post_slug'   => 'live',
        'post_title'  => 'Live Page',
        'post_status' => PostStatus::PUBLISH,
    ], $attributes));

    $page->setMeta('template', $template);

    return $page->fresh();
}

it('only treats single-file components as livewire templates', function () {
    expect(TemplateResolver::isLivewireTemplate('page-live'))->toBeTrue()
        ->and(TemplateResolver::isLivewireTemplate('page-plain'))->toBeFalse()
        ->and(TemplateResolver::isLivewireTemplate('missing-template'))->toBeFalse();
});

it('renders a livewire page template through the fallback route', function () {
    livePage();

    $this->get('/live')
        ->assertOk()
        ->assertSee('<div id="layout">', false)
        ->assertSee('Live page: Live Page')
        ->assertSee('Count: 0')
        ->assertSee('wire:snapshot', false);
});

it('shares franken field values with the component', function () {
    livePage(['post_content' => ['custom_fields' => ['hero' => ['title' => 'Hello from the CMS']]]]);

    $this->get('/live')
        ->assertOk()
        ->assertSee('Hello from the CMS');
});

it('keeps field values and the page across livewire updates', function () {
    $page = livePage(['post_content' => ['custom_fields' => ['hero' => ['title' => 'Hello from the CMS']]]]);

    Livewire::test(TemplateResolver::livewireName('page-live'), ['page' => $page])
        ->assertSee('Hello from the CMS')
        ->call('increment')
        ->assertSee('Count: 1')
        ->assertSee('Hello from the CMS')
        ->assertSee('Live page: Live Page');
});

it('renders a livewire page template through a named page route', function () {
    livePage(['post_slug' => 'named-live', 'route_name' => 'named-live']);

    Cache::forget('franken_cms_page_routes');
    app(PageRouteService::class)->registerPageRoutes();
    app('router')->getRoutes()->refreshNameLookups();

    $this->get(route('named-live'))
        ->assertOk()
        ->assertSee('Live page: Live Page')
        ->assertSee('wire:snapshot', false);
});

it('still renders plain blade templates as views', function () {
    livePage(['post_slug' => 'plain'], 'page-plain');

    $this->get('/plain')
        ->assertOk()
        ->assertSee('Plain page: Live Page')
        ->assertDontSee('wire:snapshot', false);
});

it('does not render livewire templates when page components are disabled', function () {
    Config::set('franken-cms.livewire.page_components', false);

    expect(TemplateResolver::isLivewireTemplate('page-live'))->toBeFalse();
});

it('restores livewire.component_layout after rendering', function () {
    $original = config('livewire.component_layout');

    livePage();
    $this->get('/live')->assertOk();

    expect(config('livewire.component_layout'))->toBe($original);
});

it('lets the blog page render a livewire template without a posts paginator', function () {
    livePage(['post_slug' => 'blog']);

    $settings = app(ReadingSettings::class);
    $settings->post_page = 'blog';
    $settings->save();

    $this->get('/blog')
        ->assertOk()
        ->assertSee('Live page: Live Page');
});
