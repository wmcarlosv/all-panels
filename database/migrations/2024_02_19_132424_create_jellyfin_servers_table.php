<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJellyfinServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jellyfinservers', function (Blueprint $table) {
            $table->id();
            $table->string('name',255);
            $table->string('host',255);
            $table->string('port',10)->nullable();
            $table->string('api_key',255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jellyfinservers');
    }
}
