<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('users', function (Blueprint $table) {
        // Hapus email dan email_verified_at karena tidak ada di dataset
        $table->dropColumn(['email', 'email_verified_at']);
        // Tambahkan username yang unik
        $table->string('username')->unique()->after('name');
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->dropColumn('username');
    });
}
};
