<?php

namespace App\Http\Controllers;

use App\Models\Column;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KanbanController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Auto-seed default tags if user has none
        if ($user->tags()->count() === 0) {
            $defaultTags = [
                ['name' => 'Bug', 'color' => 'red'],
                ['name' => 'Recurso', 'color' => 'blue'],
                ['name' => 'Melhoria', 'color' => 'green'],
                ['name' => 'Urgente', 'color' => 'amber'],
                ['name' => 'Refatoração', 'color' => 'purple'],
            ];
            foreach ($defaultTags as $dt) {
                $user->tags()->create($dt);
            }
        }

        $tags = $user->tags()->get();

        // Busca as colunas do usuário logado trazendo junto suas respectivas tarefas ordenadas por posição e não arquivadas
        $columns = $user->columns()
            ->orderBy('position')
            ->with(['tasks' => function ($query) {
                $query->where('is_archived', false)->orderBy('position')->with(['subtasks', 'tags']);
            }])
            ->get();

        // Busca as tarefas arquivadas
        $archivedTasks = $user->tasks()
            ->where('is_archived', true)
            ->orderByDesc('updated_at')
            ->with(['subtasks', 'tags'])
            ->get();

        // Renderiza o componente Vue "Dashboard" injetando a coleção de colunas como prop
        return Inertia::render('Dashboard', [
            'columns' => $columns,
            'tags' => $tags,
            'archivedTasks' => $archivedTasks,
        ]);
    }

    public function storeColumn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:55',
        ]);

        // Pega a maior posição atual e soma 1. Evita posições duplicadas caso alguma coluna tenha sido apagada.
        $maxPosition = $request->user()->columns()->max('position');
        $nextPosition = is_null($maxPosition) ? 0 : $maxPosition + 1;

        $request->user()->columns()->create([
            'name' => $validated['name'],
            'position' => $nextPosition,
        ]);

        return back(); // Atualiza a página via Inertia mantendo o estado
    }

    /**
     * Exclui uma coluna e todas as suas tarefas vinculadas (Cascade).
     */
    public function destroyColumn(Column $column, Request $request): RedirectResponse
    {
        // Garante que o usuário só pode deletar colunas que pertencem a ele
        if ($column->user_id !== $request->user()->id) {
            abort(403);
        }

        $column->delete();

        return back();
    }

    /**
     * Cria um novo cartão de tarefa dentro de uma coluna específica.
     */
    public function storeTask(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'column_id' => 'required|exists:columns,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => [
                'required',
                Rule::exists('tags', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        // Garante que a coluna informada realmente pertence ao usuário logado
        /** @var Column $column */
        $column = $request->user()->columns()->findOrFail($validated['column_id']);

        // Pega a maior posição atual e soma 1. Evita falhas na ordenação.
        $maxPosition = $column->tasks()->max('position');
        $nextPosition = is_null($maxPosition) ? 0 : $maxPosition + 1;

        /** @var Task $task */
        $task = Task::create([
            'user_id' => $request->user()->id,
            'column_id' => $column->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
            'position' => $nextPosition,
        ]);

        if (! empty($validated['tag_ids'])) {
            $task->tags()->sync($validated['tag_ids']);
        }

        return back();
    }

    /**
     * Atualiza os dados de texto e informações de uma tarefa existente.
     */
    public function updateTask(Request $request, Task $task): RedirectResponse
    {
        if ($task->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => [
                'required',
                Rule::exists('tags', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
        ]);

        $task->tags()->sync($validated['tag_ids'] ?? []);

        return back();
    }

    /**
     * Exclui um cartão de tarefa.
     */
    public function destroyTask(Task $task, Request $request): RedirectResponse
    {
        if ($task->user_id !== $request->user()->id) {
            abort(403);
        }

        $task->delete();

        return back();
    }

    /**
     * Atualiza as posições e colunas de múltiplos cards simultaneamente após o drag-and-drop.
     * Esta rota será chamada assincronamente pelo front-end no Vue.
     */
    public function reorderTasks(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:tasks,id',
            'tasks.*.column_id' => [
                'required',
                Rule::exists('columns', 'id')->where('user_id', $request->user()->id), // Segurança: A coluna de destino DEVE ser do usuário
            ],
            'tasks.*.position' => 'required|integer',
        ]);

        // Executa as atualizações dentro de uma transação de banco de dados por segurança e performance
        \DB::transaction(function () use ($validated, $request) {
            foreach ($validated['tasks'] as $taskData) {
                // Atualiza apenas se a tarefa pertencer ao usuário autenticado
                Task::where('id', $taskData['id'])
                    ->where('user_id', $request->user()->id)
                    ->update([
                        'column_id' => $taskData['column_id'],
                        'position' => $taskData['position'],
                    ]);
            }
        });

        return back();
    }

    /**
     * Atualiza as posições de múltiplas colunas simultaneamente após o drag-and-drop.
     */
    public function reorderColumns(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'columns' => 'required|array',
            'columns.*.id' => [
                'required',
                Rule::exists('columns', 'id')->where('user_id', $request->user()->id),
            ],
            'columns.*.position' => 'required|integer',
        ]);

        \DB::transaction(function () use ($validated, $request) {
            foreach ($validated['columns'] as $columnData) {
                Column::where('id', $columnData['id'])
                    ->where('user_id', $request->user()->id)
                    ->update([
                        'position' => $columnData['position'],
                    ]);
            }
        });

        return back();
    }

    /**
     * Arquiva uma tarefa.
     */
    public function archiveTask(Task $task, Request $request): RedirectResponse
    {
        if ($task->user_id !== $request->user()->id) {
            abort(403);
        }

        $task->update(['is_archived' => true]);

        return back();
    }

    /**
     * Restaura uma tarefa arquivada.
     */
    public function restoreTask(Task $task, Request $request): RedirectResponse
    {
        if ($task->user_id !== $request->user()->id) {
            abort(403);
        }

        $task->update(['is_archived' => false]);

        return back();
    }

    /**
     * Importa o quadro inteiro a partir de um arquivo JSON.
     */
    public function importBoard(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:json',
        ]);

        $json = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($json, true);

        if (! $data || ! is_array($data)) {
            return back()->withErrors(['file' => 'Arquivo JSON inválido.']);
        }

        \DB::transaction(function () use ($data, $request) {
            $user = $request->user();

            // Limpa dados existentes do usuário
            $user->columns()->delete();
            $user->tags()->delete();

            // Recria as etiquetas primeiro, guardando mapa de IDs antigos para novos
            $tagIdMap = [];
            if (! empty($data['tags'])) {
                foreach ($data['tags'] as $tagData) {
                    $newTag = $user->tags()->create([
                        'name' => $tagData['name'],
                        'color' => $tagData['color'],
                    ]);
                    $tagIdMap[$tagData['id']] = $newTag->id;
                }
            }

            // Recria as colunas e tarefas
            if (! empty($data['columns'])) {
                foreach ($data['columns'] as $colData) {
                    $column = $user->columns()->create([
                        'name' => $colData['name'],
                        'position' => $colData['position'],
                    ]);

                    if (! empty($colData['tasks'])) {
                        foreach ($colData['tasks'] as $taskData) {
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

                            if (! empty($taskData['subtasks'])) {
                                foreach ($taskData['subtasks'] as $subData) {
                                    $task->subtasks()->create([
                                        'title' => $subData['title'],
                                        'is_completed' => $subData['is_completed'] ?? false,
                                        'position' => $subData['position'],
                                    ]);
                                }
                            }

                            if (! empty($taskData['tags'])) {
                                $newTagIds = [];
                                foreach ($taskData['tags'] as $oldTag) {
                                    if (isset($tagIdMap[$oldTag['id']])) {
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

        return back();
    }
}
