<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GradeXAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function student(?string $class, array $attributes = []): User
    {
        return User::factory()->create(['role' => 'student', 'class_name' => $class, ...$attributes]);
    }

    /**
     * Buat sesi lewat endpoint admin dan kembalikan [sesi, token polos].
     *
     * @return array{0: AttendanceToken, 1: string}
     */
    private function createSession(string $class, Carbon $startsAt, Carbon $expiresAt, ?string $title = null): array
    {
        $this->actingAs($this->admin())->post(route('admin.attendance-sessions.store'), [
            'class_name' => $class,
            'title' => $title,
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'expires_at' => $expiresAt->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('dashboard'))->assertSessionHas('attendance_session');

        $flash = session('attendance_session');
        auth()->logout();
        $this->flushSession();

        return [AttendanceToken::query()->findOrFail($flash['id']), $flash['token']];
    }

    public function test_admin_creates_grade_x_session_with_generated_token(): void
    {
        $this->travelTo(Carbon::parse('2026-11-12 07:00'));
        [$session, $token] = $this->createSession('XE3', now()->addMinutes(30), now()->addHours(2), 'Pertemuan 1');

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{10}$/', $token);
        $this->assertSame('XE3', $session->class_name);
        $this->assertSame('Pertemuan 1', $session->title);
        $this->assertSame($token, $session->token);
        $this->assertSame(hash_hmac('sha256', $token, (string) config('app.key')), $session->token_hash);
        $this->assertSame(AttendanceToken::STATUS_SCHEDULED, $session->statusKey());

        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Buat sesi presensi')
            ->assertSee('XE3 · Pertemuan 1')
            ->assertSee($token)
            ->assertSee('Terjadwal');

        $this->get(route('admin.attendance-sessions.show', $session))
            ->assertOk()
            ->assertSee($token)
            ->assertSee('Salin token');
    }

    public function test_tokens_are_unique_across_sessions(): void
    {
        $tokens = [];
        foreach (config('attendance.grade_x_classes') as $index => $class) {
            [, $tokens[]] = $this->createSession($class, now()->addMinutes($index + 1), now()->addHour());
        }

        $this->assertCount(9, array_unique($tokens));
        $this->assertSame(9, AttendanceToken::query()->distinct()->count('token'));
    }

    public function test_session_creation_is_validated(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->post(route('admin.attendance-sessions.store'), [
            'class_name' => 'XI F1',
            'starts_at' => now()->format('Y-m-d\TH:i'),
            'expires_at' => now()->addHour()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('class_name');

        $this->post(route('admin.attendance-sessions.store'), [
            'class_name' => 'XE1',
            'starts_at' => now()->addHour()->format('Y-m-d\TH:i'),
            'expires_at' => now()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors(['expires_at' => 'Waktu berakhir harus setelah waktu mulai.']);

        $this->post(route('admin.attendance-sessions.store'), [])
            ->assertSessionHasErrors(['class_name', 'starts_at', 'expires_at']);

        $this->assertDatabaseCount('attendance_tokens', 0);
    }

    public function test_duplicate_session_submission_is_rejected(): void
    {
        $payload = [
            'class_name' => 'XE1',
            'starts_at' => now()->addMinutes(5)->format('Y-m-d\TH:i'),
            'expires_at' => now()->addHour()->format('Y-m-d\TH:i'),
        ];
        $this->actingAs($this->admin());

        $this->post(route('admin.attendance-sessions.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('admin.attendance-sessions.store'), $payload)->assertSessionHasErrors('class_name');

        $this->assertDatabaseCount('attendance_tokens', 1);
    }

    public function test_status_follows_server_time(): void
    {
        $this->travelTo(Carbon::parse('2026-11-12 07:00'));
        [$session] = $this->createSession('XE1', Carbon::parse('2026-11-12 08:00'), Carbon::parse('2026-11-12 09:00'));

        $this->assertSame(AttendanceToken::STATUS_SCHEDULED, $session->statusKey());
        $this->travelTo(Carbon::parse('2026-11-12 08:00'));
        $this->assertSame(AttendanceToken::STATUS_ACTIVE, $session->statusKey());
        $this->travelTo(Carbon::parse('2026-11-12 09:00'));
        $this->assertSame(AttendanceToken::STATUS_ACTIVE, $session->statusKey());
        $this->travelTo(Carbon::parse('2026-11-12 09:01'));
        $this->assertSame(AttendanceToken::STATUS_COMPLETED, $session->statusKey());
        $this->assertSame('Selesai', $session->sessionStatus());
    }

    public function test_student_submits_attendance_once_for_own_class(): void
    {
        $this->travelTo(Carbon::parse('2026-11-12 07:00'));
        [$session, $token] = $this->createSession('XE1', now(), now()->addHour());
        $student = $this->student('XE1');
        $this->travelTo(Carbon::parse('2026-11-12 07:15:30'));

        $this->actingAs($student)
            ->post(route('attendances.store'), ['token' => strtolower($token)])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $this->post(route('attendances.store'), ['token' => $token])
            ->assertSessionHasErrors(['token' => 'Presensi untuk sesi ini sudah pernah dicatat.']);

        $this->assertDatabaseCount('attendances', 1);
        $attendance = Attendance::query()->firstOrFail();
        $this->assertSame($session->id, $attendance->attendance_token_id);
        $this->assertSame($student->id, $attendance->user_id);
        $this->assertSame('2026-11-12 07:15:30', $attendance->created_at->format('Y-m-d H:i:s'));
    }

    public function test_student_cannot_use_another_class_token_or_spoof_class(): void
    {
        [, $token] = $this->createSession('XE1', now()->subMinute(), now()->addHour());
        $otherClass = $this->student('XE2');
        $noClass = $this->student(null);

        $this->actingAs($otherClass)
            ->post(route('attendances.store'), ['token' => $token, 'class_name' => 'XE1'])
            ->assertSessionHasErrors(['token' => 'Token ini bukan untuk kelasmu.']);

        $this->actingAs($noClass)
            ->post(route('attendances.store'), ['token' => $token])
            ->assertSessionHasErrors('token');

        $this->assertDatabaseCount('attendances', 0);
        $this->assertSame('XE2', $otherClass->fresh()->class_name);
    }

    public function test_student_cannot_submit_before_start_or_after_end(): void
    {
        $this->travelTo(Carbon::parse('2026-11-12 07:00'));
        [, $token] = $this->createSession('XE4', Carbon::parse('2026-11-12 08:00'), Carbon::parse('2026-11-12 09:00'));
        $student = $this->student('XE4');

        $this->actingAs($student)->post(route('attendances.store'), ['token' => $token])->assertSessionHasErrors('token');
        $this->travelTo(Carbon::parse('2026-11-12 09:05'));
        $this->post(route('attendances.store'), ['token' => $token])->assertSessionHasErrors('token');
        $this->post(route('attendances.store'), ['token' => 'ZZZZZZZZZZ'])->assertSessionHasErrors('token');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_student_portal_only_lists_own_class_sessions_without_tokens(): void
    {
        [, $tokenXe1] = $this->createSession('XE1', now()->subMinute(), now()->addHour(), 'Sesi Kelas Satu');
        [, $tokenXe2] = $this->createSession('XE2', now()->subMinute(), now()->addHour(), 'Sesi Kelas Dua');

        $this->actingAs($this->student('XE1'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sesi Kelas Satu')
            ->assertDontSee('Sesi Kelas Dua')
            ->assertDontSee($tokenXe1)
            ->assertDontSee($tokenXe2);
    }

    public function test_only_admin_can_manage_sessions_reports_and_rosters(): void
    {
        [$session] = $this->createSession('XE1', now(), now()->addHour());
        $student = $this->student('XE1');

        $this->actingAs($student);
        $this->post(route('admin.attendance-sessions.store'), ['class_name' => 'XE1'])->assertForbidden();
        $this->get(route('admin.attendance-sessions.show', $session))->assertForbidden();
        $this->get(route('admin.attendance-report', ['class_name' => 'XE1']))->assertForbidden();
        $this->patch(route('admin.student-classes.update'), ['user_id' => $student->id, 'class_name' => 'XE9'])->assertForbidden();
        $this->assertSame('XE1', $student->fresh()->class_name);

        auth()->logout();
        $this->get(route('admin.attendance-report'))->assertRedirect(route('login'));
    }

    public function test_admin_assigns_student_class(): void
    {
        $student = $this->student(null);
        $otherAdmin = $this->admin();
        $this->actingAs($this->admin());

        $this->patch(route('admin.student-classes.update'), ['user_id' => $student->id, 'class_name' => 'XE7'])
            ->assertSessionHasNoErrors();
        $this->assertSame('XE7', $student->fresh()->class_name);

        $this->patch(route('admin.student-classes.update'), ['user_id' => $student->id, 'class_name' => 'XII F1'])
            ->assertSessionHasErrors('class_name');
        $this->patch(route('admin.student-classes.update'), ['user_id' => $otherAdmin->id, 'class_name' => 'XE1'])
            ->assertSessionHasErrors('user_id');
        $this->assertNull($otherAdmin->fresh()->class_name);
    }

    public function test_dashboard_filters_sessions_by_class_status_and_date(): void
    {
        $this->travelTo(Carbon::parse('2026-11-12 10:00'));
        $this->createSession('XE1', Carbon::parse('2026-11-10 07:00'), Carbon::parse('2026-11-10 08:00'), 'Lampau XE1');
        $this->createSession('XE1', Carbon::parse('2026-11-12 09:30'), Carbon::parse('2026-11-12 11:00'), 'Aktif XE1');
        $this->createSession('XE2', Carbon::parse('2026-11-13 07:00'), Carbon::parse('2026-11-13 08:00'), 'Besok XE2');
        $this->actingAs($this->admin());

        $this->get(route('dashboard', ['class' => 'XE1']))
            ->assertOk()->assertSee('Lampau XE1')->assertSee('Aktif XE1')->assertDontSee('Besok XE2');
        $this->get(route('dashboard', ['status' => 'active']))
            ->assertOk()->assertSee('Aktif XE1')->assertDontSee('Lampau XE1')->assertDontSee('Besok XE2');
        $this->get(route('dashboard', ['status' => 'scheduled']))
            ->assertOk()->assertSee('Besok XE2')->assertDontSee('Aktif XE1');
        $this->get(route('dashboard', ['status' => 'completed']))
            ->assertOk()->assertSee('Lampau XE1')->assertDontSee('Aktif XE1');
        $this->get(route('dashboard', ['date_from' => '2026-11-12', 'date_to' => '2026-11-12']))
            ->assertOk()->assertSee('Aktif XE1')->assertDontSee('Lampau XE1')->assertDontSee('Besok XE2');
        $this->get(route('dashboard', ['class' => "XE1' OR 1=1", 'date_from' => 'bukan-tanggal']))
            ->assertOk()->assertSee('Besok XE2');
    }

    public function test_report_lists_present_and_absent_students_by_class_and_date(): void
    {
        $this->travelTo(Carbon::parse('2026-11-12 07:00'));
        [$session, $token] = $this->createSession('XE5', now(), now()->addHour(), 'Rapor Pagi');
        $this->createSession('XE5', Carbon::parse('2026-11-14 07:00'), Carbon::parse('2026-11-14 08:00'), 'Sesi Lusa');
        $this->createSession('XE6', now(), now()->addHour(), 'Kelas Lain');
        $present = $this->student('XE5', ['name' => 'Ani Hadir']);
        $this->student('XE5', ['name' => 'Budi Absen']);
        $this->student('XE6', ['name' => 'Citra Kelas Enam']);

        $this->travelTo(Carbon::parse('2026-11-12 07:20:05'));
        $this->actingAs($present)->post(route('attendances.store'), ['token' => $token])->assertSessionHasNoErrors();

        $this->actingAs($this->admin())
            ->get(route('admin.attendance-report', ['class_name' => 'XE5', 'date_from' => '2026-11-12', 'date_to' => '2026-11-12']))
            ->assertOk()
            ->assertSee('Rapor Pagi')
            ->assertDontSee('Sesi Lusa')
            ->assertDontSee('Kelas Lain')
            ->assertSee('Ani Hadir')
            ->assertSee('Hadir · 07:20:05')
            ->assertSee('Budi Absen')
            ->assertSee('Belum presensi')
            ->assertDontSee('Citra Kelas Enam')
            ->assertSee('1/2 hadir');

        $this->get(route('admin.attendance-report', ['class_name' => 'XE5', 'date_from' => '2026-11-12', 'date_to' => '2026-11-14']))
            ->assertOk()->assertSee('Rapor Pagi')->assertSee('Sesi Lusa')->assertSee('Hadir 1/2 sesi');

        $this->get(route('admin.attendance-report', ['class_name' => 'XE5', 'date_from' => '2026-11-14', 'date_to' => '2026-11-12']))
            ->assertSessionHasErrors('date_to');

        $this->get(route('admin.attendance-sessions.show', $session))
            ->assertOk()->assertSee('Ani Hadir')->assertSee('Budi Absen')->assertSee('1/2 siswa hadir');
    }

    public function test_subject_based_session_is_not_shown_as_grade_x_detail(): void
    {
        $legacy = AttendanceToken::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.attendance-sessions.show', $legacy))
            ->assertNotFound();
    }
}
