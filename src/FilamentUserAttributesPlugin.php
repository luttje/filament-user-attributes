<?php

namespace Luttje\FilamentUserAttributes;

use BackedEnum;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource;
use UnitEnum;

/**
 * Adds the resource where users configure their user attributes to a panel:
 *
 *     $panel->plugin(FilamentUserAttributesPlugin::make());
 */
class FilamentUserAttributesPlugin implements Plugin
{
    /**
     * @var class-string<UserAttributeConfigResource>
     */
    protected string $resource = UserAttributeConfigResource::class;

    protected string | UnitEnum | null $navigationGroup = null;

    protected string | BackedEnum | null $navigationIcon = null;

    protected ?int $navigationSort = null;

    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Gets the plugin registered on the current panel, if any.
     */
    public static function get(): ?static
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if ($panel === null || !$panel->hasPlugin('filament-user-attributes')) {
            return null;
        }

        /** @var static */
        return $panel->getPlugin('filament-user-attributes');
    }

    public function getId(): string
    {
        return 'filament-user-attributes';
    }

    /**
     * Use your own resource (extending UserAttributeConfigResource) to
     * further customize how user attributes are configured.
     *
     * @param  class-string<UserAttributeConfigResource>  $resource
     */
    public function resource(string $resource): static
    {
        if (!is_a($resource, UserAttributeConfigResource::class, true)) {
            throw new \InvalidArgumentException("The resource '$resource' must extend " . UserAttributeConfigResource::class . '.');
        }

        $this->resource = $resource;

        return $this;
    }

    /**
     * @return class-string<UserAttributeConfigResource>
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    public function navigationGroup(string | UnitEnum | null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): string | UnitEnum | null
    {
        return $this->navigationGroup;
    }

    public function navigationIcon(string | BackedEnum | null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationIcon(): string | BackedEnum | null
    {
        return $this->navigationIcon;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            $this->getResource(),
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
