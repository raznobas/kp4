<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Silber\Bouncer\BouncerFacade as Bouncer;

class UsersAndRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
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
            }
        }
    }
}
