import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.remaining = parseInt(this.element.dataset.remaining, 10);
        this.render();
        this.timer = setInterval(() => this.tick(), 1000);
    }

    disconnect() {
        clearInterval(this.timer);
    }

    tick() {
        this.remaining--;
        if (this.remaining <= 0) {
            this.remaining = 0;
            clearInterval(this.timer);
            window.location.reload();
        }
        this.render();
    }

    render() {
        const m = Math.floor(this.remaining / 60);
        const s = this.remaining % 60;
        this.element.textContent =
            String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        this.element.classList.toggle('is-urgent', this.remaining <= 120);
    }
}
