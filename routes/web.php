<?php

use App\Http\Controllers\KanbanController;
use App\Http\Controllers\SubtaskController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [KanbanController::class, 'index'])->name('dashboard');

    // Rotas de gerenciamento de Colunas
    Route::post('/columns', [KanbanController::class, 'storeColumn'])->name('columns.store');
    Route::delete('/columns/{column}', [KanbanController::class, 'destroyColumn'])->name('columns.destroy');
    Route::put('/columns/reorder', [KanbanController::class, 'reorderColumns'])->name('columns.reorder');

    // Rotas de gerenciamento de Tarefas
    Route::post('/tasks', [KanbanController::class, 'storeTask'])->name('tasks.store');

    // Rota crucial: Atualiza as posições dos cards ao arrastar e soltar (Drag and Drop)
    // DEVE vir antes de /tasks/{task} para evitar conflito de Route Model Binding
    Route::put('/tasks/reorder', [KanbanController::class, 'reorderTasks'])->name('tasks.reorder');

    Route::put('/tasks/{task}', [KanbanController::class, 'updateTask'])->name('tasks.update');
    Route::delete('/tasks/{task}', [KanbanController::class, 'destroyTask'])->name('tasks.destroy');
    Route::put('/tasks/{task}/archive', [KanbanController::class, 'archiveTask'])->name('tasks.archive');
    Route::put('/tasks/{task}/restore', [KanbanController::class, 'restoreTask'])->name('tasks.restore');

    // Rotas de gerenciamento de Subtarefas (Checklist)
    Route::post('/subtasks', [SubtaskController::class, 'store'])->name('subtasks.store');
    Route::put('/subtasks/{subtask}', [SubtaskController::class, 'update'])->name('subtasks.update');
    Route::delete('/subtasks/{subtask}', [SubtaskController::class, 'destroy'])->name('subtasks.destroy');

    // Rotas de gerenciamento de Etiquetas / Tags
    Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
});

require __DIR__.'/settings.php';
