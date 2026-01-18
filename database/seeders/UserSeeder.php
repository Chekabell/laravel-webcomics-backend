<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::truncate();
        // Создаем администраторов (2 пользователя - 10%)
        User::factory(2)->admin()->create();

        // Создаем писателей (6 пользователей - 30%)
        User::factory(6)->writer()->create();

        // Создаем читателей (12 пользователей - 60%)
        User::factory(12)->create();

        // Для демонстрации: можно также создать некоторых удаленных пользователей
        // (но это уже учтено в фабрике через optional(0.1))

        // Для демонстрации: можно также создать некоторых неподтвержденных
        User::factory(3)->unverified()->create();

        // Для демонстрации: можно явно создать удаленного пользователя
        User::factory(1)->deleted()->create();

        // Создаем одного конкретного админа для тестирования
        User::factory()->create([
            'name' => 'Администратор',
            'email' => 'admin@comics.test',
            'role' => 'admin',
            'password' => Hash::make('admin'),
            'email_verified_at' => now(),
        ]);

        // Создаем одного конкретного писателя для тестирования
        User::factory()->create([
            'name' => 'Писатель',
            'email' => 'writer@comics.test',
            'role' => 'writer',
            'password' => Hash::make('password'),
        ]);

        // Создаем одного конкретного читателя для тестирования
        User::factory()->create([
            'name' => 'Читатель',
            'email' => 'reader@comics.test',
            'role' => 'reader',
            'password' => Hash::make('password'),
        ]);
    }
}
