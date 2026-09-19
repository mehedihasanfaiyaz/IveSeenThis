<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        @vite('resources/css/app.css')
        <title>{{ config('app.name') }} - {{ __('Your engineering memory') }}</title>
    </head>
    <body class="min-h-screen bg-[#f4f1ea] text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-6 py-6 sm:px-10 lg:px-16">
            <header class="flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-3 font-semibold tracking-tight">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-zinc-950 text-white dark:bg-white dark:text-zinc-950">
                        <x-app-logo-icon class="size-6 fill-current" />
                    </span>
                    <span class="text-lg">IveSeenThis</span>
                </a>
                <nav class="flex items-center gap-3 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-md px-3 py-2 font-medium hover:bg-black/5 dark:hover:bg-white/10">{{ __('Dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-md px-3 py-2 font-medium hover:bg-black/5 dark:hover:bg-white/10">{{ __('Log in') }}</a>
                        <a href="{{ route('register') }}" class="rounded-md bg-zinc-950 px-4 py-2 font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">{{ __('Create account') }}</a>
                    @endauth
                </nav>
            </header>

            <section class="grid flex-1 items-center gap-12 py-16 lg:grid-cols-[1.1fr_0.9fr] lg:py-24">
                <div class="max-w-2xl">
                    <p class="mb-6 text-sm font-semibold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-400">{{ __('A calmer way to debug') }}</p>
                    <h1 class="max-w-xl text-5xl font-semibold leading-[1.02] tracking-tight sm:text-7xl">{{ __('Turn hard-won fixes into searchable memory.') }}</h1>
                    <p class="mt-7 max-w-xl text-lg leading-8 text-zinc-600 dark:text-zinc-400">{{ __('Record what broke, what you tried, what finally worked, and what you never want to repeat.') }}</p>
                    <div class="mt-9 flex flex-wrap items-center gap-4">
                        @guest
                            <a href="{{ route('register') }}" class="rounded-md bg-zinc-950 px-5 py-3 font-medium text-white shadow-sm hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">{{ __('Start your log') }}</a>
                            <a href="{{ route('login') }}" class="rounded-md border border-zinc-300 px-5 py-3 font-medium hover:bg-white/60 dark:border-zinc-700 dark:hover:bg-zinc-900">{{ __('Log in') }}</a>
                        @else
                            <a href="{{ route('issues.create') }}" class="rounded-md bg-zinc-950 px-5 py-3 font-medium text-white shadow-sm hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">{{ __('Log an issue') }}</a>
                        @endguest
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-zinc-300 bg-[#1f2825] p-6 text-zinc-100 shadow-2xl dark:border-zinc-700">
                    <div class="mb-8 flex items-center justify-between border-b border-white/10 pb-4 text-sm text-zinc-400"><span>{{ __('Issue log') }}</span><span>{{ __('Today') }}</span></div>
                    <p class="text-sm text-amber-300">{{ __('Laravel / Docker / MySQL') }}</p>
                    <h2 class="mt-3 text-2xl font-semibold">{{ __('Connection refused') }}</h2>
                    <div class="mt-7 space-y-5 text-sm">
                        <div><p class="mb-1 text-zinc-500">{{ __('What I tried') }}</p><p>{{ __('localhost from container') }} <span class="text-red-300">{{ __('Failed') }}</span></p></div>
                        <div><p class="mb-1 text-zinc-500">{{ __('Root cause') }}</p><p>{{ __('The app needed the Docker service hostname.') }}</p></div>
                        <div class="border-l-2 border-emerald-400 pl-4"><p class="text-emerald-300">{{ __('Solution') }}</p><p class="mt-1">{{ __('Use DB_HOST=db and keep the fix searchable.') }}</p></div>
                    </div>
                    <div class="mt-8 flex flex-wrap gap-2"><span class="rounded-full bg-white/10 px-3 py-1 text-xs text-zinc-300">laravel</span><span class="rounded-full bg-white/10 px-3 py-1 text-xs text-zinc-300">docker</span><span class="rounded-full bg-white/10 px-3 py-1 text-xs text-zinc-300">mysql</span></div>
                </div>
            </section>

            <footer class="border-t border-zinc-300/80 py-5 text-sm text-zinc-500 dark:border-zinc-800">{{ __('Issues become patterns. Patterns become shortcuts.') }}</footer>
        </main>
        @fluxScripts
    </body>
</html>
