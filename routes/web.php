<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceTokenController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LearningManagementController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\SearchingAnalysisController;
use App\Http\Controllers\SearchingQuizController;
use App\Http\Controllers\SimulationAttemptController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/manage', [LearningManagementController::class, 'index'])->name('manage');
        Route::post('/subjects', [LearningManagementController::class, 'storeSubject'])->name('subjects.store');
        Route::post('/materials', [LearningManagementController::class, 'storeMaterial'])->name('materials.store');
        Route::post('/simulations', [LearningManagementController::class, 'storeSimulation'])->name('simulations.store');
        Route::post('/questions', [LearningManagementController::class, 'storeQuestion'])->name('questions.store');
        Route::post('/attendance-tokens', [AttendanceTokenController::class, 'store'])->name('attendance-tokens.store');
        Route::post('/attendance-sessions', [AttendanceTokenController::class, 'storeSession'])->name('attendance-sessions.store');
    });

    Route::middleware('role:student')->group(function (): void {
        Route::post('/attendances', [AttendanceController::class, 'store'])->middleware('throttle:10,1')->name('attendances.store');
        Route::get('/materi', [MaterialController::class, 'index'])->name('materi.index');
        Route::get('/materi/searching', [MaterialController::class, 'searching'])->name('materi.searching');
        Route::get('/materi/searching/sequential-search', [MaterialController::class, 'sequentialSearch'])->name('materi.searching.sequential');
        Route::get('/materi/searching/binary-search', [MaterialController::class, 'binarySearch'])->name('materi.searching.binary');
        Route::get('/materi/searching/simulator', [MaterialController::class, 'searchingSimulator'])->name('materi.searching.simulator');
        Route::get('/materi/searching/quiz', [SearchingQuizController::class, 'show'])->name('materi.searching.quiz');
        Route::post('/materi/searching/quiz', [SearchingQuizController::class, 'submit'])->name('materi.searching.quiz.submit');
        Route::get('/materi/searching/hots-quiz', [SearchingQuizController::class, 'showHots'])->name('materi.searching.hots-quiz');
        Route::post('/materi/searching/hots-quiz', [SearchingQuizController::class, 'submitHots'])->name('materi.searching.hots-quiz.submit');
        Route::get('/materi/searching/analysis', [SearchingAnalysisController::class, 'index'])->name('materi.searching.analysis');
        Route::get('/materi/sorting', [MaterialController::class, 'sorting'])->name('materi.sorting');
        Route::get('/materi/struktur-data', [MaterialController::class, 'strukturData'])->name('materi.struktur-data');
        Route::get('/materials/{material}', [MaterialController::class, 'show'])->name('materials.show');
        Route::get('/simulations/{simulation}', [SimulationAttemptController::class, 'showSimulation'])->name('simulations.show');
        Route::post('/simulations/{simulation}/attempts', [SimulationAttemptController::class, 'begin'])->name('simulations.begin');
        Route::get('/attempts/{attempt}', [SimulationAttemptController::class, 'showAttempt'])->name('attempts.show');
        Route::post('/attempts/{attempt}', [SimulationAttemptController::class, 'submit'])->name('attempts.submit');
    });
});
