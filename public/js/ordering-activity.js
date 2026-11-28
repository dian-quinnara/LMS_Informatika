// Aktivitas menyusun langkah algoritma/pseudocode: siswa mengurutkan langkah lalu memeriksanya.
class OrderingActivity {
    constructor(root) {
        this.root = root;
        this.answer = JSON.parse(root.dataset.steps || '[]');
        this.list = root.querySelector('#ordering-list');
        this.feedback = root.querySelector('#ordering-feedback');
        this.status = root.querySelector('#ordering-status');
        this.order = [];

        root.querySelector('#ordering-check').addEventListener('click', () => this.check());
        root.querySelector('#ordering-shuffle').addEventListener('click', () => this.shuffle());
        root.querySelector('#ordering-answer').addEventListener('click', () => this.showAnswer());
        this.shuffle();
    }

    shuffle() {
        const indexes = this.answer.map((_, index) => index);
        do {
            for (let i = indexes.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [indexes[i], indexes[j]] = [indexes[j], indexes[i]];
            }
        } while (indexes.length > 1 && indexes.every((value, index) => value === index));
        this.order = indexes;
        this.setFeedback('Belum diperiksa.');
        this.render();
    }

    move(position, delta) {
        const target = position + delta;
        if (target < 0 || target >= this.order.length) {
            return;
        }
        [this.order[position], this.order[target]] = [this.order[target], this.order[position]];
        this.render();
    }

    render(results = null) {
        this.list.innerHTML = '';
        this.order.forEach((stepIndex, position) => {
            const row = document.createElement('div');
            row.className = 'session-row';
            const text = document.createElement('div');
            const strong = document.createElement('strong');
            strong.textContent = `${position + 1}. ${this.answer[stepIndex]}`;
            strong.style.whiteSpace = 'pre-wrap';
            text.appendChild(strong);
            row.appendChild(text);

            if (results) {
                const badge = document.createElement('span');
                badge.className = results[position] ? 'status-badge' : 'draft-badge';
                badge.textContent = results[position] ? 'Tepat' : 'Belum tepat';
                row.appendChild(badge);
            }

            const buttons = document.createElement('span');
            buttons.className = 'control-buttons';
            [['↑', -1, 'Naikkan'], ['↓', 1, 'Turunkan']].forEach(([label, delta, aria]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'button button-quiet';
                button.textContent = label;
                button.setAttribute('aria-label', `${aria} langkah ${position + 1}`);
                button.disabled = (delta < 0 && position === 0) || (delta > 0 && position === this.order.length - 1);
                button.addEventListener('click', () => this.move(position, delta));
                buttons.appendChild(button);
            });
            row.appendChild(buttons);
            this.list.appendChild(row);
        });
    }

    check() {
        const results = this.order.map((stepIndex, position) => stepIndex === position);
        const correct = results.filter(Boolean).length;
        this.render(results);
        this.status.textContent = `${correct} / ${this.order.length} TEPAT`;
        this.setFeedback(correct === this.order.length
            ? 'Hebat! Urutan langkahmu sudah benar. Algoritma yang baik selalu runtut dari awal hingga akhir.'
            : `Ada ${this.order.length - correct} langkah yang posisinya belum tepat. Pikirkan: langkah mana yang harus terjadi lebih dulu agar langkah berikutnya bisa dikerjakan?`);
    }

    showAnswer() {
        this.order = this.answer.map((_, index) => index);
        this.check();
        this.setFeedback('Ini urutan yang benar. Perhatikan alasan setiap langkah berada di posisinya, lalu coba acak ulang dan susun sendiri.');
    }

    setFeedback(message) {
        this.feedback.innerHTML = '';
        const paragraph = document.createElement('p');
        paragraph.textContent = message;
        this.feedback.appendChild(paragraph);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('ordering-activity');
    if (root) {
        new OrderingActivity(root);
    }
});
