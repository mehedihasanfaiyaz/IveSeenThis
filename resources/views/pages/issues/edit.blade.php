<?php

use App\Models\Issue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Edit issue')] class extends Component {
    use WithFileUploads;

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
    public ?TemporaryUploadedFile $problemImage = null;
    public ?TemporaryUploadedFile $solutionImage = null;
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
            'id' => $attempt->id,
            'description' => $attempt->description,
            'result' => $attempt->result,
            'image' => null,
        ])->values()->all() ?: [['description' => '', 'result' => 'failed']];
        $solution = $issue->solutions->first();
        $this->solution_title = $solution?->title ?? '';
        $this->solution = $solution?->description ?? '';
    }

    public function addAttempt(): void
    {
        $this->attempts[] = ['description' => '', 'result' => 'failed', 'image' => null];
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
            'problemImage' => ['nullable', 'image', 'max:5120'],
            'solutionImage' => ['nullable', 'image', 'max:5120'],
            'attempts.*.description' => ['nullable', 'string'],
            'attempts.*.result' => ['required', Rule::in(['failed', 'worked', 'wrong'])],
            'attempts.*.image' => ['nullable', 'image', 'max:5120'],
        ]);

        DB::transaction(function () use ($validated): void {
            $this->issue->update([
                ...collect($validated)->only(['project_id', 'title', 'occurred_on', 'environment', 'problem', 'error_logs', 'root_cause', 'dont_do_again'])->all(),
                'tags' => collect(explode(',', $this->tags))->map(fn ($tag) => trim($tag))->filter()->values()->all(),
            ]);

            if ($this->problemImage) {
                $path = $this->problemImage->store('issues/'.$this->issue->id, 'public');
                $this->issue->attachments()->create([
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $this->problemImage->getClientOriginalName(),
                    'mime_type' => $this->problemImage->getMimeType(),
                    'size' => $this->problemImage->getSize(),
                ]);
            }

            $existingAttempts = $this->issue->attempts()->with('attachments')->get()->keyBy('id');
            $submittedAttemptIds = collect($this->attempts)->pluck('id')->filter()->map(fn ($id) => (int) $id);

            $existingAttempts->except($submittedAttemptIds->all())->each(function ($attempt): void {
                $attempt->attachments->each(function ($attachment): void {
                    Storage::disk($attachment->disk)->delete($attachment->path);
                    $attachment->delete();
                });
                $attempt->delete();
            });

            foreach ($this->attempts as $position => $attempt) {
                if (filled($attempt['description'])) {
                    $image = $attempt['image'] ?? null;
                    $attemptModel = $existingAttempts->get((int) ($attempt['id'] ?? 0));

                    if ($attemptModel) {
                        $attemptModel->update([
                            'position' => $position + 1,
                            'description' => $attempt['description'],
                            'result' => $attempt['result'],
                        ]);
                    } else {
                        $attemptModel = $this->issue->attempts()->create([
                            'position' => $position + 1,
                            'description' => $attempt['description'],
                            'result' => $attempt['result'],
                        ]);
                    }

                    if ($image) {
                        $path = $image->store('issues/'.$this->issue->id.'/attempts', 'public');
                        $attemptModel->attachments()->create([
                            'disk' => 'public',
                            'path' => $path,
                            'original_name' => $image->getClientOriginalName(),
                            'mime_type' => $image->getMimeType(),
                            'size' => $image->getSize(),
                        ]);
                    }
                }
            }

            if (filled($validated['solution_title'] ?? null) && filled($validated['solution'] ?? null)) {
                $solutionModel = $this->issue->solutions()->firstOrNew();
                $solutionModel->fill([
                    'user_id' => auth()->id(),
                    'title' => $validated['solution_title'],
                    'description' => $validated['solution'],
                ]);
                $solutionModel->save();

                if ($this->solutionImage) {
                    $path = $this->solutionImage->store('issues/'.$this->issue->id.'/solutions', 'public');
                    $solutionModel->attachments()->create([
                        'disk' => 'public',
                        'path' => $path,
                        'original_name' => $this->solutionImage->getClientOriginalName(),
                        'mime_type' => $this->solutionImage->getMimeType(),
                        'size' => $this->solutionImage->getSize(),
                    ]);
                }
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
    <div class="workspace-frame">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div><flux:link :href="route('issues.show', $issue)" wire:navigate icon="arrow-left">{{ __('Back to issue') }}</flux:link><p class="eyebrow mt-6">{{ __('Issue editor') }}</p><flux:heading size="xl" class="mt-2">{{ __('Shape the record, not just the fix.') }}</flux:heading><flux:subheading class="mt-2">{{ __('Keep the context, the dead ends, and the useful answer together.') }}</flux:subheading></div>
            <flux:badge color="amber">{{ __('Editing') }}</flux:badge>
        </div>

        <div class="editor-grid">
            <form wire:submit="save" class="surface space-y-10 p-6 sm:p-8">
                <section id="context" class="space-y-5">
                    <div><p class="eyebrow">{{ __('01 / Context') }}</p><flux:heading size="lg" class="mt-1">{{ __('Name the moment') }}</flux:heading></div>
                    <flux:input wire:model="title" :label="__('Title')" required />
                    <div class="grid gap-5 md:grid-cols-2"><flux:select wire:model="project_id" :label="__('Project')"><option value="">{{ __('Select a project') }}</option>@foreach ($this->projects() as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach</flux:select><flux:input wire:model="occurred_on" :label="__('Date')" type="date" /></div>
                    <flux:input wire:model="environment" :label="__('Environment')" placeholder="Laravel / Docker / MySQL" />
                </section>

                <section id="problem" class="editor-section space-y-5"><div><p class="eyebrow">{{ __('02 / Problem') }}</p><flux:heading size="lg" class="mt-1">{{ __('Show the failure clearly') }}</flux:heading></div><flux:textarea wire:model="problem" :label="__('Problem')" rows="6" required /><flux:textarea wire:model="error_logs" :label="__('Error / logs')" rows="5" /><div class="rounded-lg border border-dashed border-[#d39a50] p-4"><label class="text-sm font-medium">{{ __('Add a problem screenshot') }}</label><input wire:model="problemImage" type="file" accept="image/*" class="mt-3 block w-full text-sm" />@error('problemImage')<flux:text color="red">{{ $message }}</flux:text>@enderror</div></section>

                <section id="attempts" class="editor-section space-y-5"><div class="flex flex-wrap items-end justify-between gap-3"><div><p class="eyebrow">{{ __('03 / Trail') }}</p><flux:heading size="lg" class="mt-1">{{ __('Keep the dead ends') }}</flux:heading></div><flux:button type="button" variant="ghost" icon="plus" wire:click="addAttempt">{{ __('Add attempt') }}</flux:button></div>@foreach ($attempts as $index => $attempt)<div class="attempt-card"><div class="mb-4 flex items-center justify-between"><div class="flex items-center gap-3"><span class="flex size-8 items-center justify-center rounded-full bg-[#d39a50] text-sm font-semibold text-[#18302d]">{{ $index + 1 }}</span><flux:text class="font-medium">{{ __('Attempt :number', ['number' => $index + 1]) }}</flux:text></div>@if(count($attempts) > 1)<flux:button type="button" variant="ghost" icon="trash" :aria-label="__('Remove attempt')" wire:click="removeAttempt({{ $index }})" />@endif</div><div class="grid gap-4 md:grid-cols-[1fr_10rem]"><div><flux:textarea wire:model="attempts.{{ $index }}.description" :label="__('What did you try?')" rows="3" /><input wire:model="attempts.{{ $index }}.image" type="file" accept="image/*" class="mt-3 block w-full text-sm" /></div><flux:select wire:model="attempts.{{ $index }}.result" :label="__('Result')"><option value="failed">{{ __('Failed') }}</option><option value="wrong">{{ __('Wrong assumption') }}</option><option value="worked">{{ __('Worked') }}</option></flux:select></div></div>@endforeach</section>

                <section id="lesson" class="editor-section space-y-5"><div><p class="eyebrow">{{ __('04 / Lesson') }}</p><flux:heading size="lg" class="mt-1">{{ __('Make the answer reusable') }}</flux:heading></div><div class="grid gap-5 md:grid-cols-2"><flux:textarea wire:model="root_cause" :label="__('Root cause')" rows="5" /><flux:textarea wire:model="dont_do_again" :label="__('Don\'t do this again')" rows="5" /></div><flux:input wire:model="tags" :label="__('Tags')" placeholder="laravel, docker, mysql" /></section>

                <section id="solution" class="editor-section space-y-5"><div><p class="eyebrow">{{ __('05 / Solution') }}</p><flux:heading size="lg" class="mt-1">{{ __('Record what worked') }}</flux:heading></div><flux:input wire:model="solution_title" :label="__('Solution title')" /><flux:textarea wire:model="solution" :label="__('Solution')" rows="6" /><div class="rounded-lg border border-dashed border-emerald-500/50 p-4"><label class="text-sm font-medium">{{ __('Add a solution screenshot') }}</label><input wire:model="solutionImage" type="file" accept="image/*" class="mt-3 block w-full text-sm" />@error('solutionImage')<flux:text color="red">{{ $message }}</flux:text>@enderror</div></section>

                <div class="flex flex-wrap justify-end gap-3 border-t border-[#18302d]/10 pt-6 dark:border-white/10"><flux:button variant="ghost" :href="route('issues.show', $issue)" wire:navigate>{{ __('Cancel') }}</flux:button><flux:button variant="primary" type="submit" icon="check">{{ __('Save changes') }}</flux:button></div>
            </form>

            <aside class="editor-rail space-y-4"><div class="surface p-5"><p class="eyebrow">{{ __('Record map') }}</p><nav class="mt-4 space-y-1 text-sm"><a href="#context" class="block rounded-md px-3 py-2 hover:bg-[#d39a50]/10">01 &nbsp; {{ __('Context') }}</a><a href="#problem" class="block rounded-md px-3 py-2 hover:bg-[#d39a50]/10">02 &nbsp; {{ __('Problem') }}</a><a href="#attempts" class="block rounded-md px-3 py-2 hover:bg-[#d39a50]/10">03 &nbsp; {{ __('Attempts') }}</a><a href="#lesson" class="block rounded-md px-3 py-2 hover:bg-[#d39a50]/10">04 &nbsp; {{ __('Lesson') }}</a><a href="#solution" class="block rounded-md px-3 py-2 hover:bg-[#d39a50]/10">05 &nbsp; {{ __('Solution') }}</a></nav></div><div class="rounded-xl bg-[#18302d] p-5 text-[#f7f3eb]"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#d39a50]">{{ __('A useful habit') }}</p><p class="mt-3 text-sm leading-6">{{ __('A good issue record explains not only the fix, but why the obvious fixes failed.') }}</p></div></aside>
        </div>
    </div>
</section>
