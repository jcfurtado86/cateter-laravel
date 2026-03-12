<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('full_name');
            $table->string('record_number')->unique();
            $table->date('birth_date');
            $table->enum('sex', ['M', 'F', 'OUTRO']);
            $table->enum('race', ['BRANCA', 'PARDA', 'PRETA', 'AMARELA', 'INDIGENA', 'NAO_INFORMADA'])->default('NAO_INFORMADA');
            $table->string('phone');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
