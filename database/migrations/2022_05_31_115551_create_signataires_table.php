<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSignatairesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('signataires', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_sending');
            $table->string('name');
            $table->string('email');
            $table->enum('type',['Signataire','Validataire','CC']);
            $table->json('widget')->nullable();
            $table->json('signataire_answers')->nullable();
            $table->timestamps();
            $table->foreign('id_sending')->references('id')->on('sendings')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('signataires');
    }
}
