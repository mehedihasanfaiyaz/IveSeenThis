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
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8">
        <div class="flex flex-wrap items-end justify-between gap-4"><div><flux:heading size="xl">{{ __('Welcome back, :name', ['name' => $user->name]) }}</flux:heading><flux:subheading>{{ __('What did you learn today?') }}</flux:subheading></div><flux:button variant="primary" icon="plus" :href="route('issues.create')" wire:navigate>{{ __('Log issue') }}</flux:button></div>
        <div class="grid gap-4 md:grid-cols-3"><a href="{{ route('issues.index') }}" wire:navigate class="rounded-xl border border-zinc-200 p-5 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/60"><flux:text>{{ __('Issues') }}</flux:text><flux:heading size="xl" class="mt-2">{{ $issueCount }}</flux:heading></a><a href="{{ route('solutions.index') }}" wire:navigate class="rounded-xl border border-zinc-200 p-5 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/60"><flux:text>{{ __('Solutions') }}</flux:text><flux:heading size="xl" class="mt-2">{{ $solutionCount }}</flux:heading></a><a href="{{ route('projects.index') }}" wire:navigate class="rounded-xl border border-zinc-200 p-5 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/60"><flux:text>{{ __('Projects') }}</flux:text><flux:heading size="xl" class="mt-2">{{ $projectCount }}</flux:heading></a></div>
        <section><div class="mb-4 flex items-center justify-between"><flux:heading size="lg">{{ __('Recent issues') }}</flux:heading><flux:link :href="route('issues.index')" wire:navigate>{{ __('View all') }}</flux:link></div><div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">@forelse($recentIssues as $issue)<a href="{{ route('issues.show', $issue) }}" wire:navigate class="flex items-center justify-between gap-4 border-b border-zinc-200 p-4 last:border-0 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/60"><div class="min-w-0"><flux:heading class="truncate">{{ $issue->title }}</flux:heading><flux:text class="truncate">{{ $issue->project?->name ?? __('Unassigned project') }}</flux:text></div><flux:text class="shrink-0">{{ $issue->created_at->diffForHumans() }}</flux:text></a>@empty<div class="p-10 text-center"><flux:text>{{ __('Your recent issues will appear here.') }}</flux:text></div>@endforelse</div></section>
    </div>
</x-layouts::app>
