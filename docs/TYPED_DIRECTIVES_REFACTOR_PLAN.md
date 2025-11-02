# Typed Field Directives Refactor Plan

**Date**: 2025-01-11
**Status**: Approved - Ready for Implementation

## Overview

Refactor from single `@cmsField()` directive to specific typed directives (`@frankenText`, `@frankenRepeater`, etc.) with dual access patterns:
1. **Directives** for declarative rendering with auto-echo
2. **Helper function** `frankenField()` for programmatic raw data access

## User Requirements

✅ **Repeater variables**: Namespaced access via `$franken->title` (safer, prevents conflicts)
✅ **Repeater syntax**: `@frankenRepeater('field', [...options])` - NO type parameter
✅ **Backward compat**: Keep `@cmsField` working with deprecation path
✅ **Output behavior**: Auto-echo for directives (current behavior)
✅ **Helper name**: `frankenField('field.name')`
✅ **Helper returns**: Always raw/processed data (arrays, collections, strings)
✅ **Dual access**: Support BOTH `@frankenRepeater` block AND `frankenField('items')` manual foreach
✅ **Auto-populate**: ALL directives populate `$cmsFields` for maximum flexibility

---

## Key Design Principles

### Directive vs Helper Access Patterns

**Directives (Declarative):**
- `@frankenText('title')` → Auto-echo rendered HTML
- `@frankenTags('skills')` → Auto-echo formatted tag HTML
- `@frankenRepeater('items')...@endFrankenRepeater` → Auto-loop with template

**Helper (Programmatic):**
- `frankenField('title')` → Raw string value
- `frankenField('skills')` → Array `['Laravel', 'PHP', 'Tailwind']`
- `frankenField('items')` → Collection for manual `@foreach`

### Example Use Cases

**Tags - Rendered Output:**
```blade
@frankenTags('skills')
{{-- Auto-outputs: <span>Laravel</span><span>PHP</span> --}}
```

**Tags - Custom Rendering:**
```blade
@foreach (frankenField('skills') as $skill)
    <badge>{{ $skill }}</badge>
@endforeach
```

**Repeater - Template Block:**
```blade
@frankenRepeater('features', [...])
    <div>{{ $franken->title }}</div>
@endFrankenRepeater
```

**Repeater - Manual Control:**
```blade
@php $features = frankenField('features'); @endphp
<x-feature-grid :items="$features" />

{{-- Or manual foreach: --}}
@foreach (frankenField('features') as $feature)
    <custom-layout :data="$feature" />
@endforeach
```

---

## Implementation Phases

### **Phase 1: Create frankenField() Helper**

**File**: `src/helpers.php`

Add new global helper function:

```php
if (! function_exists('frankenField')) {
    /**
     * Get raw field data from current page's custom fields
     *
     * Returns unrendered/processed data:
     * - Simple fields: raw string/number/boolean
     * - Tags: array of tag strings
     * - Repeaters: Collection of items (cleaned structure)
     * - Media: media object or URL
     *
     * @param string $fieldName Field name (supports dot notation)
     * @return mixed Raw field value
     */
    function frankenField(string $fieldName): mixed
    {
        $currentPage = app(\FrankenCms\Services\CurrentPageService::class)->getPage();

        if (!$currentPage) {
            return null;
        }

        $value = data_get($currentPage->custom_fields, $fieldName);

        // For repeaters, apply cleaning (remove custom_fields nesting)
        if (is_array($value) && !empty($value)) {
            $first = reset($value);
            if (is_array($first) && isset($first['custom_fields'])) {
                // It's a repeater - clean the structure
                return collect($value)->map(function ($item) {
                    if (is_array($item) && isset($item['custom_fields'])) {
                        return (object) array_merge($item, $item['custom_fields']);
                    }
                    return (object) $item;
                });
            }
        }

        return $value;
    }
}
```

**Also add helper for expression parsing:**

```php
if (! function_exists('_parseFieldExpression')) {
    /**
     * Parse field directive expression into name and options
     *
     * @internal Used by Blade directives
     */
    function _parseFieldExpression(string $fieldName, array $options = []): array
    {
        return ['name' => $fieldName, 'options' => $options];
    }
}
```

### **Phase 2: Create Typed Field Directives** (Non-Repeater)

**File**: `src/FrankenCmsServiceProvider.php`

Add method `registerTypedFieldDirectives()` and call it from `packageBooted()`:

```php
protected function registerTypedFieldDirectives(): void
{
    $fieldTypes = [
        'Text' => 'text',
        'Textarea' => 'textarea',
        'Email' => 'email',
        'Url' => 'url',
        'Number' => 'number',
        'Select' => 'select',
        'File' => 'file',
        'Image' => 'image',
        'MediaImage' => 'mediaImage',
        'RichEditor' => 'richEditor',
        'Toggle' => 'toggle',
        'Checkbox' => 'checkbox',
        'Tags' => 'tags',
    ];

    foreach ($fieldTypes as $directiveSuffix => $fieldType) {
        Blade::directive("franken{$directiveSuffix}", function ($expression) use ($fieldType) {
            return "<?php
                \$__fParams = _parseFieldExpression({$expression});
                \$__fName = \$__fParams['name'];
                \$__fOpts = \$__fParams['options'];
                \$__fVar = cmsFieldVariableName(\$__fName);

                if (!isset(\$cmsFields)) {
                    \$cmsFields = collect();
                    view()->share('cmsFields', \$cmsFields);
                }

                if (!\$cmsFields->has(\$__fVar)) {
                    \$__fValue = _renderCmsField(\$__fName, '{$fieldType}', \$__fOpts);
                    \$cmsFields[\$__fVar] = \$__fValue;
                    view()->share('cmsFields', \$cmsFields);
                } else {
                    \$__fValue = \$cmsFields->get(\$__fVar);
                }

                echo \$__fValue;
                unset(\$__fParams, \$__fName, \$__fOpts, \$__fVar, \$__fValue);
            ?>";
        });
    }
}
```

**Update `packageBooted()` to call this:**

```php
protected function packageBooted(): void
{
    // ... existing code ...

    $this->registerBladeDirectives();
    $this->registerTypedFieldDirectives(); // ADD THIS LINE

    // ... rest of code ...
}
```

### **Phase 3: Implement Repeater Block Directive**

**File**: `src/FrankenCmsServiceProvider.php`

Add to `registerTypedFieldDirectives()` method AFTER the foreach loop:

```php
// Add repeater block directive (after the foreach loop)
Blade::directive('frankenRepeater', function ($expression) {
    return "<?php
        \$__rptParams = _parseFieldExpression({$expression});
        \$__rptName = \$__rptParams['name'];
        \$__rptOpts = \$__rptParams['options'];
        \$__rptVar = cmsFieldVariableName(\$__rptName);

        if (!isset(\$cmsFields)) {
            \$cmsFields = collect();
            view()->share('cmsFields', \$cmsFields);
        }

        if (!\$cmsFields->has(\$__rptVar)) {
            \$__rptCollection = _renderCmsField(\$__rptName, 'repeater', \$__rptOpts);
            \$cmsFields[\$__rptVar] = \$__rptCollection;
            view()->share('cmsFields', \$cmsFields);
        } else {
            \$__rptCollection = \$cmsFields[\$__rptVar];
        }

        foreach (\$__rptCollection as \$__rptItem):
            \$franken = \$__rptItem;
    ?>";
});

Blade::directive('endFrankenRepeater', function () {
    return "<?php
        endforeach;
        unset(\$franken, \$__rptItem, \$__rptCollection, \$__rptVar, \$__rptName, \$__rptOpts, \$__rptParams);
    ?>";
});
```

**Note**: RepeaterFieldRenderer already returns cleaned items as objects (from our previous fix), so `$franken` will be an object with properties accessible via `$franken->title`.

### **Phase 4: Update Template Parser**

**File**: `src/Services/TemplateFieldParser.php`

Update the `parseContent()` method to detect new directives:

```php
protected function parseContent(string $content): array
{
    $fields = [];

    // Pattern for new typed directives
    $typedPattern = '/@franken(Text|Textarea|Email|Url|Number|Select|File|Image|MediaImage|RichEditor|Toggle|Checkbox|Tags|Repeater)\s*\(\s*([\'"])([^\'"]+)\2(?:\s*,\s*(\[.*?\]))?\s*\)/s';

    if (preg_match_all($typedPattern, $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $directiveType = $match[1]; // e.g., "Text", "Repeater"
            $fieldName = $match[3];
            $optionsString = $match[4] ?? '[]';

            // Convert directive name to field type
            $fieldType = strtolower($directiveType);
            if ($fieldType === 'mediaimage') {
                $fieldType = 'media_image';
            } elseif ($fieldType === 'richeditor') {
                $fieldType = 'richEditor';
            }

            $fields[$fieldName] = [
                'name' => $fieldName,
                'type' => $fieldType,
                'options' => eval("return {$optionsString};"),
            ];
        }
    }

    // Keep support for old @cmsField directive (backward compat)
    $oldPattern = '/@cmsField\s*\(\s*([\'"])([^\'"]+)\1\s*,\s*([\'"])([^\'"]+)\3(?:\s*,\s*(\[.*?\]))?\s*\)/s';

    if (preg_match_all($oldPattern, $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $fieldName = $match[2];
            $fieldType = $match[4];
            $optionsString = $match[5] ?? '[]';

            // Only add if not already present from typed directive
            if (!isset($fields[$fieldName])) {
                $fields[$fieldName] = [
                    'name' => $fieldName,
                    'type' => $fieldType,
                    'options' => eval("return {$optionsString};"),
                ];
            }
        }
    }

    return $fields;
}
```

### **Phase 5: Update Stubs and Documentation**

**Files**: `stubs/theme/*.blade.php`

Update stub templates to use new directives. Example:

```blade
{{-- Hero Section --}}
<section class="hero">
    <h1>@frankenText('hero.title')</h1>
    <div>@frankenRichEditor('hero.content')</div>
</section>

{{-- Features with Block Directive --}}
@frankenRepeater('features.items', [
    'schema' => [
        ['name' => 'icon', 'type' => 'text'],
        ['name' => 'title', 'type' => 'text'],
        ['name' => 'description', 'type' => 'textarea'],
    ]
])
    <div class="feature">
        <span>{{ $franken->icon }}</span>
        <h3>{{ $franken->title }}</h3>
        <p>{{ $franken->description }}</p>
    </div>
@endFrankenRepeater

{{-- Tags with Custom Rendering --}}
<div class="tags">
    @foreach (frankenField('skills') as $skill)
        <span class="tag">{{ $skill }}</span>
    @endforeach
</div>

{{-- Repeater with Manual Control (Pass to Component) --}}
<x-feature-grid :features="frankenField('features')" />
```

### **Phase 6: Backward Compatibility**

**File**: `src/FrankenCmsServiceProvider.php`

Add deprecation notice to existing `@cmsField` directive (in the `registerBladeDirectives()` method):

```php
// At the top of the @cmsField directive registration
Blade::directive('cmsField', function ($expression) {
    // Add deprecation warning
    if (config('app.debug')) {
        \Log::channel('daily')->warning(
            '@cmsField directive is deprecated. Use typed directives like @frankenText, @frankenRepeater, or frankenField() helper instead.',
            ['expression' => $expression]
        );
    }

    // Keep existing implementation working...
    // (rest of current code remains unchanged)
});
```

### **Phase 7: Tests and Documentation**

1. **Test Coverage**:
   - Test all 13 field type directives
   - Test `frankenField()` helper returns raw data
   - Test repeater block directive with `$franken` variable
   - Test dual access (directive + helper for same field)
   - Test backward compatibility with `@cmsField`

2. **Documentation**:
   - Create migration guide: `@cmsField` → typed directives
   - Document `frankenField()` helper use cases
   - Show examples of when to use directive vs helper
   - Repeater template block patterns

**New test file**: `tests/Feature/FrankenFieldHelperTest.php`

---

## Complete Usage Examples

### **Simple Field - Declarative**
```blade
{{-- Auto-rendered output --}}
<h1>@frankenText('page.title')</h1>
```

### **Simple Field - Programmatic**
```blade
{{-- Get raw value for custom handling --}}
@php
    $title = frankenField('page.title');
    $uppercaseTitle = strtoupper($title);
@endphp
<h1>{{ $uppercaseTitle }}</h1>
```

### **Tags - Both Patterns**
```blade
{{-- Pattern 1: Auto-rendered tag list --}}
@frankenTags('skills')

{{-- Pattern 2: Custom rendering --}}
@foreach (frankenField('skills') as $skill)
    <span class="badge">{{ $skill }}</span>
@endforeach
```

### **Repeater - Block Directive**
```blade
@frankenRepeater('team.members', [
    'schema' => [
        ['name' => 'name', 'type' => 'text'],
        ['name' => 'role', 'type' => 'text'],
        ['name' => 'bio', 'type' => 'textarea'],
    ]
])
    <div class="member">
        <h3>{{ $franken->name }}</h3>
        <p class="role">{{ $franken->role }}</p>
        <p>{{ $franken->bio }}</p>
    </div>
@endFrankenRepeater
```

### **Repeater - Manual Access**
```blade
{{-- Pass collection to component --}}
<x-team-grid :members="frankenField('team.members')" />

{{-- Custom foreach with conditional logic --}}
@foreach (frankenField('team.members') as $member)
    @if ($member->featured)
        <featured-card :member="$member" />
    @else
        <regular-card :member="$member" />
    @endif
@endforeach

{{-- Use in Alpine.js --}}
<div x-data="{ members: @js(frankenField('team.members')) }">
    <template x-for="member in members">
        <div x-text="member.name"></div>
    </template>
</div>
```

---

## Files to Modify

1. **`src/helpers.php`** - Add `frankenField()` and `_parseFieldExpression()` helpers
2. **`src/FrankenCmsServiceProvider.php`** - Register all typed directives
3. **`src/Services/TemplateFieldParser.php`** - Update parser for new directives
4. **`stubs/theme/*.blade.php`** - Update example templates
5. **`tests/Unit/TemplateFieldParserTest.php`** - Add parser tests
6. **`tests/Feature/CustomFieldsIntegrationTest.php`** - Add integration tests
7. **`tests/Feature/FrankenFieldHelperTest.php`** - NEW: Test helper function

---

## Benefits

✅ **Declarative OR Imperative**: Choose directive auto-echo or programmatic access
✅ **Maximum Flexibility**: Tags can be rendered OR accessed as array
✅ **Repeater Freedom**: Use block template OR manual foreach OR pass to components
✅ **Type Safety**: Directive name encodes field type
✅ **DX**: Cleaner syntax, better auto-completion
✅ **Safe Variables**: `$franken` namespace in repeaters prevents conflicts
✅ **Backward Compatible**: Existing `@cmsField` templates work
✅ **Composable**: Mix directives with helper in same template

---

## Implementation Order

1. ✅ Add `frankenField()` helper (raw data access)
2. ✅ Add `_parseFieldExpression()` helper
3. ✅ Register 13 typed directives (auto-echo)
4. ✅ Implement `@frankenRepeater` block directive
5. ✅ Update `TemplateFieldParser`
6. ✅ Test dual access patterns
7. ✅ Update stub templates
8. ✅ Write comprehensive tests
9. ✅ Add deprecation to `@cmsField`
10. ✅ Document migration and patterns

---

## Technical Notes

### Expression Parsing
The `_parseFieldExpression()` helper is designed to work with Blade's expression system. When a directive like `@frankenText('title', ['label' => 'Title'])` is compiled, Blade passes the entire expression as a string. The helper receives already-evaluated parameters.

### RepeaterFieldRenderer Integration
Our previous refactor to `RepeaterFieldRenderer` already returns cleaned item objects (without nested `custom_fields`), so the `$franken` variable will be a plain object with direct property access (`$franken->title` instead of `$franken->custom_fields->title`).

### Variable Cleanup
All directive implementations include `unset()` calls to clean up temporary variables and prevent scope pollution. This is critical for nested directives and repeaters.

### Parser Regex Notes
The parser uses a single regex pattern with capturing groups to detect all typed directives. The field type is extracted from the directive name suffix (e.g., `frankenText` → `text`), with special handling for `MediaImage` → `media_image` and `RichEditor` → `richEditor`.

---

## Migration Path

### Step 1: Developers Can Start Using New Directives Immediately
Old and new syntax work side-by-side. Developers can migrate templates incrementally.

### Step 2: Deprecation Warnings (Optional)
When `app.debug` is true, old `@cmsField` usage logs warnings to help identify templates that need updating.

### Step 3: Future: Create Migration Command (Optional)
Could build `php artisan franken-cms:migrate-directives` command to auto-convert templates.

### Step 4: Eventually Remove @cmsField (Future Major Version)
After sufficient deprecation period, `@cmsField` can be removed in a major version bump.

---

**End of Plan**
