<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::get('documents', [\App\Http\Controllers\DocumentController::class, 'index'])->name('documents.index');
    
    // Incident routes
    Route::get('incidents', [\App\Http\Controllers\IncidentController::class, 'index'])->name('incidents.index');
    Route::get('incidents/create', [\App\Http\Controllers\IncidentController::class, 'create'])->name('incidents.create');
    Route::post('incidents', [\App\Http\Controllers\IncidentController::class, 'store'])->name('incidents.store');
    Route::get('incidents/{incident}', [\App\Http\Controllers\IncidentController::class, 'show'])->name('incidents.show');
    Route::post('incidents/{incident}/comments', [\App\Http\Controllers\IncidentController::class, 'addComment'])->name('incidents.comments.store');
    Route::get('incidents/{incident}/download', [\App\Http\Controllers\IncidentController::class, 'downloadFile'])->name('incidents.download');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
