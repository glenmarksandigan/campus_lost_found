<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_id', 30)->nullable()->after('contact_number');
            $table->string('address', 255)->nullable()->after('student_id');
            $table->string('zipcode', 10)->nullable()->after('address');
            $table->string('organizer_role', 50)->nullable()->after('zipcode');
            $table->boolean('can_edit')->default(true)->after('organizer_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['student_id', 'address', 'zipcode', 'organizer_role', 'can_edit']);
        });
    }
};
