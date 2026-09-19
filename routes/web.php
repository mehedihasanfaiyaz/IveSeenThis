<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('home');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('issues', 'pages::issues.index')->name('issues.index');
    Route::livewire('issues/create', 'pages::issues.create')->name('issues.create');
    Route::livewire('issues/{issue}/edit', 'pages::issues.edit')->name('issues.edit');
    Route::livewire('issues/{issue}', 'pages::issues.show')->name('issues.show');
    Route::livewire('projects', 'pages::projects.index')->name('projects.index');
    Route::livewire('solutions', 'pages::solutions.index')->name('solutions.index');
});

require __DIR__.'/settings.php';
