<?php

use App\Http\Controllers\Api\AbsenceController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashbookController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\KioskController;
use App\Http\Controllers\Api\LatePaymentController;
use App\Http\Controllers\Api\MemberApp\MemberAuthController;
use App\Http\Controllers\Api\MemberApp\MemberCheckInController;
use App\Http\Controllers\Api\MemberApp\MemberPortalController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PublicRegistrationController;
use App\Http\Controllers\Api\PublicSiteController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SubscriptionPlanController;
use App\Http\Controllers\Api\TrainingProgramController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Demande d'adhésion depuis le site web (activable par le paramètre online_registration_enabled)
// Site vitrine : coordonnées, formules et ouverture des inscriptions (lecture seule)
Route::get('/public/club', [PublicSiteController::class, 'show'])->middleware('throttle:60,1');
Route::post('/public/registrations', [PublicRegistrationController::class, 'store'])->middleware('throttle:10,60');

// Application mobile adhérent
Route::prefix('member')->group(function () {
    Route::post('/login', [MemberAuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'member'])->group(function () {
        Route::post('/logout', [MemberAuthController::class, 'logout']);
        Route::put('/password', [MemberAuthController::class, 'updatePassword']);
        Route::get('/me', [MemberPortalController::class, 'me']);
        Route::get('/balance', [MemberPortalController::class, 'balance']);
        Route::get('/subscriptions', [MemberPortalController::class, 'subscriptions']);
        Route::get('/payments', [MemberPortalController::class, 'payments']);
        Route::get('/payments/{payment}/receipt', [MemberPortalController::class, 'receipt'])->whereNumber('payment');
        Route::get('/attendances', [MemberPortalController::class, 'attendances']);
        Route::get('/programs', [MemberPortalController::class, 'programs']);
        Route::get('/plans', [MemberPortalController::class, 'plans']);
        Route::post('/check-in', [MemberCheckInController::class, 'store'])->middleware('throttle:10,1');
    });
});

// Personnel (un token d'adhérent est refusé par le middleware staff)
Route::middleware(['auth:sanctum', 'staff'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Adhérents
    Route::get('/members/by-qr/{token}', [MemberController::class, 'byQr']);
    Route::get('/members/{member}/subscriptions', [MemberController::class, 'subscriptions']);
    Route::get('/members/{member}/subscriptions/suggested-start', [SubscriptionController::class, 'suggestedStart']);
    Route::get('/members/{member}/attendances', [AttendanceController::class, 'byMember']);
    Route::get('/members/{member}/payments', [PaymentController::class, 'byMember']);
    Route::get('/members/{member}/outstanding', [PaymentController::class, 'outstanding']);
    Route::get('/members/{member}/reminders', [ReminderController::class, 'byMember']);
    Route::get('/members/{member}/programs', [TrainingProgramController::class, 'byMember']);
    Route::post('/members/{member}/validate', [MemberController::class, 'validateMembership']);
    Route::post('/members/{member}/app-access', [KioskController::class, 'grantAccess'])->middleware('role:manager,agent');
    Route::delete('/members/{member}/app-access', [KioskController::class, 'revokeAccess'])->middleware('role:manager,agent');
    Route::get('/kiosk/token', [KioskController::class, 'token'])->middleware('role:manager,agent');
    Route::apiResource('members', MemberController::class)->except('destroy');

    // Formules (lecture pour tous)
    Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index']);
    Route::get('/subscription-plans/{subscription_plan}', [SubscriptionPlanController::class, 'show']);

    // Abonnements
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);
    Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show']);

    // Pointage
    Route::get('/attendances/check', [AttendanceController::class, 'check']);
    Route::get('/attendances', [AttendanceController::class, 'index']);
    Route::post('/attendances', [AttendanceController::class, 'store']);

    // Paiements
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::post('/payments/batch', [PaymentController::class, 'storeBatch']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt']);

    // Recouvrement
    Route::get('/late-payments', [LatePaymentController::class, 'index']);
    Route::get('/absences', [AbsenceController::class, 'index']);
    Route::get('/reminders/preview', [ReminderController::class, 'preview']);
    Route::post('/reminders', [ReminderController::class, 'store']);

    // Caisse (saisie par tous, annulation par le gérant)
    Route::get('/expense-categories', [ExpenseCategoryController::class, 'index']);
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::get('/expenses/{expense}', [ExpenseController::class, 'show']);
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update']);
    Route::get('/cashbook', [CashbookController::class, 'index']);

    // Paramètres en lecture (tolérance, nom du club...) pour tous
    Route::get('/settings', [SettingController::class, 'index']);

    // Programmes : lecture pour tous, écriture coach et gérant
    Route::get('/programs/{program}', [TrainingProgramController::class, 'show']);
    Route::middleware('role:manager,coach')->group(function () {
        Route::get('/programs', [TrainingProgramController::class, 'index']);
        Route::post('/members/{member}/programs', [TrainingProgramController::class, 'store']);
        Route::put('/programs/{program}', [TrainingProgramController::class, 'update']);
        Route::patch('/programs/{program}/approve', [TrainingProgramController::class, 'approve']);
        Route::patch('/programs/{program}/decline', [TrainingProgramController::class, 'decline']);
        Route::patch('/programs/{program}/archive', [TrainingProgramController::class, 'archive']);
    });

    // Réservé au gérant
    Route::middleware('role:manager')->group(function () {
        Route::delete('/members/{member}', [MemberController::class, 'destroy']);
        Route::patch('/members/{member}/status', [MemberController::class, 'updateStatus']);

        Route::post('/subscription-plans', [SubscriptionPlanController::class, 'store']);
        Route::put('/subscription-plans/{subscription_plan}', [SubscriptionPlanController::class, 'update']);
        Route::delete('/subscription-plans/{subscription_plan}', [SubscriptionPlanController::class, 'destroy']);

        Route::patch('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
        Route::delete('/attendances/{attendance}', [AttendanceController::class, 'destroy']);
        Route::patch('/payments/{payment}/cancel', [PaymentController::class, 'cancel']);
        Route::patch('/expenses/{expense}/cancel', [ExpenseController::class, 'cancel']);

        Route::post('/expense-categories', [ExpenseCategoryController::class, 'store']);
        Route::put('/expense-categories/{expense_category}', [ExpenseCategoryController::class, 'update']);
        Route::delete('/expense-categories/{expense_category}', [ExpenseCategoryController::class, 'destroy']);
        Route::get('/cashbook/export', [CashbookController::class, 'export']);

        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::put('/settings', [SettingController::class, 'update']);
        Route::apiResource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});
