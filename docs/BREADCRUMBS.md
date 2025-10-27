# FrankenCMS Breadcrumbs

FrankenCMS provides automatic breadcrumb generation for all pages, posts, and taxonomy archives. Breadcrumbs help users understand their current location in your site's hierarchy and provide an easy way to navigate back to parent pages.

## Features

- **Automatic Generation**: Breadcrumbs are automatically created based on page hierarchy and URL structure
- **Performance Optimized**: Uses recursive CTEs to fetch all ancestors in a single database query
- **SEO Friendly**: Includes Schema.org structured data (BreadcrumbList) for better search engine understanding
- **Customizable**: Easy to style and configure to match your theme
- **Manual Definitions**: Supports custom breadcrumb definitions for non-CMS routes
- **Accessible**: Follows WCAG guidelines with proper ARIA labels and semantic HTML

## Quick Start

### Basic Usage

Add the breadcrumbs component to your theme layout:

```blade
<!DOCTYPE html>
<html>
<head>
    <title>{{ $page->post_title ?? 'My Site' }}</title>
</head>
<body>
    <!-- Breadcrumbs -->
    <x-franken-cms::breadcrumbs />

    <!-- Your content -->
    @yield('content')
</body>
</html>
```

That's it! Breadcrumbs will automatically appear on all pages, posts, and archive pages.

### Custom Styling

You can add custom CSS classes to the breadcrumbs component:

```blade
<x-franken-cms::breadcrumbs class="my-custom-breadcrumbs text-sm mb-4" />
```

## How It Works

### Pages

For hierarchical pages, breadcrumbs follow the parent-child relationship:

```
Home > About > Team > Leadership
```

Example page structure:
- About (parent_id: null)
  - Team (parent_id: About)
    - Leadership (parent_id: Team)

### Posts

Posts show the blog listing page as their parent:

```
Home > Blog > My First Post
```

The blog listing page is determined by the `post_page` setting in **Settings > Reading**.

### Taxonomy Archives

Taxonomy archives (categories, tags) show the blog listing as parent:

```
Home > Blog > Technology
```

## Configuration

Edit `config/franken-cms.php` to customize breadcrumb behavior:

```php
'breadcrumbs' => [
    'enabled'      => true,   // Enable/disable breadcrumbs globally
    'home_text'    => 'Home', // Text for the home link
    'show_current' => true,   // Show current page in breadcrumbs
],
```

### Disabling Breadcrumbs

To disable breadcrumbs globally:

```php
'breadcrumbs' => [
    'enabled' => false,
],
```

Or disable for specific pages in your template:

```blade
@if($page->post_slug !== 'homepage')
    <x-franken-cms::breadcrumbs />
@endif
```

## Manual Breadcrumb Definitions

For custom routes outside of the CMS, you can define breadcrumbs manually in `routes/breadcrumbs.php`:

```php
<?php

use Diglactic\Breadcrumbs\Breadcrumbs;

// Custom route breadcrumbs
Breadcrumbs::for('shop', function ($trail) {
    $trail->parent('franken-cms.home');
    $trail->push('Shop', route('shop'));
});

Breadcrumbs::for('product', function ($trail, $product) {
    $trail->parent('shop');
    $trail->push($product->name, route('product', $product));
});
```

Then in your view:

```blade
{{ Breadcrumbs::render('product', $product) }}
```

**Note**: You can use `franken-cms.home` as a parent to inherit the homepage breadcrumb.

## Styling Examples

### Tailwind CSS (Default)

The default breadcrumbs use Tailwind CSS classes and look great out of the box.

### Custom Separator

To change the separator (default is a chevron), you can override the Blade template by publishing views:

```bash
php artisan vendor:publish --tag="franken-cms-views"
```

Then edit `resources/views/vendor/franken-cms/components/breadcrumbs.blade.php`:

```blade
@if(!$loop->last)
    <span class="mx-2">/</span>  {{-- Changed from chevron to slash --}}
@endif
```

### Horizontal Layout with Border

```blade
<x-franken-cms::breadcrumbs class="py-3 px-4 border-b border-gray-200 bg-white" />
```

### Minimal Text-Only Style

```blade
<x-franken-cms::breadcrumbs class="text-xs text-gray-500 space-x-1" />
```

## Performance

### Query Optimization

FrankenCMS breadcrumbs are highly optimized:

- **Single Query**: All ancestors are fetched in one database query using recursive CTEs
- **Minimal Columns**: Only loads `id`, `post_slug`, `post_title`, and `parent_id` - skipping large JSON fields
- **No N+1 Queries**: Traditional ancestor loading would query once per level (5 levels = 5 queries). Our approach uses 1 query regardless of depth.

Performance comparison for a 5-level deep page:

| Method | Queries | Columns Loaded | JSON Parsing |
|--------|---------|---------------|--------------|
| Traditional | 5 | All (~10) | Yes |
| FrankenCMS | 1 | 4 | No |

**Result**: ~80-90% reduction in query time and memory usage

### Database Compatibility

Recursive CTEs are supported by:
- MySQL 8.0+
- PostgreSQL 8.4+
- SQLite 3.8.3+
- MariaDB 10.2+

## SEO Benefits

FrankenCMS breadcrumbs include Schema.org structured data:

```html
<nav aria-label="Breadcrumbs">
    <ol itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="/" itemprop="item">
                <span itemprop="name">Home</span>
            </a>
            <meta itemprop="position" content="1" />
        </li>
        <!-- ... -->
    </ol>
</nav>
```

This structured data helps search engines understand your site structure and may result in breadcrumb display in search results.

## Accessibility

FrankenCMS breadcrumbs follow WCAG 2.1 AA guidelines:

- Semantic HTML with proper `<nav>`, `<ol>`, `<li>` elements
- ARIA label (`aria-label="Breadcrumbs"`)
- Clear visual hierarchy
- Keyboard accessible links
- Sufficient color contrast (configurable via CSS)

## Advanced Usage

### Conditional Breadcrumbs

Show breadcrumbs only on specific post types:

```blade
@if($page->post_type === 'page')
    <x-franken-cms::breadcrumbs />
@endif
```

### Programmatic Access

Access breadcrumbs programmatically in your controllers:

```php
use Diglactic\Breadcrumbs\Breadcrumbs;

$breadcrumbs = Breadcrumbs::generate('franken-cms.page', $page);

foreach ($breadcrumbs as $breadcrumb) {
    echo $breadcrumb->title; // Page title
    echo $breadcrumb->url;   // Page URL
}
```

### Integrating with Existing Breadcrumbs

If you have existing manual breadcrumb definitions, you can reference CMS breadcrumbs as parents:

```php
// Your custom breadcrumb
Breadcrumbs::for('documentation', function ($trail) {
    // Use a CMS page as parent
    $docsPage = \FrankenCms\Models\Page::where('post_slug', 'docs')->first();
    $trail->parent('franken-cms.page', $docsPage);
    $trail->push('Documentation', route('docs'));
});
```

## Troubleshooting

### Breadcrumbs Not Showing

1. **Check config**: Ensure `breadcrumbs.enabled` is `true` in `config/franken-cms.php`
2. **Check current page**: The component requires a current page to be set. Make sure you're viewing a CMS page/post.
3. **Clear cache**: Run `php artisan config:clear` and `php artisan view:clear`

### Incorrect Hierarchy

Breadcrumb hierarchy follows the `parent_id` relationship, not categories or tags. Ensure your pages have the correct parent set in the Filament admin.

### Performance Issues

If you're experiencing slow breadcrumb generation:

1. **Check database**: Ensure you're using MySQL 8.0+ (or compatible) for recursive CTE support
2. **Check indexes**: The `parent_id` column should be indexed (FrankenCMS migrations include this)
3. **Profile queries**: Use Laravel Debugbar to verify only 1 query is executed

## API Reference

### Breadcrumb Routes

FrankenCMS automatically registers these breadcrumb names:

- `franken-cms.home` - Homepage
- `franken-cms.page` - Individual pages (requires `$page` parameter)
- `franken-cms.post` - Individual posts (requires `$post` parameter)
- `franken-cms.blog` - Blog listing page
- `franken-cms.taxonomy` - Taxonomy archives (requires `$taxonomy` and `$term` parameters)

### Component Props

The `<x-franken-cms::breadcrumbs />` component accepts:

- `class` - Additional CSS classes to add to the nav element

### Model Methods

The `Post` model includes:

```php
// Get ancestors efficiently (1 query)
$ancestors = $page->getBreadcrumbAncestors();
// Returns Collection of objects with: id, post_slug, post_title, parent_id

// Get ancestors traditionally (N queries)
$ancestors = $page->ancestors();
// Returns Collection of full Post models
```

**Recommendation**: Use `getBreadcrumbAncestors()` for breadcrumbs and similar use cases where you only need basic information.

## Examples

### Full Page Template with Breadcrumbs

```blade
{{-- resources/views/theme/page-default.blade.php --}}
@extends('theme.layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        {{-- Breadcrumbs --}}
        <x-franken-cms::breadcrumbs class="mb-6" />

        {{-- Page Content --}}
        <article>
            <h1 class="text-4xl font-bold mb-4">{{ $page->post_title }}</h1>

            <div class="prose max-w-none">
                {!! $page->renderRichContent('post_content') !!}
            </div>
        </article>
    </div>
@endsection
```

### Blog Archive with Breadcrumbs

```blade
{{-- resources/views/theme/archive.blade.php --}}
@extends('theme.layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        {{-- Breadcrumbs show: Home > Blog > Category Name --}}
        <x-franken-cms::breadcrumbs class="mb-6" />

        <h1 class="text-4xl font-bold mb-8">{{ $term->name }}</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($posts as $post)
                <article class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold mb-2">
                        <a href="{{ $post->permalink_url }}">{{ $post->post_title }}</a>
                    </h2>
                    <p class="text-gray-600">{{ $post->getMeta('post_teaser') }}</p>
                </article>
            @endforeach
        </div>
    </div>
@endsection
```

## Further Reading

- [diglactic/laravel-breadcrumbs Documentation](https://github.com/diglactic/laravel-breadcrumbs)
- [Schema.org BreadcrumbList](https://schema.org/BreadcrumbList)
- [WCAG 2.1 Breadcrumb Guidelines](https://www.w3.org/WAI/WCAG21/Techniques/general/G65)
