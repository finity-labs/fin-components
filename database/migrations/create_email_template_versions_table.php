<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\FinityLabs\FinMail\Models\EmailTemplate::class)
                ->constrained('email_templates')
                ->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('subject');
            $table->json('preheader')->nullable();
            $table->json('body');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['email_template_id', 'version']);
            $table->index(['email_template_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_template_versions');
    }
};
