<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->after('email_verified_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('image_path')->nullable();
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->string('document_path')->nullable();
        });

        Schema::table('environmental_claims', function (Blueprint $table) {
            $table->string('document_path')->nullable();
        });

        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->foreignId('environmental_claim_id')->nullable()->constrained()->nullOnDelete();
            $table->json('red_flags')->nullable();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['product_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'user_id']);
        });

        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('environmental_claim_id');
            $table->dropColumn('red_flags');
        });

        Schema::table('environmental_claims', function (Blueprint $table) {
            $table->dropColumn('document_path');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('document_path');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('blocked_at');
        });
    }
};
