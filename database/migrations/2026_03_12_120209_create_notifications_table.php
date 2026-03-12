<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('sent_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone');
            $table->enum('type', ['ALERT_3D', 'ALERT_1D', 'ALERT_DUE', 'MANUAL']);
            $table->text('message');
            $table->enum('status', ['SENT', 'FAILED']);
            $table->timestamp('sent_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
