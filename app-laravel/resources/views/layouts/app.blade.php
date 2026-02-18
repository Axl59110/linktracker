<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Link Tracker'))</title>

    @vite(['resources/css/app.css', 'resources/js/alpine.js'])
</head>
<body class="antialiased bg-neutral-50 text-neutral-600">
    <div id="app" class="min-h-screen">
        {{-- Sidebar Navigation (Desktop) --}}
        @include('components.sidebar')

        {{-- Main Content Area --}}
        <div class="lg:pl-64">
            {{-- Topbar (Breadcrumb + User Menu) --}}
            @include('components.topbar')

            {{-- Flash Messages (centralisés) --}}
            @if(session('success') || session('error') || session('warning') || session('info'))
            <div class="px-6 pt-4 max-w-[1600px] mx-auto">
                @if(session('success'))
                    <x-alert variant="success" :auto-dismiss="5" class="mb-4">
                        {{ session('success') }}
                    </x-alert>
                @endif
                @if(session('error'))
                    <x-alert variant="danger" :auto-dismiss="8" :dismissible="true" class="mb-4">
                        {{ session('error') }}
                    </x-alert>
                @endif
                @if(session('warning'))
                    <x-alert variant="warning" :auto-dismiss="6" :dismissible="true" class="mb-4">
                        {{ session('warning') }}
                    </x-alert>
                @endif
                @if(session('info'))
                    <x-alert variant="info" :auto-dismiss="5" class="mb-4">
                        {{ session('info') }}
                    </x-alert>
                @endif
            </div>
            @endif

            {{-- Page Content --}}
            <main class="p-6 max-w-[1600px] mx-auto">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Additional Scripts --}}
    @stack('scripts')
</body>
</html>
