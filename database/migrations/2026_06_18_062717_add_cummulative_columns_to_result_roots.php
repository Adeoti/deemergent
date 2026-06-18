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
        Schema::table('result_roots', function (Blueprint $table) {
            // Fixed dropdown values, but stored as plain strings for flexibility.
            // Term: "1st Term", "2nd Term", "3rd Term"
            // Academic Session: "2025/2026" ... "2049/2050"
            $table->string('term')->nullable()->after('name');
            $table->string('academic_session')->nullable()->after('term');

            // Index to speed up cumulative lookups (grouping by session + class + subject across terms)
            $table->index(['academic_session', 'term']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('result_roots', function (Blueprint $table) {
            $table->dropIndex(['academic_session', 'term']);
            $table->dropColumn(['term', 'academic_session']);
        });
    }
};