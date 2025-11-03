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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
			$table->unsignedBigInteger('user_id');
			$table->unsignedBigInteger('category_id')->nullable();
			$table->string('gmail_id')->unique();
			$table->string('from')->nullable();
			$table->string('subject')->nullable();
			$table->longText('body')->nullable();
			$table->text('summary')->nullable();
			$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
