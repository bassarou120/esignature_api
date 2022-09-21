<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_group');
            $table->unsignedBigInteger('id_member');
            $table->boolean('is_responsible')->default('0');
            $table->timestamps();
            $table->foreign('id_group')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('id_member')->references('id')->on('members')->onDelete('cascade');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_members');
    }
}
