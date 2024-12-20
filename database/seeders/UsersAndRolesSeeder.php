<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\CollaborationRequest;
use Silber\Bouncer\BouncerFacade as Bouncer;

class UsersAndRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Bouncer::role()->firstOrCreate(['name' => 'admin', 'title' => 'Администратор']);
        Bouncer::role()->firstOrCreate(['name' => 'director', 'title' => 'Директор']);
        Bouncer::role()->firstOrCreate(['name' => 'manager', 'title' => 'Менеджер']);

        Bouncer::ability()->firstOrCreate(['name' => 'manage-categories', 'title' => 'Настройка категорий']);
        Bouncer::ability()->firstOrCreate(['name' => 'manage-sales', 'title' => 'Управление продажами']);
        Bouncer::ability()->firstOrCreate(['name' => 'manage-leads', 'title' => 'Управление лидами']);

        // Назначение разрешений ролям
        Bouncer::allow('admin')->everything();
        Bouncer::allow('director')->to([
            'manage-categories',
            'manage-sales',
            'manage-leads',
        ]);
        Bouncer::allow('manager')->to([
            'manage-sales',
            'manage-leads',
        ]);

        // Создание администратора
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin',
            'password' => bcrypt('password_for_admin_123456'),
        ]);
        Bouncer::assign('admin')->to($admin);

        // Создание директоров и менеджеров
        $directors = [];
        for ($i = 1; $i <= 6; $i++) {
            $director = User::firstOrCreate([
                'email' => "director$i@example.com",
            ], [
                'name' => "Director $i",
                'password' => bcrypt('password_123456'),
            ]);
            $director->director_id = $director->id;
            $director->save();
            Bouncer::assign('director')->to($director);
            $directors[] = $director;
        }

        foreach ($directors as $director) {
            for ($i = 1; $i <= 2; $i++) {
                $manager = User::firstOrCreate([
                    'email' => "manager{$i}for{$director->id}@example.com",
                ], [
                    'name' => "Manager for {$director->id} ($i)",
                    'password' => bcrypt('password_123456'),
                ]);
                Bouncer::assign('manager')->to($manager);
                $manager->director_id = $director->id; // Устанавливаем связь с директором
                $manager->save();

                // Создаем запись в таблице collaboration_requests
                CollaborationRequest::create([
                    'manager_id' => $manager->id,
                    'director_id' => $director->id,
                    'manager_email' => $manager->email,
                    'director_email' => $director->email,
                    'status' => 'approved', // или другой статус по умолчанию
                ]);
            }
        }
    }
}
