<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // Add index for name searches
            $table->string('sku')->unique(); // Ensure unique SKU with index
            $table->integer('quantity')->index(); // Index for quantity-based queries
            $table->decimal('price', 10, 2)->index(); // Index for price-based filtering
            $table->timestamps();

            // Composite index for common query patterns
            $table->index(['quantity', 'price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
}
