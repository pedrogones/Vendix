<style>
    .qr-wrap {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .qr-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
    }

    .qr-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #111827;
    }

    .qr-tabs {
        display: inline-flex;
        padding: 4px;
        border-radius: 12px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
    }

    .qr-tab {
        padding: 8px 14px;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 8px;
        border: none;
        background: transparent;
        cursor: pointer;
        color: #374151;
    }

    .qr-tab.active {
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(0,0,0,.08);
        color: #111827;
    }

    .qr-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .2rem .6rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 600;
        background: #f3f4f6;
        color: #374151;
    }

    .qr-badge--primary {
        background: #e0e7ff;
        color: #3730a3;
    }

    .qr-badge--success {
        background: #dcfce7;
        color: #166534;
    }

    .qr-badge--danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .qr-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: .75rem;
        margin-bottom: .75rem;
    }

    .qr-control {
        display: flex;
        flex-direction: column;
        gap: .35rem;
        font-size: .75rem;
        color: #6b7280;
    }

    .qr-select {
        min-width: 180px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        padding: 8px 10px;
        font-size: .875rem;
        color: #111827;
    }

    .qr-btn {
        border: none;
        border-radius: 10px;
        background: #111827;
        color: #ffffff;
        padding: 8px 12px;
        font-size: .875rem;
        font-weight: 600;
        cursor: pointer;
    }

    .qr-btn:disabled {
        opacity: .6;
        cursor: not-allowed;
    }

    .qr-reader-box {
        position: relative;
        width: 100%;
        border-radius: 16px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        min-height: 220px;
    }

    .qr-reader-box.active {
        aspect-ratio: 16 / 9;
        height: auto;
        max-height: 45vh;
    }

    #qr-reader,
    #qr-reader > div,
    #qr-reader video,
    #qr-reader canvas,
    #qr-reader img {
        width: 100% !important;
        height: 100% !important;
    }

    #qr-reader video {
        object-fit: cover !important;
        border-radius: 16px;
    }

    #qr-reader__dashboard_section,
    #qr-reader__dashboard_section_csr,
    #qr-reader__dashboard_section_swaplink,
    #qr-reader__header_message {
        display: none !important;
    }

    .qr-reader-placeholder {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        color: #6b7280;
        text-align: center;
        padding: 1rem;
    }

    .qr-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,.75);
        backdrop-filter: blur(4px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .75rem;
        font-size: 0.875rem;
        color: #111827;
    }

    .qr-actions {
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
        margin-top: 1rem;
    }

    .qr-manual {
        display: flex;
        gap: .75rem;
        align-items: flex-end;
    }

    .qr-input {
        flex: 1;
    }

    .qr-file {
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }

    .qr-file input[type="file"] {
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        padding: .6rem .75rem;
        font-size: .875rem;
    }

    .qr-debug {
        font-size: .75rem;
        color: #6b7280;
        margin-top: .5rem;
    }
</style>

<div
    x-data="qrScanner({ target: 'app.filament.resources.products.pages.manage-products' })"
    x-init="init()"
    class="qr-wrap"
>
    <div class="qr-header">
        <div>
            <div class="qr-title">Escanear produto</div>

            <div class="qr-tabs">
                <button
                    type="button"
                    class="qr-tab"
                    :class="{ 'active': tab === 'camera' }"
                    @click.prevent="tab = 'camera'"
                >
                    Câmera
                </button>

                <button
                    type="button"
                    class="qr-tab"
                    :class="{ 'active': tab === 'manual' }"
                    @click.prevent="tab = 'manual'"
                >
                    Digitar
                </button>

                <button
                    type="button"
                    class="qr-tab"
                    :class="{ 'active': tab === 'file' }"
                    @click.prevent="tab = 'file'"
                >
                    Imagem
                </button>
            </div>
        </div>

        <span class="qr-badge" :class="'qr-badge--' + badgeColor">
            <span x-text="badgeText"></span>
        </span>
    </div>

    <div
        x-show="tab === 'camera'"
        x-transition.opacity.duration.200ms
        style="display: none;"
    >
        <div>
            <x-filament::section>
                <x-slot name="heading">Leitor</x-slot>

                <div class="qr-toolbar">
                    <label class="qr-control">
                        <span>Modo</span>
                        <select x-model="mode" class="qr-select">
                            <option value="auto">Auto</option>
                            <option value="barcode">Código de barras</option>
                            <option value="qr">QR Code</option>
                        </select>
                    </label>

                    <label class="qr-control" x-show="cameras.length > 1">
                        <span>Câmera</span>
                        <select x-model="cameraId" class="qr-select">
                            <template x-for="(camera, index) in cameras" :key="camera.id">
                                <option :value="camera.id" x-text="camera.label || `Câmera ${index + 1}`"></option>
                            </template>
                        </select>
                    </label>

                    <button
                        type="button"
                        class="qr-btn"
                        x-show="torchSupported"
                        @click.prevent="toggleTorch()"
                        x-text="torchOn ? 'Desligar flash' : 'Ligar flash'"
                    ></button>
                </div>

                <div
                    class="qr-reader-box"
                    :class="{ 'active': cameraActive }"
                >
                    <div
                        x-show="!cameraActive && !overlay"
                        class="qr-reader-placeholder"
                    >
                        Ative a câmera para escanear
                    </div>

                    <div wire:ignore id="qr-reader" style="position:absolute; inset:0"></div>

                    <div x-show="overlay" class="qr-overlay">
                        <x-filament::loading-indicator class="h-6 w-6" />
                        <span x-text="overlayText"></span>
                    </div>
                </div>

                <div class="qr-actions">
                    <x-filament::button
                        color="gray"
                        size="sm"
                        x-on:click="open()"
                        x-bind:disabled="overlay"
                    >
                        Abrir câmera
                    </x-filament::button>

                    <x-filament::button
                        color="gray"
                        size="sm"
                        x-on:click="restart()"
                        x-bind:disabled="overlay"
                    >
                        Reiniciar
                    </x-filament::button>
                </div>

                <div class="qr-debug" x-text="helperText"></div>
                <div data-qr-debug class="qr-debug"></div>
                <div data-qr-errors class="qr-debug" style="white-space: pre-wrap;"></div>
            </x-filament::section>
        </div>
    </div>

    <div
        x-show="tab === 'manual'"
        x-transition.opacity.duration.200ms
        style="display: none;"
    >
        <div>
            <x-filament::section>
                <x-slot name="heading">Digitar código</x-slot>

                <div class="qr-manual">
                    <div class="qr-input">
                        <x-filament::input
                            x-model="manualCode"
                            x-on:keydown.enter.prevent="sendManual()"
                            placeholder="Digite ou cole o código"
                        />
                    </div>

                    <x-filament::button
                        type="button"
                        color="primary"
                        x-on:click.prevent="sendManual()"
                        x-bind:disabled="overlay || manualCode.trim() === ''"
                    >
                        <span x-show="!overlay">Enviar</span>
                        <span x-show="overlay">Processando</span>
                    </x-filament::button>
                </div>

                <div class="qr-debug" x-text="helperText"></div>
                <div data-qr-debug class="qr-debug"></div>
                <div data-qr-errors class="qr-debug" style="white-space: pre-wrap;"></div>
            </x-filament::section>
        </div>
    </div>

    <div
        x-show="tab === 'file'"
        x-transition.opacity.duration.200ms
        style="display: none;"
    >
        <div>
            <x-filament::section>
                <x-slot name="heading">Ler de imagem</x-slot>

                <div class="qr-file">
                    <input type="file" accept="image/*" x-on:change="scanFile($event)" />
                    <span class="qr-debug">Selecione uma foto nítida do código.</span>
                </div>

                <div class="qr-debug" x-text="helperText"></div>
                <div data-qr-debug class="qr-debug"></div>
                <div data-qr-errors class="qr-debug" style="white-space: pre-wrap;"></div>
            </x-filament::section>
        </div>
    </div>
</div>
