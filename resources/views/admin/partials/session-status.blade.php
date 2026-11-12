@switch($session->statusKey())
    @case(\App\Models\AttendanceToken::STATUS_ACTIVE)
        <span class="active-badge">{{ $session->sessionStatus() }}</span>
        @break
    @case(\App\Models\AttendanceToken::STATUS_SCHEDULED)
        <span class="status-badge">{{ $session->sessionStatus() }}</span>
        @break
    @default
        <span class="draft-badge">{{ $session->sessionStatus() }}</span>
@endswitch
