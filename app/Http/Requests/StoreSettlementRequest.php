<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Table;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSettlementRequest extends FormRequest
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
        $playerIds = $table ? $table->players()->pluck('id')->toArray() : [];

        return [
            'player_id' => [
                'required',
                'integer',
                Rule::in($playerIds),
            ],
            'amount' => ['required', 'numeric'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'player_id.in' => 'The selected player does not belong to this table.',
        ];
    }
}
