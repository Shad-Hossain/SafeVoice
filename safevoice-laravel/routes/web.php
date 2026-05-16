<?php

use Illuminate\Support\Facades\Route;

// ── Public Pages ─────────────────────────────────────────────
Route::get('/',             fn() => view('home'))->name('home');
Route::get('/leaderboard',  fn() => view('pages.leaderboard'))->name('leaderboard');
Route::get('/legal',        fn() => view('pages.legal_help'))->name('legal');
Route::get('/track',        fn() => view('pages.complaint_track'))->name('track');
Route::get('/sos',          fn() => view('pages.sos'))->name('sos');

// ── Auth Pages ───────────────────────────────────────────────
Route::get('/login',        fn() => view('pages.login'))->name('login');
Route::get('/register',     fn() => view('pages.register'))->name('register');

// ── User Dashboard (auth guard via JS/session check) ─────────
Route::get('/dashboard',    fn() => view('pages.dashboard'))->name('dashboard');
Route::get('/complaint',    fn() => view('pages.complaint'))->name('complaint');

// ── Admin Pages ──────────────────────────────────────────────
Route::prefix('admin')->group(function () {
    Route::get('/login',       fn() => view('admin.login'))->name('admin.login');
    Route::get('/dashboard',   fn() => view('admin.dashboard'))->name('admin.dashboard');
    Route::get('/complaints',  fn() => view('admin.complaints'))->name('admin.complaints');
});

// ── Super Admin Pages ────────────────────────────────────────
Route::prefix('super-admin')->group(function () {
    Route::get('/login',     fn() => view('super_admin_login'))->name('super-admin.login');
    Route::get('/dashboard', fn() => view('super_admin_dashboard'))->name('super-admin.dashboard');
});
