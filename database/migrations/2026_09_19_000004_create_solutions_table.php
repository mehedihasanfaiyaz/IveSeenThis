<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->longText('description');
            $table->timestamps();

            $table->index(['user_id', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solutions');
    }
};
