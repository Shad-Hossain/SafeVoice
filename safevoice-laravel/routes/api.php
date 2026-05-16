<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SosController;
use App\Http\Controllers\OfficerController;
use App\Http\Controllers\PrivateInvestigatorController;

// Auth
Route::post('/register',         [AuthController::class, 'register']);
Route::post('/login',            [AuthController::class, 'login']);
Route::post('/logout',           [AuthController::class, 'logout']);
Route::get('/check-session',     [AuthController::class, 'checkSession']);

// Complaints
Route::get('/complaints',                [ComplaintController::class, 'index']);
Route::get('/complaints/{id}',           [ComplaintController::class, 'show']);
Route::post('/complaints/submit',        [ComplaintController::class, 'submit']);
Route::post('/complaints/update-status', [ComplaintController::class, 'updateStatus']);
Route::get('/my-complaints',             [ComplaintController::class, 'myComplaints']);

// Admin
Route::post('/admin/login',               [AdminController::class, 'login']);
Route::post('/admin/logout',              [AdminController::class, 'logout']);
Route::get('/admin/users',                [AdminController::class, 'users']);
Route::post('/admin/users/update-status', [AdminController::class, 'updateUserStatus']);

// SOS
Route::post('/sos/create',          [SosController::class, 'create']);
Route::get('/sos/alerts',           [SosController::class, 'alerts']);
Route::get('/sos/my-notifications', [SosController::class, 'myNotifications']);
Route::post('/sos/respond',         [SosController::class, 'respond']);

// Officers
Route::get('/officers',         [OfficerController::class, 'index']);
Route::post('/officers',        [OfficerController::class, 'store']);
Route::post('/officers/toggle', [OfficerController::class, 'toggle']);

// Private Investigators
Route::get('/pi',               [PrivateInvestigatorController::class, 'index']);
Route::post('/pi/assign',       [PrivateInvestigatorController::class, 'assign']);
Route::get('/pi/notifications', [PrivateInvestigatorController::class, 'notifications']);
Route::post('/pi/payment',      [PrivateInvestigatorController::class, 'payment']);