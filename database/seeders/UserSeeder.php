<?php

namespace Database\Seeders;

use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@dev.com',
            'password' => Hash::make('admin@dev.com'),
            'role' => UserRole::ADMIN->value,
            'status' => UserStatus::ACTIVE->value,
            'agreed_to_terms' => true,
        ]);
        User::create([
            'first_name' => 'Buyer',
            'last_name' => 'User',
            'email' => 'user@dev.com',
            'password' => Hash::make('user@dev.com'),
            'role' => UserRole::BUYER->value,
            'status' => UserStatus::ACTIVE->value,
            'agreed_to_terms' => true,
        ]);
        User::create([
            'first_name' => 'Pending Manufacturer',
            'last_name' => 'User',
            'email' => 'pending-manufacturer@dev.com',
            'password' => Hash::make('pending-manufacturer@dev.com'),
            'role' => UserRole::MANUFACTURER->value,
            'status' => UserStatus::ACTIVE->value,
            'agreed_to_terms' => true,
            'manufacture_status' => UserManuFactureStatus::PENDING->value,
            'manufacture_status_at' => now(),
        ]);
        User::create([
            'first_name' => 'Approved Manufacturer',
            'last_name' => 'User',
            'email' => 'manufacturer@dev.com',
            'password' => Hash::make('manufacturer@dev.com'),
            'role' => UserRole::MANUFACTURER->value,
            'status' => UserStatus::ACTIVE->value,
            'agreed_to_terms' => true,
            'manufacture_status' => UserManuFactureStatus::APPROVED->value,
            'manufacture_status_at' => now(),
        ]);
        User::create([
            'first_name' => 'Rejected Manufacturer',
            'last_name' => 'User',
            'email' => 'rejected-manufacturer@dev.com',
            'password' => Hash::make('rejected-manufacturer@dev.com'),
            'role' => UserRole::MANUFACTURER->value,
            'status' => UserStatus::ACTIVE->value,
            'agreed_to_terms' => true,
            'manufacture_status' => UserManuFactureStatus::REJECTED->value,
            'manufacture_status_reason' => 'Does not meet the criteria for approval.',
            'manufacture_status_at' => now(),
        ]);

        User::create([
            'first_name' => 'Mehedi',
            'last_name' => 'Mehedi',
            'email' => 'meheduvau@gmail.com',
            'password' => Hash::make('meheduvau@gmail.com'),
            'role' => UserRole::MANUFACTURER->value,
            'status' => UserStatus::ACTIVE->value,
            'agreed_to_terms' => true,
            'manufacture_status' => UserManuFactureStatus::APPROVED->value,
            'manufacture_status_at' => now(),
        ]);

        // Other Status Users
        User::create([
            'first_name' => 'Deactivated User',
            'last_name' => 'User',
            'email' => 'deactivated-user@dev.com',
            'password' => Hash::make('deactivated-user@dev.com'),
            'role' => UserRole::BUYER->value,
            'status' => UserStatus::DEACTIVATED->value,
        ]);
        User::create([
            'first_name' => 'Deleted User',
            'last_name' => 'User',
            'email' => 'deleted-user@dev.com',
            'password' => Hash::make('deleted-user@dev.com'),
            'role' => UserRole::BUYER->value,
            'status' => UserStatus::DELETED->value,
            'deleted_at' => now(),
            'deleted_reason' => 'User requested deletion.',
        ]);
        User::create([
            'first_name' => 'Permanently Deleted User',
            'last_name' => 'User',
            'email' => 'permanently-deleted-user@dev.com',
            'password' => Hash::make('permanently-deleted-user@dev.com'),
            'role' => UserRole::BUYER->value,
            'status' => UserStatus::DELETED->value,
            'is_permanently_deleted' => true,
            'deleted_at' => now(),
            'deleted_reason' => 'User requested deletion.',
        ]);
        User::create([
            'first_name' => 'Pending Deletion User',
            'last_name' => 'User',
            'email' => 'pending-deletion-user@dev.com',
            'password' => Hash::make('pending-deletion-user@dev.com'),
            'role' => UserRole::BUYER->value,
            'status' => UserStatus::SCHEDULED_DELETION->value,
            'deleted_at' => now(),
            'deleted_reason' => 'User requested deletion.',
        ]);
        User::create([
            'first_name' => 'Suspended User',
            'last_name' => 'User',
            'email' => 'suspended-user@dev.com',
            'password' => Hash::make('suspended-user@dev.com'),
            'role' => UserRole::BUYER->value,
            'status' => UserStatus::SUSPENDED->value,
        ]);
    }
}
