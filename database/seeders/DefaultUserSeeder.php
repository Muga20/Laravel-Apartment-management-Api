<?php

namespace Database\Seeders;

use App\Models\Roles;
use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $company = Company::firstOrCreate([
            'companyId' => 'cm0001',
            'name' => 'House Management System',
            'status' => 'active',
            'slug' => 'house-management-system'
        ]);

        $user = User::create([
            'uuid' => '4949d82e-b3b3-4f24-b3ed-7413b213yyu43ac',
            'email' => 'sudo@sudo.com',
            'password' => Hash::make('123456789'),
            'status' => 'active',
            'authType' => 'password',
            'company_id' => $company->id,
        ]);

        // Fetch first name and last name from user_details table
        $userDetails = DB::table('user_details')->where('user_id', $user->id)->first();

        if ($userDetails) {
            $firstName = $userDetails->first_name;
            $lastName = $userDetails->last_name;

            // Update user with first name and last name
            $user->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);
        }

        // Assign roles to the user
        $roles = Roles::whereIn('slug', ['user', 'admin', 'sudo'])->get();
        $user->roles()->attach($roles);
    }

}
