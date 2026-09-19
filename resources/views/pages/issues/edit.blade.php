<?php

use App\Models\Issue;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit issue')] class extends Component {
    public Issue $issue;
    public string $title = '';
    public ?int $project_id = null;
    public string $occurred_on = '';
    public string $environment = '';
    public string $problem = '';
    public string $error_logs = '';
    public string $root_cause = '';
    public string $solution_title = '';
    public string $solution = '';
    public string $dont_do_again = '';
    public string $tags = '';
    public array $attempts = [];

    public function mount(Issue $issue): void
    {
        abort_unless($issue->user_id === auth()->id(), 404);
        $this->issue = $issue->load(['attempts', 'solutions']);
        $this->title = $issue->title;
        $this->project_id = $issue->project_id;
        $this->occurred_on = $issue->occurred_on?->format('Y-m-d') ?? '';
        $this->environment = $issue->environment ?? '';
        $this->problem = $issue->problem;
        $this->error_logs = $issue->error_logs ?? '';
        $this->root_cause = $issue->root_cause ?? '';
        $this->dont_do_again = $issue->dont_do_again ?? '';
        $this->tags = implode(', ', $issue->tags ?? []);
        $this->attempts = $issue->attempts->map(fn ($attempt) => [
            'description' => $attempt->description,
            'result' => $attempt->result,
        ])->values()->all() ?: [['description' => '', 'result' => 'failed']];
        $solution = $issue->solutions->first();
        $this->solution_title = $solution?->title ?? '';
        $this->solution = $solution?->description ?? '';
    }

    public function addAttempt(): void
    {
        $this->attempts[] = ['description' => '', 'result' => 'failed'];
    }

    public function removeAttempt(int $index): void
    {
        unset($this->attempts[$index]);
        $this->attempts = array_values($this->attempts);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('user_id', auth()->id())],
            'occurred_on' => ['nullable', 'date'],
            'environment' => ['nullable', 'string'],
            'problem' => ['required', 'string'],
            'error_logs' => ['nullable', 'string'],
            'root_cause' => ['nullable', 'string'],
            'solution_title' => ['nullable', 'string', 'max:255'],
            'solution' => ['nullable', 'string'],
            'dont_do_again' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
            'attempts.*.description' => ['nullable', 'string'],
            'attempts.*.result' => ['required', Rule::in(['failed', 'worked', 'wrong'])],
        ]);

        DB::transaction(function () use ($validated): void {
            $this->issue->update([
                ...collect($validated)->only(['project_id', 'title', 'occurred_on', 'environment', 'problem', 'error_logs', 'root_cause', 'dont_do_again'])->all(),
                'tags' => collect(explode(',', $this->tags))->map(fn ($tag) => trim($tag))->filter()->values()->all(),
            ]);

            $this->issue->attempts()->delete();
            foreach ($this->attempts as $position => $attempt) {
                if (filled($attempt['description'])) {
                    $this->issue->attempts()->create([...$attempt, 'position' => $position + 1]);
                }
            }

            if (filled($validated['solution_title'] ?? null) && filled($validated['solution'] ?? null)) {
                $this->issue->solutions()->updateOrCreate(
                    ['title' => $validated['solution_title']],
                    ['user_id' => auth()->id(), 'description' => $validated['solution']],
                );
            } else {
                $this->issue->solutions()->delete();
            }
        });

        $this->redirect(route('issues.show', $this->issue), navigate: true);
    }

    public function projects()
    {
        return auth()->user()->projects()->orderBy('name')->get();
    }
}; ?>

<section class="w-full">
    <div class="mx-auto w-full max-w-4xl">
        <div class="mb-8"><flux:link :href="route('issues.show', $issue)" wire:navigate icon="arrow-left">{{ __('Back to issue') }}</flux:link><flux:heading size="xl" class="mt-4">{{ __('Edit issue') }}</flux:heading><flux:subheading>{{ __('Update the details, attempts, or solution as you learn more.') }}</flux:subheading></div>
        <form wire:submit="save" class="space-y-8">
            <div class="grid gap-5 md:grid-cols-2">
                <flux:input wire:model="title" :label="__('Title')" required class="md:col-span-2" />
                <flux:select wire:model="project_id" :label="__('Project')"><option value="">{{ __('Select a project') }}</option>@foreach ($this->projects() as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach</flux:select>
                <flux:input wire:model="occurred_on" :label="__('Date')" type="date" />
                <flux:input wire:model="environment" :label="__('Environment')" class="md:col-span-2" />
            </div>
            <flux:textarea wire:model="problem" :label="__('Problem')" rows="5" required />
            <flux:textarea wire:model="error_logs" :label="__('Error / logs')" rows="5" />
            <div class="space-y-4"><div class="flex items-center justify-between"><flux:heading size="lg">{{ __('What I tried') }}</flux:heading><flux:button type="button" variant="ghost" icon="plus" wire:click="addAttempt">{{ __('Add attempt') }}</flux:button></div>@foreach ($attempts as $index => $attempt)<div class="grid gap-3 md:grid-cols-[1fr_9rem_auto]"><flux:input wire:model="attempts.{{ $index }}.description" :label="__('Attempt :number', ['number' => $index + 1])" /><flux:select wire:model="attempts.{{ $index }}.result" :label="__('Result')"><option value="failed">{{ __('Failed') }}</option><option value="wrong">{{ __('Wrong assumption') }}</option><option value="worked">{{ __('Worked') }}</option></flux:select>@if(count($attempts) > 1)<flux:button type="button" variant="ghost" icon="trash" :aria-label="__('Remove attempt')" wire:click="removeAttempt({{ $index }})" class="self-end" />@endif</div>@endforeach</div>
            <div class="grid gap-5 md:grid-cols-2"><flux:textarea wire:model="root_cause" :label="__('Root cause')" rows="4" /><flux:textarea wire:model="dont_do_again" :label="__('Don\'t do this again')" rows="4" /><flux:input wire:model="solution_title" :label="__('Solution title')" /><flux:input wire:model="tags" :label="__('Tags')" /><flux:textarea wire:model="solution" :label="__('Solution')" rows="5" class="md:col-span-2" /></div>
            <div class="flex justify-end gap-3"><flux:button variant="ghost" :href="route('issues.show', $issue)" wire:navigate>{{ __('Cancel') }}</flux:button><flux:button variant="primary" type="submit">{{ __('Save changes') }}</flux:button></div>
        </form>
    </div>
</section>
