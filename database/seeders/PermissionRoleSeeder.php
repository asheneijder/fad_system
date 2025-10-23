<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'can.view.request',
            'can.manage.permissions',
            'can.view.users',
            'can.view.reports',
            'can.view.assets',
            'can.view.models',
            'can.view.categories',
            'can.view.licenses',
            'can.view.stationary-items',
            'can.manage.request',
            'can.view.audit',
            'can.approve.request',
            'can.reject.request',
            'can.create.permission',
            'can.delete.permission',
            'can.edit.permission',
            'can.create.user',
            'can.delete.user',
            'can.view.claim-request',
            'can.manage.claim-request',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $systemAdminRole = Role::firstOrCreate(['name' => 'system-admin', 'guard_name' => 'web']);
        $fadApproverRole = Role::firstOrCreate(['name' => 'fad-approver', 'guard_name' => 'web']);
        $hodApproverRole = Role::firstOrCreate(['name' => 'hod-approver', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'artb-staff', 'guard_name' => 'web']);

        // Assign all permissions to system-admin
        $systemAdminRole->givePermissionTo(Permission::all());

        $fadApproverPermissions = [
            'can.view.request',
            'can.manage.permissions',
            'can.view.users',
            'can.view.reports',
            'can.view.assets',
            'can.view.models',
            'can.view.categories',
            'can.view.licenses',
            'can.view.stationary-items',
            'can.manage.request',
            'can.view.audit',
            'can.approve.request',
            'can.reject.request',
            'can.create.user',
            'can.view.claim-request',
            'can.manage.claim-request',
        ];

        $fadApproverRole->givePermissionTo($fadApproverPermissions);

        $hodApproverPermissions = [
            'can.view.request',
            'can.view.claim-request',
            'can.manage.claim-request',
        ];

        $hodApproverRole->givePermissionTo($hodApproverPermissions);

        // Assign basic permissions to staff role
        $staffPermissions = [
            'can.view.request',
            'can.view.claim-request',
        ];

        $staffRole->givePermissionTo($staffPermissions);

        // Assign system-admin role to your email
        $yourEmail = 'muqri.amin@artrustees.com.my'; // Replace with your actual email
        $adminUser = User::where('email', $yourEmail)->first();

        if ($adminUser) {
            $adminUser->assignRole('system-admin');
            $this->command->info("System Admin role assigned to: {$yourEmail}");
        } else {
            $this->command->error("User with email {$yourEmail} not found!");
        }

        // Assign artb-staff role to all other users
        $otherUsers = User::where('email', '!=', $yourEmail)->get();
        foreach ($otherUsers as $user) {
            $user->assignRole('artb-staff');
        }

        $this->command->info("Artb-Staff role assigned to {$otherUsers->count()} users");

        $this->command->info('Permission and Role seeding completed successfully!');
    }
}
