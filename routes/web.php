<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CollaborationController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TaskController;
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
    return Inertia::render('Auth/Register');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

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
    ->only(['index', 'store', 'update', 'destroy', 'show'])
    ->middleware(['auth', 'verified', 'can:manage-sales']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('clients')->group(function () {
        Route::get('/search', [ClientController::class, 'search'])->name('clients.search');
        Route::get('/renewals', [ClientController::class, 'renewals'])->name('clients.renewals');
        Route::get('/trials', [ClientController::class, 'trials'])->name('clients.trials');
        Route::get('/old', [ClientController::class, 'old'])->name('clients.old');
        Route::get('/source-options', [ClientController::class, 'getSourceOptions'])->name('clients.getSourceOptions');
        Route::resource('/', ClientController::class)
            ->only(['index', 'store', 'update', 'destroy', 'show'])
            ->names([
                'index' => 'clients.index', 'store' => 'clients.store', 'update' => 'clients.update',
                'destroy' => 'clients.destroy', 'show' => 'clients.show',
            ])
            ->parameters(['' => 'client']);
    });

    Route::prefix('collaboration')->group(function () {
        Route::get('/send-request', function () {
            return Inertia::render('Collaboration/SendRequest');
        })->name('collaboration.send-request');

        Route::get('/get-current-request/{id}', [CollaborationController::class, 'getCurrentRequest'])
            ->name('collaboration.getCurrentRequest');

        Route::get('/all-requests', [CollaborationController::class, 'index'])
            ->name('collaboration.all-requests');

        Route::post('/approve-request/{requestId}', [CollaborationController::class, 'approveRequest'])
            ->name('collaboration.approve-request');
        Route::post('/reject-request/{requestId}', [CollaborationController::class, 'rejectRequest'])
            ->name('collaboration.reject-request');
        Route::post('/delete-manager/{managerId}', [CollaborationController::class, 'deleteManager'])
            ->name('collaboration.delete-manager');

        Route::post('/send-request', [CollaborationController::class, 'sendRequest'])
            ->name('collaboration.send-request');

        Route::post('/delete-request/{id}', [CollaborationController::class, 'deleteRequest'])
            ->name('collaboration.delete-request');
    });
});

Route::resource('leads', LeadController::class)
    ->only(['index', 'store', 'update', 'destroy', 'show'])
    ->middleware(['auth', 'verified']);

Route::resource('tasks', TaskController::class)
    ->only(['index', 'store', 'update', 'destroy', 'show'])
    ->middleware(['auth', 'verified']);


require __DIR__ . '/auth.php';
