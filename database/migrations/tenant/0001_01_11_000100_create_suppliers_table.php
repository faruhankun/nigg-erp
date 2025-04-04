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
        Schema::create('suppliers', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Columns
            $table->string('name');
            $table->json('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();

            // Foreign keys
            $table->string('entity_type')->default('PERS');                  // PERS / COMP
            $table->unsignedBigInteger('entity_id')->nullable();        

            // Timestamps
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
