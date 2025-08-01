<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

// Rota para juntar-se a um projeto via link
Route::get('/join-project/{token}', function ($token) {
    $shareLink = \App\Models\ProjectShareLink::where('token', $token)
        ->where('is_active', true)
        ->where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        })
        ->first();

    if (!$shareLink) {
        return redirect()->route('dashboard')->with('error', 'Link de convite inválido ou expirado.');
    }

    $project = $shareLink->project;
    $user = Auth::user();

    // Verificar se o usuário já é membro
    if ($project->members()->where('user_id', $user->id)->exists()) {
        return redirect()->route('dashboard')->with('info', 'Você já é membro deste projeto.');
    }

    // Adicionar usuário ao projeto
    $project->members()->attach($user->id, ['role' => 'member']);

    return redirect()->route('dashboard')->with('success', "Você foi adicionado ao projeto '{$project->name}' com sucesso!");
})->middleware(['auth'])->name('join-project');
});

require __DIR__.'/auth.php';
