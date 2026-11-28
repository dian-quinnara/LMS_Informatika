@extends('layouts.app')

@section('title', 'Simulasi Sorting')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">SIMULASI SORTING</span><h1>Simulasi Algoritma Pengurutan</h1><p>{{ $simulation['description'] }}</p></div>
        <a class="button button-quiet" href="{{ route('materi.sorting') }}"><span aria-hidden="true">&#8592;</span> Kembali ke Sorting</a>
    </section>

    <section class="content-section">
        <article class="simulator-panel" id="sorting-simulator">
            <div class="simulator-section">
                <div class="section-heading"><div><span class="section-kicker">PILIH ALGORITMA</span><h2>Algoritma Pengurutan</h2></div></div>
                <div class="algorithm-selector">
                    <button class="algorithm-button algorithm-button-active" type="button" data-algorithm="bubble" aria-pressed="true"><strong>Bubble Sort</strong><small>Tukar pasangan bersebelahan</small></button>
                    <button class="algorithm-button" type="button" data-algorithm="selection" aria-pressed="false"><strong>Selection Sort</strong><small>Pilih nilai terkecil tiap putaran</small></button>
                    <button class="algorithm-button" type="button" data-algorithm="insertion" aria-pressed="false"><strong>Insertion Sort</strong><small>Sisipkan ke posisi yang tepat</small></button>
                </div>
            </div>

            <div class="simulator-section">
                <div class="section-heading"><div><span class="section-kicker">DATA INPUT</span><h2>Masukkan atau Buat Data</h2></div></div>
                <div class="data-input-area">
                    <div class="form-stack">
                        <label class="field-label" for="sorting-data">Masukkan 3–10 angka dipisahkan dengan koma</label>
                        <input id="sorting-data" type="text" value="72, 85, 64, 90, 78" placeholder="Contoh: 72, 85, 64, 90, 78">
                    </div>
                    <div class="button-group">
                        <button class="button button-quiet" type="button" id="sorting-random">Buat Data Acak</button>
                        <button class="button button-primary" type="button" id="sorting-start">Mulai simulasi</button>
                    </div>
                </div>
            </div>

            <div class="simulator-section">
                <div class="section-heading"><div><span class="section-kicker">VISUALISASI</span><h2>Langkah pengurutan</h2></div><span class="section-count" id="sorting-counter">LANGKAH 0 / 0</span></div>
                <div class="visualization-container"><div class="array-boxes" id="sorting-boxes"></div></div>
                <div class="control-buttons">
                    <button class="button button-quiet" type="button" id="sorting-prev">&#8592; Sebelumnya</button>
                    <button class="button button-primary" type="button" id="sorting-next">Berikutnya &#8594;</button>
                    <button class="button button-quiet" type="button" id="sorting-play">Putar otomatis</button>
                </div>
                <div class="explanation-panel" id="sorting-explanation" role="status" aria-live="polite"><p>Tekan "Mulai simulasi" untuk melihat langkah pertama.</p></div>
                <div class="step-info-panel">
                    <div class="step-detail"><span class="step-label">PERBANDINGAN</span><strong id="sorting-comparisons">0</strong></div>
                    <div class="step-detail"><span class="step-label">PENUKARAN / PERGESERAN</span><strong id="sorting-swaps">0</strong></div>
                </div>
            </div>
        </article>
    </section>
    <script src="{{ asset('js/sorting-simulator.js') }}"></script>
@endsection
