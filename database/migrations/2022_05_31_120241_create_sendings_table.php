<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSendingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sendings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_document');
            $table->unsignedBigInteger('id_type_signature');
            $table->unsignedBigInteger('statut');
            $table->integer('nbre_signataire')->default(0);
            $table->json('configuration')->nullable();
            $table->boolean('register_as_model')->default(0);
            $table->string('objet')->nullable();
            $table->string('message')->nullable();
            $table->enum('callback',['Personnalisé','Quotidien','Hebdomadaire','Aucun'])->nullable();
            $table->enum('expiration',['Personnalisé','Aucun'])->nullable();
            $table->integer('remember')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->boolean('is_config')->default('0');
            $table->timestamps();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_document')->references('id')->on('documents')->onDelete('cascade');
            $table->foreign('id_type_signature')->references('id')->on('type__signatures')->onDelete('cascade');
            $table->foreign('statut')->references('id')->on('statues')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sendings');
    }
}
