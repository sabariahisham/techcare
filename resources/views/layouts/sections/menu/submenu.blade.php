@php
    use Illuminate\Support\Facades\Route;
@endphp

<ul class="menu-sub">
    @if (isset($menu))
        @php
            $currentRouteName = Route::currentRouteName();
            if (!isset($getMenuActiveClass)) {
                $layout = isset($configData['layout']) ? $configData['layout'] : 'vertical';
                $getMenuActiveClass = function ($item) use (&$getMenuActiveClass, $currentRouteName, $layout) {
                    if ($currentRouteName === $item->slug) {
                        return 'active';
                    }

                    if (isset($item->submenu)) {
                        foreach ($item->submenu as $child) {
                            if ($getMenuActiveClass($child)) {
                                return $layout === 'vertical' ? 'active open' : 'active';
                            }
                        }

                        if (is_array($item->slug)) {
                            foreach ($item->slug as $slug) {
                                if (str_contains($currentRouteName, $slug) && strpos($currentRouteName, $slug) === 0) {
                                    return $layout === 'vertical' ? 'active open' : 'active';
                                }
                            }
                        } elseif ($item->slug) {
                            if (
                                str_contains($currentRouteName, $item->slug) &&
                                strpos($currentRouteName, $item->slug) === 0
                            ) {
                                return $layout === 'vertical' ? 'active open' : 'active';
                            }
                        }
                    }

                    return null;
                };
            }
        @endphp

        @foreach ($menu as $submenu)
            {{-- active menu method --}}
            @php
                $activeClass = $getMenuActiveClass($submenu);
                $linkClass = isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link';
                if ($activeClass) {
                    $linkClass .= ' active';
                }
            @endphp

            @if (
                (!isset($submenu->permission) && !isset($submenu->role)) ||
                    (isset($submenu->permission) && auth()->user()->can($submenu->permission)) ||
                    (isset($submenu->role) && auth()->user()->hasAnyRole((array) $submenu->role)))
                <li class="menu-item {{ $activeClass }}">
                    <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}"
                        class="{{ $linkClass }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
                        @if (isset($submenu->icon))
                            <i class="{{ $submenu->icon }}"></i>
                        @endif
                        <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
                        @isset($submenu->badge)
                            <div class="badge bg-{{ $submenu->badge[0] }} rounded-pill ms-auto">{{ $submenu->badge[1] }}
                            </div>
                        @endisset
                    </a>

                    {{-- submenu --}}
                    @if (isset($submenu->submenu))
                        @include('layouts.sections.menu.submenu', [
                            'menu' => $submenu->submenu,
                            'getMenuActiveClass' => $getMenuActiveClass,
                        ])
                    @endif
                </li>
            @endif
        @endforeach
    @endif
</ul>
