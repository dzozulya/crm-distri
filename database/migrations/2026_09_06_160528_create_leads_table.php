<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('managers')
                ->nullOnDelete();

            $table->string('status', 32);

            $table->timestamps();
        });

        DB::statement("
        CREATE INDEX idx_leads_unassigned_new
        ON leads (id)
        WHERE status = 'NEW'
          AND manager_id IS NULL
    ");

        DB::statement("
        CREATE INDEX idx_leads_manager_open
        ON leads (manager_id)
        WHERE status IN ('NEW', 'IN_PROGRESS')
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
