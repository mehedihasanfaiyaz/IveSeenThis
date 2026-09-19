<?php

use App\Models\Issue;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Issues')] class extends Component {
    public string $search = '';

    #[Computed]
    public function issues()
    {
        return auth()->user()->issues()
            ->with('project')
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('problem', 'like', "%{$this->search}%")
                    ->orWhere('environment', 'like', "%{$this->search}%")
                    ->orWhereJsonContains('tags', $this->search);
            }))
            ->latest('occurred_on')
            ->latest()
            ->get();
    }
}; ?>

<section class="w-full">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('Issues') }}</flux:heading>
                <flux:subheading>{{ __('Every problem becomes a shortcut for future you.') }}</flux:subheading>
            </div>
            <flux:button variant="primary" icon="plus" :href="route('issues.create')" wire:navigate>{{ __('Log issue') }}</flux:button>
        </div>

        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search title, project, error, or tag...')" />

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            @forelse ($this->issues as $issue)
                <a href="{{ route('issues.show', $issue) }}" wire:navigate class="block border-b border-zinc-200 p-5 transition hover:bg-zinc-50 last:border-b-0 dark:border-zinc-700 dark:hover:bg-zinc-800/60">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <flux:heading size="lg" class="truncate">{{ $issue->title }}</flux:heading>
                            <flux:text class="mt-1">{{ $issue->project?->name ?? __('Unassigned project') }} · {{ $issue->occurred_on?->format('M j, Y') ?? __('No date') }}</flux:text>
                        </div>
                        @if ($issue->solutions->isNotEmpty())
                            <flux:badge color="green" icon="check">{{ __('Solved') }}</flux:badge>
                        @else
                            <flux:badge color="amber">{{ __('Open') }}</flux:badge>
                        @endif
                    </div>
                    <flux:text class="mt-4 line-clamp-2">{{ $issue->problem }}</flux:text>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($issue->tags ?? [] as $tag)
                            <flux:badge size="sm">{{ $tag }}</flux:badge>
                        @endforeach
                    </div>
                </a>
            @empty
                <div class="p-12 text-center">
                    <flux:heading>{{ $search ? __('No matching issues') : __('Your log is empty') }}</flux:heading>
                    <flux:text class="mt-2">{{ $search ? __('Try a different search.') : __('Capture the first problem while it is still fresh.') }}</flux:text>
                </div>
            @endforelse
        </div>
    </div>
</section>
