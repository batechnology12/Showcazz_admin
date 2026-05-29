<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixRolesTablePrimaryKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Identify and fix duplicate IDs
        $roles = DB::table('roles')->orderBy('created_at', 'asc')->get();
        $id = 1;
        foreach ($roles as $role) {
            // Update the ID to be unique
            DB::table('roles')
                ->where('role_name', $role->role_name)
                ->where('created_at', $role->created_at)
                ->update(['id' => $id]);
            
            // Note: We are assuming that since duplicate IDs were possible,
            // we should re-assign them starting from 1.
            // Super Admin was created first, so it will get ID 1.
            // Monika was created later, so it will get ID 2.
            $id++;
        }

        // 2. Add Primary Key constraint
        // First ensure there are no duplicate IDs remaining (should be fixed above)
        Schema::table('roles', function (Blueprint $table) {
            // In PostgreSQL, we can add a primary key to an existing column
            DB::statement('ALTER TABLE roles ADD PRIMARY KEY (id)');
        });

        // 3. Sync the sequence for auto-increment
        $maxId = DB::table('roles')->max('id') ?: 0;
        $nextId = $maxId + 1;
        DB::statement("SELECT setval('roles_id_seq', $maxId)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            DB::statement('ALTER TABLE roles DROP CONSTRAINT roles_pkey');
        });
    }
}
