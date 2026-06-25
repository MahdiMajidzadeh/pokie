<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Table;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $token = $this->route('token');
        $table = $token ? Table::where('token', $token)->first() : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('players', 'name')->where('table_id', $table?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A player with this name already exists at this table.',
        ];
    }
}
