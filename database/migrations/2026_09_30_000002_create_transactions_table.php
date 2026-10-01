<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->string('transaction_number')->unique();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('customer')->default('Umum');
            $t->string('member_no')->nullable();
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('discount')->default(0);
            $t->unsignedBigInteger('tax')->default(0);
            $t->unsignedBigInteger('other_fee')->default(0);
            $t->unsignedBigInteger('grand_total');
            $t->unsignedBigInteger('paid_amount');
            $t->unsignedBigInteger('change_amount');
            $t->string('payment_method');
            $t->string('status')->default('paid');
            $t->timestamps();
        });

        Schema::create('transaction_details', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->string('product_code');
            $t->string('product_name');
            $t->unsignedBigInteger('price');
            $t->unsignedInteger('qty');
            $t->unsignedBigInteger('discount')->default(0);
            $t->unsignedBigInteger('subtotal');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
        Schema::dropIfExists('transactions');
    }
};
