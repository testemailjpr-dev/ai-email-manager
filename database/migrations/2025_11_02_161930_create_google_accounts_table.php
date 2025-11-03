<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('google_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->json('google_token')->nullable(); // store full token JSON
            $table->string('google_refresh_token')->nullable();
            $table->boolean('primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_accounts');
    }
};
