{{-- Default Menu Template --}}
<nav class="menu menu--{{ $slug }}">
    @if(!empty($menuItems))
        @include('franken-cms::menu.partials.menu-list', ['items' => $menuItems])
    @endif
</nav>