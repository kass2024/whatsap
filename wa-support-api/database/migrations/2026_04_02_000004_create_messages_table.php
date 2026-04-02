<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type', 16)->index();
            $table->string('message_type', 32)->index();
            $table->text('content')->nullable();
            $table->string('media_disk', 32)->nullable();
            $table->string('media_path', 1024)->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->string('file_name', 512)->nullable();
            $table->string('wa_message_id', 128)->nullable()->unique();
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('read_at_agent')->nullable()->index();
            $table->string('template_name', 256)->nullable();
            $table->string('template_language', 16)->nullable();
            $table->json('template_components')->nullable();
            $table->json('meta_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
