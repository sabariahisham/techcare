@php
    use Illuminate\Support\Facades\Route;
    $configData = Helper::appClasses();
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    <!-- ! Hide app brand if navbar-full -->
    @if (!isset($navbarFull))
        <div class="app-brand demo">
            <a href="{{ url('/') }}" class="app-brand-link">
                {{-- Full logo with text - shown when menu is expanded --}}
                <img src="{{ asset('assets/img/logos/btm-techcare.png') }}" alt="btm-TechCare Logo"
                    class="app-brand-logo-img logo-expanded">
                {{-- Icon only - shown when menu is collapsed --}}
                @if (!isset($navbarHideToggle))
                    <a href="javascript:void(0)" class="nav-item nav-link px-0 me-xl-6 logo-collapsed">
                        <i class="bx bx-menu bx-md"></i>
                    </a>
                @endif
                {{-- <img src="{{ asset('assets/img/logos/logo-techcare.png') }}" alt="TechCare"
                    class="app-brand-logo-img logo-collapsed"> --}}
                {{-- <span class="app-brand-logo demo">@include('_partials.macros',["width"=>25,"withbg"=>'var(--bs-primary)'])</span> --}}
                {{-- <img src="{{ asset('assets/img/logos/logoShield.png') }}" alt="Your Logo" style="height: 200px; width: auto;"> --}}
                {{-- <span class="app-brand-text demo menu-text fw-bold ms-2">{{config('variables.templateName')}}</span> --}}
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="bx bx-chevron-left bx-sm d-flex align-items-center justify-content-center"></i>
            </a>
        </div>
    @endif

    <div class="menu-inner-shadow"></div>

    @php
        $currentRouteName = Route::currentRouteName();
        $getMenuActiveClass = function ($item) use (&$getMenuActiveClass, $currentRouteName, $configData) {
            if ($currentRouteName === $item->slug) {
                return 'active';
            }

            if (isset($item->submenu)) {
                foreach ($item->submenu as $child) {
                    if ($getMenuActiveClass($child)) {
                        return $configData['layout'] === 'vertical' ? 'active open' : 'active';
                    }
                }

                if (is_array($item->slug)) {
                    foreach ($item->slug as $slug) {
                        if (str_contains($currentRouteName, $slug) && strpos($currentRouteName, $slug) === 0) {
                            return $configData['layout'] === 'vertical' ? 'active open' : 'active';
                        }
                    }
                } elseif ($item->slug) {
                    if (str_contains($currentRouteName, $item->slug) && strpos($currentRouteName, $item->slug) === 0) {
                        return $configData['layout'] === 'vertical' ? 'active open' : 'active';
                    }
                }
            }

            return null;
        };
    @endphp

    <ul class="menu-inner py-1">
        @foreach ($menuData[0]->menu as $menu)
            {{-- adding active and open class if child is active --}}

            {{-- menu headers --}}
            @if (isset($menu->menuHeader))
                @if (
                    (!isset($menu->permission) && !isset($menu->role)) ||
                        (isset($menu->permission) && auth()->user()->can($menu->permission)) ||
                        (isset($menu->role) && auth()->user()->hasAnyRole((array) $menu->role)))
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
                    </li>
                @endif
            @else
                {{-- active menu method --}}
                @php
                    $activeClass = $getMenuActiveClass($menu);
                @endphp

                {{-- main menu --}}
                @if (
                    (!isset($menu->permission) && !isset($menu->role)) ||
                        (isset($menu->permission) && auth()->user()->can($menu->permission)) ||
                        (isset($menu->role) && auth()->user()->hasAnyRole((array) $menu->role)))
                    @php
                        $menuLinkClass = isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link';
                        if ($activeClass) {
                            $menuLinkClass .= ' active';
                        }
                    @endphp
                    <li class="menu-item {{ $activeClass }}">
                        <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
                            class="{{ $menuLinkClass }}" @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
                            @isset($menu->icon)
                                <i class="{{ $menu->icon }}"></i>
                            @endisset
                            <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
                            @isset($menu->badge)
                                <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
                            @endisset
                        </a>

                        {{-- submenu --}}
                        @isset($menu->submenu)
                            @include('layouts.sections.menu.submenu', [
                                'menu' => $menu->submenu,
                                'getMenuActiveClass' => $getMenuActiveClass,
                            ])
                        @endisset
                    </li>
                @endif
            @endif
        @endforeach
    </ul>

</aside>
