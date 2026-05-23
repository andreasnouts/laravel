<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // this should be handled elsewhere (middleware?)
    }

    /**
     * Validation rules that apply to a request of this Type.
     * @return array
     */
    public function rules(): array
    {
        return [
            'guide_hash_id'              => ['required', 'string', 'size:9'],
            'notes'                      => ['nullable', 'string'],
            'items'                      => ['required', 'array', 'min:1'],
            'items.*.tour_name'          => ['required', 'string', 'max:255'],
            'items.*.participants'       => ['required', 'integer', 'min:1'],
            'items.*.price_per_person'   => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'guide_hash_id.required'           => 'A Guide must be specified, hash_id not given!',
            'guide_hash_id.size'               => 'Invalid Guide identifier (length mismatch)!',
            'items.required'                   => 'At least one Tour item must be given!',
            'items.*.tour_name.required'       => 'Missing Tour name!',
            'items.*.participants.min'          => 'There must be at least 1 Participant!',
            'items.*.price_per_person.min'     => 'Price per person cannot be negative!',
        ];
    }
}
