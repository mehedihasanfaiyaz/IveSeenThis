<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->text('description');
            $table->enum('result', ['failed', 'worked', 'wrong'])->default('failed');
            $table->timestamps();

            $table->unique(['issue_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_attempts');
    }
};
