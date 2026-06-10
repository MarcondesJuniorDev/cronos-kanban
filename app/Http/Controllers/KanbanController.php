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
        // Busca as colunas do usuário logado trazendo junto suas respectivas tarefas ordenadas por posição
        $columns = $request->user()->columns()
            ->orderBy('position')
            ->with(['tasks' => function ($query) {
                $query->orderBy('position');
            }])
            ->get();

        // Renderiza o componente Vue "Dashboard" injetando a coleção de colunas como prop
        return Inertia::render('Dashboard', [
            'columns' => $columns,
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
        ]);

        // Garante que a coluna informada realmente pertence ao usuário logado
        $column = $request->user()->columns()->findOrFail($validated['column_id']);

        // Pega a maior posição atual e soma 1. Evita falhas na ordenação.
        $maxPosition = $column->tasks()->max('position');
        $nextPosition = is_null($maxPosition) ? 0 : $maxPosition + 1;

        Task::create([
            'user_id' => $request->user()->id,
            'column_id' => $column->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'],
            'position' => $nextPosition,
        ]);

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
        ]);

        $task->update($validated);

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
}
