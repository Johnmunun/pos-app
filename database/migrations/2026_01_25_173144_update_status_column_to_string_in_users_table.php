<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change the status column from boolean to string using raw SQL
        DB::statement('ALTER TABLE users MODIFY COLUMN status VARCHAR(255) DEFAULT "active"');
        
        // Update any existing boolean values to string values
        DB::statement("UPDATE users SET status = CASE WHEN status = 1 THEN 'active' ELSE 'inactive' END WHERE status IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to boolean - this is more complex, we'll set based on string values
        DB::statement("UPDATE users SET status = CASE WHEN status = 'active' OR status = 'pending' THEN 1 ELSE 0 END");
        DB::statement('ALTER TABLE users MODIFY COLUMN status BOOLEAN DEFAULT 1');
    }
};
