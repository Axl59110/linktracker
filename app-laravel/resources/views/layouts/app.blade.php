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

            {{-- Page Content --}}
            <main class="p-6 max-w-7xl mx-auto">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Additional Scripts --}}
    @stack('scripts')
</body>
</html>
