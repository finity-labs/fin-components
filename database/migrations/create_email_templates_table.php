<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255)->unique();
            $table->json('name');
            $table->unsignedTinyInteger('category')->default(1)->index();
            $table->json('tags')->nullable();

            // Content (translatable — stored as JSON: {"en": "...", "hu": "..."})
            $table->json('subject');
            $table->json('preheader')->nullable();
            $table->json('body');
            $table->string('view_path', 255)->nullable();

            // Sender
            $table->json('from')->nullable();

            // Theme
            $table->foreignIdFor(\FinityLabs\FinMail\Models\EmailTheme::class)
                ->nullable()
                ->constrained('email_themes')
                ->nullOnDelete();

            // Token documentation
            $table->json('token_schema')->nullable();

            // State
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
