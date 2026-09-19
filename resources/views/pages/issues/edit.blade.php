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
    <div class="workspace-frame w-full max-w-4xl">
        <div class="mb-8"><flux:link :href="route('issues.show', $issue)" wire:navigate icon="arrow-left">{{ __('Back to issue') }}</flux:link><flux:heading size="xl" class="mt-4">{{ __('Edit issue') }}</flux:heading><flux:subheading>{{ __('Update the details, attempts, or solution as you learn more.') }}</flux:subheading></div>
        <form wire:submit="save" class="surface space-y-8 p-6 sm:p-8">
            <div class="grid gap-5 md:grid-cols-2">
                <flux:input wire:model="title" :label="__('Title')" required class="md:col-span-2" />
                <flux:select wire:model="project_id" :label="__('Project')"><option value="">{{ __('Select a project') }}</option>@foreach ($this->projects() as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach</flux:select>
                <flux:input wire:model="occurred_on" :label="__('Date')" type="date" />
                <flux:input wire:model="environment" :label="__('Environment')" class="md:col-span-2" />
            </div>
            <flux:textarea wire:model="problem" :label="__('Problem')" rows="5" required /><div><label class="text-sm font-medium">{{ __('Problem image') }}</label><input wire:model="problemImage" type="file" accept="image/*" class="mt-2 block w-full text-sm" />@error('problemImage')<flux:text color="red">{{ $message }}</flux:text>@enderror</div>
            <flux:textarea wire:model="error_logs" :label="__('Error / logs')" rows="5" />
            <div class="space-y-4"><div class="flex items-center justify-between"><flux:heading size="lg">{{ __('What I tried') }}</flux:heading><flux:button type="button" variant="ghost" icon="plus" wire:click="addAttempt">{{ __('Add attempt') }}</flux:button></div>@foreach ($attempts as $index => $attempt)<div class="grid gap-3 md:grid-cols-[1fr_9rem_auto]"><div><flux:input wire:model="attempts.{{ $index }}.description" :label="__('Attempt :number', ['number' => $index + 1])" /><input wire:model="attempts.{{ $index }}.image" type="file" accept="image/*" class="mt-2 block w-full text-sm" /></div><flux:select wire:model="attempts.{{ $index }}.result" :label="__('Result')"><option value="failed">{{ __('Failed') }}</option><option value="wrong">{{ __('Wrong assumption') }}</option><option value="worked">{{ __('Worked') }}</option></flux:select>@if(count($attempts) > 1)<flux:button type="button" variant="ghost" icon="trash" :aria-label="__('Remove attempt')" wire:click="removeAttempt({{ $index }})" class="self-end" />@endif</div>@endforeach</div>
            <div class="grid gap-5 md:grid-cols-2"><flux:textarea wire:model="root_cause" :label="__('Root cause')" rows="4" /><flux:textarea wire:model="dont_do_again" :label="__('Don\'t do this again')" rows="4" /><flux:input wire:model="solution_title" :label="__('Solution title')" /><flux:input wire:model="tags" :label="__('Tags')" /><flux:textarea wire:model="solution" :label="__('Solution')" rows="5" class="md:col-span-2" /><div class="md:col-span-2"><label class="text-sm font-medium">{{ __('Solution image') }}</label><input wire:model="solutionImage" type="file" accept="image/*" class="mt-2 block w-full text-sm" />@error('solutionImage')<flux:text color="red">{{ $message }}</flux:text>@enderror</div></div>
            <div class="flex justify-end gap-3"><flux:button variant="ghost" :href="route('issues.show', $issue)" wire:navigate>{{ __('Cancel') }}</flux:button><flux:button variant="primary" type="submit">{{ __('Save changes') }}</flux:button></div>
        </form>
    </div>
</section>
