<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>|string>
     */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:projects,slug'],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:5000'],
            'locale' => ['required', 'string', 'max:15', 'regex:/^[a-z]{2,3}(-[A-Z]{2}(-[A-Za-z][A-Za-z0-9]+)?)?$/'],
            'primary_domain' => ['nullable', 'string', 'max:191'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
