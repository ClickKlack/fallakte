<?php

namespace App\Http\Requests;

use App\Enums\EpisodeStatus;
use App\Models\Episode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Abhaken bzw. Entfernen des Hakens einer Folge, optional mit Datum.
 */
class UpdateEpisodeSeenRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'seen' => ['required', 'boolean'],
            'seen_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1970-01-01', 'before_or_equal:today'],
        ];
    }

    /**
     * Angekündigte Folgen können nicht als gesehen markiert werden.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var Episode $episode */
                $episode = $this->route('episode');

                if ($this->boolean('seen') && $episode->status === EpisodeStatus::Announced) {
                    $validator->errors()->add('seen', __('episodes.announced_cannot_be_seen'));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['seen' => __('episodes.seen'), 'seen_at' => __('episodes.seen_at')];
    }
}
