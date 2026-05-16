<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('officers')->insert([
            ['officer_code'=>'OFC-A1','name'=>'Officer Karim',  'badge'=>'BD-4421','department'=>'Transport Crimes Unit',    'is_active'=>1,'assigned_cases'=>0,'created_at'=>now()],
            ['officer_code'=>'OFC-B2','name'=>'Officer Sultana','badge'=>'BD-5537','department'=>'Anti-Corruption Cell',     'is_active'=>1,'assigned_cases'=>1,'created_at'=>now()],
            ['officer_code'=>'OFC-C3','name'=>'Officer Hossain','badge'=>'BD-3312','department'=>'Passenger Safety Division','is_active'=>1,'assigned_cases'=>0,'created_at'=>now()],
            ['officer_code'=>'OFC-H8','name'=>'Officer Mitu',   'badge'=>'BD-9901','department'=>'Anti-Corruption Cell',     'is_active'=>1,'assigned_cases'=>1,'created_at'=>now()],
        ]);

        DB::table('private_investigators')->insert([
            ['pi_code'=>'PI-001','full_name'=>'Rahim Uddin Chowdhury','email'=>'rahim.pi001@safevoice.com','phone'=>'01711000001','address'=>'House 12, Road 5, Dhanmondi, Dhaka','nid_number'=>'1234567890123','login_email'=>'rahim.pi001@safevoice.com','is_active'=>1,'active_cases'=>2,'total_cases'=>14,'joined_at'=>now()],
            ['pi_code'=>'PI-002','full_name'=>'Farida Khanam',        'email'=>'farida.pi002@safevoice.com','phone'=>'01811000002','address'=>'Flat 3B, Block C, Mirpur-10, Dhaka','nid_number'=>'9876543210987','login_email'=>'farida.pi002@safevoice.com','is_active'=>1,'active_cases'=>1,'total_cases'=>9, 'joined_at'=>now()],
            ['pi_code'=>'PI-003','full_name'=>'Kamal Hossain',        'email'=>'kamal.pi003@safevoice.com','phone'=>'01911000003','address'=>'Village: Narayanganj Sadar, Narayanganj','nid_number'=>'1122334455667','login_email'=>'kamal.pi003@safevoice.com','is_active'=>1,'active_cases'=>3,'total_cases'=>22,'joined_at'=>now()],
        ]);

        DB::table('super_admins')->insert([
            ['username'=>'superadmin','password_hash'=>'$2y$10$KzgnS8JwXxalUaZoW51zW.ysX2koXyVRAfeC/gOpXMybm3I0smEcq','created_at'=>now()],
        ]);
    }
}