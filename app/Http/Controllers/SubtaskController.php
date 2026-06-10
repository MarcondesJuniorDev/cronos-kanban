<?php

namespace App\Http\Controllers;

use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubtaskController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'title' => 'required|string|max:255',
        ]);

        // Garante que a tarefa pertence ao usuário logado
        /** @var Task $task */
        $task = $request->user()->tasks()->findOrFail($validated['task_id']);

        $maxPosition = $task->subtasks()->max('position');
        $nextPosition = is_null($maxPosition) ? 0 : $maxPosition + 1;

        $task->subtasks()->create([
            'title' => $validated['title'],
            'is_completed' => false,
            'position' => $nextPosition,
        ]);

        return back();
    }

    public function update(Request $request, Subtask $subtask): RedirectResponse
    {
        // Garante que a subtarefa pertence a uma tarefa do usuário logado
        if ($subtask->task->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'is_completed' => 'sometimes|required|boolean',
        ]);

        $subtask->update($validated);

        return back();
    }

    public function destroy(Subtask $subtask, Request $request): RedirectResponse
    {
        if ($subtask->task->user_id !== $request->user()->id) {
            abort(403);
        }

        $subtask->delete();

        return back();
    }
}
