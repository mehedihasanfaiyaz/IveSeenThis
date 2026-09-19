<?php

use App\Models\Issue;
use App\Models\Project;
use App\Models\Solution;

$user = auth()->user();
$issueCount = $user->issues()->count();
$solutionCount = $user->solutions()->count();
$projectCount = $user->projects()->count();
$recentIssues = $user->issues()->with('project')->latest()->limit(5)->get();
?>

<x-layouts::app :title="__('Dashboard')">
    <div class="workspace-frame flex flex-col gap-9">
        <div class="flex flex-wrap items-end justify-between gap-5">
            <div><p class="eyebrow">{{ __('Personal engineering log') }}</p><flux:heading size="xl" class="mt-2">{{ __('Good to see you, :name.', ['name' => $user->name]) }}</flux:heading><flux:subheading class="mt-2">{{ __('Keep the hard-won knowledge close.') }}</flux:subheading></div>
            <flux:button variant="primary" icon="plus" :href="route('issues.create')" wire:navigate>{{ __('Log an issue') }}</flux:button>
        </div>
        <div class="grid gap-4 md:grid-cols-3"><a href="{{ route('issues.index') }}" wire:navigate class="metric-card"><span class="text-sm opacity-75">{{ __('Issues logged') }}</span><strong class="mt-8 block text-4xl font-semibold">{{ $issueCount }}</strong></a><a href="{{ route('solutions.index') }}" wire:navigate class="metric-card"><span class="text-sm opacity-80">{{ __('Solutions kept') }}</span><strong class="mt-8 block text-4xl font-semibold">{{ $solutionCount }}</strong></a><a href="{{ route('projects.index') }}" wire:navigate class="metric-card"><span class="text-sm opacity-80">{{ __('Project') }}</span><strong class="mt-8 block text-2xl font-semibold">{{ $projectCount ? __('IveSeenThis') : __('Start one') }}</strong></a></div>
        <div class="grid gap-6 lg:grid-cols-[1.35fr_0.65fr]">
            <section class="surface overflow-hidden"><div class="flex items-end justify-between border-b border-[#18302d]/10 px-6 py-5 dark:border-white/10"><div><p class="eyebrow">{{ __('Latest entries') }}</p><flux:heading size="lg" class="mt-1">{{ __('Recent issues') }}</flux:heading></div><flux:link :href="route('issues.index')" wire:navigate>{{ __('View archive') }}</flux:link></div>@forelse($recentIssues as $issue)<a href="{{ route('issues.show', $issue) }}" wire:navigate class="issue-row flex items-center justify-between gap-5"><div class="min-w-0"><flux:heading class="truncate">{{ $issue->title }}</flux:heading><flux:text class="mt-1 truncate">{{ $issue->project?->name ?? __('Unassigned project') }} · {{ $issue->created_at->diffForHumans() }}</flux:text></div>@if($issue->solutions->isNotEmpty())<flux:badge color="green" icon="check">{{ __('Solved') }}</flux:badge>@else<flux:badge color="amber">{{ __('Open') }}</flux:badge>@endif</a>@empty<div class="p-12 text-center"><flux:text>{{ __('Your recent issues will appear here.') }}</flux:text></div>@endforelse</section>
            <aside class="surface flex flex-col justify-between p-6"><div><p class="eyebrow">{{ __('A small ritual') }}</p><flux:heading size="lg" class="mt-2">{{ __('Leave a better trail than you found.') }}</flux:heading><flux:text class="mt-4 leading-7">{{ __('Write down the failed paths too. They are often the most useful part of the fix.') }}</flux:text></div><flux:link class="mt-8" :href="route('issues.create')" wire:navigate icon="arrow-right">{{ __('Capture a lesson') }}</flux:link></aside>
        </div>
    </div>
</x-layouts::app>
