<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('category_user', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignId('category_id')->cascadeOnDelete()->nullable();
            $table->foreignId('user_id')->cascadeOnDelete()->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('category_user');
    }
};
