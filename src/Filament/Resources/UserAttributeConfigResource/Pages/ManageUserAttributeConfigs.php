<?php

namespace Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ManageRecords;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource;
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;

class ManageUserAttributeConfigs extends ManageRecords
{
    public static string $injectedResource = UserAttributeConfigResource::class;

    public static function getResource(): string
    {
        return static::$injectedResource;
    }

    protected $listeners = ['managedUserAttributes' => '$refresh'];

    protected function getHeaderActions(): array
    {
        $resources = FilamentUserAttributes::getConfigurableResources();

        return [
            Actions\Action::make('Manage user attributes')
                ->form([
                    Forms\Components\Select::make('resource_type')
                        ->options($resources)
                        ->label(ucfirst(__('filament-user-attributes::user-attributes.attributes.resource_type')))
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
