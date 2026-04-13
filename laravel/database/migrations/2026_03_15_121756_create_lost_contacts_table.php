<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('lost_reports')->onDelete('cascade');
            $table->string('finder_name');
            $table->string('finder_contact')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_contacts');
    }
};
