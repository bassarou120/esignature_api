<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShareTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('share', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_model');
            $table->unsignedBigInteger('id_member')->nullable();
            $table->unsignedBigInteger('id_group')->nullable();
            $table->foreign('id_model')->references('id')->on('sendings')->onDelete('cascade');
            $table->foreign('id_member')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('id_group')->references('id')->on('groups')->onDelete('cascade');
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
        Schema::dropIfExists('share');
    }
}
