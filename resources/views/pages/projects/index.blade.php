<?php

use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Projects')] class extends Component {
    public string $name = '';
    public string $description = '';

    #[Computed]
    public function projects() { return auth()->user()->projects()->withCount('issues')->orderBy('name')->get(); }

    public function save(): void
    {
        $validated = $this->validate(['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string']]);
        auth()->user()->projects()->create([...$validated, 'slug' => Project::makeSlug($validated['name'])]);
        $this->reset('name', 'description');
    }
}; ?>

<section class="w-full">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8">
        <div><flux:heading size="xl">{{ __('Projects') }}</flux:heading><flux:subheading>{{ __('Give your fixes a home and make patterns visible.') }}</flux:subheading></div>
        <form wire:submit="save" class="grid gap-3 rounded-xl border border-zinc-200 p-5 md:grid-cols-[1fr_1fr_auto] dark:border-zinc-700"><flux:input wire:model="name" :label="__('Project name')" placeholder="Inqord Auth" required /><flux:input wire:model="description" :label="__('Description')" placeholder="Authentication and account flows" /><flux:button variant="primary" type="submit" class="self-end">{{ __('Add project') }}</flux:button></form>
        <div class="grid gap-4 md:grid-cols-2">@forelse ($this->projects as $project)<div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700"><div class="flex justify-between gap-4"><flux:heading size="lg">{{ $project->name }}</flux:heading><flux:badge>{{ $project->issues_count }} {{ __('issues') }}</flux:badge></div><flux:text class="mt-2">{{ $project->description ?: __('No description yet.') }}</flux:text></div>@empty<div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center md:col-span-2"><flux:text>{{ __('Add your first project above.') }}</flux:text></div>@endforelse</div>
    </div>
</section>
