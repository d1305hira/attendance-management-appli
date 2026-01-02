<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'testman1',
            'email' => 'testman1@example.com',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'testman2',
            'email' => 'testman2@example.com',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'testman3',
            'email' => 'testman3@example.com',
            'password' => Hash::make('password'),
        ]);
    }
}
