<?php

use App\Http\Controllers\Api\DaemonController;
use App\Http\Controllers\Api\ServerApiController;
use Illuminate\Support\Facades\Route;

Route::post('/daemon/auth', [DaemonController::class, 'auth'])->middleware('throttle:api');
Route::post('/daemon/heartbeat', [DaemonController::class, 'heartbeat'])->middleware('throttle:api');

Route::middleware(['api.token', 'throttle:api'])->group(function (): void {
    Route::get('/servers', [ServerApiController::class, 'index']);
    Route::get('/servers/{server}', [ServerApiController::class, 'show']);
    Route::post('/servers/{server}/start', [ServerApiController::class, 'power'])->defaults('action', 'start');
    Route::post('/servers/{server}/stop', [ServerApiController::class, 'power'])->defaults('action', 'stop');
    Route::post('/servers/{server}/restart', [ServerApiController::class, 'power'])->defaults('action', 'restart');
    Route::post('/servers/{server}/kill', [ServerApiController::class, 'power'])->defaults('action', 'kill');
    Route::get('/servers/{server}/stats', [ServerApiController::class, 'stats']);
    Route::get('/servers/{server}/files', [ServerApiController::class, 'files']);
    Route::post('/servers/{server}/files/upload', [ServerApiController::class, 'upload']);
    Route::post('/servers/{server}/files/create', [ServerApiController::class, 'createFile']);
    Route::patch('/servers/{server}/files/rename', [ServerApiController::class, 'renameFile']);
    Route::delete('/servers/{server}/files/delete', [ServerApiController::class, 'deleteFile']);
    Route::get('/servers/{server}/databases', [ServerApiController::class, 'databases']);
    Route::post('/servers/{server}/databases', [ServerApiController::class, 'createDatabase']);
    Route::delete('/servers/{server}/databases/{database}', [ServerApiController::class, 'deleteDatabase']);
    Route::get('/servers/{server}/backups', [ServerApiController::class, 'backups']);
    Route::post('/servers/{server}/backups', [ServerApiController::class, 'createBackup']);
    Route::post('/servers/{server}/backups/{backup}/restore', [ServerApiController::class, 'restoreBackup']);
    Route::delete('/servers/{server}/backups/{backup}', [ServerApiController::class, 'deleteBackup']);
});
