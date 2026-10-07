@extends('layouts.app')

@section('title', 'Analisis Searching')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">ANALISIS SEARCHING</span><h1>Analisis Pemahaman</h1><p>Umpan balik personal berdasarkan jawabanmu pada Latihan soal LOTS dan Latihan soal HOTS, disusun mengikuti Tujuan Pembelajaran materi Searching.</p></div>
        <a class="button button-quiet" href="{{ route('materi.searching') }}"><span aria-hidden="true">&#8592;</span> Kembali ke Searching</a>
    </section>

    @if (! $hasResults)
        <section class="content-section">
            <div class="section-heading"><div><span class="section-kicker">BELUM ADA HASIL</span><h2>Analisis belum tersedia</h2></div></div>
            <div class="empty-state">
                <strong>Kamu belum mengerjakan latihan apa pun.</strong>
                <span>Kerjakan Latihan soal LOTS dan Latihan soal HOTS terlebih dahulu, lalu kembali ke halaman ini untuk melihat analisis pemahamanmu.</span>
            </div>
            <div class="control-buttons">
                <a class="button button-primary" href="{{ route('materi.searching.quiz') }}">Mulai Latihan soal LOTS</a>
                <a class="button button-quiet" href="{{ route('materi.searching.hots-quiz') }}">Mulai Latihan soal HOTS</a>
            </div>
        </section>
    @else
        <section class="result-banner">
            <span>TINGKAT PEMAHAMAN</span>
            <strong>{{ $score }}<small>%</small></strong>
            <p>{{ $level['label'] }} · {{ $correctCount }} dari {{ $questionTotal }} jawaban benar</p>
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div><span class="section-kicker">EVALUASI</span><h2>Tingkat pemahamanmu</h2></div>
                <span class="section-count">{{ $level['label'] }}</span>
            </div>
            <div class="explanation-panel review-explanation">
                <strong>TINGKAT PEMAHAMAN · {{ $level['label'] }}</strong>
                <p>{{ $level['description'] }}</p>
            </div>
            <div class="list-row">
                <div><strong>Latihan yang sudah dikerjakan</strong><small>{{ implode(' · ', $completedQuizzes) }}</small></div>
                <span class="status-badge">Selesai</span>
            </div>
            @if ($missingQuizzes !== [])
                <div class="list-row">
                    <div><strong>Latihan yang belum dikerjakan</strong><small>{{ implode(' · ', $missingQuizzes) }}</small></div>
                    <span class="draft-badge">Belum dikerjakan</span>
                </div>
            @endif
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div><span class="section-kicker">TUJUAN PEMBELAJARAN</span><h2>Capaian per tujuan pembelajaran</h2></div>
                <span class="section-count">{{ count($indicators) }} TP</span>
            </div>
            @foreach ($indicators as $indicator)
                <div class="list-row">
                    <div>
                        <strong>TP {{ $loop->iteration }} · {{ $indicator['objective'] }}</strong>
                        <small>{{ $indicator['title'] }} · {{ $indicator['correct'] }} dari {{ $indicator['total'] }} soal benar ({{ $indicator['accuracy'] }}%)</small>
                    </div>
                    <span class="{{ $indicator['mastered'] ? 'status-badge' : 'draft-badge' }}">{{ $indicator['mastered'] ? 'Dikuasai' : 'Perlu ditingkatkan' }}</span>
                </div>
            @endforeach
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div><span class="section-kicker">SUDAH DIKUASAI</span><h2>Konsep yang sudah kamu kuasai</h2></div>
                <span class="section-count">{{ count($masteredIndicators) }} TP</span>
            </div>
            @if ($masteredIndicators === [])
                <div class="empty-state">
                    <strong>Belum ada tujuan pembelajaran yang dikuasai.</strong>
                    <span>Fokus dulu pada tujuan pembelajaran yang masih perlu ditingkatkan di bawah ini.</span>
                </div>
            @else
                @foreach ($masteredIndicators as $indicator)
                    <div class="list-row">
                        <div><strong>{{ $indicator['objective'] }}</strong><small>{{ $indicator['correct'] }} dari {{ $indicator['total'] }} soal benar</small></div>
                        <span class="status-badge">Dikuasai</span>
                    </div>
                @endforeach
            @endif
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div><span class="section-kicker">MASIH PERLU DIPERBAIKI</span><h2>Konsep yang masih perlu ditingkatkan</h2></div>
                <span class="section-count">{{ count($improvementIndicators) }} TP</span>
            </div>
            @if ($improvementIndicators === [])
                <div class="empty-state">
                    <strong>Semua tujuan pembelajaran sudah tercapai.</strong>
                    <span>Pertahankan dengan mengulang latihan secara berkala.</span>
                </div>
            @else
                @foreach ($improvementIndicators as $indicator)
                    <div class="list-row">
                        <div><strong>{{ $indicator['objective'] }}</strong><small>{{ $indicator['title'] }} · baru {{ $indicator['correct'] }} dari {{ $indicator['total'] }} soal benar</small></div>
                        <span class="draft-badge">Perlu ditingkatkan</span>
                    </div>
                @endforeach
            @endif
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div><span class="section-kicker">SOAL YANG MASIH SALAH</span><h2>Bagian yang perlu kamu pelajari lagi</h2></div>
                <span class="section-count">{{ count($wrongItems) }} SOAL</span>
            </div>
            @if ($wrongItems === [])
                <div class="empty-state">
                    <strong>Tidak ada soal yang salah.</strong>
                    <span>Semua jawabanmu tepat pada latihan yang sudah dikerjakan.</span>
                </div>
            @else
                @foreach ($wrongItems as $item)
                    <div class="list-row">
                        <div>
                            <strong>{{ $item['question'] }}</strong>
                            <small>{{ $item['quiz'] }} · Jawaban benar: {{ $item['answer'] }} · Pilihanmu: {{ $item['selected'] ?? 'Tidak dijawab' }}</small>
                        </div>
                        <span class="draft-badge">Belum tepat</span>
                    </div>
                @endforeach
            @endif
        </section>

        <section class="content-section">
            <div class="section-heading">
                <div><span class="section-kicker">PENGAYAAN</span><h2>Rekomendasi belajar untukmu</h2></div>
                <span class="section-count">{{ count($recommendations) }} SARAN</span>
            </div>
            <div class="explanation-panel review-explanation">
                <strong>LANGKAH SELANJUTNYA</strong>
                <ol>
                    @foreach ($recommendations as $recommendation)
                        <li>{{ $recommendation }}</li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section class="content-section">
            <div class="control-buttons">
                <a class="button button-primary" href="{{ route('materi.searching.quiz') }}">Kerjakan lagi Latihan soal LOTS</a>
                <a class="button button-quiet" href="{{ route('materi.searching.hots-quiz') }}">Kerjakan lagi Latihan soal HOTS</a>
                <a class="button button-quiet" href="{{ route('materi.searching') }}"><span aria-hidden="true">&#8592;</span> Kembali ke Searching</a>
            </div>
        </section>
    @endif
@endsection
