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
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('paddle_updated_at')->nullable()->after('billed_at');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('paddle_updated_at')->nullable()->after('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('paddle_updated_at');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('paddle_updated_at');
        });
    }
};
