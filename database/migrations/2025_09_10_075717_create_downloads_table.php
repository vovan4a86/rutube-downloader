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
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('video_id')->nullable();
            $table->string('title')->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->string('status')->default('pending');
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
