<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('mongodb')->table('rooms')->whereNull('kicked_users')->update(
            ['kicked_users' => []]
        );
    }

    public function down(): void
    {
        DB::connection('mongodb')->table('rooms')->update(
            ['$unset' => ['kicked_users' => '']]
        );
    }
};
