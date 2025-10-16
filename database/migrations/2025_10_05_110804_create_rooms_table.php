<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('users');
            $table->json('settings');
            $table->json('canvas');
            $table->string('owner');
            $table->string('artist');
            $table->boolean('started')->default(false);
            $table->json('chat');
            $table->json('status');
            $table->timestamps();
            $table->index(['id'], 'id_index');
            $table->index(['name'], 'name_index');
            $table->index(['owner'], 'owner_index');
            $table->index(['settings.public'], 'public_room_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
