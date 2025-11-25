<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test 1',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        User::firstOrCreate(
            ['email' => 'test2@example.com'],
            [
                'name' => 'Test 2',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        User::firstOrCreate(
            ['email' => 'test3@example.com'],
            [
                'name' => 'Test 3',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }
}
