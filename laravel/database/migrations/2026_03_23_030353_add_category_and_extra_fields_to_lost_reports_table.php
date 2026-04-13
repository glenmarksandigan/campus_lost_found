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
        Schema::table('lost_reports', function (Blueprint $table) {
            $table->string('category')->nullable()->after('item_name');
            $table->string('extra_brand')->nullable();
            $table->string('extra_model')->nullable();
            $table->string('extra_color')->nullable();
            $table->string('extra_case')->nullable();
            $table->string('extra_contents')->nullable();
            $table->string('extra_material')->nullable();
            $table->string('extra_id_type')->nullable();
            $table->string('extra_id_name')->nullable();
            $table->string('extra_key_type')->nullable();
            $table->string('extra_keychain')->nullable();
            $table->string('extra_type')->nullable();
            $table->string('extra_serial')->nullable();
            $table->string('extra_size')->nullable();
            $table->string('extra_label')->nullable();
            $table->string('extra_title')->nullable();
            $table->string('extra_cover_color')->nullable();
            $table->string('extra_markings')->nullable();
            $table->string('extra_item_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lost_reports', function (Blueprint $table) {
            $table->dropColumn([
                'category', 'extra_brand', 'extra_model', 'extra_color', 'extra_case',
                'extra_contents', 'extra_material', 'extra_id_type', 'extra_id_name',
                'extra_key_type', 'extra_keychain', 'extra_type', 'extra_serial',
                'extra_size', 'extra_label', 'extra_title', 'extra_cover_color',
                'extra_markings', 'extra_item_type'
            ]);
        });
    }
};
