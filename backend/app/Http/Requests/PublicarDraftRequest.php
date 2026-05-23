<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicarDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // El body del request es vacío — la validación del payload viene del draft en BD,
    // no del request. La lógica vive en el controller.
    public function rules(): array
    {
        return [];
    }
}
