<?php

namespace Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource;

class ManageUserAttributeConfigs extends ManageRecords
{
    protected static string $resource = UserAttributeConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function ($data) {
                    $model = $data['model_type'];

                    // TODO: Move this to the package (duplicate code of HasUserAttributesTable)
                    if (! in_array(HasUserAttributesContract::class, class_implements($model))) {
                        throw new \Exception('The model does not implement the HasUserAttributesContract interface.');
                    }

                    $config = $model::getUserAttributesConfig();

                    $newData = [
                        'model_type' => $model,
                        'owner_id' => $config->getKey(),
                        'owner_type' => $config->getMorphClass(),
                    ];
                    unset($data['model_type']);
                    $newData['config'] = $data;

                    return $newData;
                }),
        ];
    }
}
