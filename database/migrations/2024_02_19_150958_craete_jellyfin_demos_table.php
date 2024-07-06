<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CraeteJellyfinDemosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jellyfindemos', function (Blueprint $table) {
            $table->id();
            $table->integer('jellyfinserver_id');
            $table->integer('jellyfinpackage_id')->nullable();
            $table->string('name',255);
            $table->string('email',255)->nullable();
            $table->string('password',255);
            $table->integer('hours');
            $table->datetime('date_to');
            $table->integer('user_id');
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
        Schema::dropIfExists('jellyfindemos');
    }
}
