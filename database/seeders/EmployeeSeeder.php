<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        for ($i = 0; $i < 10; $i++) {
            // Create related user
            $user = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password'),
            ]);

            $positionId = $faker->numberBetween(1, 22);
            $sectionId = $faker->numberBetween(6, 21);
            $validDepartmentIds = [1, 3, 4, 5, 6, 7];
            $departmentId = $faker->randomElement($validDepartmentIds);

            $groupId = 1;
            $payGroupId = 10;

            Employee::create([
                'user_id' => $user->id,
                'role' => 'employee',
                'name' => $user->name,
                'national_identity_number' => $faker->unique()->nik(),
                'family_number_card' => $faker->unique()->numerify('3371############'),
                'email' => $user->email,
                'gender' => $faker->randomElement(['Laki-laki', 'Perempuan']),
                'title' => $faker->jobTitle,
                'photo' => null,
                'address' => $faker->address,
                'place_of_birth' => $faker->city,
                'date_of_birth' => $faker->date('Y-m-d', '-20 years'),
                'kk_number' => $faker->numerify('3371############'),
                'religion' => $faker->randomElement(['Islam', 'Kristen', 'Hindu', 'Budha']),
                'phone' => $faker->phoneNumber,
                'marital_status' => $faker->randomElement(['Sudah Kawin', 'Belum Kawin']),
                'dependents_count' => $faker->randomElement(),
                'education' => $faker->randomElement(['SMA', 'D3', 'S1', 'S2']),
                'department_id' => $departmentId,
                'position_id' => $positionId,
                'bank_name' => 'Mandiri',
                'group_id' => $groupId,
                'section_id' => $sectionId,
                'tmt' => $faker->date('Y-m-d', '-2 years'),
                'contract_end_date' => $faker->date('Y-m-d', '+2 years'),
                'salary' => $faker->numberBetween(3000000, 10000000),
                'bank_account_name' => $user->name,
                'bank_account_number' => $faker->numerify('9876543#######'),
                'employee_number' => $faker->unique()->numerify('EMP-####'),
                'pay_group_id' => $payGroupId,
            ]);
        }
    }
}
