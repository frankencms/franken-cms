{{-- Bootstrap Menu Template --}}
<nav class="navbar navbar-expand-lg">
    @if(!empty($menuItems))
        @include('franken-cms::menu.partials.bootstrap-menu-list', ['items' => $menuItems])
    @endif
</nav>