<?php

use App\Models\Column;
use App\Models\Task;
use App\Models\User;

test('guests are redirected to the login page when archiving a task', function () {
    $user = User::factory()->create();
    $column = Column::create([
        'user_id' => $user->id,
        'name' => 'A Fazer',
        'position' => 0,
    ]);
    $task = Task::create([
        'user_id' => $user->id,
        'column_id' => $column->id,
        'title' => 'Tarefa a Arquivar',
        'priority' => 'low',
    ]);

    $this->put(route('tasks.archive', $task))
        ->assertRedirect(route('login'));
});

test('authenticated users can archive a task', function () {
    $user = User::factory()->create();
    $column = Column::create([
        'user_id' => $user->id,
        'name' => 'A Fazer',
        'position' => 0,
    ]);
    $task = Task::create([
        'user_id' => $user->id,
        'column_id' => $column->id,
        'title' => 'Tarefa a Arquivar',
        'priority' => 'low',
    ]);

    $this->actingAs($user);

    $response = $this->put(route('tasks.archive', $task));

    $response->assertRedirect();
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'is_archived' => true,
    ]);
});

test('authenticated users can restore an archived task', function () {
    $user = User::factory()->create();
    $column = Column::create([
        'user_id' => $user->id,
        'name' => 'A Fazer',
        'position' => 0,
    ]);
    $task = Task::create([
        'user_id' => $user->id,
        'column_id' => $column->id,
        'title' => 'Tarefa Arquivada',
        'priority' => 'medium',
        'is_archived' => true,
    ]);

    $this->actingAs($user);

    $response = $this->put(route('tasks.restore', $task));

    $response->assertRedirect();
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'is_archived' => false,
    ]);
});

test('users cannot archive tasks belonging to other users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $column = Column::create([
        'user_id' => $user1->id,
        'name' => 'A Fazer',
        'position' => 0,
    ]);
    $task = Task::create([
        'user_id' => $user1->id,
        'column_id' => $column->id,
        'title' => 'Tarefa do User 1',
        'priority' => 'high',
    ]);

    $this->actingAs($user2);

    $response = $this->put(route('tasks.archive', $task));

    $response->assertStatus(403);
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'is_archived' => false,
    ]);
});
