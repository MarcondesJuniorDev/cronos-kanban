<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Task;
use App\Models\Column;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            "name"=> "Developer",
            "email"=> "dev@admin.com",
            "password"=> bcrypt("M4rc0nd35"),
        ]);

        $defaultColumns = [
            'A Fazer',
            'Em Progresso',
            'Concluído',
        ];

        foreach ($defaultColumns as $index => $columnName) {
            $column = Column::create([
                'user_id' => $user->id,
                'name' => $columnName,
                'position' => $index,
            ]);

            $taskCount = rand(3, 5);
            for ($i = 0; $i < $taskCount; $i++) {
                Task::factory()->create([
                    'user_id'=> $user->id,
                    'column_id' => $column->id,
                    'position' => $i,
                ]);
            }
        }
    }
}
