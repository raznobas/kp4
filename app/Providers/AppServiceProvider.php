<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Silber\Bouncer\Bouncer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $bouncer = $this->app->make(Bouncer::class);

        $bouncer->role()->firstOrCreate(['name' => 'admin', 'title' => 'Администратор']);
        $bouncer->role()->firstOrCreate(['name' => 'director', 'title' => 'Директор']);
        $bouncer->role()->firstOrCreate(['name' => 'manager', 'title' => 'Менеджер']);

        $bouncer->ability()->firstOrCreate(['name' => 'manage-categories', 'title' => 'Настройка категорий']);
        $bouncer->ability()->firstOrCreate(['name' => 'manage-sales', 'title' => 'Управление продажами']);
        $bouncer->ability()->firstOrCreate(['name' => 'manage-leads', 'title' => 'Управление лидами']);
        $bouncer->ability()->firstOrCreate(['name' => 'manage-tasks', 'title' => 'Управление задачами']);

        // Назначение разрешений ролям
        $bouncer->allow('admin')->everything();
        $bouncer->allow('director')->to([
            'manage-categories',
            'manage-sales',
            'manage-leads',
            'manage-tasks'
        ]);
        $bouncer->allow('manager')->to([
            'manage-sales',
            'manage-leads',
            'manage-tasks'
        ]);
    }
}
