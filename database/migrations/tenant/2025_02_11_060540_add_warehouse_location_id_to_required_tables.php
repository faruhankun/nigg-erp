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
        Schema::table('outbound_products', function (Blueprint $table) {
            $table->foreignId('warehouse_location_id')->constrained('warehouse_locations')->nullable();
        });

        Schema::table('inbound_products', function (Blueprint $table) {
            $table->foreignId('warehouse_location_id')->constrained('warehouse_locations')->nullable();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->foreignId('warehouse_location_id')->constrained('warehouse_locations')->nullable();
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('warehouse_location_id')->constrained('warehouse_locations')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outbound_products', function (Blueprint $table) {
            $table->dropForeign('outbound_products_warehouse_location_id_foreign');
            $table->dropColumn('warehouse_location_id');
        });

        Schema::table('inbound_products', function (Blueprint $table) {
            $table->dropForeign('inbound_products_warehouse_location_id_foreign');
            $table->dropColumn('warehouse_location_id');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropForeign('inventories_warehouse_location_id_foreign');
            $table->dropColumn('warehouse_location_id');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign('inventory_movements_warehouse_location_id_foreign');
            $table->dropColumn('warehouse_location_id');
        });
    }
};
