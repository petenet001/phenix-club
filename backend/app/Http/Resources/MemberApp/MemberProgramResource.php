<?php

namespace App\Http\Resources\MemberApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Programme vu par l'adhérent : sans les notes internes du coach. */
class MemberProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind->value,
            'kind_label' => $this->kind->label(),
            'title' => $this->title,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_effective' => $this->status->isEffective(),
            'author' => $this->author?->name,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'content' => $this->content,
            'review_note' => $this->review_note,
        ];
    }
}
