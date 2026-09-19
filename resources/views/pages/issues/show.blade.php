<?php

use App\Models\Issue;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Issue')] class extends Component {
    public Issue $issue;
    public string $newAttemptDescription = '';
    public string $newAttemptResult = 'failed';

    public function mount(Issue $issue): void
    {
        abort_unless($issue->user_id === auth()->id(), 404);
        $this->issue = $issue->load(['project', 'attempts', 'solutions', 'relatedIssues']);
    }

    public function deleteIssue(): void
    {
        $this->issue->delete();
        $this->redirect(route('issues.index'), navigate: true);
    }

    public function addAttempt(): void
    {
        $validated = $this->validate([
            'newAttemptDescription' => ['required', 'string'],
            'newAttemptResult' => ['required', Rule::in(['failed', 'worked', 'wrong'])],
        ]);

        $this->issue->attempts()->create([
            'position' => ((int) $this->issue->attempts()->max('position')) + 1,
            'description' => $validated['newAttemptDescription'],
            'result' => $validated['newAttemptResult'],
        ]);

        $this->issue->load(['attempts', 'solutions']);
        $this->reset('newAttemptDescription');
        $this->newAttemptResult = 'failed';
    }
}; ?>

<section class="w-full">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div><flux:link :href="route('issues.index')" wire:navigate icon="arrow-left">{{ __('Back to issues') }}</flux:link><flux:heading size="xl" class="mt-4">{{ $issue->title }}</flux:heading><flux:text class="mt-2">{{ $issue->project?->name ?? __('Unassigned project') }} · {{ $issue->occurred_on?->format('F j, Y') ?? __('No date') }} · {{ $issue->environment }}</flux:text></div>
            <div class="flex items-center gap-2">
                @if ($issue->solutions->isNotEmpty())<flux:badge color="green" icon="check">{{ __('Solved') }}</flux:badge>@endif
                <flux:button variant="ghost" icon="pencil" :href="route('issues.edit', $issue)" wire:navigate>{{ __('Edit') }}</flux:button>
                <flux:button variant="ghost" icon="trash" wire:click="deleteIssue" wire:confirm="{{ __('Delete this issue permanently?') }}">{{ __('Delete') }}</flux:button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <div class="space-y-6">
                <section><flux:heading size="lg">{{ __('Problem') }}</flux:heading><flux:text class="mt-3 whitespace-pre-line">{{ $issue->problem }}</flux:text></section>
                @if ($issue->error_logs)<section><flux:heading size="lg">{{ __('Error / logs') }}</flux:heading><pre class="mt-3 overflow-x-auto rounded-lg bg-zinc-950 p-4 text-sm text-zinc-100">{{ $issue->error_logs }}</pre></section>@endif
                <section><flux:heading size="lg">{{ __('What I tried') }}</flux:heading>@if ($issue->attempts->isNotEmpty())<div class="mt-3 space-y-3">@foreach ($issue->attempts as $attempt)<div class="flex gap-3"><flux:badge :color="$attempt->result === 'worked' ? 'green' : ($attempt->result === 'wrong' ? 'amber' : 'red')">{{ ucfirst($attempt->result) }}</flux:badge><flux:text>{{ $attempt->description }}</flux:text></div>@endforeach</div>@else<flux:text class="mt-3">{{ __('No attempts recorded yet.') }}</flux:text>@endif
                    @if ($issue->solutions->isEmpty())
                        <form wire:submit="addAttempt" class="mt-5 grid gap-3 rounded-xl border border-dashed border-zinc-300 p-4 dark:border-zinc-700 md:grid-cols-[1fr_10rem_auto]">
                            <flux:input wire:model="newAttemptDescription" :label="__('Next attempt')" placeholder="Try using the service hostname" required />
                            <flux:select wire:model="newAttemptResult" :label="__('Result')"><option value="failed">{{ __('Failed') }}</option><option value="wrong">{{ __('Wrong assumption') }}</option><option value="worked">{{ __('Worked') }}</option></flux:select>
                            <flux:button variant="primary" type="submit" class="self-end">{{ __('Add attempt') }}</flux:button>
                        </form>
                    @endif
                </section>
            </div>
            <aside class="space-y-6">
                @if ($issue->root_cause)<section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700"><flux:heading size="lg">{{ __('Root cause') }}</flux:heading><flux:text class="mt-3 whitespace-pre-line">{{ $issue->root_cause }}</flux:text></section>@endif
                @if ($issue->solutions->isNotEmpty())<section class="rounded-xl border border-green-200 bg-green-50 p-5 dark:border-green-900 dark:bg-green-950/30"><flux:heading size="lg">{{ __('Solution') }}</flux:heading>@foreach ($issue->solutions as $solution)<flux:heading class="mt-3">{{ $solution->title }}</flux:heading><flux:text class="mt-1 whitespace-pre-line">{{ $solution->description }}</flux:text>@endforeach</section>@endif
                @if ($issue->dont_do_again)<section class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/30"><flux:heading size="lg">{{ __('Don\'t do this again') }}</flux:heading><flux:text class="mt-3 whitespace-pre-line">{{ $issue->dont_do_again }}</flux:text></section>@endif
                <div class="flex flex-wrap gap-2">@foreach ($issue->tags ?? [] as $tag)<flux:badge>{{ $tag }}</flux:badge>@endforeach</div>
            </aside>
        </div>
    </div>
</section>
