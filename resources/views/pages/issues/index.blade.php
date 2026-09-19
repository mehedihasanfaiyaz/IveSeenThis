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
            ->with(['project', 'solutions'])
            ->withCount('attempts')
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
    <div class="workspace-frame flex flex-col gap-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="eyebrow">{{ __('The archive') }}</p>
                <flux:heading size="xl">{{ __('Issues') }}</flux:heading>
                <flux:subheading>{{ __('Every problem becomes a shortcut for future you.') }}</flux:subheading>
            </div>
            <flux:button variant="primary" icon="plus" :href="route('issues.create')" wire:navigate>{{ __('Log issue') }}</flux:button>
        </div>

        <div class="surface p-3"><flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search title, project, error, or tag...')" /></div>

        <div class="surface overflow-hidden">
            <div class="overflow-x-auto">
                <table class="issue-table w-full min-w-[760px] text-left">
                    <thead><tr><th>{{ __('Issue') }}</th><th>{{ __('Project / environment') }}</th><th>{{ __('Attempts') }}</th><th>{{ __('Status') }}</th><th>{{ __('Logged') }}</th><th><span class="sr-only">{{ __('Open') }}</span></th></tr></thead>
                    <tbody>
                    @forelse ($this->issues as $issue)
                        <tr>
                            <td><a href="{{ route('issues.show', $issue) }}" wire:navigate class="issue-table-title">{{ $issue->title }}</a><span class="issue-table-problem">{{ $issue->problem }}</span><span class="issue-table-tags">@foreach ($issue->tags ?? [] as $tag)<span>{{ $tag }}</span>@endforeach</span></td>
                            <td><strong>{{ $issue->project?->name ?? __('Unassigned') }}</strong><span>{{ $issue->environment ?: __('No environment') }}</span></td>
                            <td>{{ $issue->attempts_count }}</td>
                            <td>@if ($issue->solutions->isNotEmpty())<flux:badge color="green" icon="check">{{ __('Solved') }}</flux:badge>@else<flux:badge color="amber">{{ __('Open') }}</flux:badge>@endif</td>
                            <td>{{ $issue->occurred_on?->format('M j, Y') ?? __('No date') }}</td>
                            <td><a href="{{ route('issues.show', $issue) }}" wire:navigate class="issue-table-arrow" aria-label="{{ __('Open issue') }}">→</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-12 text-center"><flux:heading>{{ $search ? __('No matching issues') : __('Your log is empty') }}</flux:heading><flux:text class="mt-2">{{ $search ? __('Try a different search.') : __('Capture the first problem while it is still fresh.') }}</flux:text></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
