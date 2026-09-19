<?php

use App\Models\Issue;
use App\Models\Project;
use App\Models\Solution;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Log issue')] class extends Component {
    use WithFileUploads;

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
    public array $attempts = [['description' => '', 'result' => 'failed', 'image' => null]];

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

        $issue = DB::transaction(function () use ($validated) {
            $issue = auth()->user()->issues()->create([
                ...collect($validated)->only(['project_id', 'title', 'occurred_on', 'environment', 'problem', 'error_logs', 'root_cause', 'dont_do_again'])->all(),
                'tags' => collect(explode(',', $this->tags))->map(fn ($tag) => trim($tag))->filter()->values()->all(),
            ]);

            if ($this->problemImage) {
                $path = $this->problemImage->store('issues/'.$issue->id, 'public');
                $issue->attachments()->create([
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $this->problemImage->getClientOriginalName(),
                    'mime_type' => $this->problemImage->getMimeType(),
                    'size' => $this->problemImage->getSize(),
                ]);
            }

            foreach ($this->attempts as $position => $attempt) {
                if (filled($attempt['description'])) {
                    $image = $attempt['image'] ?? null;
                    $createdAttempt = $issue->attempts()->create([
                        'position' => $position + 1,
                        'description' => $attempt['description'],
                        'result' => $attempt['result'],
                    ]);

                    if ($image) {
                        $path = $image->store('issues/'.$issue->id.'/attempts', 'public');
                        $createdAttempt->attachments()->create([
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
                $solutionModel = $issue->solutions()->create([
                    'user_id' => auth()->id(),
                    'title' => $validated['solution_title'],
                    'description' => $validated['solution'],
                ]);

                if ($this->solutionImage) {
                    $path = $this->solutionImage->store('issues/'.$issue->id.'/solutions', 'public');
                    $solutionModel->attachments()->create([
                        'disk' => 'public',
                        'path' => $path,
                        'original_name' => $this->solutionImage->getClientOriginalName(),
                        'mime_type' => $this->solutionImage->getMimeType(),
                        'size' => $this->solutionImage->getSize(),
                    ]);
                }
            }

            return $issue;
        });

        $this->redirect(route('issues.show', $issue), navigate: true);
    }

    public function projects()
    {
        return auth()->user()->projects()->orderBy('name')->get();
    }
}; ?>

<section class="w-full">
    <div class="mx-auto w-full max-w-4xl">
        <div class="mb-8">
            <flux:heading size="xl">{{ __('Log an issue') }}</flux:heading>
            <flux:subheading>{{ __('Capture enough context that the fix is reusable next time.') }}</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-8">
            <div class="grid gap-5 md:grid-cols-2">
                <flux:input wire:model="title" :label="__('Title')" placeholder="Laravel Docker MySQL connection refused" required class="md:col-span-2" />
                <flux:select wire:model="project_id" :label="__('Project')">
                    <option value="">{{ __('Select a project') }}</option>
                    @foreach ($this->projects() as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach
                </flux:select>
                <flux:input wire:model="occurred_on" :label="__('Date')" type="date" />
                <flux:input wire:model="environment" :label="__('Environment')" placeholder="Laravel 13 / Docker / MySQL 8.4" class="md:col-span-2" />
            </div>

            <flux:textarea wire:model="problem" :label="__('Problem')" rows="5" required />
            <div><label class="text-sm font-medium">{{ __('Problem image') }}</label><input wire:model="problemImage" type="file" accept="image/*" class="mt-2 block w-full text-sm" /><flux:text class="mt-1">{{ __('PNG, JPG, GIF, or WebP up to 5 MB.') }}</flux:text>@error('problemImage')<flux:text color="red">{{ $message }}</flux:text>@enderror</div>
            <flux:textarea wire:model="error_logs" :label="__('Error / logs')" rows="5" />

            <div class="space-y-4">
                <div class="flex items-center justify-between"><flux:heading size="lg">{{ __('What I tried') }}</flux:heading><flux:button type="button" variant="ghost" icon="plus" wire:click="addAttempt">{{ __('Add attempt') }}</flux:button></div>
                @foreach ($attempts as $index => $attempt)
                    <div class="grid gap-3 md:grid-cols-[1fr_9rem_auto]">
                        <div><flux:input wire:model="attempts.{{ $index }}.description" :label="__('Attempt :number', ['number' => $index + 1])" /><input wire:model="attempts.{{ $index }}.image" type="file" accept="image/*" class="mt-2 block w-full text-sm" /></div>
                        <flux:select wire:model="attempts.{{ $index }}.result" :label="__('Result')"><option value="failed">{{ __('Failed') }}</option><option value="wrong">{{ __('Wrong assumption') }}</option><option value="worked">{{ __('Worked') }}</option></flux:select>
                        @if (count($attempts) > 1)<flux:button type="button" variant="ghost" icon="trash" :aria-label="__('Remove attempt')" wire:click="removeAttempt({{ $index }})" class="self-end" />@endif
                    </div>
                @endforeach
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <flux:textarea wire:model="root_cause" :label="__('Root cause')" rows="4" />
                <flux:textarea wire:model="dont_do_again" :label="__('Don\'t do this again')" rows="4" placeholder="Never use localhost from inside the app container." />
                <flux:input wire:model="solution_title" :label="__('Solution title')" placeholder="Use the service hostname" />
                <flux:input wire:model="tags" :label="__('Tags')" placeholder="laravel, docker, mysql" />
                <flux:textarea wire:model="solution" :label="__('Solution')" rows="5" class="md:col-span-2" />
                <div class="md:col-span-2"><label class="text-sm font-medium">{{ __('Solution image') }}</label><input wire:model="solutionImage" type="file" accept="image/*" class="mt-2 block w-full text-sm" />@error('solutionImage')<flux:text color="red">{{ $message }}</flux:text>@enderror</div>
            </div>

            <div class="flex justify-end gap-3"><flux:button variant="ghost" :href="route('issues.index')" wire:navigate>{{ __('Cancel') }}</flux:button><flux:button variant="primary" type="submit">{{ __('Save issue') }}</flux:button></div>
        </form>
    </div>
</section>
