<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;


final class DistributeLeadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
/** Validator rules empty. Nothing to validate */
    public function rules(): array
    {
        return [];
    }

}
