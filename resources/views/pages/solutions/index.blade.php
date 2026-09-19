<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Solutions')] class extends Component {
    public string $search = '';

    #[Computed]
    public function solutions() { return auth()->user()->solutions()->with('issue')->when($this->search, fn ($query) => $query->where('title', 'like', "%{$this->search}%")->orWhere('description', 'like', "%{$this->search}%"))->latest()->get(); }
}; ?>

<section class="w-full">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8"><div><flux:heading size="xl">{{ __('Solutions') }}</flux:heading><flux:subheading>{{ __('The fixes worth finding again.') }}</flux:subheading></div><flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search your solutions...')" /><div class="grid gap-4 md:grid-cols-2">@forelse ($this->solutions as $solution)<a href="{{ $solution->issue ? route('issues.show', $solution->issue) : route('solutions.index') }}" wire:navigate class="rounded-xl border border-zinc-200 p-5 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/60"><flux:heading size="lg">{{ $solution->title }}</flux:heading><flux:text class="mt-2 line-clamp-4 whitespace-pre-line">{{ $solution->description }}</flux:text>@if($solution->issue)<flux:text class="mt-4 text-sm">{{ __('From: :title', ['title' => $solution->issue->title]) }}</flux:text>@endif</a>@empty<div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center md:col-span-2"><flux:text>{{ __('Solutions appear here when you record a fix on an issue.') }}</flux:text></div>@endforelse</div></div>
</section>
