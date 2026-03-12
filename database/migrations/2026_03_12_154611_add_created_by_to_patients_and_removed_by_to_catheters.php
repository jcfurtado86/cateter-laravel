<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->uuid('created_by_id')->nullable()->after('active');
            $table->foreign('created_by_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('catheter_records', function (Blueprint $table) {
            $table->uuid('removed_by_id')->nullable()->after('removed_at');
            $table->foreign('removed_by_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropColumn('created_by_id');
        });

        Schema::table('catheter_records', function (Blueprint $table) {
            $table->dropForeign(['removed_by_id']);
            $table->dropColumn('removed_by_id');
        });
    }
};
