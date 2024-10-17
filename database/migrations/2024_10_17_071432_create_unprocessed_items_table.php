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
        Schema::create('unprocessed_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('log_id');
            $table->string('file');
            $table->string('email');
            $table->text('reason_for_failure');
            $table->boolean('reprocess_attempted')->default(false);
            $table->timestamps();

            $table->foreign('log_id')->references('id')->on('logs')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unprocessed_items');
    }
};
