<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('device');
            $table->string('ip_address')->nullable();
            $table->string('issue');
            $table->enum('status', ['Critical', 'Warning', 'Monitoring', 'Info'])->default('Info');
            $table->string('duration')->nullable();          // e.g. "1h 12m"
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();    // null = masih active
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
