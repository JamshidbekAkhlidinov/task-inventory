<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Inventory') &middot; {{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6">
                <a href="{{ route('dashboard') }}" class="text-lg font-semibold">Inventory</a>

                <nav class="flex flex-wrap gap-1 text-sm">
                    @php
                        $links = [
                            'dashboard' => 'Dashboard',
                            'purchases.create' => 'Purchase',
                            'provider-refunds.create' => 'Provider Refund',
                            'orders.create' => 'Order',
                            'client-refunds.create' => 'Client Refund',
                            'batches.profit' => 'Batch Profit',
                        ];
                    @endphp

                    @foreach ($links as $routeName => $label)
                        <a
                            href="{{ route($routeName) }}"
                            class="rounded-md px-3 py-1.5 font-medium transition {{ request()->routeIs($routeName) ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold">@yield('title')</h1>
                @hasSection('subtitle')
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@yield('subtitle')</p>
                @endif
            </div>

            <div id="app-alert" class="mb-6 hidden rounded-md border px-4 py-3 text-sm"></div>

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
