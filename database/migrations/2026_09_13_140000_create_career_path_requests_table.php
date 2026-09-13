<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_path_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('requested_path');
            $table->string('status', 32)->default('pending');
            $table->timestamps();

            $table->index(['requested_path', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_path_requests');
    }
};
