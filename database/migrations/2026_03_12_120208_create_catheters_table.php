<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catheter_records', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('created_by_id')->constrained('users');
            $table->boolean('had_previous_catheter');
            $table->dateTime('insertion_date');
            $table->enum('procedure_type', ['ELETIVO', 'URGENCIA']);
            $table->string('indication');
            $table->string('caliber');
            $table->enum('insertion_side', ['DIREITO', 'ESQUERDO']);
            $table->string('passage_type');
            $table->boolean('safety_wire');
            $table->integer('min_days');
            $table->integer('max_days');
            $table->dateTime('min_removal_date');
            $table->dateTime('max_removal_date');
            $table->dateTime('removed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catheter_records');
    }
};
