<?php

declare(strict_types=1);

use Capell\Smart404\Http\Controllers\Smart404AssetsController;
use Capell\Smart404\Http\Controllers\Smart404SuggestionsController;
use Illuminate\Support\Facades\Route;

Route::get('/smart-404/smart-404.js', [Smart404AssetsController::class, 'script'])
    ->name('capell-smart-404.script');

Route::get('/smart-404/smart-404.css', [Smart404AssetsController::class, 'styles'])
    ->name('capell-smart-404.styles');

Route::middleware(['web', 'throttle:capell-smart-404-suggestions'])
    ->get('/smart-404/suggestions', Smart404SuggestionsController::class)
    ->name('capell-smart-404.suggestions');
