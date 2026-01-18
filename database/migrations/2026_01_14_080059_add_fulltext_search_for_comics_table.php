<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 8. ПОЛНОТЕКСТОВЫЙ ПОИСК (только для PostgreSQL)
        if (!app()->runningUnitTests() && config('database.default') !== 'sqlite') {
                DB::statement("
                    CREATE INDEX comics_title_description_fts
                    ON comics USING GIN(
                        to_tsvector('russian',
                            coalesce(title, '') || ' ' || coalesce(description, '')
                        )
                    )
                ");
            }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
