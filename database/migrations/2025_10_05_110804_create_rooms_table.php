<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->jsonb('users');
            $table->jsonb('settings');
            $table->jsonb('canvas');
            $table->string('owner');
            $table->string('artist');
            $table->jsonb('chat');
            $table->jsonb('status');
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
