<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatbot;
use App\Models\CustomField;
use App\Models\EmailTemplate;
use App\Models\KnowledgeBaseArticle;
use App\Models\Menu;
use App\Models\Pipeline;
use App\Models\ReportBuilder;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowCondition;
use App\Models\WorkflowTrigger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clones a team's CONFIGURATION into a brand-new team (a template). Copies the
 * structural models below with fresh PKs and remaps their intra-config foreign
 * keys; does NOT copy transactional data (contacts/leads/deals/tasks/…) or
 * members.
 */
class TeamCloneService
{
    /** Config models to copy, in a stable order. */
    private const CLONEABLE_MODELS = [
        Pipeline::class,
        Stage::class,
        Workflow::class,
        WorkflowAction::class,
        WorkflowCondition::class,
        WorkflowTrigger::class,
        EmailTemplate::class,
        CustomField::class,
        Tag::class,
        KnowledgeBaseArticle::class,
        Chatbot::class,
        ReportBuilder::class,
        Menu::class,
    ];

    /**
     * Cross-model FK columns to remap: model => [column => referenced model].
     * Verified against the live schema — Pipeline.stage_id is a model-declared
     * relation with no column (drift), so it is not here; the hasColumn guard
     * in patchForeignKeys also skips any edge whose column is absent.
     *
     * @var array<class-string, array<string, class-string>>
     */
    private const FK_EDGES = [
        Stage::class => ['pipeline_id' => Pipeline::class],
        WorkflowAction::class => ['workflow_id' => Workflow::class],
        WorkflowCondition::class => ['workflow_action_id' => WorkflowAction::class],
        WorkflowTrigger::class => ['workflow_id' => Workflow::class],
        Menu::class => ['parent_id' => Menu::class],
    ];

    public function clone(Team $source, string $name, User $owner): Team
    {
        $team = new Team;
        $team->name = $name;
        $team->user_id = $owner->id;
        $team->personal_team = false;
        $team->save();

        // FK checks OFF must wrap (not nest inside) the transaction — sqlite
        // ignores PRAGMA foreign_keys once a transaction is open. With FKs off
        // we can insert rows keeping their old cross-refs, then patch to the
        // new ids; this needs no dependency ordering and handles the
        // self-referential Menu (parent may be inserted after child).
        Schema::withoutForeignKeyConstraints(function () use ($source, $team): void {
            DB::transaction(function () use ($source, $team): void {
                $idMap = $this->copyRows($source, $team);
                $this->patchForeignKeys($team, $idMap);
            });
        });

        return $team;
    }

    /**
     * @return array<class-string, array<int, int>> per model: old id => new id
     */
    private function copyRows(Team $source, Team $team): array
    {
        $idMap = [];

        foreach (self::CLONEABLE_MODELS as $class) {
            $table = (new $class)->getTable();
            if (! Schema::hasTable($table)) {
                continue;
            }

            $map = [];
            foreach (DB::table($table)->where('team_id', $source->id)->get() as $row) {
                $data = (array) $row;
                $oldId = (int) $data['id'];
                unset($data['id']);
                $data['team_id'] = $team->id;
                // Edge FK columns keep their old values here; patched in pass 2.
                $map[$oldId] = (int) DB::table($table)->insertGetId($data);
            }

            $idMap[$class] = $map;
        }

        return $idMap;
    }

    /**
     * @param  array<class-string, array<int, int>>  $idMap
     */
    private function patchForeignKeys(Team $team, array $idMap): void
    {
        foreach (self::FK_EDGES as $class => $edges) {
            $table = (new $class)->getTable();
            if (! Schema::hasTable($table) || empty($idMap[$class])) {
                continue;
            }

            // Drift guard: skip an edge whose column the table does not have.
            $edges = array_filter(
                $edges,
                fn (string $column): bool => Schema::hasColumn($table, $column),
                ARRAY_FILTER_USE_KEY,
            );
            if ($edges === []) {
                continue;
            }

            foreach (DB::table($table)->where('team_id', $team->id)->get() as $row) {
                $updates = [];
                foreach ($edges as $column => $referenced) {
                    $old = $row->{$column};
                    if ($old === null) {
                        continue;
                    }
                    // Unmapped ref -> null rather than dangle to the source team.
                    $updates[$column] = $idMap[$referenced][(int) $old] ?? null;
                }

                if ($updates !== []) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        }
    }
}
