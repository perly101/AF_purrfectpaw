<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClinicRegisterController;
use App\Http\Controllers\Step1Controller;
use App\Http\Controllers\Step2Controller;
use App\Http\Controllers\ClinicAppointmentController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\ClinicFieldController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ClinicHomeController;
use App\Http\Controllers\Clinic\SettingsController; 
use App\Http\Controllers\User\PetController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SystemLogController;
use App\Http\Controllers\EmailTestController;
use App\Http\Controllers\MailTestController;
use App\Http\Controllers\Doctor\DashboardController as DoctorDashboardController;
use App\Http\Controllers\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Doctor\PatientController as DoctorPatientController;
use App\Http\Controllers\Doctor\ProfileController as DoctorProfileController;
use App\Http\Controllers\Doctor\NotificationController as DoctorNotificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public route: Landing page
use App\Http\Controllers\LandingController;
use App\Http\Controllers\SiteCommentController;
Route::get('/', [LandingController::class, 'index'])->name('landing');
// Site comments (public)
Route::post('/site-comments', [SiteCommentController::class, 'store'])->name('site.comments.store');

// Google OAuth Routes
Route::get('/auth/google', [App\Http\Controllers\Auth\GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [App\Http\Controllers\Auth\GoogleController::class, 'handleGoogleCallback']);

// Mail Test Routes (admin only)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/test-email', [MailTestController::class, 'showForm'])->name('test-email');
    Route::post('/test-email', [MailTestController::class, 'sendTest'])->name('test-email.send');
});

// User dashboard (accessible to all authenticated users)
Route::get('/dashboard', function () {
    return view('/user/dashboard');
})->middleware(['auth'])->name('dashboard');

// ========== ADMIN ROUTES ==========
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Admin dashboard
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/dashboard/refresh-stats', [AdminController::class, 'refreshStats']);
    
    // User management
    Route::get('/admin/usermag', [AdminController::class, 'usermag'])->name('admin.usermag');
    Route::get('/admin/user-stats/{type}', [AdminController::class, 'getUserStats']);
    Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
    Route::get('/admin/users/{id}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
    Route::put('/admin/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    
    // Bulk actions and export
    Route::post('/admin/users/bulk', [AdminController::class, 'bulkAction'])->name('admin.users.bulk');
    Route::get('/admin/users/export', [AdminController::class, 'exportUsers'])->name('admin.users.export');
    
    // Subscription management
    Route::get('/admin/subscriptions/pending', [AdminController::class, 'pendingSubscriptions'])->name('admin.subscriptions.pending');
    Route::get('/admin/subscriptions/all', [AdminController::class, 'allSubscriptions'])->name('admin.subscriptions.all');
    Route::get('/admin/subscriptions/{id}', [AdminController::class, 'showSubscription'])->name('admin.subscriptions.show');
    Route::post('/admin/subscriptions/confirm/{id}', [App\Http\Controllers\FakePaymentController::class, 'adminConfirm'])->name('admin.subscriptions.confirm');
    Route::post('/admin/subscriptions/{id}/email', [AdminController::class, 'emailSubscriptionReceipt'])->name('admin.subscriptions.email');
    
    // Admin settings
    Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');
    Route::post('/admin/settings', [AdminController::class, 'updateSettings'])->name('admin.updateSettings');
    
    // Application Settings
    Route::get('/admin/application-settings', [SettingController::class, 'index'])->name('admin.application-settings');
    Route::post('/admin/application-settings/general', [SettingController::class, 'updateGeneral'])->name('admin.settings.general');
    Route::post('/admin/application-settings/email', [SettingController::class, 'updateEmail'])->name('admin.settings.email');
    Route::post('/admin/application-settings/appearance', [SettingController::class, 'updateAppearance'])->name('admin.settings.appearance');
    Route::post('/admin/application-settings/security', [SettingController::class, 'updateSecurity'])->name('admin.settings.security');
    
    // Email test endpoint
    Route::get('/admin/test-email', [EmailTestController::class, 'testEmail'])->name('admin.test.email');
    
    // System Logs
    Route::get('/admin/system-logs', [SystemLogController::class, 'index'])->name('admin.system-logs');
    Route::post('/admin/system-logs/clear', [SystemLogController::class, 'clear'])->name('admin.system-logs.clear');
    Route::get('/admin/system-logs/export', [SystemLogController::class, 'export'])->name('admin.system-logs.export');
    
    // Clinic list and detail management
    Route::get('/admin/clinics', [AdminController::class, 'clinicList'])->name('admin.clinics');
    Route::get('/admin/clinics/{id}', [AdminController::class, 'viewClinic'])->name('admin.clinics.view');
    Route::delete('/admin/clinics/{id}', [AdminController::class, 'deleteClinic'])->name('admin.clinics.delete');
    Route::put('/clinics/{id}/update-details', [AdminController::class, 'updateClinicDetails'])->name('admin.clinic.updateDetails');
    Route::put('/clinics/{userId}/update-account', [AdminController::class, 'updateClinicAccount'])->name('admin.clinic.updateAccount');
    Route::get('/admin/clinic/{id}/download', [AdminController::class, 'downloadClinicInfo'])->name('admin.clinic.download');
    
    // Clinic registration routes
    Route::get('/clinic/step1', [Step1Controller::class, 'create'])->name('step1.create');
    Route::post('/clinic/step1', [Step1Controller::class, 'store'])->name('step1.store');
    Route::get('/clinic/step2', [Step2Controller::class, 'create'])->name('step2.create');
    Route::post('/clinic/step2', [Step2Controller::class, 'store'])->name('step2.store');
    Route::get('/clinic/register', [ClinicRegisterController::class, 'showRegisterForm'])->name('clinic.showForm');
    Route::post('/clinic/register', [ClinicRegisterController::class, 'register'])->name('clinic.register');
});

// ========== CLINIC ROUTES ==========
Route::middleware(['auth', 'role:clinic'])->group(function () {
    // Clinic dashboard
    Route::get('/clinic/dashboard', [\App\Http\Controllers\Clinic\DashboardController::class, 'index'])->name('clinic.dashboard');
    Route::get('/clinic/dashboard/stats', [\App\Http\Controllers\Clinic\DashboardController::class, 'getStats'])->name('clinic.dashboard.stats');
    
    // Availability management
    Route::get('/clinic/availability', [\App\Http\Controllers\Clinic\AvailabilityController::class, 'index'])->name('clinic.availability.index');
    Route::post('/clinic/availability/daily', [\App\Http\Controllers\Clinic\AvailabilityController::class, 'updateDailySchedule'])->name('clinic.availability.daily');
    Route::post('/clinic/availability/breaks', [\App\Http\Controllers\Clinic\AvailabilityController::class, 'storeBreak'])->name('clinic.availability.breaks.store');
    Route::delete('/clinic/availability/breaks/{id}', [\App\Http\Controllers\Clinic\AvailabilityController::class, 'destroyBreak'])->name('clinic.availability.breaks.destroy');
    Route::post('/clinic/availability/special-dates', [\App\Http\Controllers\Clinic\AvailabilityController::class, 'storeSpecialDate'])->name('clinic.availability.special-dates.store');
    Route::delete('/clinic/availability/special-dates/{id}', [\App\Http\Controllers\Clinic\AvailabilityController::class, 'destroySpecialDate'])->name('clinic.availability.special-dates.destroy');
    
    // Gallery management
    Route::get('/clinic/gallery', [GalleryController::class, 'index'])->name('clinic.gallery.index');
    Route::post('/clinic/gallery', [GalleryController::class, 'store'])->name('clinic.gallery.store');
    Route::delete('/clinic/gallery/{id}', [GalleryController::class, 'destroy'])->name('clinic.gallery.delete');
    
    // Subscription management
    Route::get('/clinic/subscription/receipt', [\App\Http\Controllers\Clinic\SubscriptionController::class, 'showReceipt'])->name('clinic.subscription.receipt');
    Route::post('/clinic/subscription/receipt/email', [\App\Http\Controllers\Clinic\SubscriptionController::class, 'emailReceipt'])->name('clinic.subscription.email');
    
    // Field management
    Route::get('/clinic/fields', [ClinicFieldController::class, 'index'])->name('clinic.fields.index');
    Route::post('/clinic/fields', [ClinicFieldController::class, 'store'])->name('clinic.fields.store');
    Route::get('/clinic/fields/{id}/edit', [ClinicFieldController::class, 'edit'])->name('clinic.fields.edit');
    Route::put('/clinic/fields/{id}', [ClinicFieldController::class, 'update'])->name('clinic.fields.update');
    Route::delete('/clinic/fields/{id}', [ClinicFieldController::class, 'destroy'])->name('clinic.fields.destroy');
    
    // Homepage customization
    Route::prefix('clinic')->name('clinic.')->group(function () {
        Route::get('/home', [ClinicHomeController::class, 'index'])->name('home');
        Route::post('/home', [ClinicHomeController::class, 'updateContent'])->name('home.update');
        Route::post('/services', [ClinicHomeController::class, 'storeService'])->name('services.store');
        Route::put('/services/{service}', [ClinicHomeController::class, 'updateService'])->name('services.update');
        Route::delete('/services/{service}', [ClinicHomeController::class, 'destroyService'])->name('services.destroy');
        
        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.update-profile');
        Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.update-password');
        
        // Status update
        Route::post('/update-status', [\App\Http\Controllers\Clinic\StatusController::class, 'updateStatus'])->name('update.status');
        
        // Appointments management
        Route::get('/appointments', [\App\Http\Controllers\Clinic\AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/archived', [\App\Http\Controllers\Clinic\AppointmentController::class, 'archivedAppointments'])->name('appointments.archived');
        Route::get('/appointments/patient-history/{name}/{phone}', [\App\Http\Controllers\Clinic\AppointmentController::class, 'patientHistory'])->name('appointments.patient-history');
        Route::get('/appointments/{id}', [\App\Http\Controllers\Clinic\AppointmentController::class, 'show'])->name('appointments.show');
        Route::put('/appointments/{id}/status', [\App\Http\Controllers\Clinic\AppointmentController::class, 'updateStatus'])->name('appointments.update-status');
        Route::delete('/appointments/{id}', [\App\Http\Controllers\Clinic\AppointmentController::class, 'delete'])->name('appointments.delete');
        Route::post('/appointments/{id}/assign-doctor', [\App\Http\Controllers\Clinic\AppointmentController::class, 'assignDoctor'])->name('appointments.assign-doctor');
        Route::post('/appointments/{id}/add-notes', [\App\Http\Controllers\Clinic\AppointmentController::class, 'addNotes'])->name('appointments.add-notes');
        
        // Doctors management
        Route::get('/doctors', [\App\Http\Controllers\Clinic\DoctorController::class, 'index'])->name('doctors.index');
        Route::get('/doctors/create', [\App\Http\Controllers\Clinic\DoctorController::class, 'create'])->name('doctors.create');
        Route::post('/doctors', [\App\Http\Controllers\Clinic\DoctorController::class, 'store'])->name('doctors.store');
        Route::get('/doctors/{id}', [\App\Http\Controllers\Clinic\DoctorController::class, 'show'])->name('doctors.show');
        Route::get('/doctors/{id}/edit', [\App\Http\Controllers\Clinic\DoctorController::class, 'edit'])->name('doctors.edit');
        Route::put('/doctors/{id}', [\App\Http\Controllers\Clinic\DoctorController::class, 'update'])->name('doctors.update');
        Route::delete('/doctors/{id}', [\App\Http\Controllers\Clinic\DoctorController::class, 'destroy'])->name('doctors.destroy');
        Route::patch('/doctors/{id}/status', [\App\Http\Controllers\Clinic\DoctorController::class, 'updateStatus'])->name('doctors.update-status');
        
        // Patients management
        Route::get('/patients', [\App\Http\Controllers\Clinic\PatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/{patientId}', [\App\Http\Controllers\Clinic\PatientController::class, 'show'])->name('patients.show');
        
        // Notifications management
        Route::get('/notifications', [\App\Http\Controllers\Clinic\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/settings', [\App\Http\Controllers\Clinic\NotificationController::class, 'settings'])->name('notifications.settings');
        Route::post('/notifications/{id}/mark-read', [\App\Http\Controllers\Clinic\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/notifications/read-all', [\App\Http\Controllers\Clinic\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{id}', [\App\Http\Controllers\Clinic\NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::get('/check-new-notifications', [\App\Http\Controllers\Clinic\NotificationController::class, 'checkNewNotifications']);
        Route::get('/notifications-component', [\App\Http\Controllers\Clinic\NotificationController::class, 'getNotificationsComponent']);
        
        // Test notification route
        Route::get('/test-notification', [\App\Http\Controllers\Clinic\TestNotificationController::class, 'testNotification'])->name('notifications.test');
    });
});

// ========== GLOBAL NOTIFICATION ROUTES ==========
// These routes are accessible from any page in the app and handle global notifications
Route::middleware(['auth'])->group(function () {
    Route::get('/check-new-notifications', [\App\Http\Controllers\GlobalNotificationController::class, 'checkNewNotifications']);
    Route::get('/notification-count', [\App\Http\Controllers\GlobalNotificationController::class, 'getNotificationCount']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\GlobalNotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\GlobalNotificationController::class, 'markAllAsRead']);
});

// ========== TEST ROUTES ==========
// These routes are for testing functionality and should be disabled in production
Route::prefix('test')->group(function () {
    Route::get('/appointment-notification', [\App\Http\Controllers\Test\TestNotificationController::class, 'testAppointmentNotification']);
    Route::get('/notification-sound', [\App\Http\Controllers\Test\TestNotificationController::class, 'testNotificationSound'])->name('test.notification-sound');
    Route::get('/global-notifications', function() {
        return view('test.global-notification-test');
    })->name('test.global-notifications');
    
    // SMS Test Routes
    // Route::get('/sms', [\App\Http\Controllers\Test\SmsTestController::class, 'test'])->name('test.sms');
    // Route::get('/sms/appointment-confirmation', [\App\Http\Controllers\Test\SmsTestController::class, 'testAppointmentConfirmation'])->name('test.sms.appointment-confirmation');
    Route::get('/sms/page', function() {
        return view('test.sms-test');
    })->name('test.sms.page');
});

// ========== DOCTOR ROUTES ==========
Route::middleware(['auth', 'role:doctor'])->prefix('doctor')->name('doctor.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DoctorDashboardController::class, 'index'])->name('dashboard');
    
    // Appointments
    Route::get('/appointments', [DoctorAppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/{id}', [DoctorAppointmentController::class, 'show'])->name('appointments.show');
    Route::put('/appointments/{id}/status', [DoctorAppointmentController::class, 'updateStatus'])->name('appointments.update-status');
    Route::post('/appointments/{id}/accept-decline', [DoctorAppointmentController::class, 'acceptDecline'])->name('appointments.accept-decline');
    Route::post('/appointments/{id}/start-consultation', [DoctorAppointmentController::class, 'startConsultation'])->name('appointments.start-consultation');
    Route::post('/appointments/{id}/complete-consultation', [DoctorAppointmentController::class, 'completeConsultation'])->name('appointments.complete-consultation');
    Route::post('/appointments/{id}/send-sms', [DoctorAppointmentController::class, 'sendSmsNotification'])->name('appointments.send-sms');
    
    // Patients
    Route::get('/patients', [DoctorPatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/{phone}', [DoctorPatientController::class, 'show'])->name('patients.show');
    Route::get('/patients/{phone}/history', [DoctorPatientController::class, 'getHistory'])->name('patients.history');
    
    // Profile
    Route::get('/profile', [DoctorProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [DoctorProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [DoctorProfileController::class, 'updatePassword'])->name('profile.update-password');
    
    // Notifications
    Route::get('/notifications', [DoctorNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-as-read', [DoctorNotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/notifications/mark-all-as-read', [DoctorNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
    Route::delete('/notifications/{id}', [DoctorNotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/notifications/check', [DoctorNotificationController::class, 'checkNewNotifications'])->name('notifications.check');
    Route::get('/notifications/count', [DoctorNotificationController::class, 'getNotificationCount'])->name('notifications.count');
});

// ========== PUBLIC APPOINTMENT ROUTES ==========
Route::middleware(['auth'])->group(function () {
    Route::post('/appointments/{clinic}', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{id}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::get('clinic/appointments/preview/{clinic}', [AppointmentController::class, 'previewForm'])->name('appointments.preview');
});

require __DIR__.'/auth.php';

// Include SMS test routes (for development only)
if (app()->environment('local')) {
    require __DIR__.'/sms-test.php';
    require __DIR__.'/sms-test-simple.php';
    require __DIR__.'/debug-fields.php';
}
