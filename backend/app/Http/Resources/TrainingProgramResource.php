<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = fn ($u) => $u ? ['id' => $u->id, 'name' => $u->name] : null;

        return [
            'id' => $this->id,
            'member' => $this->whenLoaded('member', fn () => ['id' => $this->member->id, 'code' => $this->member->code, 'full_name' => $this->member->full_name]),
            'kind' => $this->kind->value,
            'kind_label' => $this->kind->label(),
            'title' => $this->title,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_effective' => $this->status->isEffective(),
            'proposed_by_member' => $this->proposed_by_member,
            'author' => $this->whenLoaded('author', fn () => $user($this->author)),
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'content' => $this->content,
            'notes' => $this->notes,
            'reviewed_by' => $this->whenLoaded('reviewer', fn () => $user($this->reviewer)),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'review_note' => $this->review_note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
