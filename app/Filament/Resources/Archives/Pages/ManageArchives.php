<?php

namespace App\Filament\Resources\Archives\Pages;

use App\Filament\Resources\Archives\ArchiveResource;
use App\Services\Archives\ArchiveUploadService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageArchives extends ManageRecords
{
    protected static string $resource = ArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data) {
                    return app(ArchiveUploadService::class)->upload(
                        file: $data['file'],
                        type: $data['type'],
                        category: $data['category'] ?? null,
                        visibility: $data['visibility'],
                        attachable: auth()->user(),
                    );
                })->visible(fn () => auth()->user()->can('create-archives')),
        ];
    }
}
