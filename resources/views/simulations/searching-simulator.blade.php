@extends('layouts.app')

@section('title', 'Simulasi Searching')

@section('content')
    <section class="page-heading">
        <div>
            <span class="section-kicker">SIMULASI SEARCHING</span>
            <h1>Simulasi Algoritma Pencarian</h1>
            <p>Pelajari bagaimana algoritma pencarian bekerja melalui simulasi interaktif.</p>
        </div>
        <a class="button button-quiet" href="{{ route('materi.searching') }}"><span aria-hidden="true">&#8592;</span> Kembali ke Searching</a>
    </section>

    <section class="content-section">
        <article class="simulator-panel">
            <!-- Algorithm Selection -->
            <div class="simulator-section">
                <div class="section-heading">
                    <div>
                        <span class="section-kicker">PILIH ALGORITMA</span>
                        <h2>Algoritma Pencarian</h2>
                    </div>
                </div>
                <div class="algorithm-selector">
                    <button class="algorithm-button algorithm-button-active" data-algorithm="sequential" aria-pressed="true">
                        <strong>Sequential Search</strong>
                        <small>Memeriksa satu per satu dari awal</small>
                    </button>
                    <button class="algorithm-button" data-algorithm="binary" aria-pressed="false">
                        <strong>Binary Search</strong>
                        <small>Membagi dua area pencarian berulang kali</small>
                    </button>
                </div>
            </div>

            <!-- Data Input -->
            <div class="simulator-section">
                <div class="section-heading">
                    <div>
                        <span class="section-kicker">DATA INPUT</span>
                        <h2>Masukkan atau Buat Data</h2>
                    </div>
                </div>
                <div class="data-input-area">
                    <div class="form-stack">
                        <label class="field-label" for="data-input">Masukkan angka dipisahkan dengan koma</label>
                        <input
                            id="data-input"
                            type="text"
                            placeholder="Contoh: 12, 7, 25, 18, 30, 10"
                            value="12, 7, 25, 18, 30, 10"
                        >
                    </div>
                    <div class="button-group">
                        <button class="button button-quiet" id="use-example-btn">Gunakan Contoh Data</button>
                        <button class="button button-quiet" id="generate-random-btn">Buat Data Acak</button>
                    </div>
                </div>
            </div>

            <!-- Target Number -->
            <div class="simulator-section">
                <div class="section-heading">
                    <div>
                        <span class="section-kicker">TARGET PENCARIAN</span>
                        <h2>Angka yang Dicari</h2>
                    </div>
                </div>
                <div class="form-stack">
                    <label class="field-label" for="target-input">Masukkan angka yang ingin dicari</label>
                    <input
                        id="target-input"
                        type="text"
                        placeholder="Contoh: 18"
                        value="18"
                    >
                </div>
            </div>

            <!-- Data Visualization -->
            <div class="simulator-section">
                <div class="section-heading">
                    <div>
                        <span class="section-kicker">VISUALISASI DATA</span>
                        <h2>Array Elemen</h2>
                    </div>
                </div>
                <div id="sort-warning" class="warning-message" style="display: none;">
                    <strong>⚠ Data harus terurut untuk Binary Search</strong>
                    <p>Binary Search hanya bekerja pada data yang sudah diurutkan. Silakan urutkan data terlebih dahulu.</p>
                    <button class="button button-primary" id="sort-data-btn">Urutkan Data</button>
                </div>
                <div class="visualization-container">
                    <div id="array-visualization" class="array-boxes"></div>
                </div>
            </div>

            <!-- Binary Search Specific Info -->
            <div id="binary-search-info" class="simulator-section" style="display: none;">
                <div class="info-panel">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div class="info-stat">
                            <span class="info-label">Low</span>
                            <strong id="binary-low">-</strong>
                        </div>
                        <div class="info-stat">
                            <span class="info-label">Mid</span>
                            <strong id="binary-mid">-</strong>
                        </div>
                        <div class="info-stat">
                            <span class="info-label">High</span>
                            <strong id="binary-high">-</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Control Buttons -->
            <div class="simulator-section">
                <div class="control-buttons">
                    <button class="button button-primary" id="start-btn">Mulai Simulasi</button>
                    <button class="button button-quiet" id="next-step-btn" disabled>Langkah Berikutnya</button>
                    <button class="button button-quiet" id="run-auto-btn" disabled>Jalankan Otomatis</button>
                    <button class="button button-quiet" id="pause-btn" disabled style="display: none;">Jeda</button>
                    <button class="button button-quiet" id="reset-btn" disabled>Reset</button>
                </div>
            </div>

            <!-- Learning Explanation Panel -->
            <div class="simulator-section">
                <div class="section-heading">
                    <div>
                        <span class="section-kicker">PENJELASAN</span>
                        <h2>Apa yang Terjadi?</h2>
                    </div>
                </div>
                <div id="explanation-panel" class="explanation-panel">
                    <p id="explanation-text">Pilih algoritma, masukkan data, dan klik "Mulai Simulasi" untuk memulai.</p>
                </div>
            </div>

            <!-- Step Information Panel -->
            <div id="step-info-section" class="simulator-section" style="display: none;">
                <div class="section-heading">
                    <div>
                        <span class="section-kicker">INFORMASI STEP</span>
                        <h2>Detail Langkah Saat Ini</h2>
                    </div>
                </div>
                <div id="step-info-panel" class="step-info-panel"></div>
            </div>

            <!-- Result Panel -->
            <div id="result-section" class="simulator-section" style="display: none;">
                <div id="result-panel" class="result-panel"></div>
            </div>

            <!-- Comparison Section -->
            <div id="comparison-section" class="simulator-section" style="display: none;">
                <div class="section-heading">
                    <div>
                        <span class="section-kicker">PERBANDINGAN</span>
                        <h2>Bandingkan Algoritma</h2>
                    </div>
                </div>
                <div id="comparison-content" class="comparison-content"></div>
            </div>

            <!-- Validation Messages -->
            <div id="validation-messages" class="error-message" style="display: none;"></div>
        </article>
    </section>

    <script src="{{ asset('js/searching-simulator.js') }}"></script>
@endsection
