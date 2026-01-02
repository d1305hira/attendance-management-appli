<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\WorktimeRequestController;

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


Route::middleware(['auth'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->name('attendance.index');

    Route::post('/attendance', [AttendanceController::class, 'store'])
        ->name('attendance.store');

    Route::post('/attendance/end', [AttendanceController::class, 'end'])
        ->name('attendance.end');

    Route::post('/attendance/break', [AttendanceController::class, 'break'])
        ->name('attendance.break');

    Route::post('/attendance/break/end', [AttendanceController::class, 'break_end'])
        ->name('attendance.break_end');

    Route::get('/attendance/list', [AttendanceController::class, 'attendance_list'])
        ->name('attendance_list');

    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'attendance_detail'])->name('attendance_detail');

    Route::post('/attendance/detail/{id}',[WorktimeRequestController::class,'store'])
        ->name('attendance.correction_request');
  });

  Route::get('/stamp_correction_request/list', function () {

    // 一般ユーザー（web）を先に判定
    if (Auth::guard('web')->check()) {
        return app(WorktimeRequestController::class)->stamp_correction_request_list();
    }

    // 管理者（admin）は後で判定
    if (Auth::guard('admin')->check()) {
        return app(AdminController::class)->admin_stamp_correction_request_list();
    }

    // どちらでもなければ 403
    abort(403);
    })->name('stamp_correction_request_list');


Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminController::class, 'showLoginForm'])
        ->name('admin.login.form');

    Route::post('/login', [AdminController::class, 'login'])
        ->name('admin.login');

    Route::post('/logout', function () {
        Auth::guard('admin')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        return redirect('/admin/login');
        })->name('admin.logout');

    Route::middleware(['auth:admin'])->group(function () {
        Route::get('/attendance/list', [AdminController::class, 'index'])
            ->name('admin.attendance_list');

        Route::get('/attendance/{id}',[AdminController::class,'admin_attendance_detail'])
            ->name('admin.attendance_detail');

        Route::post('/attendance/{id}',[AdminController::class,'admin_attendance_update'])
            ->name('admin.attendance_update');

        Route::get('/staff/list', [AdminController::class, 'staff_list'])
            ->name('admin.staff.list');

        Route::get('/attendance/staff/{id}', [AdminController::class, 'staff_attendance'])
            ->name('admin.staff.attendance');
        });
    });

Route::middleware(['auth:admin'])->group(function(){
    Route::get('/stamp-correction-request/{id}/approve', [AdminController::class, 'admin_stamp_correction_request_approve'])
        ->name('admin.stamp_correction_request.approve');

    Route::post('/stamp-correction-request/{id}/approve', [AdminController::class, 'admin_stamp_correction_request_update'])
        ->name('admin.stamp_correction_request.update');
});