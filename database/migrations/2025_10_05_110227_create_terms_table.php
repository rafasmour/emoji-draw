<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('value');
            $table->enum('difficulty', ['easy','medium','hard']);
            $table->enum('category', ['math','science','history']);
            $table->enum('language', ['GR', 'EN']);
            $table->timestamps();
            $table->index(['id'], 'id_index');
            $table->index(['difficulty'], 'difficulty_index');
            $table->index(['category'], 'category_index');
            $table->index(['category', 'difficulty'], 'category_difficulty_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
