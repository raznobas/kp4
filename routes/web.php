<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChirpController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

//Route::get('/', function () {
//    return Inertia::render('Welcome', [
//        'canLogin' => Route::has('login'),
//        'canRegister' => Route::has('register'),
//        'laravelVersion' => Application::VERSION,
//        'phpVersion' => PHP_VERSION,
//    ]);
//});
Route::get('/', function () {
    return redirect()->route('login');
});
Route::get('/register', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::resource('chirps', ChirpController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware(['auth', 'verified']);

Route::resource('categories', CategoryController::class)
    ->only(['index', 'store', 'update', 'destroy', 'destroyCost'])
    ->middleware(['auth', 'verified', 'can:manage-categories']);

Route::post('/categories/store-cost', [CategoryController::class, 'storeCost'])
    ->name('categories.storeCost')
    ->middleware(['auth', 'verified', 'can:manage-categories']);

Route::delete('/categories/destroy-cost/{id}', [CategoryController::class, 'destroyCost'])
    ->name('categories.destroyCost')
    ->middleware(['auth', 'verified', 'can:manage-categories']);

Route::resource('sales', SaleController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware(['auth', 'verified', 'can:manage-sales']);

Route::get('/clients/search', [ClientController::class, 'search'])
    ->name('clients.search');

Route::resource('clients', ClientController::class)
    ->only(['index', 'store', 'update', 'destroy', 'show'])
    ->middleware(['auth', 'verified']);

Route::resource('leads', LeadController::class)
    ->only(['index', 'store', 'update', 'destroy', 'show'])
    ->middleware(['auth', 'verified']);


require __DIR__ . '/auth.php';
