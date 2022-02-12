<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            "firstname"=>"Lawrence",
            "lastname"=>"Elango",
            "gender"=>"male",
            "role_id"=>"2",
            "image"=>"default.png",
            "email"=>"elangolawrence@gmail.com",
            "password"=>Hash::make("123456")
        ]);
    }
}
