<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determina se o usuário pode ver a lista de projetos.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determina se o usuário pode ver um projeto específico.
     *
     * Regra: O usuário pode ver o projeto se ele for um membro desse projeto.
     */
    public function view(User $user, Project $project): bool
    {
        
        return $project->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determina se o usuário pode criar novos projetos.
     *
     * Regra: Qualquer usuário autenticado pode criar um novo projeto.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determina se o usuário pode atualizar um projeto (ex: mudar o nome).
     *
     * Regra: O usuário pode atualizar o projeto se ele for um membro
     * E se o seu papel ('role') na tabela pivô for 'admin'.
     */
    public function update(User $user, Project $project): bool
    {

        $member = $project->members()->where('user_id', $user->id)->first();

        if (!$member) {
            return false;
        }

        return $member->pivot->role === 'admin';
    }

    /**
     * Determina se o usuário pode apagar um projeto.
     *
     * Regra: Apenas um 'admin' do projeto pode apagá-lo.
     * Reutilizamos a lógica do método update() para manter o código limpo (DRY - Don't Repeat Yourself).
     */
    public function delete(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /**
     * Determina se o usuário pode convidar outros membros para o projeto.
     *
     * Regra: Apenas um 'admin' pode convidar.
     */
    public function inviteMember(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /**
     * Determina se o usuário pode remover outro membro do projeto.
     *
     * Regra: Apenas um 'admin' pode remover membros.
     */
    public function removeMember(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /**
     * Os métodos restore e forceDelete são para Soft Deletes.
     */
    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
}
