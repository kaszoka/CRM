import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['qr', 'secret'];
    static values = {
        uri: String
    };

    connect() {
        this.renderQrCode();
    }

    renderQrCode() {
        const el = this.hasQrTarget ? this.qrTarget : document.getElementById('twofa-qr-img');
        if (!el) return;

        const uri = this.uriValue || el.getAttribute('data-uri');
        if (!uri) return;

        const draw = () => {
            if (typeof window.QRCode !== 'undefined') {
                el.innerHTML = '';
                new window.QRCode(el, {
                    text: uri,
                    width: 300,
                    height: 300,
                    correctLevel: window.QRCode.CorrectLevel.L
                });
            }
        };

        if (typeof window.QRCode === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
            script.onload = draw;
            document.head.appendChild(script);
        } else {
            draw();
        }
    }

    copySecret() {
        const secretEl = this.hasSecretTarget ? this.secretTarget : document.getElementById('twofa-secret');
        const text = secretEl ? secretEl.textContent.trim() : '';
        if (!text) return;

        navigator.clipboard.writeText(text).then(() => {
            alert('Klucz skopiowany do schowka.');
        }).catch(() => {
            prompt('Skopiuj ręcznie (Ctrl+C):', text);
        });
    }
}
