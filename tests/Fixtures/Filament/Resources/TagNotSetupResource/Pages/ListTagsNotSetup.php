<?php

namespace Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\TagNotSetupResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Filament\Resources\TagNotSetupResource;

class ListTagsNotSetup extends ListRecords
{
    protected static string $resource = TagNotSetupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
