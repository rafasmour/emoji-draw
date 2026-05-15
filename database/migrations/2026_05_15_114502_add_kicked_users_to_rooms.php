<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('mongodb')->collection('rooms')->whereNull('kicked_users')->update(
            ['$set' => ['kicked_users' => []]],
            ['multiple' => true],
        );
    }

    public function down(): void
    {
        DB::connection('mongodb')->collection('rooms')->update(
            ['$unset' => ['kicked_users' => '']],
            ['multiple' => true],
        );
    }
};
