<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection($this->connection())->create($this->table(), function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(false);
            $table->string('version')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection())->dropIfExists($this->table());
    }

    private function table(): string
    {
        return (string) config('laranail.package-management.activation.table', 'laranail_extension_states');
    }

    private function connection(): ?string
    {
        $connection = config('laranail.package-management.activation.connection');

        return $connection === null ? null : (string) $connection;
    }
};
