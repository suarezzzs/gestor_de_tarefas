<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Responsável
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('backlog'); // backlog, doing, review, done
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->date('due_date')->nullable(); // Data de entrega
            $table->integer('order')->default(0); // Para ordenar os cards na coluna
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
