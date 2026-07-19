@if ($template)
    <x-og-image :view="$template" :data="['post' => $post]" />
@elseif ($url)
    <x-og-image :url="$url" />
@endif
