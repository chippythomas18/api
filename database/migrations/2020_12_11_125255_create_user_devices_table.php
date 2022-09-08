<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('app_version', 10)->nullable();
            $table->string('fcm_token', 255)->nullable();
            $table->string('device_id', 155)->nullable();
            $table->string('os_type', 10)->nullable();
            $table->string('phone_version', 10)->nullable();
            $table->string('phone_model', 155)->nullable();
            $table->unsignedBigInteger('created_by')->default(0);
            $table->unsignedBigInteger('updated_by')->default(0);
            $table->timestamps();
            $table->softDeletes('deleted_at', 0);

            $table->index(['user_id'], 'user_devices_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_devices');
    }
}
