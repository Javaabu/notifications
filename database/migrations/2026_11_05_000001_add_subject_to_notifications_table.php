<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Javaabu\Notifications\Notifications;

/**
 * The model a notification is about (`subject`), on new and existing notifications tables
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn(Notifications::table(), 'subject_type')) {
            return;
        }

        Schema::table(Notifications::table(), function (Blueprint $table) {
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn(Notifications::table(), 'subject_type')) {
            return;
        }

        Schema::table(Notifications::table(), function (Blueprint $table) {
            $table->dropIndex(['subject_type', 'subject_id']);
            $table->dropColumn(['subject_type', 'subject_id']);
        });
    }
};
