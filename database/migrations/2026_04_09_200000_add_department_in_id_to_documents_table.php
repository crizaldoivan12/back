<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'department_in_id')) {
                $table->foreignId('department_in_id')
                    ->nullable()
                    ->after('amount')
                    ->constrained('departments');
                $table->index('department_in_id', 'documents_department_in_perf_index');
            }
        });

        if (Schema::hasColumn('documents', 'department_in_id') && Schema::hasColumn('documents', 'routed_department_id')) {
            DB::table('documents')
                ->whereNull('department_in_id')
                ->update([
                    'department_in_id' => DB::raw('routed_department_id'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'department_in_id')) {
                $table->dropIndex('documents_department_in_perf_index');
                $table->dropConstrainedForeignId('department_in_id');
            }
        });
    }
};
