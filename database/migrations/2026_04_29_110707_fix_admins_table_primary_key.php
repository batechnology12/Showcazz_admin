<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixAdminsTablePrimaryKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Identify and fix duplicate IDs in admins table
        $admins = DB::table('admins')->orderBy('created_at', 'asc')->get();
        $id = 1;
        foreach ($admins as $admin) {
            DB::table('admins')
                ->where('email', $admin->email)
                ->update(['id' => $id]);
            $id++;
        }

        // 2. Add Primary Key constraint
        Schema::table('admins', function (Blueprint $table) {
            DB::statement('ALTER TABLE admins ADD PRIMARY KEY (id)');
        });

        // 3. Sync the sequence for auto-increment
        $maxId = DB::table('admins')->max('id') ?: 0;
        DB::statement("SELECT setval('admins_id_seq', $maxId)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admins', function (Blueprint $table) {
            DB::statement('ALTER TABLE admins DROP CONSTRAINT admins_pkey');
        });
    }
}
