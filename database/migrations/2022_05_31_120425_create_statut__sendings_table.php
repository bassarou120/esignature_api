<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatutSendingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('statut__sendings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_sending');
            $table->unsignedBigInteger('id_signataire');
            $table->unsignedBigInteger('id_statut');
            $table->timestamps();
            $table->foreign('id_sending')->references('id')->on('sendings')->onDelete('cascade');
            $table->foreign('id_signataire')->references('id')->on('signataires')->onDelete('cascade');
            $table->foreign('id_statut')->references('id')->on('statuses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('statut__sendings');
    }
}
