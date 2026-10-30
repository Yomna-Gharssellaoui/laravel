<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pro_account_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('requested_role');
            $table->string('company_name');
            $table->string('location');
            $table->string('activity_type')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('registration_number', 50)->nullable();
            $table->text('motivation');
            $table->string('document_path')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pro_account_requests');
    }
};
