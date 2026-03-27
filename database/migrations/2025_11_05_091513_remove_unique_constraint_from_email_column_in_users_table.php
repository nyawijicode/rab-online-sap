<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueConstraintFromEmailColumnInUsersTable extends Migration
{
    // /**
    //  * Run the migrations.
    //  *
    //  * @return void
    //  */
    // public function up()
    // {
    //     // Menghapus unique constraint dari kolom email
    //     Schema::table('users', function (Blueprint $table) {
    //         $table->dropUnique('users_email_unique'); // Hapus constraint unik berdasarkan nama index default
    //     });
    // }

    // /**
    //  * Reverse the migrations.
    //  *
    //  * @return void
    //  */
    // public function down()
    // {
    //     // Jika ingin mengembalikan perubahan ini, tambahkan lagi unique constraint pada kolom email
    //     Schema::table('users', function (Blueprint $table) {
    //         $table->unique('email');
    //     });
    // }
}
