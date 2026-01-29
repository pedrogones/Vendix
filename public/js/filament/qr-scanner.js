(() => {
    if (window.__qrScannerRegistered) return;
    window.__qrScannerRegistered = true;

    const register = () => {
        if (!window.Alpine) return;

        Alpine.data('qrScanner', ({target}) => ({
            tab: 'camera',
            scanner: null,
            manualCode: '',

            cameraActive: false,

            overlay: false,
            overlayText: '',
            badgeText: 'Pronto',
            badgeColor: 'gray',
            helperText: 'Escolha como identificar o produto acima (Câmera ou Digitar)',

            _opening: false,
            _armed: false,

            async init() {
                this.installGlobalErrorTrap();
                this.debug('init OK');

                this.setUi('gray', 'Toque para ativar a câmera', false);

                this.$watch('tab', async (value) => {
                    if (value === 'manual') {
                        await this.stop();
                        this.cameraActive = false;
                        this.setUi('gray', 'Digite o código', false);
                        return;
                    }

                    // voltou para câmera: arma de novo para exigir gesto
                    this.armAutoStart();
                });

                if (this.tab === 'camera') {
                    this.armAutoStart();
                }

                window.addEventListener('qr:result', (e) => {
                    const data = e.detail || {};
                    if (data.found) {
                        this.setUi('success', data.message || 'Encontrado.', false);
                        return;
                    }
                    this.setUi('danger', data.message || 'Não encontrado.', false);
                    this.tab = 'manual';
                });
            },

            isIOS() {
                return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            },

            debug(message, extra) {
                const text =
                    `[${new Date().toLocaleTimeString()}] ${message}` +
                    (extra ? `\n${typeof extra === 'string' ? extra : JSON.stringify(extra, null, 2)}` : '');

                const el = document.getElementById('qr-debug');
                if (el) el.textContent = text;

                const el2 = document.getElementById('qr-errors');
                if (el2) el2.textContent = text;
            },

            installGlobalErrorTrap() {
                if (this._errorTrapInstalled) return;
                this._errorTrapInstalled = true;

                window.addEventListener('error', (e) => {
                    this.debug('JS error', {
                        message: e.message,
                        file: e.filename,
                        line: e.lineno,
                        col: e.colno,
                    });
                });

                window.addEventListener('unhandledrejection', (e) => {
                    this.debug('Promise rejection', {
                        reason: String(e.reason?.message ?? e.reason ?? 'unknown'),
                    });
                });
            },

            armAutoStart() {
                if (this._armed) return;
                this._armed = true;

                this.cameraActive = false;
                this.setUi('gray', 'Toque para ativar a câmera', false);

                // só inicia com gesto do usuário dentro do box da câmera
                const box = this.$el.querySelector('.qr-reader-box');
                if (!box) return;

                const handler = async () => {
                    this._armed = false;

                    // // dá tempo do x-show renderizar e do modal estabilizar
                    // await this.$nextTick();
                    // requestAnimationFrame(() => requestAnimationFrame(() => this.open()));
                };

                box.addEventListener('click', handler, {once: true});
                box.addEventListener('touchend', handler, {once: true});
            },

            setUi(color, text, overlay = false) {
                this.badgeColor = color;
                this.badgeText = text;
                this.overlay = overlay;
                this.overlayText = text;
            },

            async open() {
                if (this._opening) return;
                this._opening = true;

                try {
                    if (!navigator.mediaDevices?.getUserMedia) {
                        this.setUi('danger', 'Câmera não suportada.', false);
                        return;
                    }

                    const el = document.getElementById('qr-reader');
                    if (!el) {
                        this.setUi('danger', 'Leitor não encontrado.', false);
                        return;
                    }

                    this.scanner = new Html5Qrcode('qr-reader');

                    const formats = this.isIOS()
                        ? [Html5QrcodeSupportedFormats.QR_CODE]
                        : [
                            Html5QrcodeSupportedFormats.QR_CODE,
                            Html5QrcodeSupportedFormats.EAN_13,
                            Html5QrcodeSupportedFormats.EAN_8,
                            Html5QrcodeSupportedFormats.UPC_A,
                            Html5QrcodeSupportedFormats.UPC_E,
                            Html5QrcodeSupportedFormats.CODE_128,
                        ];

                    await this.scanner.start(
                        { facingMode: 'environment' },
                        {
                            fps: this.isIOS() ? 10 : 20,
                            qrbox: { width: 260, height: 260 },
                            formatsToSupport: formats,
                        },
                        (text) => this.onRead(text)
                    );

                    this.setUi('primary', 'Escaneando...', false);
                } catch (e) {
                    console.error(e);
                    this.setUi('danger', 'Permissão negada ou câmera indisponível.', false);
                } finally {
                    this._opening = false;
                }
            },

            async onRead(code) {
                const value = String(code ?? '').trim();
                if (!value) return;

                this.setUi('success', 'Código lido. Abrindo...', true);
                this.debug('Código lido', value);

                try {
                    navigator.vibrate?.(80);
                } catch {
                }

                await this.stop(true);

                Livewire?.dispatchTo?.(target, 'barcodeScanned', {code: value});
            },

            async sendManual() {
                const code = this.manualCode.trim();
                if (!code) return;

                this.setUi('primary', 'Carregando produto...', true);
                this.debug('Manual enviado', code);

                await this.stop(true);

                Livewire?.dispatchTo?.(target, 'barcodeScanned', {code});

                this.manualCode = '';
            },

            async restart() {
                // restart sempre conta como gesto porque veio de botão
                if (this.tab !== 'camera') this.tab = 'camera';
                await this.open();
            },

            async stop(keepOverlay = false) {
                if (!this.scanner) {
                    if (!keepOverlay) this.overlay = false;
                    return;
                }

                try {
                    await this.scanner.stop();
                } catch {
                }
                try {
                    this.scanner.clear();
                } catch {
                }

                this.scanner = null;

                if (!keepOverlay) this.overlay = false;
            },
        }));
    };

    document.addEventListener('alpine:init', register);
    if (document.readyState !== 'loading') setTimeout(register, 0);
})();
