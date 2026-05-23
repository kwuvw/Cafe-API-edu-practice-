<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('work_shifts')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `work_shifts` MODIFY `end` DATETIME NULL');
            DB::statement('ALTER TABLE `work_shifts` MODIFY `active` TINYINT(1) NOT NULL DEFAULT 1');

            return;
        }

        Schema::table('work_shifts', function (Blueprint $table) {
            $table->dateTime('end')->nullable()->change();
            $table->boolean('active')->default(true)->change();
        });
    }

    public function down(): void
    {
        // Keeping the rollback empty avoids forcing non-null end values into historical data.
    }
};
