<?php

namespace App\Http\Resources;

use App\Enums\EpisodeStatus;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Eine Zeile der Folgenliste.
 *
 * @mixin Episode
 */
class EpisodeListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'series' => ['slug' => $this->series->slug, 'name' => $this->series->name],
            'number' => $this->display_number,
            'title' => $this->title,
            'first_aired' => $this->first_aired?->toDateString(),
            'broadcaster' => $this->broadcaster,
            'team' => $this->team ? ['id' => $this->team->id, 'name' => $this->team->name] : null,
            'status' => $this->status->value,
            'seen' => $this->seen,
            'seen_at' => $this->seen_at?->toDateString(),
            'can_mark_seen' => $this->status === EpisodeStatus::Aired,
        ];
    }
}
