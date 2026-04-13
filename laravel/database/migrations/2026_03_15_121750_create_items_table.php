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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('item_name', 255);
            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('found_location', 255)->nullable();
            $table->string('storage_location', 255)->nullable();
            $table->date('found_date')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('status', 50)->default('Published');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
