<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Config;
use App\Models\WorkHour;

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
        $data = [
                    'office_lat' => -6.239986,
                    'office_lon' => 106.942840,
                ];
        Config::create($data);
        $data = [
                    [
                        'day' => 1,
                        'shift' => 1,
                        'start' => '08:00:00',
                        'end' => '16:00:00',
                    ],
                    [
                        'day' => 2,
                        'shift' => 1,
                        'start' => '08:00:00',
                        'end' => '16:00:00',
                    ],
                    [
                        'day' => 3,
                        'shift' => 1,
                        'start' => '08:00:00',
                        'end' => '16:00:00',
                    ],
                    [
                        'day' => 4,
                        'shift' => 1,
                        'start' => '08:00:00',
                        'end' => '16:00:00',
                    ],
                    [
                        'day' => 5,
                        'shift' => 1,
                        'start' => '08:00:00',
                        'end' => '16:00:00',
                    ],
                    [
                        'day' => 6,
                        'shift' => 1,
                        'start' => '08:00:00',
                        'end' => '13:00:00',
                    ],
                    [
                        'day' => 1,
                        'shift' => 2,
                        'start' => '16:00:00',
                        'end' => '00:00:00',
                    ],
                    [
                        'day' => 2,
                        'shift' => 2,
                        'start' => '16:00:00',
                        'end' => '00:00:00',
                    ],
                    [
                        'day' => 3,
                        'shift' => 2,
                        'start' => '16:00:00',
                        'end' => '00:00:00',
                    ],
                    [
                        'day' => 4,
                        'shift' => 2,
                        'start' => '16:00:00',
                        'end' => '00:00:00',
                    ],
                    [
                        'day' => 5,
                        'shift' => 2,
                        'start' => '16:00:00',
                        'end' => '00:00:00',
                    ],
                    [
                        'day' => 6,
                        'shift' => 2,
                        'start' => '13:00:00',
                        'end' => '18:00:00',
                    ],
                    
                    [
                        'day' => 1,
                        'shift' => 3,
                        'start' => '00:00:00',
                        'end' => '08:00:00',
                    ],
                    [
                        'day' => 2,
                        'shift' => 3,
                        'start' => '00:00:00',
                        'end' => '08:00:00',
                    ],
                    [
                        'day' => 3,
                        'shift' => 3,
                        'start' => '00:00:00',
                        'end' => '08:00:00',
                    ],
                    [
                        'day' => 4,
                        'shift' => 3,
                        'start' => '00:00:00',
                        'end' => '08:00:00',
                    ],
                    [
                        'day' => 5,
                        'shift' => 3,
                        'start' => '00:00:00',
                        'end' => '08:00:00',
                    ],
                    [
                        'day' => 6,
                        'shift' => 3,
                        'start' => '18:00:00',
                        'end' => '23:00:00',
                    ],
                ];
        WorkHour::insert($data);
        // \App\Models\User::factory(10)->create();
    }
}
