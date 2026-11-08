<?php

namespace Tests\Feature;

use App\Models\AttendanceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_class_session_and_student_can_attend_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $startsAt = now()->addMinutes(5);
        $expiresAt = now()->addMinutes(45);

        $this->actingAs($admin)
            ->get(route('admin.manage'))
            ->assertOk()
            ->assertSee(route('admin.attendance-sessions.store'), false);

        $this->post(route('admin.attendance-sessions.store'), [
            'class_name' => 'XE1',
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'expires_at' => $expiresAt->format('Y-m-d\TH:i'),
        ])->assertRedirect()->assertSessionHas('attendance_session');

        $plainToken = session('attendance_session')['token'];
        $this->assertIsString($plainToken);
        $token = AttendanceToken::query()->where('class_name', 'XE1')->sole();
        $this->assertNull($token->subject_id);

        $this->get(route('admin.manage'))
            ->assertOk()
            ->assertSee('Kelas XE1')
            ->assertSee($plainToken);

        $this->actingAs($student)
            ->get(route('dashboard', ['section' => 'attendance']))
            ->assertOk()
            ->assertSee('Kelas XE1');

        $this->travelTo($startsAt->copy()->addMinute());

        $this->post(route('attendances.store'), ['token' => $plainToken])
            ->assertRedirect()
            ->assertSessionHas('status', 'Presensi Kelas XE1 berhasil dicatat.');

        $this->get(route('dashboard', ['section' => 'attendance']))
            ->assertOk()
            ->assertSee('Kelas XE1')
            ->assertSee('Sudah hadir');

        $this->post(route('attendances.store'), ['token' => $plainToken])
            ->assertRedirect()
            ->assertSessionHas('status', 'Presensi untuk sesi ini sudah pernah dicatat.');

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'attendance_token_id' => $token->id,
            'subject_id' => null,
        ]);
        $this->travelBack();
    }

    public function test_student_cannot_create_class_session(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)
            ->post(route('admin.attendance-sessions.store'), [
                'class_name' => 'XE1',
                'starts_at' => now()->addMinutes(5)->format('Y-m-d\TH:i'),
                'expires_at' => now()->addHour()->format('Y-m-d\TH:i'),
            ])->assertForbidden();

        $this->assertDatabaseCount('attendance_tokens', 0);
    }

    public function test_class_token_is_rejected_before_or_after_its_time_window(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $tokens = [
            ['code' => 'CLASS12345', 'start' => now()->subHours(2), 'end' => now()->subHour()],
            ['code' => 'FUTURE1234', 'start' => now()->addHour(), 'end' => now()->addHours(2)],
        ];

        foreach ($tokens as $item) {
            AttendanceToken::query()->create([
                'class_name' => 'XE2',
                'token_hash' => hash_hmac('sha256', $item['code'], (string) config('app.key')),
                'starts_at' => $item['start'],
                'expires_at' => $item['end'],
            ]);
        }

        $this->actingAs($student);
        foreach ($tokens as $item) {
            $this->post(route('attendances.store'), ['token' => $item['code']])
                ->assertSessionHasErrors('token');
        }
        $this->assertDatabaseCount('attendances', 0);
    }
}
