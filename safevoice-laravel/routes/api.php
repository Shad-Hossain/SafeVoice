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
use App\Http\Controllers\EvidenceRequestController;
use App\Http\Controllers\UserNotificationController;

// ─────────────────────────────────────────────────────────────────────────────
// PUBLIC ROUTES — login/register ছাড়া কিছু নেই এখানে
// ─────────────────────────────────────────────────────────────────────────────

Route::post('/register',        [AuthController::class, 'register']);
Route::post('/login',           [AuthController::class, 'login']);
Route::post('/forget_password', [AuthController::class, 'forgotPassword']);
Route::get('/check-session',    [AuthController::class, 'checkSession']);
Route::get('/check_session',    [AuthController::class, 'checkSession']); // legacy

// ✅ Public stats (login ছাড়া দেখা যায়)
Route::get('/stats', [AdminController::class, 'publicStats']);

// ✅ Complaint track করা public (শুধু status দেখা যায়, কোনো PII নেই)
Route::get('/track_complaint', [ComplaintController::class, 'track']);

// ─────────────────────────────────────────────────────────────────────────────
// USER PROTECTED ROUTES — Sanctum token অথবা session লাগবে
// ✅ auth:sanctum middleware user_id client থেকে নেওয়ার সুযোগ বন্ধ করে দেয়
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Complaints ───────────────────────────────────────────────

    // ── Notifications ─────────────────────────────────────────────

    // ── Evidence ─────────────────────────────────────────────────
    Route::post('/upload_complaint_evidence', [EvidenceController::class, 'uploadComplaint']);
    Route::get('/get_complaints_evidence',    [EvidenceController::class, 'getComplaintEvidence']);
    Route::post('/upload_sos_evidence',       [EvidenceController::class, 'uploadSos']);

    // ── SOS ──────────────────────────────────────────────────────
    Route::post('/user/update-location',          [SosController::class, 'updateLocation']);
    Route::get('/sos/nearby-count',               [SosController::class, 'nearbyCount']);
    Route::post('/sos/notify',                    [SosController::class, 'notify']);
    Route::post('/sos/create',                    [SosController::class, 'create']);
    Route::post('/create_sos',                    [SosController::class, 'create']); // legacy
    Route::get('/sos/my-notifications',           [SosController::class, 'myNotifications']);
    Route::get('/get_my_sos_notifications',       [SosController::class, 'myNotifications']); // legacy
    Route::post('/sos/respond',                   [SosController::class, 'respond']);
    Route::post('/sos/cancel',                    [SosController::class, 'cancelAlert']);
    Route::post('/respond_to_sos',                [SosController::class, 'respond']); // legacy
    Route::post('/sos/upload-evidence',           [SosController::class, 'uploadEvidence']);
    Route::get('/sos/my-responds',                [SosController::class, 'myResponds']);
    Route::post('/sos/submit-responder-evidence', [SosController::class, 'submitResponderEvidence']);


    // ── FCM ───────────────────────────────────────────────────────
    Route::post('/fcm/register-token',   [\App\Http\Controllers\FcmController::class, 'registerToken']);
    Route::post('/fcm/unregister-token', [\App\Http\Controllers\FcmController::class, 'unregisterToken']);

    // ── AI ────────────────────────────────────────────────────────
    Route::post('/ai/enhance-description', [\App\Http\Controllers\AiController::class, 'enhanceDescription']);
    Route::post('/ai/analyze-complaint',   [\App\Http\Controllers\AiController::class, 'analyzeComplaint']);
});

// ─────────────────────────────────────────────────────────────────────────────
// SESSION-BASED ROUTES — controller নিজে session/token check করে
// auth:sanctum বাদ দেওয়া হয়েছে কারণ frontend session cookie পাঠায়, Bearer নয়
// ─────────────────────────────────────────────────────────────────────────────
Route::get('/pi/user-notifications', [PrivateInvestigatorController::class, 'notifications']);
Route::post('/pi/payment',           [PrivateInvestigatorController::class, 'payment']);
Route::post('/pi/reject-payment',    [PrivateInvestigatorController::class, 'rejectPayment']);
Route::get('/pi/anonymous-contact',  [ComplaintController::class, 'anonymousPIContact']);
Route::post('/pi/acknowledge-contact',[ComplaintController::class, 'acknowledgePIContact']);
Route::get('/profile',               [AuthController::class, 'getProfile']);
Route::post('/profile/update',       [AuthController::class, 'updateProfile']);
Route::post('/complaints/submit',  [ComplaintController::class, 'submit']);
Route::post('/submit_complaint',   [ComplaintController::class, 'submit']); // legacy
Route::get('/my-complaints',       [ComplaintController::class, 'myComplaints']);
Route::get('/get_user_complaints', [ComplaintController::class, 'myComplaints']); // legacy
Route::get('/notifications',              [UserNotificationController::class, 'index']);
Route::get('/notifications/unread-count', [UserNotificationController::class, 'unreadCount']);
Route::post('/notifications/mark-read',   [UserNotificationController::class, 'markRead']);
Route::delete('/notifications/{id}',      [UserNotificationController::class, 'destroy']);

// ─────────────────────────────────────────────────────────────────────────────
// PUBLIC SOS (login ছাড়া leaderboard দেখা যায়)
// ─────────────────────────────────────────────────────────────────────────────
Route::get('/sos/alerts',         [SosController::class, 'alerts']);
Route::get('/get_sos_alert',      [SosController::class, 'alerts']); // legacy
Route::get('/sos/victim-evidence',[SosController::class, 'victimEvidence']);
Route::get('/leaderboard',        [SosController::class, 'leaderboard']);
Route::get('/leaderboard/search', [SosController::class, 'leaderboardSearch']);
Route::get('/sos/all-requests',   [SosController::class, 'allSosRequests']);
Route::get('/sos/active-recent',  [SosController::class, 'activeRecentAlerts']);

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN ROUTES — আলাদা admin auth session দরকার
// ─────────────────────────────────────────────────────────────────────────────

Route::prefix('admin')->group(function () {
    Route::post('/login',  [AdminController::class, 'login']);
    Route::post('/logout', [AdminController::class, 'logout']);

    // ✅ Admin complaint list — user_id, anonymous_user_id expose করে না (ComplaintController::index দেখো)
    Route::get('/complaints',                     [ComplaintController::class, 'index']);
    Route::get('/complaints/{id}',                [ComplaintController::class, 'show']);
    Route::post('/complaints/update-status',      [ComplaintController::class, 'updateStatus']);
    Route::post('/update_status',                 [ComplaintController::class, 'updateStatus']); // legacy

    Route::get('/users',                          [AdminController::class, 'users']);
    Route::get('/sos-evidence-pending',           [SosController::class, 'adminPendingEvidence']);
    Route::get('/user/{id}',                      [AdminController::class, 'getUserById']);
    Route::post('/users/update-status',           [AdminController::class, 'updateUserStatus']);

    Route::get('/pending-accounts',               [AdminController::class, 'pendingAccounts']);
    Route::post('/approve-account',               [AdminController::class, 'approveAccount']);
    Route::post('/reject-account',                [AdminController::class, 'rejectAccount']);

    Route::post('/sos-evidence-verify',           [SosController::class, 'adminVerifyEvidence']);

    Route::get('/payments',                       [PrivateInvestigatorController::class, 'pendingPayments']);
});

// legacy admin routes (backward compat)
Route::get('/manage_user',                [AdminController::class, 'users']); // legacy
Route::post('/admin_login',               [AdminController::class, 'login']);
Route::post('/complaints/update-status',  [ComplaintController::class, 'updateStatus']); // legacy (no prefix)
Route::post('/update_status',             [ComplaintController::class, 'updateStatus']); // legacy
Route::get('/complaints',                 [ComplaintController::class, 'index']); // legacy
Route::get('/complaints/{id}',            [ComplaintController::class, 'show']); // legacy
Route::get('/complaints',      [ComplaintController::class, 'index']);
Route::get('/complaints/{id}', [ComplaintController::class, 'show']);

// ─────────────────────────────────────────────────────────────────────────────
// SUPER ADMIN ROUTES
// ─────────────────────────────────────────────────────────────────────────────

Route::prefix('super-admin')->group(function () {
    Route::post('/login',  [SuperAdminController::class, 'login']);
    Route::post('/logout', [SuperAdminController::class, 'logout']);
    Route::get('/stats',         [SuperAdminController::class, 'stats']);
    Route::get('/users',         [SuperAdminController::class, 'users']);
    Route::get('/complaints',    [SuperAdminController::class, 'complaints']);
    Route::post('/update-status',[SuperAdminController::class, 'updateUserStatus']);
    Route::get('/pi-cases',      [SuperAdminController::class, 'piCases']);
    Route::post('/add-pi',       [PrivateInvestigatorController::class, 'store']);
    Route::get('/notifications',              [SuperAdminController::class, 'notifications']);
    Route::get('/notifications/unread-count', [SuperAdminController::class, 'notificationsUnreadCount']);
    Route::post('/notifications/mark-read',   [SuperAdminController::class, 'notificationsMarkRead']);
    Route::get('/refunds',                    [SuperAdminController::class, 'refunds']);
    Route::get('/refunds/pending-count',      [SuperAdminController::class, 'refundsPendingCount']);
    Route::post('/refunds/mark-processed',    [SuperAdminController::class, 'markRefundProcessed']);
    Route::post('/pi/update',    [PrivateInvestigatorController::class, 'update']);
    Route::post('/pi/toggle',    [PrivateInvestigatorController::class, 'toggle']);
    Route::post('/pi/delete',    [PrivateInvestigatorController::class, 'destroy']);
    Route::post('/pi/password',  [PrivateInvestigatorController::class, 'changePassword']);
});

Route::post('/super_admin_auth', [SuperAdminController::class, 'login']); // legacy

// ─────────────────────────────────────────────────────────────────────────────
// OFFICERS & PI (Admin managed)
// ─────────────────────────────────────────────────────────────────────────────

Route::get('/officers',         [OfficerController::class, 'index']);
Route::post('/officers',        [OfficerController::class, 'store']);
Route::post('/officers/toggle', [OfficerController::class, 'toggle']);

Route::get('/pi',              [PrivateInvestigatorController::class, 'index']);
Route::post('/add_pi',         [PrivateInvestigatorController::class, 'store']);
Route::post('/pi/assign',      [PrivateInvestigatorController::class, 'assign']);
Route::post('/pi_assign',      [PrivateInvestigatorController::class, 'assign']); // legacy
Route::post('/pi/notify',      [PrivateInvestigatorController::class, 'sendNotification']);
Route::post('/pi_notification',[PrivateInvestigatorController::class, 'sendNotification']); // legacy
Route::get('/pi_management',   [PrivateInvestigatorController::class, 'index']); // legacy

// ─────────────────────────────────────────────────────────────────────────────
// EVIDENCE REQUESTS
// ─────────────────────────────────────────────────────────────────────────────

Route::post('/evidence-request/create',         [EvidenceRequestController::class, 'create']);
Route::get('/evidence-request/pending',         [EvidenceRequestController::class, 'getPending']);
Route::post('/evidence-request/skip',           [EvidenceRequestController::class, 'skip']);
Route::post('/evidence-request/mark-submitted', [EvidenceRequestController::class, 'markSubmitted']);
Route::get('/evidence-request/admin-list',      [EvidenceRequestController::class, 'adminList']);
Route::post('/evidence-request/check-expired',  [EvidenceRequestController::class, 'checkExpired']);
Route::get('/evidence-request/expired-list',    [EvidenceRequestController::class, 'expiredList']);
Route::post('/evidence-request/reject',         [EvidenceRequestController::class, 'reject']);
Route::get('/evidence-request/expired',         [EvidenceRequestController::class, 'getExpired']);