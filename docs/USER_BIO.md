# User Bio System

FrankenCMS provides a user bio system that allows authors to have rich profile information displayed on posts and other content. This includes biographical text, job titles, profile images, website links, and social media connections.

## Features

- **Profile Information**: Title/role, biography text, website URL
- **Profile Image**: Avatar with automatic thumbnail generation (200x200, 400x400)
- **Configurable Image Shape**: Choose between circular or square profile images
- **Social Links**: Flexible key-value storage for any social platform
- **Easy Integration**: Simple trait-based setup for your User model
- **Template Ready**: Pre-built examples in the post template

## Configuration

Configure user bio settings in `config/franken-cms.php`:

```php
'user_bio' => [
    // Profile image shape: 'circle' or 'square'
    'image_shape' => env('CMS_BIO_IMAGE_SHAPE', 'circle'),
],
```

Or set via environment variable:

```bash
# .env
CMS_BIO_IMAGE_SHAPE=circle  # or 'square'
```

| Option | Values | Default | Description |
|--------|--------|---------|-------------|
| `image_shape` | `circle`, `square` | `circle` | Shape of profile images in admin and templates |

When set to `circle`:
- Admin uploader shows circular preview with circle cropper
- Templates apply `rounded-full` class to images

When set to `square`:
- Admin uploader shows square preview
- Templates apply `rounded-lg` class to images

## Setup

### 1. Add HasBio Trait to Your User Model

Your application's User model must use the `HasBio` trait to enable bio functionality:

```php
<?php

namespace App\Models;

use FrankenCms\Traits\HasBio;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasBio;

    // ... rest of your model
}
```

### 2. Run Migrations

The `user_bios` table is created automatically when you run the FrankenCMS migrations:

```bash
php artisan migrate
```

## UserBio Model

The `FrankenCms\Models\UserBio` model stores all bio-related data.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `user_id` | int | Foreign key to the user |
| `title` | string | Job title or role (e.g., "Senior Developer") |
| `bio` | string | Biography text (supports HTML) |
| `website` | string | Personal website URL |
| `social_links` | array | Array of social link objects (see Social Links System) |

### Media Collections

The UserBio model uses Spatie MediaLibrary for profile images:

| Collection | Description |
|------------|-------------|
| `bio-image` | Single profile image |

### Media Conversions

| Conversion | Size | Description |
|------------|------|-------------|
| `bio-thumb` | 200x200 | Square thumbnail for compact displays |
| `bio-large` | 400x400 | Higher resolution for larger displays |

## HasBio Trait Methods

The `HasBio` trait provides these methods on your User model:

### `bio()`

Returns the HasOne relationship to the UserBio model.

```php
$user->bio; // Returns UserBio model or null
$user->bio->title; // Access bio properties
```

### `hasBio()`

Check if the user has a bio record.

```php
if ($user->hasBio()) {
    // User has bio information
}
```

### `getOrCreateBio()`

Get the existing bio or create a new empty one. Useful in admin forms.

```php
$bio = $user->getOrCreateBio();
$bio->title = 'New Title';
$bio->save();
```

## Social Links System

FrankenCMS provides a structured, config-driven social links system with:

- **Predefined platforms** - Select from 20+ preconfigured social platforms
- **Smart URL handling** - Enter a username OR full URL; usernames are automatically converted
- **Icon support** - Integrates with Blade Icons ecosystem
- **Config customization** - Add or override platforms via config only

### Data Structure

Social links are stored as an array of objects:

```php
[
    ['platform' => 'twitter', 'value' => 'username'],
    ['platform' => 'github', 'value' => 'https://github.com/username'],
]
```

This format supports ordering, allows both usernames and URLs, and enables multiple links per platform if needed.

### Available Platforms

The following platforms are available by default:

| Key | Platform | Example URL Pattern |
|-----|----------|---------------------|
| `twitter` | Twitter / X | `https://twitter.com/{username}` |
| `github` | GitHub | `https://github.com/{username}` |
| `linkedin` | LinkedIn | `https://linkedin.com/in/{username}` |
| `facebook` | Facebook | `https://facebook.com/{username}` |
| `instagram` | Instagram | `https://instagram.com/{username}` |
| `youtube` | YouTube | `https://youtube.com/@{username}` |
| `tiktok` | TikTok | `https://tiktok.com/@{username}` |
| `mastodon` | Mastodon | `https://mastodon.social/@{username}` |
| `bluesky` | Bluesky | `https://bsky.app/profile/{username}` |
| `threads` | Threads | `https://threads.net/@{username}` |
| `discord` | Discord | `https://discord.gg/{username}` |
| `twitch` | Twitch | `https://twitch.tv/{username}` |
| `dribbble` | Dribbble | `https://dribbble.com/{username}` |
| `behance` | Behance | `https://behance.net/{username}` |
| `medium` | Medium | `https://medium.com/@{username}` |
| `devto` | DEV.to | `https://dev.to/{username}` |
| `stackoverflow` | Stack Overflow | `https://stackoverflow.com/users/{username}` |
| `codepen` | CodePen | `https://codepen.io/{username}` |
| `pinterest` | Pinterest | `https://pinterest.com/{username}` |
| `reddit` | Reddit | `https://reddit.com/user/{username}` |

### Adding Custom Platforms

Add or override platforms in `config/franken-cms.php`:

```php
'social_platforms' => [
    'myplatform' => [
        'label' => 'My Platform',
        'url_pattern' => 'https://myplatform.com/u/{username}',
        'icon' => 'heroicon-o-link',  // Blade Icons component name
        'placeholder' => 'username or full URL',
    ],
    // Override an existing platform
    'mastodon' => [
        'label' => 'Mastodon',
        'url_pattern' => 'https://your-instance.social/@{username}',
        'icon' => 'fab-mastodon',
    ],
],
```

### Icon Support

Social link icons use the [Blade Icons](https://blade-ui-kit.com/blade-icons) ecosystem. The default configuration uses Font Awesome brand icons (`fab-*`). To enable icons:

1. Install a Blade Icons package:
   ```bash
   composer require owenvoke/blade-fontawesome
   ```

2. Icons will automatically render in the `<x-social-link>` component.

If an icon package is not installed, links will display with a generic link icon fallback.

## UserBio Model Methods

### `getSocialLinks()`

Get all social links with resolved URLs. Returns a collection of link objects.

```php
$links = $authorBio->getSocialLinks();

foreach ($links as $link) {
    echo $link['platform'];  // 'twitter'
    echo $link['value'];     // 'johndoe' or 'https://twitter.com/johndoe'
    echo $link['url'];       // Always a full URL: 'https://twitter.com/johndoe'
    echo $link['label'];     // 'Twitter / X'
    echo $link['icon'];      // 'fab-x-twitter'
}
```

### `hasSocialLinks()`

Check if the user has any social links configured.

```php
if ($authorBio->hasSocialLinks()) {
    // Show social links section
}
```

### `getSocialLink(string $key)` (Legacy)

Get a specific social link by platform key. Supports both new and legacy data formats.

```php
$twitter = $authorBio->getSocialLink('twitter');
$linkedin = $authorBio->getSocialLink('linkedin');
```

### `setSocialLink(string $key, ?string $value)` (Legacy)

Set a social link for a specific platform.

```php
$authorBio->setSocialLink('twitter', '@johndoe');
$authorBio->setSocialLink('github', 'https://github.com/johndoe');
$authorBio->save();
```

## Template Usage

### Basic Author Bio Display

```blade
@if ($post->author && $post->author->hasBio())
    @php
        $authorBio = $post->author->bio;
    @endphp

    <div class="author-bio">
        <h3>{{ $post->author->name }}</h3>

        @if ($authorBio->title)
            <p class="author-title">{{ $authorBio->title }}</p>
        @endif

        @if ($authorBio->bio)
            <div class="author-description">
                {!! $authorBio->bio !!}
            </div>
        @endif
    </div>
@endif
```

### With Profile Image

```blade
@if ($post->author && $post->author->hasBio())
    @php
        $authorBio = $post->author->bio;
        $bioImage = $authorBio->getFirstMedia('bio-image');
        $bioImageShape = config('franken-cms.user_bio.image_shape', 'circle');
    @endphp

    <div class="author-bio">
        {{-- Avatar --}}
        <div class="author-avatar">
            @if ($bioImage)
                <img
                    src="{{ $bioImage->getUrl('bio-thumb') }}"
                    alt="{{ $post->author->name }}"
                    class="{{ $bioImageShape === 'circle' ? 'rounded-full' : 'rounded-lg' }}"
                    width="80"
                    height="80"
                    loading="lazy"
                />
            @else
                {{-- Fallback: Display initials --}}
                <div class="avatar-initials {{ $bioImageShape === 'circle' ? 'rounded-full' : 'rounded-lg' }}">
                    {{ strtoupper(substr($post->author->name, 0, 1)) }}
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div class="author-info">
            <h3>{{ $post->author->name }}</h3>
            @if ($authorBio->title)
                <p>{{ $authorBio->title }}</p>
            @endif
        </div>
    </div>
@endif
```

### With Website and Social Links

```blade
@if ($post->author && $post->author->hasBio())
    @php
        $authorBio = $post->author->bio;
    @endphp

    <div class="author-bio">
        <h3>{{ $post->author->name }}</h3>

        {{-- Website Link --}}
        @if ($authorBio->website)
            <a href="{{ $authorBio->website }}" target="_blank" rel="noopener noreferrer">
                Visit Website
            </a>
        @endif

        {{-- Social Links using directive --}}
        <div class="social-links">
            @frankenSocialLinks($authorBio)
                <a
                    href="{{ $socialLink['url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-blue-600 hover:text-blue-800"
                >
                    {{ $socialLink['label'] }}
                </a>
            @endFrankenSocialLinks
        </div>
    </div>
@endif
```

### @frankenSocialLinks Directive

The `@frankenSocialLinks` directive iterates over a user bio's social links, giving you full control over the markup. This follows the same pattern as `@frankenMenu`.

**Usage:**

```blade
@frankenSocialLinks($authorBio)
    {{-- Your custom markup here --}}
    {{-- $socialLink is available inside the loop --}}
@endFrankenSocialLinks
```

**Available `$socialLink` properties:**

| Property | Type | Description |
|----------|------|-------------|
| `platform` | string | Platform key (e.g., 'twitter', 'github') |
| `value` | string | Original value entered (username or full URL) |
| `url` | string | Resolved full URL |
| `label` | string | Human-readable platform name (e.g., 'Twitter / X') |
| `icon` | string\|null | Blade Icons component name (e.g., 'fab-x-twitter') |

**Example with custom styling:**

```blade
@frankenSocialLinks($authorBio)
    <a
        href="{{ $socialLink['url'] }}"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center gap-2 px-3 py-1 bg-gray-100 rounded-full hover:bg-gray-200"
        aria-label="{{ $socialLink['label'] }}"
    >
        {{-- Your icon implementation --}}
        <span>{{ $socialLink['label'] }}</span>
    </a>
@endFrankenSocialLinks
```

**Example with Blade Icons (if installed):**

```blade
@frankenSocialLinks($authorBio)
    <a href="{{ $socialLink['url'] }}" target="_blank" rel="noopener noreferrer">
        @if($socialLink['icon'])
            <x-dynamic-component :component="'icon-' . $socialLink['icon']" class="size-5" />
        @endif
        <span class="sr-only">{{ $socialLink['label'] }}</span>
    </a>
@endFrankenSocialLinks
```

### Complete Example (From Post Template)

The `stubs/theme/post.blade.php` template includes a full author bio section with styling:

```blade
{{-- Author Bio --}}
@if ($post->author && $post->author->hasBio())
    @php
        $authorBio = $post->author->bio;
        $bioImage = $authorBio->getFirstMedia('bio-image');
        $bioImageShape = config('franken-cms.user_bio.image_shape', 'circle');
    @endphp
    <div class="author-card">
        <div class="flex gap-4">
            {{-- Avatar --}}
            <div class="shrink-0">
                @if ($bioImage)
                    <img
                        src="{{ $bioImage->hasGeneratedConversion('bio-thumb') ? $bioImage->getUrl('bio-thumb') : $bioImage->getUrl() }}"
                        alt="{{ $bioImage->getCustomProperty('alt') ?? $post->author->name }}"
                        class="size-16 object-cover {{ $bioImageShape === 'circle' ? 'rounded-full' : 'rounded-lg' }}"
                        loading="lazy"
                    />
                @else
                    <div class="size-16 bg-gray-200 flex items-center justify-center text-2xl font-bold {{ $bioImageShape === 'circle' ? 'rounded-full' : 'rounded-lg' }}">
                        {{ strtoupper(substr($post->author->name, 0, 1)) }}
                    </div>
                @endif
            </div>

            {{-- Info --}}
            <div class="flex-1">
                <h3 class="text-xl font-semibold">{{ $post->author->name }}</h3>

                @if ($authorBio->title)
                    <p class="text-sm text-gray-600">{{ $authorBio->title }}</p>
                @endif

                @if ($authorBio->bio)
                    <div class="prose prose-sm mt-2">
                        {!! $authorBio->bio !!}
                    </div>
                @endif

                {{-- Links --}}
                <div class="flex gap-4 mt-4">
                    @if ($authorBio->website)
                        <a href="{{ $authorBio->website }}" target="_blank" rel="noopener noreferrer">
                            Website
                        </a>
                    @endif

                    @foreach ($authorBio->social_links ?? [] as $platform => $url)
                        @if ($url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                                {{ ucfirst($platform) }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
```

## Programmatic Usage

### Creating a Bio

```php
use FrankenCms\Models\UserBio;

// Method 1: Using getOrCreateBio()
$bio = $user->getOrCreateBio();
$bio->title = 'Software Engineer';
$bio->bio = '<p>I write code and drink coffee.</p>';
$bio->website = 'https://example.com';
$bio->save();

// Method 2: Direct creation
$bio = UserBio::create([
    'user_id' => $user->id,
    'title' => 'Software Engineer',
    'bio' => '<p>I write code and drink coffee.</p>',
    'website' => 'https://example.com',
    'social_links' => [
        'twitter' => 'https://twitter.com/username',
        'github' => 'https://github.com/username',
        'linkedin' => 'https://linkedin.com/in/username',
    ],
]);
```

### Updating Social Links

Using the new structured format (recommended):

```php
$bio = $user->bio;

// Set social links as array of objects
$bio->social_links = [
    ['platform' => 'twitter', 'value' => 'johndoe'],       // Username only
    ['platform' => 'github', 'value' => 'johndoe'],        // Username only
    ['platform' => 'linkedin', 'value' => 'john-doe'],     // Username only
    ['platform' => 'mastodon', 'value' => 'https://mastodon.social/@johndoe'], // Full URL
];
$bio->save();
```

Using legacy methods (for backward compatibility):

```php
$bio = $user->bio;

// Set individual links
$bio->setSocialLink('twitter', '@johndoe');
$bio->setSocialLink('github', 'https://github.com/johndoe');
$bio->save();
```

### Adding a Profile Image

```php
$bio = $user->getOrCreateBio();

// Add image from path
$bio->addMedia('/path/to/image.jpg')
    ->toMediaCollection('bio-image');

// Add image from upload
$bio->addMediaFromRequest('avatar')
    ->toMediaCollection('bio-image');
```

### Retrieving Profile Image

```php
$bio = $user->bio;
$image = $bio->getFirstMedia('bio-image');

if ($image) {
    $thumbnailUrl = $image->getUrl('bio-thumb');  // 200x200
    $largeUrl = $image->getUrl('bio-large');      // 400x400
    $originalUrl = $image->getUrl();              // Original size
}
```

## Admin UI

User bios are managed through the Filament admin panel under the User resource. The bio editing interface includes:

- **Profile Image Upload**: Drag-and-drop image uploader
- **Title Field**: Text input for job title/role
- **Bio Editor**: Rich text editor for biography
- **Website Field**: URL input with validation
- **Social Links**: Dynamic key-value inputs for social platforms

## Supported Social Platforms

See the [Available Platforms](#available-platforms) section above for the complete list of 20+ supported social platforms. Each platform has:

- A human-readable label
- A URL pattern for username-to-URL conversion
- An icon component name (requires Blade Icons package)
- Placeholder text for the input field

To add custom platforms or override defaults, see [Adding Custom Platforms](#adding-custom-platforms).

## Best Practices

### Security

The `bio` field supports HTML. If you allow user-submitted content, sanitize it before saving:

```php
use Illuminate\Support\Str;

$bio->bio = Str::of($userInput)->sanitizeHtml();
$bio->save();
```

### Performance

When displaying multiple posts with author bios, eager load the relationships:

```php
$posts = Post::with(['author.bio' => function ($query) {
    $query->with('media'); // Eager load bio images
}])->get();
```

### Fallback Display

Always provide fallbacks for missing data:

```blade
{{-- Name fallback --}}
{{ $post->author?->name ?? 'Anonymous' }}

{{-- Avatar fallback to initials --}}
@if ($bioImage)
    <img src="{{ $bioImage->getUrl('bio-thumb') }}" alt="">
@else
    <span>{{ strtoupper(substr($post->author->name, 0, 1)) }}</span>
@endif

{{-- Check before accessing bio properties --}}
@if ($post->author?->hasBio())
    {{ $post->author->bio->title }}
@endif
```
