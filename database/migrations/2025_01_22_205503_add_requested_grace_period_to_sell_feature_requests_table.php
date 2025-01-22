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
        Schema::table('sell_feature_requests', function (Blueprint $table) {
            $table->timestamp('requested_grace_period')->nullable()->after('price_irr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sell_feature_requests', function (Blueprint $table) {
            $table->dropColumn('requested_grace_period');
        });
    }
};
