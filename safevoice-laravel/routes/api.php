<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SosController;
use App\Http\Controllers\OfficerController;
use App\Http\Controllers\PrivateInvestigatorController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\EvidenceController;

// ── Auth ─────────────────────────────────────────────────────
Route::post('/register',         [AuthController::class, 'register']);
Route::post('/login',            [AuthController::class, 'login']);
Route::post('/logout',           [AuthController::class, 'logout']);
Route::get('/check-session',     [AuthController::class, 'checkSession']);
Route::get('/check_session',     [AuthController::class, 'checkSession']); // legacy

// ── Complaints ───────────────────────────────────────────────
Route::get('/complaints',                [ComplaintController::class, 'index']);
Route::get('/complaints/{id}',           [ComplaintController::class, 'show']);
Route::post('/complaints/submit',        [ComplaintController::class, 'submit']);
Route::post('/submit_complaint',         [ComplaintController::class, 'submit']); // legacy
Route::post('/complaints/update-status', [ComplaintController::class, 'updateStatus']);
Route::post('/update_status',            [ComplaintController::class, 'updateStatus']); // legacy
Route::get('/my-complaints',             [ComplaintController::class, 'myComplaints']);
Route::get('/get_user_complaints',       [ComplaintController::class, 'myComplaints']); // legacy

// ── Evidence ─────────────────────────────────────────────────
Route::post('/upload_complaint_evidence', [EvidenceController::class, 'uploadComplaint']);
Route::get('/get_complaints_evidence',    [EvidenceController::class, 'getComplaintEvidence']);
Route::post('/upload_sos_evidence',       [EvidenceController::class, 'uploadSos']);

// ── Admin ────────────────────────────────────────────────────
Route::post('/admin/login',               [AdminController::class, 'login']);
Route::post('/admin_login',               [AdminController::class, 'login']); // legacy
Route::post('/admin/logout',              [AdminController::class, 'logout']);
Route::get('/admin/users',                [AdminController::class, 'users']);
Route::get('/manage_user',                [AdminController::class, 'users']); // legacy
Route::post('/admin/users/update-status', [AdminController::class, 'updateUserStatus']);

// ── Super Admin ──────────────────────────────────────────────
Route::post('/super_admin_auth',          [SuperAdminController::class, 'login']);
Route::post('/super-admin/login',         [SuperAdminController::class, 'login']);
Route::post('/super-admin/logout',        [SuperAdminController::class, 'logout']);
Route::get('/super-admin/stats',          [SuperAdminController::class, 'stats']);
Route::get('/super-admin/users',          [SuperAdminController::class, 'users']);
Route::get('/super-admin/complaints',     [SuperAdminController::class, 'complaints']);
Route::post('/super-admin/update-status', [SuperAdminController::class, 'updateUserStatus']);

// ── SOS ──────────────────────────────────────────────────────
Route::post('/sos/notify',          [SosController::class, 'notify']);
Route::post('/sos/create',          [SosController::class, 'create']); // ← new (sos.js uses this)
Route::post('/create_sos',          [SosController::class, 'create']); // legacy
Route::get('/sos/alerts',           [SosController::class, 'alerts']);
Route::get('/get_sos_alert',        [SosController::class, 'alerts']); // legacy
Route::get('/sos/my-notifications', [SosController::class, 'myNotifications']);
Route::get('/get_my_sos_notifications', [SosController::class, 'myNotifications']); // legacy
Route::post('/sos/respond',         [SosController::class, 'respond']);
Route::post('/respond_to_sos',      [SosController::class, 'respond']); // legacy

// ── Officers ─────────────────────────────────────────────────
Route::get('/officers',         [OfficerController::class, 'index']);
Route::post('/officers',        [OfficerController::class, 'store']);
Route::post('/officers/toggle', [OfficerController::class, 'toggle']);

// ── Private Investigators ────────────────────────────────────
Route::get('/pi',               [PrivateInvestigatorController::class, 'index']);
Route::post('/add_pi',          [PrivateInvestigatorController::class, 'store']);
Route::post('/pi/assign',       [PrivateInvestigatorController::class, 'assign']);
Route::post('/pi_assign',       [PrivateInvestigatorController::class, 'assign']); // legacy
Route::get('/pi/notifications', [PrivateInvestigatorController::class, 'notifications']);
Route::get('/pi_notification',  [PrivateInvestigatorController::class, 'notifications']); // legacy
Route::post('/pi/payment',      [PrivateInvestigatorController::class, 'payment']);
Route::get('/pi_management',    [PrivateInvestigatorController::class, 'index']); // legacy