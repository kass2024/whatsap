<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 32)->unique()->comment('Digits only, no + prefix');
            $table->string('customer_name')->nullable();
            $table->timestamp('last_customer_message_at')->nullable()->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('open')->index();
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_preview', 512)->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
