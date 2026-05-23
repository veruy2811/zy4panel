<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\Server\ServerPanelController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoreController::class, 'home'])->name('home');
Route::get('/products', [StoreController::class, 'products'])->name('products.index');
Route::get('/products/{product:slug}', [StoreController::class, 'product'])->name('products.show');
Route::post('/cart/{plan}/add', [StoreController::class, 'addToCart'])->name('cart.add');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:login');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/checkout', [StoreController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [StoreController::class, 'placeOrder'])->name('checkout.place');
    Route::get('/invoice/{invoice}', [StoreController::class, 'invoice'])->name('invoice.show');
    Route::post('/invoice/{invoice}/payment', [StoreController::class, 'uploadPayment'])->name('invoice.payment');

    Route::get('/client/dashboard', [ClientController::class, 'dashboard'])->name('client.dashboard');
    Route::get('/client/orders', [ClientController::class, 'orders'])->name('client.orders');
    Route::get('/client/invoices', [ClientController::class, 'invoices'])->name('client.invoices');
    Route::get('/client/servers', [ClientController::class, 'servers'])->name('client.servers');

    Route::prefix('/server/{server}')->name('server.')->group(function (): void {
        Route::get('/', [ServerPanelController::class, 'dashboard'])->name('dashboard');
        Route::get('/console', [ServerPanelController::class, 'console'])->name('console');
        Route::get('/files', [ServerPanelController::class, 'files'])->name('files');
        Route::post('/files/upload', [ServerPanelController::class, 'uploadFile'])->name('files.upload');
        Route::post('/files/create', [ServerPanelController::class, 'createFile'])->name('files.create');
        Route::patch('/files/rename', [ServerPanelController::class, 'renameFile'])->name('files.rename');
        Route::delete('/files/delete', [ServerPanelController::class, 'deleteFile'])->name('files.delete');
        Route::get('/databases', [ServerPanelController::class, 'databases'])->name('databases');
        Route::post('/databases', [ServerPanelController::class, 'createDatabase'])->name('databases.create');
        Route::delete('/databases/{database}', [ServerPanelController::class, 'deleteDatabase'])->name('databases.delete');
        Route::get('/backups', [ServerPanelController::class, 'backups'])->name('backups');
        Route::post('/backups', [ServerPanelController::class, 'createBackup'])->name('backups.create');
        Route::get('/backups/{backup}/download', [ServerPanelController::class, 'downloadBackup'])->name('backups.download');
        Route::post('/backups/{backup}/restore', [ServerPanelController::class, 'restoreBackup'])->name('backups.restore');
        Route::delete('/backups/{backup}', [ServerPanelController::class, 'deleteBackup'])->name('backups.delete');
        Route::get('/network', [ServerPanelController::class, 'network'])->name('network');
        Route::get('/startup', [ServerPanelController::class, 'startup'])->name('startup');
        Route::patch('/startup', [ServerPanelController::class, 'updateStartup'])->name('startup.update');
        Route::get('/settings', [ServerPanelController::class, 'settings'])->name('settings');
        Route::patch('/settings', [ServerPanelController::class, 'updateSettings'])->name('settings.update');
        Route::post('/settings/reinstall', [ServerPanelController::class, 'reinstall'])->name('settings.reinstall');
        Route::delete('/settings/delete', [ServerPanelController::class, 'deleteServer'])->name('settings.delete');
        Route::get('/activity', [ServerPanelController::class, 'activity'])->name('activity');
        Route::post('/{action}', [ServerPanelController::class, 'power'])->name('power');
    });
});

Route::middleware(['auth', 'admin'])->prefix('/admin')->name('admin.')->group(function (): void {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');

    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::post('/products', [AdminController::class, 'storeProduct'])->name('products.store');
    Route::patch('/products/{product}', [AdminController::class, 'updateProduct'])->name('products.update');

    Route::get('/plans', [AdminController::class, 'plans'])->name('plans');
    Route::post('/plans', [AdminController::class, 'storePlan'])->name('plans.store');
    Route::patch('/plans/{plan}', [AdminController::class, 'updatePlan'])->name('plans.update');

    Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
    Route::get('/invoices', [AdminController::class, 'invoices'])->name('invoices');
    Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
    Route::post('/payments/{payment}/approve', [AdminController::class, 'approvePayment'])->name('payments.approve');
    Route::post('/payments/{payment}/reject', [AdminController::class, 'rejectPayment'])->name('payments.reject');

    Route::get('/servers', [AdminController::class, 'servers'])->name('servers');
    Route::post('/servers/{server}/suspend', [AdminController::class, 'suspendServer'])->name('servers.suspend');
    Route::post('/servers/{server}/unsuspend', [AdminController::class, 'unsuspendServer'])->name('servers.unsuspend');

    Route::get('/nodes', [AdminController::class, 'nodes'])->name('nodes');
    Route::post('/nodes', [AdminController::class, 'storeNode'])->name('nodes.store');
    Route::patch('/nodes/{node}', [AdminController::class, 'updateNode'])->name('nodes.update');

    Route::get('/allocations', [AdminController::class, 'allocations'])->name('allocations');
    Route::post('/allocations', [AdminController::class, 'storeAllocation'])->name('allocations.store');

    Route::get('/templates', [AdminController::class, 'templates'])->name('templates');
    Route::post('/templates', [AdminController::class, 'storeTemplate'])->name('templates.store');

    Route::match(['get', 'post'], '/settings', [AdminController::class, 'settings'])->name('settings');
    Route::get('/logs', [AdminController::class, 'logs'])->name('logs');
});
