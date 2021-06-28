<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $data = [
                    'username' => 'admin',
                    'name' => 'Administrator',
                    'password' => \Hash::make('123'),
                    'role' => 'Admin',
                    'api_token' => '123',
                ];
        User::create($data);
        // \App\Models\User::factory(10)->create();
    }
}
