<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReminderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'member' => $this->whenLoaded('member', fn () => ['id' => $this->member->id, 'code' => $this->member->code, 'full_name' => $this->member->full_name, 'phone' => $this->member->phone]),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'channel' => $this->channel->value,
            'message' => $this->message,
            'status' => $this->status,
            'error' => $this->error,
            'sent_at' => $this->sent_at->toIso8601String(),
            'sent_by' => $this->whenLoaded('sender', fn () => $this->sender ? ['id' => $this->sender->id, 'name' => $this->sender->name] : null),
            'link' => $this->when(isset($this->link), $this->link ?? null),
        ];
    }
}
