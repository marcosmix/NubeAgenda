@php
    $pageTitle = $title ?? null;
    $appName = config('app.name', 'NubeAgenda');
    $navItems = [];

    if (!empty($navigation) && is_iterable($navigation)) {
        foreach ($navigation as $item) {
            if (is_array($item) && isset($item['label'])) {
                $navItems[] = array_merge([
                    'href' => $item['href'] ?? '#',
                    'active' => $item['active'] ?? false,
                    'attributes' => $item['attributes'] ?? [],
                ], ['label' => $item['label']]);
            }
        }
    }

    if (empty($navItems)) {
        $navItems = [
            [
                'label' => __('Inicio'),
                'href' => url('/'),
                'active' => request()->routeIs('home'),
            ],
            [
                'label' => __('Agenda'),
                'href' => url('/agenda'),
                'active' => request()->routeIs('agenda.*'),
            ],
            [
                'label' => __('Perfil'),
                'href' => url('/perfil'),
                'active' => request()->routeIs('profile.*'),
            ],
        ];
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', [
            'title' => filled($pageTitle) ? "$pageTitle · $appName" : $appName,
        ])
    </head>
    <body class="mobile-shell bg-white text-neutral-900 antialiased">
        <a href="#main-content" class="skip-to-content">{{ __('Saltar al contenido principal') }}</a>

        <header class="mobile-header">
            <div class="mobile-header__branding">
                <a class="mobile-brand" href="{{ route('home') }}" wire:navigate>
                    <span class="touch-target bg-neutral-900 text-white dark:bg-white dark:text-neutral-900">
                        <x-app-logo-icon class="size-7" />
                        <span class="sr-only">{{ $appName }}</span>
                    </span>
                    <span class="fluid-type text-balance">{{ $pageTitle ?? $appName }}</span>
                </a>

                @isset($headerActions)
                    <div class="flex items-center gap-2">
                        {{ $headerActions }}
                    </div>
                @endisset
            </div>

            <nav class="mobile-nav" aria-label="{{ __('Navegación principal') }}">
                <ul class="mobile-nav__list" role="list">
                    @foreach ($navItems as $item)
                        @php
                            $isActive = (bool) ($item['active'] ?? false);
                            $linkAttributes = $item['attributes'] ?? [];
                        @endphp
                        <li class="mobile-nav__item">
                            <a
                                href="{{ $item['href'] }}"
                                @class(['mobile-nav__link', 'mobile-nav__link--active' => $isActive])
                                @if ($isActive) aria-current="page" @endif
                                @foreach ($linkAttributes as $attr => $value)
                                    {{ $attr }}="{{ $value }}"
                                @endforeach
                            >
                                <span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </header>

        <main id="main-content" class="mobile-main" tabindex="-1">
            {{ $slot }}
        </main>

        <footer class="mobile-footer">
            <p>{{ __('Optimizado para gestos y pantallas pequeñas. Desliza horizontalmente para explorar tu agenda.') }}</p>
            @isset($footer)
                <div class="mt-2 text-sm text-balance">
                    {{ $footer }}
                </div>
            @endisset
        </footer>

        @fluxScripts
    </body>
</html>
