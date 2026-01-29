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

    /* garante que o container manda no tamanho */
    .qr-reader-box {
        position: relative;
        width: 100%;
        border-radius: 16px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        min-height: 220px;
    }

    /* quando câmera ativa, mantém 16:9 e ocupa bem */
    .qr-reader-box.active {
        aspect-ratio: 16 / 9;
        height: auto;
        max-height: 45vh;
    }

    /* força tudo que o html5-qrcode cria a ocupar o box */
    #qr-reader,
    #qr-reader > div,
    #qr-reader video,
    #qr-reader canvas,
    #qr-reader img {
        width: 100% !important;
        height: 100% !important;
    }

    /* vídeo cobrindo certinho sem “sobrar” */
    #qr-reader video {
        object-fit: cover !important;
        border-radius: 16px;
    }

    /* some com UI interna que o html5-qrcode coloca */
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

            </div>
        </div>

        <x-filament::badge color="gray">
            <span x-text="badgeText"></span>
        </x-filament::badge>
    </div>

    <div
        x-show="tab === 'camera'"
        x-transition.opacity.duration.200ms
        style="display: none;"
    >
        <div>
            <x-filament::section>
                <x-slot name="heading">Leitor</x-slot>

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
                </div>
                <div class="qr-actions">
                    <x-filament::button
                        color="gray"
                        size="sm"
                        x-on:click="restart()"
                        x-bind:disabled="overlay"
                    >
                        Reiniciar
                    </x-filament::button>
                </div>

                <div id="qr-debug" class="qr-debug"></div>
                <div id="qr-errors" class="qr-debug" style="white-space: pre-wrap;"></div>

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
                <div id="qr-debug" class="qr-debug"></div>
                <div id="qr-errors" class="qr-debug" style="white-space: pre-wrap;"></div>

            </x-filament::section>
        </div>
    </div>
</div>
