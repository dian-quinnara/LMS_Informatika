class SearchingSimulator {
    constructor() {
        this.algorithm = 'sequential';
        this.data = [];
        this.target = null;
        this.currentStep = 0;
        this.steps = [];
        this.isRunning = false;
        this.isPaused = false;
        this.autoPlayTimer = null;
        this.autoPlaySpeed = 1000; // milliseconds
        this.comparisonCount = 0;
        this.results = {
            sequential: null,
            binary: null,
        };

        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Algorithm selection
        document.querySelectorAll('.algorithm-button').forEach((btn) => {
            btn.addEventListener('click', (e) => this.selectAlgorithm(e));
        });

        // Data input buttons
        document.getElementById('use-example-btn').addEventListener('click', () => this.useExampleData());
        document.getElementById('generate-random-btn').addEventListener('click', () => this.generateRandomData());

        // Control buttons
        document.getElementById('start-btn').addEventListener('click', () => this.startSimulation());
        document.getElementById('next-step-btn').addEventListener('click', () => this.nextStep());
        document.getElementById('run-auto-btn').addEventListener('click', () => this.runAutomatic());
        document.getElementById('pause-btn').addEventListener('click', () => this.pauseAutomatic());
        document.getElementById('reset-btn').addEventListener('click', () => this.reset());

        // Sort data button
        document.getElementById('sort-data-btn').addEventListener('click', () => this.sortData());
    }

    selectAlgorithm(event) {
        const selectedAlgorithm = event.currentTarget.dataset.algorithm;
        
        document.querySelectorAll('.algorithm-button').forEach((btn) => {
            btn.classList.remove('algorithm-button-active');
            btn.setAttribute('aria-pressed', 'false');
        });
        
        event.currentTarget.classList.add('algorithm-button-active');
        event.currentTarget.setAttribute('aria-pressed', 'true');
        
        this.algorithm = selectedAlgorithm;
        this.reset();
        this.updateBinarySearchInfo();
        this.checkDataSorted();
    }

    useExampleData() {
        const exampleData = [12, 7, 25, 18, 30, 10];
        document.getElementById('data-input').value = exampleData.join(', ');
        this.reset();
    }

    generateRandomData() {
        const count = Math.floor(Math.random() * 8) + 5; // 5-12 elements
        const data = [];
        for (let i = 0; i < count; i++) {
            data.push(Math.floor(Math.random() * 100) + 1);
        }
        document.getElementById('data-input').value = data.join(', ');
        this.reset();
    }

    validateInputs() {
        const dataInput = document.getElementById('data-input').value.trim();
        const targetInput = document.getElementById('target-input').value.trim();
        const validationMessages = document.getElementById('validation-messages');
        
        validationMessages.style.display = 'none';
        validationMessages.innerHTML = '';

        // Validate data
        if (!dataInput) {
            this.showValidationError('Masukkan data terlebih dahulu.');
            return false;
        }

        const dataParts = dataInput.split(',').map((s) => s.trim()).filter((s) => s !== '');

        if (dataParts.length < 2) {
            this.showValidationError('Masukkan minimal 2 angka.');
            return false;
        }

        if (dataParts.length > 50) {
            this.showValidationError('Maksimal 50 angka untuk visualisasi yang baik.');
            return false;
        }

        const data = [];
        for (const part of dataParts) {
            const num = parseFloat(part);
            if (Number.isNaN(num)) {
                this.showValidationError('Data hanya boleh berisi angka.');
                return false;
            }
            data.push(num);
        }

        // Validate target
        if (!targetInput) {
            this.showValidationError('Masukkan target pencarian.');
            return false;
        }

        const target = parseFloat(targetInput);
        if (Number.isNaN(target)) {
            this.showValidationError('Target harus berupa angka.');
            return false;
        }

        this.data = data;
        this.target = target;
        return true;
    }

    showValidationError(message) {
        const validationMessages = document.getElementById('validation-messages');
        validationMessages.innerHTML = `<ul><li>${message}</li></ul>`;
        validationMessages.style.display = 'block';
    }

    checkDataSorted() {
        if (this.algorithm !== 'binary') return;

        const isSorted = this.data.every((val, i, arr) => i === 0 || arr[i - 1] <= val);
        const sortWarning = document.getElementById('sort-warning');

        if (!isSorted && this.data.length > 0) {
            sortWarning.style.display = 'block';
        } else {
            sortWarning.style.display = 'none';
        }
    }

    sortData() {
        if (!this.validateInputs()) return;

        this.data.sort((a, b) => a - b);
        document.getElementById('data-input').value = this.data.join(', ');
        document.getElementById('sort-warning').style.display = 'none';
        this.reset();
        this.showExplanation('Data telah diurutkan. Sekarang Anda dapat menjalankan Binary Search.');
    }

    startSimulation() {
        if (!this.validateInputs()) return;

        this.clearAutoPlay();
        this.isRunning = true;
        this.currentStep = 0;
        this.comparisonCount = 0;
        this.steps = [];

        // Clear previous result
        document.getElementById('result-section').style.display = 'none';
        document.getElementById('comparison-section').style.display = 'none';

        if (this.algorithm === 'sequential') {
            this.generateSequentialSearchSteps();
        } else {
            // Check if data is sorted
            const isSorted = this.data.every((val, i, arr) => i === 0 || arr[i - 1] <= val);
            if (!isSorted) {
                this.showValidationError('Binary Search memerlukan data terurut. Silakan urutkan data terlebih dahulu.');
                this.isRunning = false;
                return;
            }
            this.generateBinarySearchSteps();
        }

        this.updateUI();
        this.renderStep();
        this.updateBinarySearchInfo();
        this.showExplanation('Simulasi dimulai. Klik "Langkah Berikutnya" untuk melanjutkan.');
    }

    generateSequentialSearchSteps() {
        for (let i = 0; i < this.data.length; i++) {
            const value = this.data[i];
            const isMatch = value === this.target;
            const comparisons = i + 1;

            this.steps.push({
                type: 'sequential',
                index: i,
                value: value,
                isMatch: isMatch,
                comparisons: comparisons,
                found: isMatch,
                message: isMatch
                    ? `Target ditemukan! Nilai ${value} sama dengan ${this.target}.`
                    : `Nilai ${value} tidak sama dengan target ${this.target}. Lanjut ke elemen berikutnya.`,
            });

            if (isMatch) break;
        }

        // If not found, add final step
        if (this.steps.length === 0 || !this.steps[this.steps.length - 1].found) {
            this.steps.push({
                type: 'sequential',
                index: -1,
                value: null,
                isMatch: false,
                comparisons: this.data.length,
                found: false,
                message: `Target ${this.target} tidak ditemukan. Semua elemen telah diperiksa.`,
            });
        }
    }

    generateBinarySearchSteps() {
        let low = 0;
        let high = this.data.length - 1;
        let stepCount = 0;

        while (low <= high) {
            const mid = Math.floor((low + high) / 2);
            const midValue = this.data[mid];
            stepCount += 1;

            this.steps.push({
                type: 'binary',
                low: low,
                high: high,
                mid: mid,
                midValue: midValue,
                comparisons: stepCount,
                found: midValue === this.target,
                message: this.getBinarySearchExplanation(low, high, mid, midValue),
            });

            if (midValue === this.target) {
                break;
            } else if (midValue < this.target) {
                low = mid + 1;
            } else {
                high = mid - 1;
            }
        }
    }

    getBinarySearchExplanation(low, high, mid, midValue) {
        let explanation = `Nilai tengah (mid) adalah indeks ${mid} dengan nilai ${midValue}. `;

        if (midValue === this.target) {
            explanation += `Target ditemukan!`;
        } else if (midValue < this.target) {
            explanation += `Karena ${this.target} > ${midValue}, cari di separuh kanan. Elemen di separuh kiri dapat dihilangkan.`;
        } else {
            explanation += `Karena ${this.target} < ${midValue}, cari di separuh kiri. Elemen di separuh kanan dapat dihilangkan.`;
        }

        return explanation;
    }

    renderStep() {
        const step = this.steps[this.currentStep];
        if (!step) return;

        this.renderArrayVisualization(step);
        this.updateStepInfo(step);
        this.updateBinarySearchInfo(step);
    }

    renderArrayVisualization(step) {
        const container = document.getElementById('array-visualization');
        container.innerHTML = '';

        for (let i = 0; i < this.data.length; i++) {
            const box = document.createElement('div');
            box.className = 'array-box';
            box.setAttribute('data-index', i);

            const indexLabel = document.createElement('div');
            indexLabel.className = 'box-index';
            indexLabel.textContent = i;

            const valueLabel = document.createElement('div');
            valueLabel.className = 'box-value';
            valueLabel.textContent = this.data[i];

            box.appendChild(valueLabel);
            box.appendChild(indexLabel);

            // Apply state classes
            if (step.type === 'sequential') {
                if (i < step.index) {
                    box.classList.add('box-checked');
                } else if (i === step.index) {
                    box.classList.add('box-checking');
                    if (step.isMatch) {
                        box.classList.add('box-found');
                    } else {
                        box.classList.add('box-not-matched');
                    }
                }
            } else if (step.type === 'binary') {
                if (i < step.low || i > step.high) {
                    box.classList.add('box-eliminated');
                } else if (i === step.mid) {
                    box.classList.add('box-checking');
                    if (step.found) {
                        box.classList.add('box-found');
                    } else if (this.data[i] < this.target) {
                        // Will search right
                    } else {
                        // Will search left
                    }
                }
            }

            container.appendChild(box);
        }
    }

    updateStepInfo(step) {
        const stepInfoSection = document.getElementById('step-info-section');
        const stepInfoPanel = document.getElementById('step-info-panel');
        const stepNumber = this.currentStep + 1;

        stepInfoSection.style.display = 'block';

        let html = `<div class="step-detail"><strong>Langkah ${stepNumber}</strong></div>`;

        if (step.type === 'sequential') {
            html += `
                <div class="step-detail">
                    <span class="step-label">Indeks</span>
                    <strong>${step.index}</strong>
                </div>
                <div class="step-detail">
                    <span class="step-label">Nilai saat ini</span>
                    <strong>${step.value}</strong>
                </div>
                <div class="step-detail">
                    <span class="step-label">Target</span>
                    <strong>${this.target}</strong>
                </div>
                <div class="step-detail">
                    <span class="step-label">Hasil perbandingan</span>
                    <strong>${step.isMatch ? '✓ Cocok' : '✗ Tidak cocok'}</strong>
                </div>
                <div class="step-detail">
                    <span class="step-label">Total perbandingan</span>
                    <strong>${step.comparisons}</strong>
                </div>
            `;
        } else if (step.type === 'binary') {
            html += `
                <div class="step-detail">
                    <span class="step-label">Low</span>
                    <strong>${step.low}</strong>
                </div>
                <div class="step-detail">
                    <span class="step-label">High</span>
                    <strong>${step.high}</strong>
                </div>
                <div class="step-detail">
                    <span class="step-label">Mid</span>
                    <strong>${step.mid}</strong>
                </div>
                <div class="step-detail">
                    <span class="step-label">Nilai di Mid</span>
                    <strong>${step.midValue}</strong>
                </div>
                <div class="step-detail">
                    <span class="step-label">Total perbandingan</span>
                    <strong>${step.comparisons}</strong>
                </div>
            `;
        }

        stepInfoPanel.innerHTML = html;
        this.showExplanation(step.message);
    }

    updateBinarySearchInfo(step = null) {
        const binaryInfo = document.getElementById('binary-search-info');

        if (this.algorithm !== 'binary') {
            binaryInfo.style.display = 'none';
            return;
        }

        binaryInfo.style.display = 'block';

        if (step && step.type === 'binary') {
            document.getElementById('binary-low').textContent = step.low;
            document.getElementById('binary-mid').textContent = step.mid;
            document.getElementById('binary-high').textContent = step.high;
        } else {
            document.getElementById('binary-low').textContent = '-';
            document.getElementById('binary-mid').textContent = '-';
            document.getElementById('binary-high').textContent = '-';
        }
    }

    showExplanation(text) {
        document.getElementById('explanation-text').textContent = text;
    }

    nextStep() {
        if (!this.isRunning || this.isPaused) return;

        if (this.currentStep < this.steps.length - 1) {
            this.currentStep += 1;
            this.renderStep();
            this.updateUI();
        } else {
            this.finishSimulation();
        }
    }

    runAutomatic() {
        if (!this.isRunning || this.isPaused) return;

        this.clearAutoPlay();
        document.getElementById('run-auto-btn').style.display = 'none';
        document.getElementById('pause-btn').style.display = 'inline-flex';

        const playNextStep = () => {
            if (!this.isPaused && this.currentStep < this.steps.length - 1) {
                this.currentStep += 1;
                this.renderStep();
                this.updateUI();
                this.autoPlayTimer = setTimeout(playNextStep, this.autoPlaySpeed);
            } else if (this.currentStep === this.steps.length - 1 && !this.isPaused) {
                this.finishSimulation();
            }
        };

        this.autoPlayTimer = setTimeout(playNextStep, this.autoPlaySpeed);
    }

    pauseAutomatic() {
        this.isPaused = true;
        this.clearAutoPlay();
        document.getElementById('pause-btn').style.display = 'none';
        document.getElementById('run-auto-btn').style.display = 'inline-flex';
        this.updateUI();
    }

    clearAutoPlay() {
        if (this.autoPlayTimer) {
            clearTimeout(this.autoPlayTimer);
            this.autoPlayTimer = null;
        }
    }

    finishSimulation() {
        this.clearAutoPlay();
        this.isRunning = false;

        const finalStep = this.steps[this.steps.length - 1];
        this.displayResult(finalStep);
        this.updateUI();
    }

    displayResult(finalStep) {
        const resultSection = document.getElementById('result-section');
        const resultPanel = document.getElementById('result-panel');
        const found = finalStep.found || (finalStep.type === 'sequential' && finalStep.isMatch);

        if (found) {
            const foundIndex = this.algorithm === 'sequential'
                ? finalStep.index
                : this.steps.findIndex((s) => s.found === true && s.type === 'binary');

            const index = this.algorithm === 'sequential' ? finalStep.index : finalStep.mid;
            resultPanel.innerHTML = `
                <div class="result-success">
                    <div class="result-icon">✓</div>
                    <strong>TARGET DITEMUKAN</strong>
                    <div class="result-details">
                        <div class="result-row">
                            <span>Target:</span>
                            <strong>${this.target}</strong>
                        </div>
                        <div class="result-row">
                            <span>Indeks:</span>
                            <strong>${index}</strong>
                        </div>
                        <div class="result-row">
                            <span>Total Perbandingan:</span>
                            <strong>${finalStep.comparisons}</strong>
                        </div>
                    </div>
                </div>
            `;

            // Store result for comparison
            this.results[this.algorithm] = {
                found: true,
                index: index,
                comparisons: finalStep.comparisons,
            };
        } else {
            resultPanel.innerHTML = `
                <div class="result-failure">
                    <div class="result-icon">✕</div>
                    <strong>TARGET TIDAK DITEMUKAN</strong>
                    <div class="result-details">
                        <div class="result-row">
                            <span>Target:</span>
                            <strong>${this.target}</strong>
                        </div>
                        <div class="result-row">
                            <span>Total Perbandingan:</span>
                            <strong>${finalStep.comparisons}</strong>
                        </div>
                    </div>
                </div>
            `;

            // Store result for comparison
            this.results[this.algorithm] = {
                found: false,
                comparisons: finalStep.comparisons,
            };
        }

        resultSection.style.display = 'block';
        this.displayComparison();
    }

    displayComparison() {
        const comparisonSection = document.getElementById('comparison-section');
        const comparisonContent = document.getElementById('comparison-content');

        if (this.results.sequential && this.results.binary) {
            const seq = this.results.sequential;
            const bin = this.results.binary;

            let html = `
                <div class="comparison-grid">
                    <div class="comparison-item">
                        <strong>Sequential Search</strong>
                        <div class="comparison-stat">
                            <span>Perbandingan:</span>
                            <strong>${seq.comparisons}</strong>
                        </div>
            `;

            if (seq.found) {
                html += `
                        <div class="comparison-stat">
                            <span>Status:</span>
                            <strong style="color: var(--green);">Ditemukan di indeks ${seq.index}</strong>
                        </div>
                `;
            } else {
                html += `
                        <div class="comparison-stat">
                            <span>Status:</span>
                            <strong style="color: var(--coral);">Tidak ditemukan</strong>
                        </div>
                `;
            }

            html += `
                    </div>
                    <div class="comparison-item">
                        <strong>Binary Search</strong>
                        <div class="comparison-stat">
                            <span>Perbandingan:</span>
                            <strong>${bin.comparisons}</strong>
                        </div>
            `;

            if (bin.found) {
                html += `
                        <div class="comparison-stat">
                            <span>Status:</span>
                            <strong style="color: var(--green);">Ditemukan di indeks ${bin.index}</strong>
                        </div>
                `;
            } else {
                html += `
                        <div class="comparison-stat">
                            <span>Status:</span>
                            <strong style="color: var(--coral);">Tidak ditemukan</strong>
                        </div>
                `;
            }

            html += `
                    </div>
                </div>
                <div class="comparison-insight">
                    <p><strong>Observasi:</strong> Pada dataset ini, Sequential Search memerlukan <strong>${seq.comparisons}</strong> perbandingan, sementara Binary Search memerlukan <strong>${bin.comparisons}</strong> perbandingan. Perbedaan ini semakin signifikan pada dataset yang lebih besar.</p>
                </div>
            `;

            comparisonContent.innerHTML = html;
            comparisonSection.style.display = 'block';
        }
    }

    reset() {
        this.clearAutoPlay();
        this.currentStep = 0;
        this.steps = [];
        this.isRunning = false;
        this.isPaused = false;
        this.comparisonCount = 0;

        document.getElementById('array-visualization').innerHTML = '';
        document.getElementById('step-info-section').style.display = 'none';
        document.getElementById('result-section').style.display = 'none';
        document.getElementById('comparison-section').style.display = 'none';
        document.getElementById('pause-btn').style.display = 'none';
        document.getElementById('run-auto-btn').style.display = 'inline-flex';

        this.updateUI();
        this.showExplanation('Pilih algoritma, masukkan data, dan klik "Mulai Simulasi" untuk memulai.');
        this.updateBinarySearchInfo();
    }

    updateUI() {
        const canStart = !this.isRunning;
        const canStep = this.isRunning && !this.isPaused && this.currentStep < this.steps.length - 1;
        const canRunAuto = this.isRunning && !this.isPaused && this.currentStep < this.steps.length - 1;
        const canReset = this.isRunning || this.currentStep > 0;

        document.getElementById('start-btn').disabled = !canStart;
        document.getElementById('next-step-btn').disabled = !canStep;
        document.getElementById('run-auto-btn').disabled = !canRunAuto;
        document.getElementById('reset-btn').disabled = !canReset;
    }
}

// Initialize simulator when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new SearchingSimulator();
});
