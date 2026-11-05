<?php

namespace App\Services;

use App\Enums\TrainingProgramKind;
use App\Enums\TrainingProgramStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Member;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrainingProgramService
{
    /**
     * Création par un coach ou le gérant (UC-24). $impose = appliqué immédiatement (RG-PRG-03).
     */
    public function create(Member $member, array $data, User $author, bool $impose): TrainingProgram
    {
        $this->assertReviewer($author);

        return DB::transaction(function () use ($member, $data, $author, $impose) {
            $status = $impose ? TrainingProgramStatus::Imposed : TrainingProgramStatus::Proposed;

            if ($impose) {
                $this->archiveEffective($member, TrainingProgramKind::from($data['kind']));
            }

            $program = TrainingProgram::create([
                'member_id' => $member->id,
                'kind' => $data['kind'],
                'title' => $data['title'],
                'status' => $status,
                'proposed_by_member' => false,
                'author_id' => $author->id,
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
                'content' => $data['content'] ?? [],
                'notes' => $data['notes'] ?? null,
                'reviewed_by' => $impose ? $author->id : null,
                'reviewed_at' => $impose ? now() : null,
            ]);

            return $program->load(['member', 'author', 'reviewer']);
        });
    }

    public function update(TrainingProgram $program, array $data, User $actor): TrainingProgram
    {
        $this->assertReviewer($actor);

        if ($program->status === TrainingProgramStatus::Archived) {
            throw new BusinessRuleException('program_archived', 'Un programme archivé ne peut plus être modifié.');
        }

        $program->update(array_intersect_key($data, array_flip(['title', 'starts_on', 'ends_on', 'content', 'notes'])));

        return $program->refresh()->load(['member', 'author', 'reviewer']);
    }

    /** Validation (UC-25, RG-PRG-04). */
    public function approve(TrainingProgram $program, User $reviewer, ?string $note): TrainingProgram
    {
        $this->assertReviewer($reviewer);

        if ($program->status !== TrainingProgramStatus::Proposed) {
            throw new BusinessRuleException('program_not_proposed', 'Seul un programme proposé peut être validé.');
        }

        return DB::transaction(function () use ($program, $reviewer, $note) {
            $this->archiveEffective($program->member, $program->kind, $program->id);

            $program->update([
                'status' => TrainingProgramStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            return $program->refresh()->load(['member', 'author', 'reviewer']);
        });
    }

    public function decline(TrainingProgram $program, User $reviewer, string $note): TrainingProgram
    {
        $this->assertReviewer($reviewer);

        if ($program->status !== TrainingProgramStatus::Proposed) {
            throw new BusinessRuleException('program_not_proposed', 'Seul un programme proposé peut être refusé.');
        }

        $program->update([
            'status' => TrainingProgramStatus::Declined,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);

        return $program->refresh()->load(['member', 'author', 'reviewer']);
    }

    public function archive(TrainingProgram $program, User $actor): TrainingProgram
    {
        $this->assertReviewer($actor);
        $program->update(['status' => TrainingProgramStatus::Archived]);

        return $program->refresh()->load(['member', 'author', 'reviewer']);
    }

    /** Assiduité : présences réelles vs jours prévus (RG-PRG-06), sur les 4 dernières semaines. */
    public function adherence(Member $member): ?array
    {
        $program = $member->trainingPrograms()->effective()->ofKind(TrainingProgramKind::Training->value)->first();
        if ($program === null) {
            return null;
        }

        $days = collect($program->content['days'] ?? [])->map(fn ($d) => mb_strtolower($d))->values();
        if ($days->isEmpty()) {
            return null;
        }

        $map = ['lundi' => 1, 'mardi' => 2, 'mercredi' => 3, 'jeudi' => 4, 'vendredi' => 5, 'samedi' => 6, 'dimanche' => 7];
        $plannedIso = $days->map(fn ($d) => $map[$d] ?? null)->filter()->values();

        $from = now()->subDays(27)->startOfDay();
        $planned = 0;
        for ($d = $from->copy(); $d->lte(now()); $d->addDay()) {
            if ($plannedIso->contains($d->isoWeekday())) {
                $planned++;
            }
        }

        $attended = $member->attendances()->whereBetween('attended_on', [$from->toDateString(), now()->toDateString()])->count();

        return [
            'program_id' => $program->id,
            'planned_days' => $days->all(),
            'planned_sessions_4w' => $planned,
            'attended_sessions_4w' => $attended,
            'rate' => $planned > 0 ? round(min($attended / $planned, 1), 2) : null,
        ];
    }

    private function archiveEffective(Member $member, TrainingProgramKind $kind, ?int $exceptId = null): void
    {
        $member->trainingPrograms()->effective()->ofKind($kind->value)
            ->when($exceptId, fn ($q) => $q->where('id', '<>', $exceptId))
            ->update(['status' => TrainingProgramStatus::Archived->value]);
    }

    private function assertReviewer(User $user): void
    {
        if (! $user->canReviewPrograms()) {
            throw new BusinessRuleException('program_requires_coach', 'Réservé au coach et au gérant.', [], 403);
        }
    }
}
