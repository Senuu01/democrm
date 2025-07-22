<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('auth_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('company')->nullable();
            $table->boolean('email_verified')->default(false);
            $table->string('verification_code', 10)->nullable();
            $table->timestamp('verification_expires')->nullable();
            $table->string('reset_code', 10)->nullable();
            $table->timestamp('reset_expires')->nullable();
            $table->timestamps();
            
            $table->index('email');
        });
    }

    public function down()
    {
        Schema::dropIfExists('auth_users');
    }
};