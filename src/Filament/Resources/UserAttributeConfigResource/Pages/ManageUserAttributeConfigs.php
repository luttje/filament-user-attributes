<?php

namespace Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ManageRecords;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource;
use Luttje\FilamentUserAttributes\FilamentUserAttributes;

class ManageUserAttributeConfigs extends ManageRecords
{
    protected static string $resource = UserAttributeConfigResource::class;

    protected $listeners = ['managedUserAttributes' => '$refresh'];

    protected function getHeaderActions(): array
    {
        $resources = FilamentUserAttributes::getResourcesImplementingHasUserAttributesResourceContract();
        sort($resources);

        return [
            Actions\Action::make('Manage user attributes')
                ->form([
                    Forms\Components\Select::make('resource_type')
                        ->options(array_combine($resources, $resources))
                        ->label(ucfirst(__('filament-user-attributes::attributes.resource_type')))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->redirect(UserAttributeConfigResource::getUrl('edit', [
                        'record' => urlencode($data['resource_type']),
                    ]));
                }),
        ];
    }
}
