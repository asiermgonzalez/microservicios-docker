<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Usuario prueba', 
            'email' => 'prueba@gmail.com',
            'rol'=>'CLIENTE',
            'password' => Hash::make('12345678')
        ]);
    }
}
