<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Roles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("roles", function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
        Schema::create("modules", function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unique('name');
            $table->longText('description')->nullable();
        });
        Schema::create("permissions", function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unique('name');
            $table->integer('module_id')->unsigned();
            $table->longText('description')->nullable();
            $table->foreign('module_id')->references('id')->on('modules');
        });

        Schema::create("rolepermissions", function (Blueprint $table) {
            $table->increments('id');
            $table->integer('role_id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->integer('module_id')->unsigned();

            $table->foreign('role_id')->references('id')->on('roles');
            $table->foreign('permission_id')->references('id')->on('permissions');
            $table->foreign('module_id')->references('id')->on('modules');
        });



        Schema::create('userroles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned();
            $table->integer( 'role_id')->unsigned();
            $table->softDeletes();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('role_id')->references('id')->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("rolepermissions");
        Schema::dropIfExists("userroles");
        Schema::dropIfExists("roles");
        Schema::dropIfExists("permissions");
        Schema::dropIfExists("modules");
    }
}
