<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Storage;

class ProjectResource extends JsonResource
{

    public static $wrap = false;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? '',
            'description' => $this->description ?? '',
            'created_at' => $this->created_at ? (new Carbon($this->created_at))->format('Y-m-d') : null,
            'due_date' => $this->due_date ? (new Carbon($this->due_date))->format('Y-m-d') : null,
            'status' => $this->status ?? 'pending',
            'image_path' => $this->image_path ? Storage::url($this->image_path) : '',
            'createdBy' => $this->when($this->createdBy, function() {
                return new UserResource($this->createdBy);
            }),
            'updatedBy' => $this->when($this->updatedBy, function() {
                return new UserResource($this->updatedBy);
            }),
        ];
    }
}
