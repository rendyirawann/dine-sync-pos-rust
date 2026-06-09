<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Table;
use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;
    public function run(): void
    {
        // 1. Generate Meja
        for ($i = 1; $i <= 10; $i++) {
            Table::create([
                'table_number' => 'Meja ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'capacity' => 4,
            ]);
        }

        // 2. Generate Kategori
        $catFood = Category::create(['name' => 'Main Course', 'slug' => 'main-course']);
        $catBeverage = Category::create(['name' => 'Beverages', 'slug' => 'beverages']);

        // 3. Generate Menu
        Menu::create([
            'category_id' => $catFood->id,
            'name' => 'Nasi Goreng Spesial',
            'description' => 'Nasi goreng dengan telur, ayam, dan kerupuk',
            'price' => 25000,
            'is_available' => true,
        ]);

        Menu::create([
            'category_id' => $catBeverage->id,
            'name' => 'Ice Americano',
            'description' => 'Kopi hitam dingin tanpa gula',
            'price' => 18000,
            'is_available' => true,
        ]);

        $this->call([
            RolePermissionSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
