<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lost_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('item_name', 255);
            $table->text('description')->nullable();
            $table->string('last_seen_location', 255)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('status', 50)->default('Lost');
            $table->string('owner_name', 255)->nullable();
            $table->string('owner_contact', 50)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lost_reports');
    }
};
