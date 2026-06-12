<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'user_id' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'phone' => $this->phone,
            'department_id' => [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ],
            'school_id' => [
                'id' => $this->department->faculty->school->id,
                'name' => $this->department->faculty->school->name,
            ],
            'faculty_id' => [
                'id' => $this->department->faculty->id,
                'name' => $this->department->faculty->name,
            ],
            'dob' => $this->dob,
            'matric_number' => $this->matric_number,
            'confirmed_at' => $this->confirmed_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),

        ];
    }
}
