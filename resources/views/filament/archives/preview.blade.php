<div class="relative inline-block group">
    <img
        src="{{ asset('storage/' . $record->path) }}"
        class="h-64 w-auto rounded-lg border
               filter blur-sm transition duration-200"
    >

    <div class="absolute inset-0 flex items-center justify-center">
        <span class="bg-black/60 text-white text-sm font-semibold
                     px-4 py-2 rounded-md">
            Arquivo não editável, você pode desativá-lo
        </span>
    </div>
</div>
