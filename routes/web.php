<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/ms');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:10,1');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff', 'auth.session'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('contents', ContentController::class)->except('show');
    Route::get('/contents/{content}/preview', [ContentController::class, 'preview'])->name('contents.preview');
    Route::get('/contents/{content}/revisions', [ContentController::class, 'revisions'])->name('contents.revisions');
    Route::post('/contents/{content}/revisions/{revision}/restore', [ContentController::class, 'restore'])->name('contents.restore');
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::get('/categories', [AdminController::class, 'categories'])->name('categories');
    Route::post('/categories', [AdminController::class, 'saveCategory']);
    Route::delete('/categories/{category}', [AdminController::class, 'deleteCategory'])->name('categories.destroy');
    foreach (['settings', 'menus', 'users'] as $page) {
        Route::get('/'.$page, [AdminController::class, $page])->name($page);
    }
    Route::put('/settings', [AdminController::class, 'saveSettings']);
    Route::put('/menus', [AdminController::class, 'saveMenus']);
    Route::post('/users', [AdminController::class, 'saveUser']);
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    Route::put('/profile/password', [AdminController::class, 'password'])->name('password');
    Route::get('/audit', [AdminController::class, 'audit'])->name('audit');
    Route::get('/trash', [AdminController::class, 'trash'])->name('trash');
    Route::post('/trash/{id}', [AdminController::class, 'untrash'])->name('untrash');
});
Route::prefix('{locale}')->where(['locale' => 'ms|en'])->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'home'])->name('home');
    Route::get('/search', [PortalController::class, 'index'])->name('index');
    Route::get('/content/{slug}', [PortalController::class, 'show'])->name('show');
});
