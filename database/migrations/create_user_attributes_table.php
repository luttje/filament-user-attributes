<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        /**
         * Contains the values for all user attributes on a resource
         * to which a polymorphic relationship is attached.
         */
        Schema::create('user_attributes', function (Blueprint $table) {
            $table->id();

            // Large enough morph for any type of id.
            $table->string('resource_id');
            $table->string('resource_type');
            $table->json('values');

            // Ensure that each resource can only have one set of attributes.
            $table->unique(['resource_id', 'resource_type']);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_attributes');
    }
};
