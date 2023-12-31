<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        /**
         * Contains the configurations for user attributes for a specified model.
         */
        Schema::create('user_attribute_configs', function (Blueprint $table) {
            $table->id();

            // Large enough morph for any type of id.
            $table->string('owner_id');
            $table->string('owner_type');

            // The resource or component to which the configurations apply.
            $table->string('resource_type');
            $table->string('model_type'); // For easy matching to the user_attributes table
            $table->json('config');

            // Ensure that each model can only have one set of configurations per resource.
            $table->unique(['owner_id', 'owner_type', 'resource_type']);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_attribute_configs');
    }
};
