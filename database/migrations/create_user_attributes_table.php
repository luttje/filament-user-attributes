<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_attributes', function (Blueprint $table) {
            $table->id();

            $table->morphs('model');
            $table->json('values');

            // Ensure that each model can only have one set of attributes.
            $table->unique(['model_id', 'model_type']);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_attributes');
    }
};
