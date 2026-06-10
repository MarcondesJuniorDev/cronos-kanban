<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

test('guests cannot import a board', function () {
    $this->post(route('board.import'), [
        'file' => UploadedFile::fake()->create('board.json', 100),
    ])->assertRedirect(route('login'));
});

test('authenticated users can import a valid board json', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $boardData = [
        'tags' => [
            ['id' => 1, 'name' => 'Imported Tag', 'color' => 'blue'],
        ],
        'columns' => [
            [
                'name' => 'Imported Col',
                'position' => 0,
                'tasks' => [
                    [
                        'title' => 'Imported Task',
                        'description' => 'Desc',
                        'priority' => 'high',
                        'due_date' => '2026-12-31',
                        'position' => 0,
                        'is_archived' => false,
                        'subtasks' => [
                            ['title' => 'Imported Sub', 'is_completed' => true, 'position' => 0],
                        ],
                        'tags' => [
                            ['id' => 1],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent('board.json', json_encode($boardData));

    $response = $this->post(route('board.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('columns', [
        'user_id' => $user->id,
        'name' => 'Imported Col',
    ]);

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Imported Task',
        'priority' => 'high',
        'due_date' => '2026-12-31',
    ]);

    $this->assertDatabaseHas('subtasks', [
        'title' => 'Imported Sub',
        'is_completed' => true,
    ]);

    $this->assertDatabaseHas('tags', [
        'user_id' => $user->id,
        'name' => 'Imported Tag',
        'color' => 'blue',
    ]);
});
