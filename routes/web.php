<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AttendanceManagementController;
use App\Http\Controllers\Admin\ClassManagementController;
use App\Http\Controllers\Admin\ContentManagementController;
use App\Http\Controllers\Admin\LearningResultsController;
use App\Http\Controllers\Admin\QuizManagementController;
use App\Http\Controllers\Admin\StudentManagementController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceTokenController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LearningManagementController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\SearchingAnalysisController;
use App\Http\Controllers\SearchingQuizController;
use App\Http\Controllers\SimulationAttemptController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\TopicPracticeController;
use App\Support\LearningCatalog;
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
    Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->middleware('throttle:10,1')->name('password.update');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/manage', [LearningManagementController::class, 'index'])->name('manage');
        Route::post('/subjects', [LearningManagementController::class, 'storeSubject'])->name('subjects.store');
        Route::post('/materials', [LearningManagementController::class, 'storeMaterial'])->name('materials.store');
        Route::post('/simulations', [LearningManagementController::class, 'storeSimulation'])->name('simulations.store');
        Route::post('/questions', [LearningManagementController::class, 'storeQuestion'])->name('questions.store');
        Route::post('/attendance-tokens', [AttendanceTokenController::class, 'store'])->name('attendance-tokens.store');
        Route::post('/attendance-sessions', [AttendanceTokenController::class, 'storeSession'])->name('attendance-sessions.store');
        Route::get('/attendance-sessions/{attendanceSession}', [AttendanceTokenController::class, 'show'])->whereNumber('attendanceSession')->name('attendance-sessions.show');
        Route::get('/attendance-report', [AttendanceTokenController::class, 'report'])->name('attendance-report');
        Route::patch('/student-classes', [AttendanceTokenController::class, 'assignClass'])->name('student-classes.update');
        Route::get('/students/import', [StudentImportController::class, 'index'])->name('students.import');
        Route::get('/students/import/template/{format}', [StudentImportController::class, 'template'])->whereIn('format', ['xlsx', 'csv'])->name('students.import.template');
        Route::get('/students/import/example', [StudentImportController::class, 'example'])->name('students.import.example');
        Route::get('/students/import/errors', [StudentImportController::class, 'downloadErrors'])->name('students.import.errors');
        Route::post('/students/import/preview', [StudentImportController::class, 'preview'])->middleware('throttle:20,1')->name('students.import.preview');
        Route::post('/students/import', [StudentImportController::class, 'store'])->name('students.import.store');
        Route::delete('/students/import', [StudentImportController::class, 'destroy'])->name('students.import.destroy');
        Route::post('/students/initial-passwords', [StudentImportController::class, 'resetPasswords'])->middleware('throttle:10,1')->name('students.initial-passwords.reset');

        // 2. Kelola Siswa
        Route::get('/students', [StudentManagementController::class, 'index'])->name('students.index');
        Route::get('/students/create', [StudentManagementController::class, 'create'])->name('students.create');
        Route::post('/students', [StudentManagementController::class, 'store'])->name('students.store');
        Route::get('/students/{student}/edit', [StudentManagementController::class, 'edit'])->whereNumber('student')->name('students.edit');
        Route::put('/students/{student}', [StudentManagementController::class, 'update'])->whereNumber('student')->name('students.update');
        Route::patch('/students/{student}/status', [StudentManagementController::class, 'toggleActive'])->whereNumber('student')->name('students.status');
        Route::post('/students/{student}/password', [StudentManagementController::class, 'resetPassword'])->whereNumber('student')->middleware('throttle:10,1')->name('students.password');

        // 3. Kelola Kelas
        Route::get('/classes', [ClassManagementController::class, 'index'])->name('classes.index');
        Route::get('/classes/{className}', [ClassManagementController::class, 'show'])->name('classes.show');
        Route::patch('/classes/move', [ClassManagementController::class, 'move'])->name('classes.move');

        // 4. Kelola Presensi
        Route::get('/attendance', [AttendanceManagementController::class, 'index'])->name('attendance.index');
        Route::get('/attendance-report/download', [AttendanceTokenController::class, 'downloadReport'])->name('attendance-report.download');

        // 5. Kelola Materi & Simulasi
        Route::get('/content', [ContentManagementController::class, 'index'])->name('content.index');
        Route::get('/content/materials/{material}/edit', [ContentManagementController::class, 'editMaterial'])->name('content.materials.edit');
        Route::post('/content/materials', [ContentManagementController::class, 'storeMaterial'])->name('content.materials.store');
        Route::put('/content/materials/{material}', [ContentManagementController::class, 'updateMaterial'])->name('content.materials.update');
        Route::patch('/content/materials/{material}/publication', [ContentManagementController::class, 'togglePublication'])->name('content.materials.publication');
        Route::get('/content/materials/{material}/attachment', [ContentManagementController::class, 'attachment'])->name('content.materials.attachment');
        Route::get('/content/simulations/{simulation}/edit', [ContentManagementController::class, 'editSimulation'])->name('content.simulations.edit');
        Route::put('/content/simulations/{simulation}', [ContentManagementController::class, 'updateSimulation'])->name('content.simulations.update');

        // 6. Kelola Soal & Kuis
        Route::get('/quizzes', [QuizManagementController::class, 'index'])->name('quizzes.index');
        Route::post('/question-bank', [QuizManagementController::class, 'storeQuestion'])->name('question-bank.store');
        Route::get('/question-bank/{bankQuestion}/edit', [QuizManagementController::class, 'editQuestion'])->name('question-bank.edit');
        Route::put('/question-bank/{bankQuestion}', [QuizManagementController::class, 'updateQuestion'])->name('question-bank.update');
        Route::delete('/question-bank/{bankQuestion}', [QuizManagementController::class, 'destroyQuestion'])->name('question-bank.destroy');
        Route::get('/question-bank/{bankQuestion}/attachment', [QuizManagementController::class, 'questionAttachment'])->name('question-bank.attachment');
        Route::post('/quizzes', [QuizManagementController::class, 'storeQuiz'])->name('quizzes.store');
        Route::put('/quizzes/{simulation}', [QuizManagementController::class, 'updateQuiz'])->name('quizzes.update');

        // 7. Hasil & Analisis Belajar
        Route::get('/results', [LearningResultsController::class, 'index'])->name('results.index');

        // 8. Pengumuman
        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::get('/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
        Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

        // 9. Pengaturan Sistem
        Route::get('/settings', [SystemSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/identity', [SystemSettingsController::class, 'updateIdentity'])->name('settings.identity');
        Route::put('/settings/profile', [SystemSettingsController::class, 'updateProfile'])->name('settings.profile');
        Route::get('/settings/backup', [SystemSettingsController::class, 'backup'])->middleware('throttle:5,1')->name('settings.backup');
    });

    Route::middleware('role:student')->group(function (): void {
        Route::post('/attendances', [AttendanceController::class, 'store'])->middleware('throttle:10,1')->name('attendances.store');
        // Menu siswa: Beranda (dashboard), Presensi, Materi & Simulasi, Latihan & Kuis, Progres, Pengumuman, Profil.
        Route::get('/presensi', [StudentPortalController::class, 'attendance'])->name('student.attendance');
        Route::get('/materi', [StudentPortalController::class, 'materials'])->name('materi.index');
        Route::get('/latihan', [StudentPortalController::class, 'practice'])->name('student.practice');
        Route::get('/progres', [StudentPortalController::class, 'progress'])->name('student.progress');
        Route::get('/pengumuman', [StudentPortalController::class, 'announcements'])->name('student.announcements');
        Route::get('/profil', [StudentPortalController::class, 'profile'])->name('student.profile');

        Route::get('/materi/computational-thinking', [MaterialController::class, 'computationalThinking'])->name('materi.computational-thinking');
        Route::get('/materi/pseudocode', [MaterialController::class, 'pseudocode'])->name('materi.pseudocode');
        Route::get('/materi/{topic}/simulasi', [MaterialController::class, 'simulation'])->whereIn('topic', LearningCatalog::GENERIC_TOPICS)->name('materi.simulation');
        Route::get('/materi/{topic}/latihan', [TopicPracticeController::class, 'show'])->whereIn('topic', LearningCatalog::GENERIC_TOPICS)->name('materi.practice');
        Route::post('/materi/{topic}/latihan', [TopicPracticeController::class, 'submit'])->whereIn('topic', LearningCatalog::GENERIC_TOPICS)->name('materi.practice.submit');
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
        Route::get('/materials/{material}/attachment', [MaterialController::class, 'attachment'])->name('materials.attachment');
        Route::get('/simulations/{simulation}', [SimulationAttemptController::class, 'showSimulation'])->name('simulations.show');
        Route::post('/simulations/{simulation}/attempts', [SimulationAttemptController::class, 'begin'])->name('simulations.begin');
        Route::get('/attempts/{attempt}', [SimulationAttemptController::class, 'showAttempt'])->name('attempts.show');
        Route::post('/attempts/{attempt}', [SimulationAttemptController::class, 'submit'])->name('attempts.submit');
        Route::get('/questions/{question}/attachment', [SimulationAttemptController::class, 'questionAttachment'])->name('questions.attachment');
    });
});
