<?php

use App\Http\Controllers\KanbanController;
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
});

require __DIR__.'/settings.php';
