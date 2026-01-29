<?php

namespace App\Filament\Resources\ProductConsults\Pages;

use App\Filament\Resources\ProductConsults\ProductConsultResource;
use Filament\Resources\Pages\ManageRecords;

class ManageProductConsults extends ManageRecords
{
    protected static string $resource = ProductConsultResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
