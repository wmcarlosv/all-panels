<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlatformFieldInJellyfinServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jellyfinservers', function (Blueprint $table) {
            $table->enum("platform",['jellyfin','emby'])->default('jellyfin');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jellyfinservers', function (Blueprint $table) {
            //
        });
    }
}
