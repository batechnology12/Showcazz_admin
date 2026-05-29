<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Admin User Management
            ['name' => 'manage_admin_users', 'label' => 'Manage Admin Users', 'group_name' => 'Admin Management'],
            ['name' => 'manage_roles', 'label' => 'Manage Roles & Permissions', 'group_name' => 'Admin Management'],
            
            // Site Content Management
            ['name' => 'manage_companies', 'label' => 'Manage Companies', 'group_name' => 'Content Management'],
            ['name' => 'manage_users', 'label' => 'Manage Site Users', 'group_name' => 'Content Management'],
            ['name' => 'manage_posts', 'label' => 'Manage Posts', 'group_name' => 'Content Management'],
            ['name' => 'manage_jobs', 'label' => 'Manage Jobs', 'group_name' => 'Content Management'],
            
            // Subscriptions & Payments
            ['name' => 'manage_subscriptions', 'label' => 'Manage Subscriptions', 'group_name' => 'Finance'],
            
            // Analytics & Reports
            ['name' => 'manage_reports', 'label' => 'View Reports', 'group_name' => 'Analytics'],
            ['name' => 'manage_firebase_analytics', 'label' => 'View Firebase Analytics', 'group_name' => 'Analytics'],
            
            // Communications
            ['name' => 'manage_notifications', 'label' => 'Manage Push Notifications', 'group_name' => 'Communications'],
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::updateOrCreate(['name' => $permission['name']], $permission);
        }
    }
}
