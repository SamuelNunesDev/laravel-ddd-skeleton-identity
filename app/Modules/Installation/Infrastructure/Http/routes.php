<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('web')
    ->get('/', fn () => Inertia::render('Home'))
    ->name('home');
