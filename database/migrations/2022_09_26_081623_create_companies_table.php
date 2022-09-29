<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('name', 50)->nullable();
            $table->string('sso_token', 255)->nullable();
            $table->string('billing_detail', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('domain', 255)->nullable();
            $table->string('whitelist_ip', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
};
