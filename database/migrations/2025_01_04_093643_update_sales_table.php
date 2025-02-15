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
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('employee_id')->constrained('employees', 'id_employee')->onDelete('cascade');
            $table->decimal('shipping_fee_discount')->after('estimated_shipping_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('employee_id');
            $table->dropColumn('shipping_fee_discount');
        });
    }
};
