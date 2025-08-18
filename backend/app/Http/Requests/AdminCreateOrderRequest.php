<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "user" => "required|string|min:2",
            "user_avatar" => "required|file|mimes:jpeg,jpg,png,svg,gif,webp",
            "is_safe" => "required|boolean",
            "remain" => "required|integer",
            "payment_method" => "required|string|in:sbp,tbank,sber",
            "price" => "required|integer|min:0",
            "currency_id" => "required|integer|exists:currencies,id",
            "fiat_currency_id" => "required|integer|exists:fiat_currencies,id",
            "min_limit" => "nullable|integer|min:0",
            "max_limit" => "nullable|integer|min:0",
        ];
    }
}
