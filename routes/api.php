<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Task;
use App\Models\ChecklistItem;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:web');

// Rotas para tarefas
Route::get('/tasks/{task}', function (Task $task) {
    return response()->json([
        'id' => $task->id,
        'title' => $task->title,
        'description' => $task->description,
        'status' => $task->status,
        'priority' => $task->priority,
        'due_date' => $task->due_date,
        'checklist' => $task->checklistItems->map(function ($item) {
            return [
                'id' => $item->id,
                'content' => $item->content,
                'is_completed' => $item->is_completed,
            ];
        })
    ]);
})->middleware('auth:web');

// Rota para atualizar item do checklist
Route::patch('/tasks/{task}/checklist/{index}', function (Task $task, $index) {
    $checklistItem = $task->checklistItems->get($index);
    
    if (!$checklistItem) {
        return response()->json(['error' => 'Item não encontrado'], 404);
    }
    
    $checklistItem->update([
        'is_completed' => request('completed')
    ]);
    
    return response()->json([
        'success' => true,
        'title' => $task->title
    ]);
})->middleware('auth:web'); 