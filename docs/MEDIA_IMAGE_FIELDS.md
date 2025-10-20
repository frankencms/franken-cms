# Media Image Fields

FrankenCMS provides a powerful image field system that integrates with Spatie MediaLibrary, allowing you to add images with rich metadata to your custom page templates.

## Features

- **Rich Metadata**: Alt text, title, caption, attribution, focal points, dimensions
- **Flexible Rendering**: Output as `<img>`, `<figure>`, or `<picture>` elements
- **Focal Point Support**: Smart image cropping with visual focal point picker
- **Lazy Loading**: Built-in support for performance optimization
- **Custom CSS Classes**: Add your own styling
- **Responsive Images**: Support for multiple image formats and sizes

## Usage in Templates

### Basic Image Field

Add an image field to your template using the `@cmsField()` directive:

```blade
{{-- resources/views/theme/page-landing.blade.php --}}

@cmsField('hero_image', 'media_image', [
    'label' => 'Hero Image',
    'collection' => 'hero_image',
])

<div class="hero">
    {{ cmsField('hero_image') }}
</div>
```

### Rendering Options

#### Simple `<img>` Tag (Default)

```blade
{{-- Renders: <img src="..." alt="..." width="..." height="..." loading="lazy" style="object-fit: cover; object-position: 50% 50%;"> --}}
{!! @cmsField('hero_image', 'media_image', [
    'collection' => 'hero_image',
    'format' => 'img', // Default format
]) !!}
```

#### `<figure>` with Caption

```blade
{{-- Renders a <figure> with <figcaption> if caption is set --}}
{!! @cmsField('team_photo', 'media_image', [
    'collection' => 'team_photo',
    'format' => 'figure',
    'show_caption' => true,
    'show_attribution' => true,
]) !!}

{{-- Output: --}}
<figure>
    <img src="..." alt="..." ...>
    <figcaption>
        <span class="caption-text">Our amazing team at the company retreat</span>
        <span class="caption-attribution">Photo by Jane Smith</span>
    </figcaption>
</figure>
```

#### `<picture>` Element for Responsive Images

```blade
{!! @cmsField('banner', 'media_image', [
    'collection' => 'banner',
    'format' => 'picture',
    'sources' => [
        [
            'conversion' => 'webp',
            'type' => 'image/webp',
        ],
        [
            'conversion' => 'mobile',
            'media' => '(max-width: 768px)',
        ],
    ],
]) !!}
```

#### Custom CSS Classes

```blade
{!! @cmsField('profile_image', 'media_image', [
    'collection' => 'profile_image',
    'class' => 'rounded-full shadow-xl mx-auto',
]) !!}
```

## Defining Fields in Templates

### Standard Image Field

```blade
@cmsField('hero_image', 'media_image', [
    'label' => 'Hero Background',
    'description' => 'Main hero section background image',
    'collection' => 'hero_image',
    'maxSize' => 10240, // 10MB
    'acceptedFileTypes' => ['image/jpeg', 'image/png', 'image/webp'],
])
```

### Multiple Images on Same Page

```blade
@cmsField('header_logo', 'media_image', [
    'label' => 'Header Logo',
    'collection' => 'header_logo',
])

@cmsField('hero_background', 'media_image', [
    'label' => 'Hero Background',
    'collection' => 'hero_background',
])

@cmsField('about_image', 'media_image', [
    'label' => 'About Section Image',
    'collection' => 'about_image',
])
```

## Admin UI

When you define a `media_image` field in your template, editors will see:

1. **Image Preview**: Visual preview of the uploaded image
2. **Edit Details Button**: Opens a modal with:
   - Image uploader with built-in editor
   - Alt text field (for accessibility)
   - Title field (tooltip on hover)
   - Caption field (displayed in figure)
   - Attribution field (photo credit)
   - CSS classes input
   - Lazy loading toggle
   - Width/Height fields
   - **Visual Focal Point Picker** - Click to set the image focus point

### Saving Metadata

When an editor saves the page, all metadata is automatically stored in the media item's custom properties using the `ImageFieldSchema::saveImageMetadata()` method.

## Programmatic Usage

### Get Image Data

```php
use FrankenCms\Filament\Schemas\ImageFieldSchema;

$page = Post::find(1);
$imageData = ImageFieldSchema::getImageData($page, 'hero_image', 'hero_image');

// Returns:
[
    'url' => 'https://example.com/storage/...',
    'alt' => 'Description of image',
    'title' => 'Image title',
    'caption' => 'Photo caption',
    'attribution' => 'Photo by John Doe',
    'focal_x' => 45.5,
    'focal_y' => 60.2,
    'width' => 1920,
    'height' => 1080,
    'css' => 'rounded shadow-lg',
    'loading' => 'lazy',
    'media' => MediaObject,
]
```

### Save Metadata

```php
use FrankenCms\Filament\Schemas\ImageFieldSchema;

ImageFieldSchema::saveImageMetadata(
    $page,
    'hero_image',
    $formData,
    'hero_image'
);
```

### Load Metadata for Forms

```php
use FrankenCms\Filament\Schemas\ImageFieldSchema;

$metadata = ImageFieldSchema::loadImageMetadata($page, 'hero_image', 'hero_image');

// Returns form data for populating the edit modal
```

## Integration with Custom Resources

If you're building custom Filament resources that need image fields with metadata:

```php
use FrankenCms\Filament\Schemas\ImageFieldSchema;
use Filament\Forms\Form;

public function form(Form $form): Form
{
    return $form->schema([
        // ... other fields

        Section::make('Hero Section')
            ->schema([
                ...ImageFieldSchema::make(
                    fieldName: 'hero_image',
                    collection: 'hero_image',
                    options: [
                        'label' => 'Hero Background Image',
                        'description' => 'Large background image for hero section',
                        'maxSize' => 15360, // 15MB
                    ]
                ),
            ]),
    ]);
}
```

### Save Hook

```php
protected function afterSave(): void
{
    ImageFieldSchema::saveImageMetadata(
        $this->record,
        'hero_image',
        $this->form->getState(),
        'hero_image'
    );
}
```

### Load Hook

```php
protected function mutateFormDataBeforeFill(array $data): array
{
    return array_merge(
        $data,
        ImageFieldSchema::loadImageMetadata(
            $this->record,
            'hero_image',
            'hero_image'
        )
    );
}
```

## Styling the Output

### Using Tailwind CSS

```blade
{!! @cmsField('hero', 'media_image', [
    'collection' => 'hero',
    'format' => 'figure',
    'class' => 'w-full h-[500px] object-cover rounded-lg shadow-2xl',
]) !!}
```

### Custom Styling with CSS Classes

In the admin, editors can add CSS classes via the "CSS Classes" field:

```
rounded-full shadow-xl border-4 border-white mx-auto max-w-md
```

### Focal Point Styling

The focal point is automatically applied as an inline style:

```html
<img src="..." style="object-fit: cover; object-position: 45.5% 60.2%;">
```

This ensures the important part of the image stays visible when cropped.

## Best Practices

### Accessibility

Always encourage editors to fill in the **Alt Text** field:

```blade
@cmsField('product_image', 'media_image', [
    'label' => 'Product Image',
    'collection' => 'product',
    'description' => 'Please add descriptive alt text for accessibility',
])
```

### Performance

Use lazy loading for below-the-fold images:

```blade
{{-- Lazy loading is ON by default --}}
{!! @cmsField('gallery_image', 'media_image', [
    'collection' => 'gallery',
]) !!}

{{-- Disable for above-the-fold images --}}
{!! @cmsField('hero', 'media_image', [
    'collection' => 'hero',
    // Set lazy_loading to false in admin UI
]) !!}
```

### Collections

Use unique collection names for each field to avoid conflicts:

```blade
{{-- Good ✅ --}}
@cmsField('hero_background', 'media_image', ['collection' => 'hero_background'])
@cmsField('about_photo', 'media_image', ['collection' => 'about_photo'])

{{-- Bad ❌ --}}
@cmsField('hero_background', 'media_image', ['collection' => 'images'])
@cmsField('about_photo', 'media_image', ['collection' => 'images'])
```

## Example: Complete Landing Page

```blade
{{-- resources/views/theme/page-landing.blade.php --}}

@cmsField('hero_background', 'media_image', [
    'label' => 'Hero Background',
    'collection' => 'hero_background',
])

@cmsField('about_image', 'media_image', [
    'label' => 'About Section Image',
    'collection' => 'about_image',
])

@cmsField('team_photo', 'media_image', [
    'label' => 'Team Photo',
    'collection' => 'team_photo',
])

<x-theme::layouts.main>
    {{-- Hero Section --}}
    <section class="hero relative h-screen">
        {!! @cmsField('hero_background', 'media_image', [
            'collection' => 'hero_background',
            'class' => 'absolute inset-0 w-full h-full object-cover',
        ]) !!}

        <div class="relative z-10 container mx-auto">
            <h1 class="text-6xl font-bold">{{ cmsField('hero.title') }}</h1>
        </div>
    </section>

    {{-- About Section --}}
    <section class="about py-20">
        <div class="container mx-auto grid md:grid-cols-2 gap-12">
            <div>
                <h2>{{ cmsField('about.title') }}</h2>
                <p>{{ cmsField('about.text') }}</p>
            </div>

            {!! @cmsField('about_image', 'media_image', [
                'collection' => 'about_image',
                'format' => 'figure',
                'show_caption' => true,
                'class' => 'rounded-lg shadow-xl',
            ]) !!}
        </div>
    </section>

    {{-- Team Section --}}
    <section class="team py-20 bg-gray-100">
        <div class="container mx-auto">
            <h2>{{ cmsField('team.title') }}</h2>

            {!! @cmsField('team_photo', 'media_image', [
                'collection' => 'team_photo',
                'format' => 'figure',
                'show_caption' => true,
                'show_attribution' => true,
                'class' => 'max-w-4xl mx-auto',
            ]) !!}
        </div>
    </section>
</x-theme::layouts.main>
```

## Troubleshooting

### Image not displaying

1. Check that the collection name matches in both the field definition and rendering
2. Verify the image was uploaded in the admin
3. Check storage permissions and disk configuration

### Metadata not saving

Ensure you're calling `ImageFieldSchema::saveImageMetadata()` in the appropriate save hook of your resource.

### Focal point not working

The focal point requires both `focal_x` and `focal_y` values. Make sure the visual picker was used in the admin to set these values.
