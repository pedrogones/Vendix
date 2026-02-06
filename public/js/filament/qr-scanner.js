(() => {
    if (window.__qrScannerRegistered) return;
    window.__qrScannerRegistered = true;

    const register = () => {
        if (!window.Alpine) return;

        Alpine.data('qrScanner', ({ target }) => ({
            tab: 'camera',
            mode: 'auto',
            scanner: null,
            manualCode: '',

            cameraActive: false,

            overlay: false,
            overlayText: '',
            badgeText: 'Pronto',
            badgeColor: 'gray',
            helperText: 'Escolha como identificar o produto acima (Câmera, Digitar ou Imagem).',

            cameras: [],
            cameraId: null,
            torchSupported: false,
            torchOn: false,

            _opening: false,
            _armed: false,
            _lastRead: { value: null, at: 0 },

            async init() {
                this.installGlobalErrorTrap();
                this.debug('init OK');

                this.setUi('gray', 'Toque para ativar a câmera', false);
                await this.loadCameras();

                this.$watch('tab', async (value) => {
                    if (value === 'manual') {
                        await this.stop();
                        this.cameraActive = false;
                        this.setUi('gray', 'Digite o código', false);
                        return;
                    }

                    if (value === 'file') {
                        await this.stop();
                        this.cameraActive = false;
                        this.setUi('gray', 'Envie uma imagem nítida', false);
                        return;
                    }

                    this.armAutoStart();
                });

                this.$watch('mode', async () => {
                    if (this.cameraActive) {
                        await this.restart();
                    }
                });

                this.$watch('cameraId', async () => {
                    if (this.cameraActive) {
                        await this.restart();
                    }
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

                const el = this.$el?.querySelector('[data-qr-debug]');
                if (el) el.textContent = text;

                const el2 = this.$el?.querySelector('[data-qr-errors]');
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

            async loadCameras() {
                if (!window.Html5Qrcode?.getCameras) return;
                try {
                    const cameras = await Html5Qrcode.getCameras();
                    this.cameras = cameras || [];

                    if (!this.cameraId && this.cameras.length) {
                        const back = this.cameras.find((camera) => /back|rear|environment/i.test(camera.label));
                        this.cameraId = back?.id || this.cameras[0].id;
                    }
                } catch (e) {
                    this.debug('Falha ao listar câmeras', e);
                }
            },

            armAutoStart() {
                if (this._armed) return;
                this._armed = true;

                this.cameraActive = false;
                this.setUi('gray', 'Toque para ativar a câmera', false);

                const box = this.$el.querySelector('.qr-reader-box');
                if (!box) return;

                const handler = async () => {
                    this._armed = false;
                    await this.open();
                };

                box.addEventListener('click', handler, { once: true });
                box.addEventListener('touchend', handler, { once: true });
            },

            setUi(color, text, overlay = false) {
                this.badgeColor = color;
                this.badgeText = text;
                this.overlay = overlay;
                this.overlayText = text;
            },

            getFormats() {
                if (!window.Html5QrcodeSupportedFormats) return [];

                const formats = Html5QrcodeSupportedFormats;

                if (this.mode === 'qr') {
                    return [formats.QR_CODE].filter(Boolean);
                }

                if (this.mode === 'barcode') {
                    return [
                        formats.EAN_13,
                        formats.EAN_8,
                        formats.UPC_A,
                        formats.UPC_E,
                        formats.CODE_128,
                        formats.CODE_39,
                        formats.CODE_93,
                        formats.ITF,
                        formats.CODABAR,
                        formats.PDF_417,
                        formats.DATA_MATRIX,
                        formats.AZTEC,
                    ].filter(Boolean);
                }

                return [
                    formats.QR_CODE,
                    formats.EAN_13,
                    formats.EAN_8,
                    formats.UPC_A,
                    formats.UPC_E,
                    formats.CODE_128,
                ].filter(Boolean);
            },

            getFps() {
                if (this.mode === 'barcode') return 25;
                if (this.mode === 'qr') return 15;
                return 20;
            },

            getQrbox(viewfinderWidth, viewfinderHeight) {
                const minEdge = Math.min(viewfinderWidth, viewfinderHeight);

                if (this.mode === 'qr') {
                    const size = Math.floor(minEdge * 0.6);
                    return { width: size, height: size };
                }

                if (this.mode === 'barcode') {
                    return {
                        width: Math.floor(viewfinderWidth * 0.9),
                        height: Math.floor(viewfinderHeight * 0.25),
                    };
                }

                return {
                    width: Math.floor(viewfinderWidth * 0.85),
                    height: Math.floor(viewfinderHeight * 0.5),
                };
            },

            async open() {
                if (this._opening) return;
                this._opening = true;

                try {
                    if (!navigator.mediaDevices?.getUserMedia) {
                        this.setUi('danger', 'Câmera não suportada.', false);
                        return;
                    }

                    if (!window.Html5Qrcode) {
                        this.setUi('danger', 'Leitor ainda não carregou. Tente novamente.', false);
                        return;
                    }

                    const el = document.getElementById('qr-reader');
                    if (!el) {
                        this.setUi('danger', 'Leitor não encontrado.', false);
                        return;
                    }

                    await this.stop(true);
                    this.scanner = new Html5Qrcode('qr-reader');

                    const formats = this.getFormats();
                    const config = {
                        fps: this.getFps(),
                        qrbox: (w, h) => this.getQrbox(w, h),
                        formatsToSupport: formats,
                        rememberLastUsedCamera: true,
                        experimentalFeatures: {
                            useBarCodeDetectorIfSupported: true,
                        },
                    };

                    const constraints = this.cameraId
                        ? { deviceId: { exact: this.cameraId } }
                        : { facingMode: 'environment' };

                    await this.scanner.start(
                        constraints,
                        config,
                        (text) => this.onRead(text),
                        () => {}
                    );

                    this.cameraActive = true;
                    this.setUi('primary', 'Escaneando...', false);

                    await this.enableAdvancedControls();
                } catch (e) {
                    console.error(e);
                    this.setUi('danger', 'Permissão negada ou câmera indisponível.', false);
                } finally {
                    this._opening = false;
                }
            },

            async enableAdvancedControls() {
                this.torchSupported = false;
                this.torchOn = false;

                if (!this.scanner) return;

                try {
                    const capabilities = this.scanner.getRunningTrackCapabilities?.();

                    if (capabilities?.torch) {
                        this.torchSupported = true;
                    }

                    if (capabilities?.focusMode) {
                        await this.scanner.applyVideoConstraints?.({
                            advanced: [{ focusMode: 'continuous' }],
                        });
                    }
                } catch (e) {
                    this.debug('Falha ao aplicar controles avançados', e);
                }
            },

            async toggleTorch() {
                if (!this.scanner?.applyVideoConstraints) return;

                try {
                    const nextValue = !this.torchOn;
                    await this.scanner.applyVideoConstraints({
                        advanced: [{ torch: nextValue }],
                    });
                    this.torchOn = nextValue;
                } catch (e) {
                    this.debug('Flash não disponível', e);
                }
            },

            async onRead(code) {
                const value = String(code ?? '').trim();
                if (!value) return;

                const now = Date.now();
                if (this._lastRead.value === value && now - this._lastRead.at < 1500) return;
                this._lastRead = { value, at: now };

                this.setUi('success', 'Código lido. Abrindo...', true);
                this.debug('Código lido', value);

                try {
                    navigator.vibrate?.(80);
                } catch {
                    // ignore
                }

                await this.stop(true);

                Livewire?.dispatchTo?.(target, 'barcodeScanned', { code: value });
            },

            async sendManual() {
                const code = this.manualCode.trim();
                if (!code) return;

                this.setUi('primary', 'Carregando produto...', true);
                this.debug('Manual enviado', code);

                await this.stop(true);

                Livewire?.dispatchTo?.(target, 'barcodeScanned', { code });

                this.manualCode = '';
            },

            async scanFile(event) {
                const file = event?.target?.files?.[0];
                if (!file) return;

                if (!window.Html5Qrcode) {
                    this.setUi('danger', 'Leitor ainda não carregou. Tente novamente.', false);
                    return;
                }

                this.setUi('primary', 'Lendo imagem...', true);

                await this.stop(true);

                try {
                    this.scanner = new Html5Qrcode('qr-reader');
                    const text = await this.scanner.scanFile(file, true);
                    await this.onRead(text);
                } catch (e) {
                    console.error(e);
                    this.setUi('danger', 'Não foi possível ler a imagem.', false);
                } finally {
                    if (event?.target) {
                        event.target.value = '';
                    }
                }
            },

            async restart() {
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
                    // ignore
                }

                try {
                    this.scanner.clear();
                } catch {
                    // ignore
                }

                this.scanner = null;
                this.cameraActive = false;
                this.torchSupported = false;
                this.torchOn = false;

                if (!keepOverlay) this.overlay = false;
            },
        }));
    };

    document.addEventListener('alpine:init', register);
    if (document.readyState !== 'loading') setTimeout(register, 0);
})();