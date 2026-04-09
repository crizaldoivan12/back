<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

   public function up(): void
{
    Schema::table('documents', function (Blueprint $table) {

        // drop foreign key first
        $table->dropForeign(['department_id']);

        // then drop column
        $table->dropColumn('department_id');
    });
}
    public function down(): void
    {
        if (Schema::hasColumn('documents', 'department_id')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table) {

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->after('amount');

            $table->index('department_id');
            $table->index(['department_id', 'status']);
        });
    }
};