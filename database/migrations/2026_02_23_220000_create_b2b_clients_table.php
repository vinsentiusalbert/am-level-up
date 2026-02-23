<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('company_name');
            $table->string('customer_phone', 30);
            $table->string('customer_email');
            $table->string('customer_name')->nullable();
            $table->string('sector')->nullable();
            $table->string('myads_account');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->unique(['user_id', 'myads_account']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_clients');
    }
};
