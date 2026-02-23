<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_am_point_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('period_month'); // always YYYY-MM-01
            $table->unsignedInteger('client_count')->default(0);
            $table->decimal('total_topup', 18, 2)->default(0);
            $table->decimal('carry_in_amount', 18, 2)->default(0);
            $table->decimal('total_amount_for_point', 18, 2)->default(0);
            $table->decimal('point_decimal', 18, 6)->default(0);
            $table->unsignedInteger('point_rounded')->default(0);
            $table->integer('campaign_point')->default(0);
            $table->unsignedInteger('total_redeem_point')->default(0);
            $table->decimal('carry_out_amount', 18, 2)->default(0);
            $table->decimal('carry_out_decimal', 18, 6)->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->index('period_month');
            $table->unique(['user_id', 'period_month'], 'b2b_am_summary_user_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_am_point_summaries');
    }
};
