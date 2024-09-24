<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJellyfinCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jellyfincustomers', function (Blueprint $table) {
            $table->id();
            $table->integer('jellyfinserver_id');
            $table->integer('duration_id');
            $table->integer('jellyfinpackage_id')->nullable();
            $table->string('name',255);
            $table->string('email',255)->nullable();
            $table->string('password',255);
            $table->string('phone')->nullable();
            $table->text('comment')->nullable();
            $table->date('date_to');
            $table->integer('user_id');
            $table->integer('screens')->default(1);
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
        Schema::dropIfExists('jellyfincustomers');
    }
}
