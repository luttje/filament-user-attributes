<?php

namespace Luttje\FilamentUserAttributes\Tests\Fixtures\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Database\Factories\UserFactory as UserFactory;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;
use Luttje\FilamentUserAttributes\Traits\HasUserAttributesConfig;

class User extends Authenticatable implements HasUserAttributesConfigContract, HasUserAttributesContract, FilamentUser
{
    use HasFactory;
    use HasUserAttributes;
    use HasUserAttributesConfig;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }
}