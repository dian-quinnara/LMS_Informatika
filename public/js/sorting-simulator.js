// Simulasi Bubble, Selection, dan Insertion Sort langkah demi langkah (urut naik).
class SortingSimulator {
    constructor(root) {
        this.root = root;
        this.algorithm = 'bubble';
        this.steps = [];
        this.index = 0;
        this.timer = null;
        this.el = (id) => root.querySelector(`#${id}`);

        root.querySelectorAll('.algorithm-button').forEach((button) => {
            button.addEventListener('click', () => this.selectAlgorithm(button));
        });
        this.el('sorting-start').addEventListener('click', () => this.start());
        this.el('sorting-random').addEventListener('click', () => this.randomData());
        this.el('sorting-next').addEventListener('click', () => this.go(this.index + 1));
        this.el('sorting-prev').addEventListener('click', () => this.go(this.index - 1));
        this.el('sorting-play').addEventListener('click', () => this.togglePlay());
        this.start();
    }

    selectAlgorithm(button) {
        this.root.querySelectorAll('.algorithm-button').forEach((other) => {
            other.classList.toggle('algorithm-button-active', other === button);
            other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
        });
        this.algorithm = button.dataset.algorithm;
        this.start();
    }

    randomData() {
        const values = Array.from({ length: 6 }, () => Math.floor(Math.random() * 90) + 10);
        this.el('sorting-data').value = values.join(', ');
        this.start();
    }

    parseData() {
        const values = this.el('sorting-data').value.split(',').map((value) => value.trim()).filter((value) => value !== '').map(Number);
        if (values.length < 3 || values.length > 10 || values.some((value) => !Number.isFinite(value))) {
            return null;
        }
        return values;
    }

    start() {
        this.stop();
        const data = this.parseData();
        if (!data) {
            this.steps = [];
            this.el('sorting-boxes').innerHTML = '';
            this.explain('Masukkan 3 sampai 10 angka yang dipisahkan koma, misalnya 72, 85, 64.');
            this.el('sorting-counter').textContent = 'LANGKAH 0 / 0';
            return;
        }
        this.steps = this[this.algorithm](data);
        this.go(0);
    }

    snapshot(steps, array, state) {
        steps.push({ array: [...array], ...state });
    }

    bubble(input) {
        const a = [...input];
        const steps = [];
        let comparisons = 0;
        let swaps = 0;
        const sorted = [];
        this.snapshot(steps, a, { text: 'Data awal. Bubble Sort membandingkan dua angka yang bersebelahan.', comparisons, swaps, sorted: [] });
        for (let pass = 0; pass < a.length - 1; pass++) {
            let swapped = false;
            for (let i = 0; i < a.length - pass - 1; i++) {
                comparisons++;
                this.snapshot(steps, a, { text: `Putaran ${pass + 1}: bandingkan ${a[i]} dan ${a[i + 1]}.`, active: [i, i + 1], comparisons, swaps, sorted: [...sorted] });
                if (a[i] > a[i + 1]) {
                    [a[i], a[i + 1]] = [a[i + 1], a[i]];
                    swaps++;
                    swapped = true;
                    this.snapshot(steps, a, { text: `${a[i + 1]} lebih besar dari ${a[i]}, jadi keduanya ditukar.`, moved: [i, i + 1], comparisons, swaps, sorted: [...sorted] });
                }
            }
            sorted.push(a.length - pass - 1);
            this.snapshot(steps, a, { text: `Akhir putaran ${pass + 1}: ${a[a.length - pass - 1]} sudah di posisi akhirnya.`, comparisons, swaps, sorted: [...sorted] });
            if (!swapped) {
                this.snapshot(steps, a, { text: 'Tidak ada penukaran pada putaran ini, artinya data sudah terurut.', comparisons, swaps, sorted: a.map((_, i) => i) });
                return steps;
            }
        }
        this.snapshot(steps, a, { text: 'Selesai! Data sudah terurut dari terkecil ke terbesar.', comparisons, swaps, sorted: a.map((_, i) => i) });
        return steps;
    }

    selection(input) {
        const a = [...input];
        const steps = [];
        let comparisons = 0;
        let swaps = 0;
        const sorted = [];
        this.snapshot(steps, a, { text: 'Data awal. Selection Sort mencari nilai terkecil pada bagian yang belum terurut.', comparisons, swaps, sorted: [] });
        for (let i = 0; i < a.length - 1; i++) {
            let min = i;
            for (let j = i + 1; j < a.length; j++) {
                comparisons++;
                this.snapshot(steps, a, { text: `Putaran ${i + 1}: bandingkan ${a[j]} dengan nilai terkecil sementara ${a[min]}.`, active: [j], marked: [min], comparisons, swaps, sorted: [...sorted] });
                if (a[j] < a[min]) {
                    min = j;
                    this.snapshot(steps, a, { text: `${a[min]} menjadi nilai terkecil sementara.`, marked: [min], comparisons, swaps, sorted: [...sorted] });
                }
            }
            if (min !== i) {
                [a[i], a[min]] = [a[min], a[i]];
                swaps++;
                this.snapshot(steps, a, { text: `Tukar nilai terkecil ${a[i]} ke posisi ${i + 1}.`, moved: [i, min], comparisons, swaps, sorted: [...sorted] });
            }
            sorted.push(i);
            this.snapshot(steps, a, { text: `${a[i]} sudah berada di posisi akhirnya.`, comparisons, swaps, sorted: [...sorted] });
        }
        this.snapshot(steps, a, { text: 'Selesai! Data sudah terurut dari terkecil ke terbesar.', comparisons, swaps, sorted: a.map((_, i) => i) });
        return steps;
    }

    insertion(input) {
        const a = [...input];
        const steps = [];
        let comparisons = 0;
        let swaps = 0;
        this.snapshot(steps, a, { text: 'Data awal. Elemen pertama dianggap sudah terurut.', comparisons, swaps, sorted: [0] });
        for (let i = 1; i < a.length; i++) {
            const key = a[i];
            let j = i - 1;
            this.snapshot(steps, a, { text: `Ambil ${key} lalu cari posisinya di bagian kiri yang sudah terurut.`, active: [i], comparisons, swaps, sorted: [...Array(i).keys()] });
            while (j >= 0) {
                comparisons++;
                if (a[j] <= key) {
                    this.snapshot(steps, a, { text: `${a[j]} tidak lebih besar dari ${key}, jadi ${key} berhenti di sini.`, active: [j + 1], marked: [j], comparisons, swaps, sorted: [...Array(i).keys()] });
                    break;
                }
                a[j + 1] = a[j];
                a[j] = key;
                swaps++;
                this.snapshot(steps, a, { text: `${a[j + 1]} lebih besar dari ${key}, geser ${a[j + 1]} ke kanan.`, moved: [j, j + 1], comparisons, swaps, sorted: [...Array(i).keys()] });
                j--;
            }
            this.snapshot(steps, a, { text: `${key} sudah disisipkan. Bagian kiri sekarang terurut.`, comparisons, swaps, sorted: [...Array(i + 1).keys()] });
        }
        this.snapshot(steps, a, { text: 'Selesai! Data sudah terurut dari terkecil ke terbesar.', comparisons, swaps, sorted: a.map((_, i) => i) });
        return steps;
    }

    go(index) {
        if (this.steps.length === 0) {
            return;
        }
        this.index = Math.max(0, Math.min(index, this.steps.length - 1));
        const step = this.steps[this.index];
        const boxes = this.el('sorting-boxes');
        boxes.innerHTML = '';
        step.array.forEach((value, position) => {
            const box = document.createElement('div');
            box.className = 'array-box';
            if ((step.sorted || []).includes(position)) box.classList.add('box-found');
            if ((step.marked || []).includes(position)) box.classList.add('box-checked');
            if ((step.active || []).includes(position)) box.classList.add('box-checking');
            if ((step.moved || []).includes(position)) box.classList.add('box-not-matched');
            const valueEl = document.createElement('span');
            valueEl.className = 'box-value';
            valueEl.textContent = value;
            const indexEl = document.createElement('span');
            indexEl.className = 'box-index';
            indexEl.textContent = position;
            box.append(valueEl, indexEl);
            boxes.appendChild(box);
        });
        this.explain(step.text);
        this.el('sorting-counter').textContent = `LANGKAH ${this.index + 1} / ${this.steps.length}`;
        this.el('sorting-comparisons').textContent = step.comparisons;
        this.el('sorting-swaps').textContent = step.swaps;
        this.el('sorting-prev').disabled = this.index === 0;
        this.el('sorting-next').disabled = this.index === this.steps.length - 1;
        if (this.index === this.steps.length - 1) {
            this.stop();
        }
    }

    togglePlay() {
        if (this.timer) {
            this.stop();
            return;
        }
        if (this.index >= this.steps.length - 1) {
            this.go(0);
        }
        this.el('sorting-play').textContent = 'Jeda';
        this.timer = setInterval(() => this.go(this.index + 1), 900);
    }

    stop() {
        clearInterval(this.timer);
        this.timer = null;
        this.el('sorting-play').textContent = 'Putar otomatis';
    }

    explain(text) {
        const panel = this.el('sorting-explanation');
        panel.innerHTML = '';
        const paragraph = document.createElement('p');
        paragraph.textContent = text;
        panel.appendChild(paragraph);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('sorting-simulator');
    if (root) {
        new SortingSimulator(root);
    }
});
