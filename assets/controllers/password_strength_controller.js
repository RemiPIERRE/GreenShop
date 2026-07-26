import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'rule'];

    rules = {
        length: /.{8,}/,
        upper: /[A-Z]/,
        lower: /[a-z]/,
        digit: /[0-9]/,
        special: /[^A-Za-z0-9]/,
    };

    check() {
        const value = this.inputTarget.value;

        this.ruleTargets.forEach((li) => {
            const key = li.dataset.rule;
            const ok = this.rules[key].test(value);

            li.classList.toggle('valid', ok);
            li.classList.toggle('invalid', !ok);

            const icon = li.querySelector('.pwd-icon');
            if (icon) {
                icon.textContent = ok ? '✓' : '✗';
            }
        });
    }
}
