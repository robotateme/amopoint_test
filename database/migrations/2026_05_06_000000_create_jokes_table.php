<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jokes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('external_id');
            $table->string('type', 80);
            $table->text('setup');
            $table->text('punchline');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jokes');
    }
};
