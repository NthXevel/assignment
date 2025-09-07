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
        Schema::create('stock_requests', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique(); // encrypted token for this request
            $table->foreignId('requesting_branch_id')->constrained('branches')->onDelete('cascade'); // branch that requests stock
            $table->foreignId('supplying_branch_id')->nullable()->constrained('branches')->onDelete('set null'); // assigned later
            $table->enum('status', ['pending', 'assigned', 'approved', 'completed', 'cancelled'])->default('pending'); // flow status
            $table->text('notes')->nullable(); // optional notes for the request
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_requests');
    }
};
