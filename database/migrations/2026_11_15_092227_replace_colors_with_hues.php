<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colours become hues (0–359 on the OKLCH colour wheel, or null for neutral
 * grey) on projects, groups and choices. There is no production data yet, so
 * existing colours are not converted.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = ['choices', 'groups', 'projects'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedSmallInteger('hue')->nullable()->after('name');
            });

            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->string('color', 16)->default($name === 'choices' ? 'slate' : 'indigo')->after('name');
            });

            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('hue');
            });
        }
    }
};
