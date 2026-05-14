<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['ensure.authenticated'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('welcome');
    })->name('home');

    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});
$serverIp = gethostbyname(php_uname('n'));
Route::redirect('/app/*', "wss://{$serverIp}:8080/app");

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/room.php';
