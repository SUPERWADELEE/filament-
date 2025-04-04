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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            // 關聯的醫生（創建可預約時段的醫生）
        $table->foreignId('doctor_id')->nullable()->constrained('users');

        // 關聯的病患（預約的病患）
        $table->foreignId('patient_id')->nullable()->constrained('users');

        // 預約狀態：available（可預約）, booked（已預約）, completed（已完成）, canceled（已取消）
        $table->string('status')->default('available');

        // 預約類型（例如：一般診療、專科診療、緊急診療等）
        $table->string('appointment_type')->nullable();

        // 病患備註（症狀描述等）
        $table->text('patient_notes')->nullable();
        $table->string('location')->nullable();

        // 醫生備註（診療結果等）
        $table->text('doctor_notes')->nullable();
        $table->timestamps();
        $table->dateTime('starts_at');
        $table->dateTime('ends_at');
        $table->string('title');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
