<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('hpmax')->default(100);
            $table->integer('hp')->default(100);
            $table->integer('level')->default(1);
            $table->integer('pattack')->default(10);
            $table->integer('mattack')->default(10);
            $table->integer('defense')->default(10);
            $table->integer('agility')->default(10);
            $table->integer('stamina')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
