<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ImportKanbanBoard
{
    /**
     * Import the board data for the given user.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): void
    {
        DB::transaction(function () use ($data, $user) {
            // Limpa dados existentes do usuário
            $user->columns()->delete();
            $user->tags()->delete();

            // Recria as etiquetas primeiro, guardando mapa de IDs antigos para novos
            $tagIdMap = [];
            if (! empty($data['tags']) && is_array($data['tags'])) {
                foreach ($data['tags'] as $tagData) {
                    if (! is_array($tagData)) {
                        continue;
                    }
                    $newTag = $user->tags()->create([
                        'name' => $tagData['name'],
                        'color' => $tagData['color'],
                    ]);
                    $tagIdMap[$tagData['id']] = $newTag->id;
                }
            }

            // Recria as colunas e tarefas
            if (! empty($data['columns']) && is_array($data['columns'])) {
                foreach ($data['columns'] as $colData) {
                    if (! is_array($colData)) {
                        continue;
                    }
                    $column = $user->columns()->create([
                        'name' => $colData['name'],
                        'position' => $colData['position'],
                    ]);

                    if (! empty($colData['tasks']) && is_array($colData['tasks'])) {
                        foreach ($colData['tasks'] as $taskData) {
                            if (! is_array($taskData)) {
                                continue;
                            }
                            /** @var Task $task */
                            $task = Task::create([
                                'user_id' => $user->id,
                                'column_id' => $column->id,
                                'title' => $taskData['title'],
                                'description' => $taskData['description'] ?? null,
                                'priority' => $taskData['priority'],
                                'due_date' => $taskData['due_date'] ?? null,
                                'position' => $taskData['position'],
                                'is_archived' => $taskData['is_archived'] ?? false,
                            ]);

                            if (! empty($taskData['subtasks']) && is_array($taskData['subtasks'])) {
                                foreach ($taskData['subtasks'] as $subData) {
                                    if (! is_array($subData)) {
                                        continue;
                                    }
                                    $task->subtasks()->create([
                                        'title' => $subData['title'],
                                        'is_completed' => $subData['is_completed'] ?? false,
                                        'position' => $subData['position'],
                                    ]);
                                }
                            }

                            if (! empty($taskData['tags']) && is_array($taskData['tags'])) {
                                $newTagIds = [];
                                foreach ($taskData['tags'] as $oldTag) {
                                    if (is_array($oldTag) && isset($oldTag['id']) && isset($tagIdMap[$oldTag['id']])) {
                                        $newTagIds[] = $tagIdMap[$oldTag['id']];
                                    }
                                }
                                $task->tags()->sync($newTagIds);
                            }
                        }
                    }
                }
            }
        });
    }
}
