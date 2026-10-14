<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? null,
            ],
            'date' => $this->date,
            'clock_in' => $this->clock_in_at,
            'clock_out' => $this->clock_out_at,
            'breaks' => $this->whenLoaded('rests', function () {
                return $this->rests->map(function ($rest) {
                    return [
                        'id' => $rest->id,
                        'break_in' => $rest->break_in,
                        'break_out' => $rest->break_out,
                    ];
                });
            }),
            'applications' => [],
            'comment' => $this->remarks,
        ];
    }
}
