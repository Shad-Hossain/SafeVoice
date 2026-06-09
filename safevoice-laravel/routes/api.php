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
use App\Http\Controllers\LawyerAuthController;
use App\Http\Controllers\LawyerDashboardController;
use App\Http\Controllers\LegalRequestController;
use App\Http\Controllers\CasePaymentController;
use App\Http\Controllers\CommissionController;
use App\Helpers\BangladeshAreas; 
use Illuminate\Http\Request;

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
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

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
// SESSION-BASED ROUTES
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
// PUBLIC SOS
// ─────────────────────────────────────────────────────────────────────────────
Route::get('/sos/alerts',         [SosController::class, 'alerts']);
Route::get('/get_sos_alert',      [SosController::class, 'alerts']); // legacy
Route::get('/sos/victim-evidence',[SosController::class, 'victimEvidence']);
Route::get('/leaderboard',        [SosController::class, 'leaderboard']);
Route::get('/leaderboard/search', [SosController::class, 'leaderboardSearch']);
Route::get('/sos/all-requests',   [SosController::class, 'allSosRequests']);
Route::get('/sos/active-recent',  [SosController::class, 'activeRecentAlerts']);

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN ROUTES
// ─────────────────────────────────────────────────────────────────────────────

Route::prefix('admin')->group(function () {
    Route::post('/login',  [AdminController::class, 'login']);
    Route::post('/logout', [AdminController::class, 'logout']);

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

    // ── Admin Lawyer Management ───────────────────────────────
    Route::get('/legal/lawyers',                              [LawyerAuthController::class, 'allLawyers']);
    Route::get('/legal/lawyers/pending',                      [LawyerAuthController::class, 'pendingLawyers']);
    Route::get('/legal/lawyers/{lawyerId}',                   [LawyerAuthController::class, 'lawyerDetail']);
    Route::post('/legal/lawyers/{lawyerId}/verify',           [LawyerAuthController::class, 'verifyLawyer']);
    Route::post('/legal/lawyers/{lawyerId}/toggle-suspend',   [LawyerAuthController::class, 'toggleSuspend']);
    Route::post('/legal/lawyers/{lawyerId}/ban',              [LawyerAuthController::class, 'banLawyer']);
    Route::post('/legal/lawyers/{lawyerId}/warn',             [LawyerAuthController::class, 'warnLawyer']);
    Route::get('/legal/lawyers/{lawyerId}/action-history',    [LawyerAuthController::class, 'lawyerActionHistory']);

    // ── Commission Payment Review ─────────────────────────────
    Route::get('/commission/pending',                         [CommissionController::class, 'pendingPayments']);
    Route::get('/commission/all',                             [CommissionController::class, 'allPayments']);
    Route::post('/commission/{refCode}/approve',              [CommissionController::class, 'approvePayment']);
    Route::post('/commission/{refCode}/reject',               [CommissionController::class, 'rejectPayment']);

    // ── Admin Notifications (lawyer disputes + commission) ────
    Route::get('/lawyer-notifications',                       [CommissionController::class, 'adminNotifications']);
    Route::post('/lawyer-notifications/mark-read',            [CommissionController::class, 'markNotificationsRead']);
});

// legacy admin routes
Route::get('/manage_user',                [AdminController::class, 'users']); // legacy
Route::post('/admin_login',               [AdminController::class, 'login']);
Route::post('/complaints/update-status',  [ComplaintController::class, 'updateStatus']); // legacy
Route::post('/update_status',             [ComplaintController::class, 'updateStatus']); // legacy
Route::get('/complaints',                 [ComplaintController::class, 'index']); // legacy
Route::get('/complaints/{id}',            [ComplaintController::class, 'show']); // legacy

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
// OFFICERS & PI
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

// ─────────────────────────────────────────────────────────────────────────────
// BANGLADESH AREAS (public — no auth required, Flutter dropdown use করবে)
// ─────────────────────────────────────────────────────────────────────────────

Route::get('/areas', function () {
    return response()->json([
        'success' => true,
        'areas'   => BangladeshAreas::forApi(),
    ]);
});

Route::get('/areas/divisions', function () {
    return response()->json([
        'success'   => true,
        'divisions' => BangladeshAreas::divisions(),
    ]);
});

Route::get('/areas/districts/{division}', function (string $division) {
    $districts = BangladeshAreas::districtsOf($division);
    if (empty($districts)) {
        return response()->json([
            'success' => false,
            'message' => 'Division not found. Valid: ' . implode(', ', BangladeshAreas::divisions()),
        ], 404);
    }
    return response()->json([
        'success'   => true,
        'division'  => $division,
        'districts' => $districts,
    ]);
});

Route::get('/lawyers/by-area', function (Request $request) {
    $district = $request->query('district');
    $division = $request->query('division');

    $query = \App\Models\Lawyer::where('status', 'Active')
        ->where('is_available', true);

    if ($district) {
        $query->where(function ($q) use ($district) {
            $q->whereRaw("JSON_CONTAINS(serving_areas, JSON_QUOTE(?))", [$district])
              ->orWhere('preferred_district', $district);
        });
    } elseif ($division) {
        $districts = BangladeshAreas::districtsOf($division);
        $query->where(function ($q) use ($districts, $division) {
            foreach ($districts as $d) {
                $q->orWhereRaw("JSON_CONTAINS(serving_areas, JSON_QUOTE(?))", [$d]);
            }
            $q->orWhere('division', $division);
        });
    }

    $lawyers = $query->select([
        'id', 'lawyer_code', 'full_name', 'specializations',
        'experience_years', 'min_fee', 'rating', 'rating_count',
        'city', 'division', 'serving_areas', 'is_available',
        'profile_photo',
    ])->orderByDesc('rating')->get();

    return response()->json([
        'success'  => true,
        'count'    => $lawyers->count(),
        'district' => $district,
        'division' => $division,
        'lawyers'  => $lawyers,
    ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// LAWYER SYSTEM (Pathao-style legal marketplace)
// ─────────────────────────────────────────────────────────────────────────────

Route::prefix('lawyer')->group(function () {
    Route::post('/register',      [LawyerAuthController::class, 'register']);
    Route::post('/login',         [LawyerAuthController::class, 'login']);
    Route::post('/logout',        [LawyerAuthController::class, 'logout']);
    Route::get('/check-session',  [LawyerAuthController::class, 'checkSession']);
    Route::post('/ocr-extract',   [\App\Http\Controllers\LawyerOcrController::class, 'extract']);
    Route::get('/profile',        [LawyerAuthController::class, 'profile']);
    Route::post('/profile/update',[LawyerAuthController::class, 'updateProfile']);

    Route::get('/dashboard',                  [LawyerDashboardController::class, 'dashboard']);
    Route::get('/requests',                   [LawyerDashboardController::class, 'openRequests']);
    Route::get('/requests/instant',           [LawyerDashboardController::class, 'instantRequests']);
    Route::get('/requests/scheduled',         [LawyerDashboardController::class, 'scheduledRequests']);
    Route::post('/bid',                       [LawyerDashboardController::class, 'placeBid']);
    Route::put('/bid/{bidId}',                [LawyerDashboardController::class, 'updateBid']);
    Route::get('/notifications',              [LawyerDashboardController::class, 'notifications']);
    Route::get('/notifications/unread-count', [LawyerDashboardController::class, 'unreadCount']);
    Route::post('/toggle-availability',       [LawyerDashboardController::class, 'toggleAvailability']);

    // ── Earnings ──────────────────────────────────────────────
    Route::get('/earnings',                   [CasePaymentController::class, 'earnings']);

    // ── Commission ────────────────────────────────────────────
    Route::get('/commission/summary',         [CommissionController::class, 'summary']);
    Route::post('/commission/pay',            [CommissionController::class, 'submitPayment']);
});

// ─────────────────────────────────────────────────────────────────────────────
// CASE PAYMENT FLOW
// Lawyer → resolve → User pays → User confirms → Lawyer Yes/No
// ─────────────────────────────────────────────────────────────────────────────

Route::prefix('case-payment')->group(function () {
    // STEP 1: Lawyer case resolved করে, user কে payment notification পাঠায়
    Route::post('/{requestId}/resolve',          [CasePaymentController::class, 'resolveCase']);

    // STEP 2: User "আমি pay করেছি" বলে, lawyer কে confirmation request যায়
    Route::post('/{requestId}/confirm-paid',     [CasePaymentController::class, 'confirmPaid']);

    // STEP 3: Lawyer "হ্যাঁ পেয়েছি" বা "না পাইনি" জানায়
    Route::post('/{requestId}/payment-response', [CasePaymentController::class, 'paymentResponse']);

    // Lawyer: Pending payment কে dispute করো (client pay না করলে)
    Route::post('/{requestId}/dispute-pending',  [CasePaymentController::class, 'disputePending']);

    // Lawyer: Disputed payment এ admin contact করো
    Route::post('/{requestId}/contact-admin',    [CasePaymentController::class, 'contactAdmin']);

    // Payment এর current status দেখা
    Route::get('/{requestId}/status',            [CasePaymentController::class, 'paymentStatus']);
});

// ─────────────────────────────────────────────────────────────────────────────
// USER — Legal Request
// ─────────────────────────────────────────────────────────────────────────────

Route::prefix('legal-request')->group(function () {
    Route::post('/submit',                 [LegalRequestController::class, 'submit']);
    Route::get('/track/{requestId}',       [LegalRequestController::class, 'track']);

    Route::get('/my-requests',             [LegalRequestController::class, 'myRequests']);
    Route::get('/instant',                 [LegalRequestController::class, 'myInstantRequests']);
    Route::get('/scheduled',               [LegalRequestController::class, 'myScheduledRequests']);
    Route::get('/{requestId}/bids',        [LegalRequestController::class, 'getBids']);
    Route::post('/{requestId}/accept-bid', [LegalRequestController::class, 'acceptBid']);
    Route::post('/{requestId}/reject-bid', [LegalRequestController::class, 'rejectBid']);
    Route::post('/{requestId}/cancel',     [LegalRequestController::class, 'cancel']);
});