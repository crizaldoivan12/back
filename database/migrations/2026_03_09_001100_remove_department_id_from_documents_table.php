<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

   public function up(): void
{
    if (!Schema::hasColumn('documents', 'department_id')) {
        return;
    }

    // For SQLite compatibility, use table recreation
    if (DB::getDriverName() === 'sqlite') {
        DB::statement('PRAGMA foreign_keys=OFF');
        
        try {
            // Create new table without department_id
            DB::statement('
                CREATE TABLE documents_new (
                    id INTEGER PRIMARY KEY,
                    date DATE NOT NULL,
                    encoded_by_id INTEGER NOT NULL,
                    type_of_document VARCHAR NOT NULL,
                    document_code VARCHAR NOT NULL UNIQUE,
                    document_number VARCHAR,
                    pay_claimant VARCHAR NOT NULL,
                    particular TEXT NOT NULL,
                    amount DECIMAL(15, 2) NOT NULL,
                    routed_department_id INTEGER,
                    status VARCHAR NOT NULL DEFAULT \'Pending\',
                    remarks TEXT,
                    date_out DATE,
                    inactive_alerted_at TIMESTAMP,
                    inactive_read_at TIMESTAMP,
                    inactive_reason TEXT,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    deleted_at TIMESTAMP,
                    FOREIGN KEY(encoded_by_id) REFERENCES users(id),
                    FOREIGN KEY(routed_department_id) REFERENCES departments(id),
                    UNIQUE(document_number)
                )
            ');
            
            // Copy data
            DB::statement('
                INSERT INTO documents_new
                (id, date, encoded_by_id, type_of_document, document_code, document_number, 
                 pay_claimant, particular, amount, routed_department_id, status, remarks, 
                 date_out, inactive_alerted_at, inactive_read_at, inactive_reason, 
                 created_at, updated_at, deleted_at)
                SELECT id, date, encoded_by_id, type_of_document, document_code, document_number,
                 pay_claimant, particular, amount, routed_department_id, status, remarks,
                 date_out, inactive_alerted_at, inactive_read_at, inactive_reason,
                 created_at, updated_at, deleted_at
                FROM documents
            ');
            
            // Drop old table and rename
            DB::statement('DROP TABLE documents');
            DB::statement('ALTER TABLE documents_new RENAME TO documents');
            
        } catch (\Exception $e) {
            DB::statement('DROP TABLE IF EXISTS documents_new');
            DB::statement('PRAGMA foreign_keys=ON');
            throw $e;
        }
        
        DB::statement('PRAGMA foreign_keys=ON');
    } else {
        Schema::table('documents', function (Blueprint $table) {
            try {
                $table->dropForeign(['department_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }

            $table->dropColumn('department_id');
        });
    }
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