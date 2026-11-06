<?php

namespace App\Http\Requests;

use App\Enums\EpisodeStatus;
use App\Import\ParserRegistry;
use App\Models\Episode;
use App\Models\Series;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validierung für das Anlegen und Bearbeiten einer Folge.
 */
class EpisodeFormRequest extends FormRequest
{
    /**
     * Leere Texte als null behandeln, Sender trimmen.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'broadcaster' => trim((string) $this->input('broadcaster')) ?: null,
            'seen' => $this->boolean('seen'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Episode|null $episode */
        $episode = $this->route('episode');
        $seriesId = $episode?->series_id ?? $this->integer('series_id');

        return [
            'series_id' => $episode
                ? ['prohibited']
                : ['required', 'integer', Rule::exists(Series::class, 'id')->whereIn('slug', app(ParserRegistry::class)->slugs())],
            // Filme ohne offizielle Nummer dürfen ohne Nummer bleiben; neue Folgen brauchen eine.
            'number' => [
                $episode && $episode->number === null ? 'nullable' : 'required',
                'integer', 'min:1', 'max:999999',
                Rule::unique(Episode::class, 'number')->where('series_id', $seriesId)->ignore($episode?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'first_aired' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1970-01-01', 'before_or_equal:2100-12-31'],
            'broadcaster' => ['nullable', 'string', 'max:100'],
            'team_id' => ['nullable', 'integer', Rule::exists(Team::class, 'id')->where('series_id', $seriesId)],
            'seen' => ['boolean'],
            'seen_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1970-01-01', 'before_or_equal:today'],
        ];
    }

    /**
     * Angekündigte Folgen (ohne Datum oder Datum in der Zukunft) können nicht gesehen sein.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->boolean('seen') || $validator->errors()->has('first_aired')) {
                    return;
                }

                $firstAired = $this->input('first_aired') ? Carbon::parse($this->input('first_aired')) : null;

                if (EpisodeStatus::fromAirDate($firstAired) === EpisodeStatus::Announced) {
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
        return __('episodes.attributes');
    }

    /**
     * Felder der Folge (ohne series_id), „gesehen am“ nur bei gesehenen Folgen.
     *
     * @return array<string, mixed>
     */
    public function episodeData(): array
    {
        $data = $this->safe()->only(['number', 'title', 'first_aired', 'broadcaster', 'team_id', 'seen', 'seen_at']);
        $data['seen_at'] = $data['seen'] ? ($data['seen_at'] ?? null) : null;

        return $data;
    }
}
