<?php

use App\Http\Controllers\ChefController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Chef & Recipe routes
Route::get('/chefs', [ChefController::class, 'index'])->name('chefs.index');
Route::get('/chefs/{chef}', [ChefController::class, 'show'])->name('chefs.show');
Route::post('/chefs/{chef}/recipes', [ChefController::class, 'storeRecipe'])->name('chefs.recipes.store');

