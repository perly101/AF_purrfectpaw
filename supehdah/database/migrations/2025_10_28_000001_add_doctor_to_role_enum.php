<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDoctorToRoleEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Only run when using MySQL / MariaDB where ENUM can be altered this way
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Add 'doctor' to the ENUM list for role. Preserve default 'user'.
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','user','clinic','doctor') NOT NULL DEFAULT 'user'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Remove 'doctor' from the ENUM list. If any rows currently have 'doctor', this will fail.
        // Consider manually converting any 'doctor' rows back to 'user' before rolling back.
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','user','clinic') NOT NULL DEFAULT 'user'");
    }
}
