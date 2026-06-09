<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ================================================
        // 1. CREATE ALL PERMISSIONS
        // ================================================

        // --- Navigation / Page Access ---
        $navPermissions = [
            'view_dashboard',
            'view_kasir',
            'view_kitchen',
            'view_queue',
            'view_data_master',
            'view_finance',
            'view_report',
            'view_resources',
            'view_help',
        ];

        // --- Granular User Management ---
        $userPermissions = [
            'user.show',
            'user.create',
            'user.edit',
            'user.delete',
            'user.massdelete',
            'user.ban',
        ];

        // --- Granular Role Management ---
        $rolePermissions = [
            'role.show',
            'role.create',
            'role.edit',
            'role.delete',
            'role.massdelete',
        ];

        // --- Granular Data Master ---
        $masterPermissions = [
            'category.show', 'category.create', 'category.edit', 'category.delete',
            'menu.show', 'menu.create', 'menu.edit', 'menu.delete',
            'table.show', 'table.create', 'table.edit', 'table.delete',
            'promo.show', 'promo.create', 'promo.edit', 'promo.delete',
            'supplier.show', 'supplier.create', 'supplier.edit', 'supplier.delete',
            'product.show', 'product.create', 'product.edit', 'product.delete',
        ];

        // --- Granular Finance ---
        $financePermissions = [
            'expense.show', 'expense.create', 'expense.edit', 'expense.delete',
        ];

        // --- Granular Report ---
        $reportPermissions = [
            'report.sales',
            'report.items',
        ];

        // Create all permissions
        $allPermissions = array_merge(
            $navPermissions, $userPermissions, $rolePermissions,
            $masterPermissions, $financePermissions, $reportPermissions
        );

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ================================================
        // 2. CREATE ROLES (if they don't exist)
        // ================================================
        $roleSuperadmin = Role::firstOrCreate(['name' => 'Superadmin']);
        $roleAdmin      = Role::firstOrCreate(['name' => 'admin']);
        $roleKasir      = Role::firstOrCreate(['name' => 'kasir']);
        $roleKitchen    = Role::firstOrCreate(['name' => 'kitchen']);

        // ================================================
        // 3. ASSIGN PERMISSIONS TO ROLES
        // ================================================

        // SUPERADMIN — gets everything implicitly via Gate::before in AppServiceProvider
        // But we still assign explicitly for completeness
        $roleSuperadmin->syncPermissions(Permission::all());

        // ADMIN — All except Resources (User/Role Management)
        $adminPermissions = [
            'view_dashboard',
            'view_kasir',
            'view_kitchen',
            'view_queue',
            'view_data_master',
            'view_finance',
            'view_report',
            'view_help',
            'category.show', 'category.create', 'category.edit', 'category.delete',
            'menu.show', 'menu.create', 'menu.edit', 'menu.delete',
            'table.show', 'table.create', 'table.edit', 'table.delete',
            'promo.show', 'promo.create', 'promo.edit', 'promo.delete',
            'supplier.show', 'supplier.create', 'supplier.edit', 'supplier.delete',
            'product.show', 'product.create', 'product.edit', 'product.delete',
            'expense.show', 'expense.create', 'expense.edit', 'expense.delete',
            'report.sales', 'report.items',
        ];
        $roleAdmin->syncPermissions($adminPermissions);

        // KASIR — Dashboard, Kasir, Kitchen, Queue, Finance, Report
        $kasirPermissions = [
            'view_dashboard',
            'view_kasir',
            'view_kitchen',
            'view_queue',
            'view_finance',
            'view_report',
            'expense.show',
            'report.sales',
            'report.items',
        ];
        $roleKasir->syncPermissions($kasirPermissions);

        // KITCHEN — Dashboard, Kitchen, Queue only
        $kitchenPermissions = [
            'view_dashboard',
            'view_kitchen',
            'view_queue',
        ];
        $roleKitchen->syncPermissions($kitchenPermissions);
    }
}
