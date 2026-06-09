<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class GeneratePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis generate permission berdasarkan Route Name (Hanya yg pakai titik)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $routes = Route::getRoutes();
        $counter = 0;
        $skipped = 0;

        // Daftar prefix route yang ingin DIABAIKAN (bawaan framework/library)
        $ignoredPackages = [
            'ignition',
            'sanctum',
            'livewire',
            '_debugbar',
            'storage'
        ];

        $this->info('Scanning routes...');

        foreach ($routes as $route) {
            $routeName = $route->getName();

            // 1. Lewati route yang tidak punya nama
            if (!$routeName) continue;

            // 2. Lewati route bawaan framework
            $skipPackage = false;
            foreach ($ignoredPackages as $ignored) {
                if (Str::startsWith($routeName, $ignored)) {
                    $skipPackage = true;
                    break;
                }
            }
            if ($skipPackage) continue;

            // 3. CEK APAKAH ADA TITIK (.)
            // Jika tidak ada titik, SKIP.
            if (!str_contains($routeName, '.')) {
                //$this->warn("Skipped (No Dot): $routeName"); // Uncomment jika ingin lihat yg diskip
                $skipped++;
                continue;
            }

            // 4. LOGIKA PENENTUAN KATEGORI
            // Karena sudah pasti ada titik, aman langsung ambil index 0
            $parts = explode('.', $routeName);
            $rawCategory = $parts[0]; // 'users', 'roles'

            // Format Kategori (users_log -> Users Log)
            $categoryName = Str::title(str_replace(['-', '_'], ' ', $rawCategory));

            // 5. SIMPAN KE DATABASE
            $permission = Permission::firstOrCreate(
                ['name' => $routeName],
                [
                    'guard_name' => 'web',
                    'category' => $categoryName
                ]
            );

            // Update kategori jika berubah
            if ($permission->category !== $categoryName) {
                $permission->category = $categoryName;
                $permission->save();
            }

            $this->line("<info>Checked:</info> $routeName <comment>[$categoryName]</comment>");
            $counter++;
        }

        // Reset Cache Permission Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->newLine();
        $this->info("Selesai!");
        $this->info("- Permission Diproses: $counter");
        $this->info("- Route Dilewati (Tanpa Titik): $skipped");
    }
}
