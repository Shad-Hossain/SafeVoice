<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class LawyerSeeder extends Seeder
{
    public function run(): void
    {
        $lawyers = [
            [
                'lawyer_code'      => 'LAW001',
                'full_name'        => 'Adv. Rahman Hossain',
                'email'            => 'lawyer@safevoice.com',
                'email_hash'       => hash('sha256', 'lawyer@safevoice.com'),
                'phone'            => '01711000001',
                'password_hash'    => Hash::make('lawyer123'),
                'bar_council_id'   => 'BCD-2019-00123',
                'profile_photo'    => null,
                'address'          => 'Mirpur-10, Dhaka',
                'city'             => 'Dhaka',
                'specializations'  => json_encode(['criminal', 'harassment', 'cyber']),
                'experience_years' => 7,
                'min_fee'          => 1500.00,
                'bio'              => 'Experienced criminal lawyer with 7 years of practice at Dhaka Bar. Specializes in harassment and cyber crime cases.',
                'status'           => 'Active',
                'is_available'     => true,
                'total_cases'      => 42,
                'completed_cases'  => 38,
                'rating'           => 4.70,
                'rating_count'     => 31,
                'joined_at'        => now(),
                'updated_at'       => now(),
            ],
            [
                'lawyer_code'      => 'LAW002',
                'full_name'        => 'Adv. Nusrat Jahan',
                'email'            => 'lawyer2@safevoice.com',
                'email_hash'       => hash('sha256', 'lawyer2@safevoice.com'),
                'phone'            => '01711000002',
                'password_hash'    => Hash::make('lawyer123'),
                'bar_council_id'   => 'BCD-2021-00456',
                'profile_photo'    => null,
                'address'          => 'Gulshan-1, Dhaka',
                'city'             => 'Dhaka',
                'specializations'  => json_encode(['family', 'domestic', 'labor']),
                'experience_years' => 4,
                'min_fee'          => 1000.00,
                'bio'              => 'Family and domestic violence specialist. Passionate about protecting women\'s rights.',
                'status'           => 'Active',
                'is_available'     => true,
                'total_cases'      => 25,
                'completed_cases'  => 22,
                'rating'           => 4.85,
                'rating_count'     => 20,
                'joined_at'        => now(),
                'updated_at'       => now(),
            ],
            [
                'lawyer_code'      => 'LAW003',
                'full_name'        => 'Adv. Karim Uddin',
                'email'            => 'lawyer3@safevoice.com',
                'email_hash'       => hash('sha256', 'lawyer3@safevoice.com'),
                'phone'            => '01711000003',
                'password_hash'    => Hash::make('lawyer123'),
                'bar_council_id'   => 'BCD-2015-00789',
                'profile_photo'    => null,
                'address'          => 'Agrabad, Chittagong',
                'city'             => 'Chittagong',
                'specializations'  => json_encode(['property', 'fraud', 'corruption']),
                'experience_years' => 11,
                'min_fee'          => 2000.00,
                'bio'              => 'Senior advocate with expertise in property disputes and financial fraud cases.',
                'status'           => 'Active',
                'is_available'     => false,
                'total_cases'      => 87,
                'completed_cases'  => 81,
                'rating'           => 4.60,
                'rating_count'     => 65,
                'joined_at'        => now(),
                'updated_at'       => now(),
            ],
        ];

        foreach ($lawyers as $lawyer) {
            DB::table('lawyers')->updateOrInsert(
                ['email' => $lawyer['email']],
                $lawyer
            );
        }

        $this->command->info('✅ Lawyer dummy accounts created:');
        $this->command->info('   lawyer@safevoice.com  → password: lawyer123');
        $this->command->info('   lawyer2@safevoice.com → password: lawyer123');
        $this->command->info('   lawyer3@safevoice.com → password: lawyer123');
    }
}
