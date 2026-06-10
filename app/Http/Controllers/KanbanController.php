<?php

namespace App\Http\Controllers;

use App\Actions\ImportKanbanBoard;
use App\Http\Requests\ImportBoardRequest;
use App\Http\Requests\ReorderColumnsRequest;
use App\Http\Requests\ReorderTasksRequest;
use App\Http\Requests\StoreColumnRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Column;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

    public function storeColumn(StoreColumnRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
    public function destroyColumn(Column $column): RedirectResponse
    {
        Gate::authorize('delete', $column);

        $column->delete();

        return back();
    }

    /**
     * Cria um novo cartão de tarefa dentro de uma coluna específica.
     */
    public function storeTask(StoreTaskRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
    public function updateTask(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $validated = $request->validated();

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
    public function destroyTask(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return back();
    }

    /**
     * Atualiza as posições e colunas de múltiplos cards simultaneamente após o drag-and-drop.
     * Esta rota será chamada assincronamente pelo front-end no Vue.
     */
    public function reorderTasks(ReorderTasksRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Executa as atualizações dentro de uma transação de banco de dados por segurança e performance
        DB::transaction(function () use ($validated, $request) {
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
    public function reorderColumns(ReorderColumnsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
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
    public function archiveTask(Task $task): RedirectResponse
    {
        Gate::authorize('archive', $task);

        $task->update(['is_archived' => true]);

        return back();
    }

    /**
     * Restaura uma tarefa arquivada.
     */
    public function restoreTask(Task $task): RedirectResponse
    {
        Gate::authorize('restore', $task);

        $task->update(['is_archived' => false]);

        return back();
    }

    /**
     * Importa o quadro inteiro a partir de um arquivo JSON.
     */
    public function importBoard(ImportBoardRequest $request, ImportKanbanBoard $importer): RedirectResponse
    {
        $file = $request->file('file');
        if (! $file) {
            return back()->withErrors(['file' => 'Arquivo não encontrado.']);
        }

        $filePath = $file->getRealPath();
        if ($filePath === false) {
            return back()->withErrors(['file' => 'Caminho do arquivo inválido.']);
        }

        $json = file_get_contents($filePath);
        if ($json === false) {
            return back()->withErrors(['file' => 'Não foi possível ler o conteúdo do arquivo.']);
        }

        $data = json_decode($json, true);

        if (! $data || ! is_array($data)) {
            return back()->withErrors(['file' => 'Arquivo JSON inválido.']);
        }

        $importer->execute($request->user(), $data);

        return back();
    }
}
