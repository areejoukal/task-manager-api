<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectTaskSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@taskmanager.test'],
            ['name' => 'Demo User', 'password' => bcrypt('password')]
        );

        $project = $user->projects()->create([
            'name' => 'مشروع تجريبي',
            'description' => 'بيانات للاختبار',
            'color' => '#3b82f6',
        ]);

        $statuses = ['pending', 'in_progress', 'completed'];
        $priorities = ['low', 'medium', 'high'];

        for ($i = 1; $i <= 15; $i++) {
            $project->tasks()->create([
                'user_id'    => $user->id,
                'title'      => "مهمة رقم {$i}",
                'status'     => $statuses[array_rand($statuses)],
                'priority'   => $priorities[array_rand($priorities)],
                'due_date'   => now()->addDays(rand(-5, 15)),
            ]);
        }
    }
}