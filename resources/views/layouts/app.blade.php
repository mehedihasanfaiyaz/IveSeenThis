<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="workspace-main">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
