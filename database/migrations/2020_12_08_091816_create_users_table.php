<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 60);
            $table->string('last_name', 60)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('country_code', 5)->nullable();
            $table->string('mobile_number', 15)->nullable();
            $table->string('password', 1024)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->string('lang', 2)->default('en');
            $table->string('otp', 6)->nullable();
            $table->dateTime('otp_expire_on', 0)->nullable();
            $table->unsignedBigInteger('created_by')->default(0);
            $table->unsignedBigInteger('updated_by')->default(0);
            $table->timestamps();
            $table->softDeletes('deleted_at', 0);

            $table->index(['first_name', 'last_name', 'email', 'created_by', 'updated_by'], 'users_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
