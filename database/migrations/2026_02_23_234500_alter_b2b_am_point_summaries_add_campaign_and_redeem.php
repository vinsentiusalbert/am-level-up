<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_am_point_summaries')) {
            return;
        }

        Schema::table('b2b_am_point_summaries', function (Blueprint $table) {
            if (!Schema::hasColumn('b2b_am_point_summaries', 'campaign_point')) {
                $table->integer('campaign_point')->default(0)->after('point_rounded');
            }

            if (!Schema::hasColumn('b2b_am_point_summaries', 'total_redeem_point')) {
                $table->unsignedInteger('total_redeem_point')->default(0)->after('campaign_point');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('b2b_am_point_summaries')) {
            return;
        }

        Schema::table('b2b_am_point_summaries', function (Blueprint $table) {
            if (Schema::hasColumn('b2b_am_point_summaries', 'total_redeem_point')) {
                $table->dropColumn('total_redeem_point');
            }

            if (Schema::hasColumn('b2b_am_point_summaries', 'campaign_point')) {
                $table->dropColumn('campaign_point');
            }
        });
    }
};

