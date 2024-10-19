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
        Schema::create('statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('visitors')->onDelete('cascade');
            $table->string('jyv')->nullable();
            $table->string('badmail_status')->nullable();
            $table->boolean('unsubscribe')->default(false);
            $table->dateTime('send_date');
            $table->dateTime('open_date')->nullable();
            $table->string('opens');
            $table->integer('viral_opens');
            $table->dateTime('click_date')->nullable();
            $table->string('clicks');
            $table->integer('viral_clicks');
            $table->string('links')->nullable();
            $table->string('ips');
            $table->string('browsers');
            $table->string('platforms');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics');
    }
};
