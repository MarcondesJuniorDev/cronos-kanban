<?php

use App\Models\Column;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;

test('guests are redirected to the login page when storing a tag', function () {
    $this->post(route('tags.store'), ['name' => 'Test', 'color' => 'blue'])
        ->assertRedirect(route('login'));
});

test('authenticated users can create a tag', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('tags.store'), [
        'name' => 'Novo Bug',
        'color' => 'red',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('tags', [
        'user_id' => $user->id,
        'name' => 'Novo Bug',
        'color' => 'red',
    ]);
});

test('authenticated users can delete their own tag', function () {
    $user = User::factory()->create();
    $tag = Tag::create([
        'user_id' => $user->id,
        'name' => 'A Deletar',
        'color' => 'green',
    ]);

    $this->actingAs($user);

    $response = $this->delete(route('tags.destroy', $tag));

    $response->assertRedirect();
    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});

test('users cannot delete tags belonging to other users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $tag = Tag::create([
        'user_id' => $user1->id,
        'name' => 'Tag User 1',
        'color' => 'green',
    ]);

    $this->actingAs($user2);

    $response = $this->delete(route('tags.destroy', $tag));

    $response->assertStatus(403);
    $this->assertDatabaseHas('tags', ['id' => $tag->id]);
});

test('dashboard auto-seeds default tags if user has none', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->assertDatabaseEmpty('tags');

    $this->get(route('dashboard'))->assertOk();

    $this->assertDatabaseHas('tags', ['user_id' => $user->id, 'name' => 'Bug', 'color' => 'red']);
    $this->assertDatabaseHas('tags', ['user_id' => $user->id, 'name' => 'Recurso', 'color' => 'blue']);
    $this->assertDatabaseCount('tags', 5);
});

test('users can associate tags when creating a task', function () {
    $user = User::factory()->create();
    $column = Column::create([
        'user_id' => $user->id,
        'name' => 'A Fazer',
        'position' => 0,
    ]);
    $tag = Tag::create([
        'user_id' => $user->id,
        'name' => 'Urgente',
        'color' => 'red',
    ]);

    $this->actingAs($user);

    $response = $this->post(route('tasks.store'), [
        'column_id' => $column->id,
        'title' => 'Nova Tarefa Com Tag',
        'priority' => 'high',
        'tag_ids' => [$tag->id],
    ]);

    $response->assertRedirect();
    $task = Task::first();
    expect($task->tags)->toHaveCount(1);
    expect($task->tags->first()->id)->toBe($tag->id);
});

test('users cannot associate tags belonging to other users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $column = Column::create([
        'user_id' => $user1->id,
        'name' => 'A Fazer',
        'position' => 0,
    ]);
    $tag = Tag::create([
        'user_id' => $user2->id,
        'name' => 'Tag do User 2',
        'color' => 'blue',
    ]);

    $this->actingAs($user1);

    $response = $this->post(route('tasks.store'), [
        'column_id' => $column->id,
        'title' => 'Tarefa Maliciosa',
        'priority' => 'low',
        'tag_ids' => [$tag->id],
    ]);

    $response->assertSessionHasErrors(['tag_ids.0']);
});
