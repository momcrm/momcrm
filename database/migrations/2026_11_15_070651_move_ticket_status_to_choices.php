<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Points each ticket at its team's status choice instead of a fixed value,
 * and turns access rule conditions on ticket status into choice ids. The
 * teams' choices still have the default names, so the old values map by name.
 */
return new class extends Migration
{
    /**
     * The fixed values tickets used, and the default choice each became.
     *
     * @var array<string, string>
     */
    private const array NAMES = [
        'new' => 'New',
        'in_progress' => 'In progress',
        'waiting_on_customer' => 'Waiting on customer',
        'waiting_on_supplier' => 'Waiting on supplier',
        'closed' => 'Closed',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignUuid('status_id')->nullable()->after('status')->constrained('choices')->nullOnDelete();
        });

        DB::table('teams')->orderBy('id')->pluck('id')->each(function (mixed $teamId): void {
            $ids = DB::table('choices')->where('team_id', $teamId)->where('list', 'ticket_status')->pluck('id', 'name');

            foreach (self::NAMES as $value => $name) {
                DB::table('tickets')->where('team_id', $teamId)->where('status', $value)->update(['status_id' => $ids[$name] ?? null]);
            }

            $toId = fn (mixed $value): mixed => is_string($value) && isset(self::NAMES[$value]) ? ($ids[self::NAMES[$value]] ?? $value) : $value;

            DB::table('access_rules')->where('team_id', $teamId)->orderBy('id')->each(function (object $rule) use ($toId): void {
                $conditions = is_string($rule->conditions) ? json_decode($rule->conditions, true) : null;

                if (! is_array($conditions) || ! collect($conditions)->contains('attribute', 'ticket.status')) {
                    return;
                }

                $conditions = array_map(fn (mixed $condition): mixed => is_array($condition) && ($condition['attribute'] ?? null) === 'ticket.status'
                    ? [...$condition, 'value' => is_array($condition['value'] ?? null) ? array_map($toId, $condition['value']) : $toId($condition['value'] ?? null)]
                    : $condition, $conditions);

                DB::table('access_rules')->where('id', $rule->id)->update(['conditions' => json_encode($conditions)]);
            });
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'status']);
            $table->dropIndex(['status']);
            $table->dropColumn('status');
            $table->index(['team_id', 'status_id']);
        });

        Schema::table('choices', function (Blueprint $table) {
            $table->index(['list', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('choices', function (Blueprint $table) {
            $table->dropIndex(['list', 'category']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->string('status')->default('new')->after('status_id')->index();
            $table->index(['team_id', 'status']);
        });

        foreach (self::NAMES as $value => $name) {
            DB::table('tickets')
                ->whereIn('status_id', DB::table('choices')->where('list', 'ticket_status')->where('name', $name)->select('id'))
                ->update(['status' => $value]);
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'status_id']);
            $table->dropConstrainedForeignId('status_id');
        });
    }
};
