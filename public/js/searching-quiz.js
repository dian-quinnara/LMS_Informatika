class SearchingQuizProgress {
    constructor() {
        this.form = document.getElementById('searching-quiz-form');
        this.progress = document.getElementById('quiz-progress');
        this.totalQuestions = 0;

        if (!this.form || !this.progress) {
            return;
        }

        this.totalQuestions = this.form.querySelectorAll('.question-panel').length;
        this.form.addEventListener('change', () => this.updateProgress());
        this.updateProgress();
    }

    updateProgress() {
        const answered = this.form.querySelectorAll('input[type="radio"]:checked').length;
        this.progress.textContent = `${answered} / ${this.totalQuestions} TERJAWAB`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new SearchingQuizProgress();
});
