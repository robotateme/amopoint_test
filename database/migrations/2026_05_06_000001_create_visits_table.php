<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table): void {
            $table->id();
            $table->string('fingerprint', 128);
            $table->string('ip', 45);
            $table->string('city')->default('Unknown');
            $table->string('device', 40)->default('unknown');
            $table->text('user_agent')->nullable();
            $table->text('page_url')->nullable();
            $table->text('referrer')->nullable();
            $table->timestamps();

            $table->index(['fingerprint', 'created_at']);
            $table->index(['city', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
