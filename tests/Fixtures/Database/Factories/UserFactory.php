<?php

namespace Luttje\FilamentUserAttributes\Tests\Fixtures\Database\Factories;

use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;
use Orchestra\Testbench\Factories\UserFactory as OrchestraUserFactory;

class UserFactory extends OrchestraUserFactory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;
}
