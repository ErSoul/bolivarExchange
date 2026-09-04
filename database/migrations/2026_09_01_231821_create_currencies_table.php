<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\General\Currency;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->enum('base_asset', Currency::cases())->comment('Base currency asset');
            $table->decimal('value', 10, 2)->comment('Value in base asset relative to target asset');
            $table->enum('target_asset', Currency::cases())->comment('Target currency asset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
