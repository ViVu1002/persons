<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug');
            $table->string('email');
            $table->string('address');
            $table->integer('gender');
            $table->string('phone');
            $table->bigInteger('faculty_id')->unsigned()->index();
            $table->bigInteger('user_id')->unsigned()->index();
            $table->string('image');
            $table->date('date')->format('d.m.Y');
            $table->timestamps();
        });
        Schema::table('persons', function($table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('faculty_id')->references('id')->on('faculties');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('persons');
    }
}
