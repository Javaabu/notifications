<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Javaabu\Notifications\Notifications;

/**
 * Laravel's notifications table, when the app doesn't have one yet
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable(Notifications::table())) {
            return;
        }

        Schema::create(Notifications::table(), function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $this->morphs($table, 'notifiable', config('notifications.notifiable_key_type', 'integer'));
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Notifications::table());
    }

    protected function morphs(Blueprint $table, string $name, string $key_type): void
    {
        match ($key_type) {
            'uuid' => $table->uuidMorphs($name),
            'ulid' => $table->ulidMorphs($name),
            'string' => $this->stringMorphs($table, $name),
            default => $table->morphs($name),
        };
    }

    protected function stringMorphs(Blueprint $table, string $name): void
    {
        $table->string("{$name}_type");
        $table->string("{$name}_id");
        $table->index(["{$name}_type", "{$name}_id"]);
    }
};
