<?php
// app/Http/Requests/UpdateClientRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client')->id ?? $this->route('client');

        return [
            'business_name'    => ['sometimes', 'required', 'string', 'max:255'],
            'trade_name'       => ['nullable', 'string', 'max:255'],
            'tax_id'           => ['sometimes', 'required', 'string', 'max:50',
                                    Rule::unique('clients', 'tax_id')->ignore($clientId)],
            'tax_regime'       => ['nullable', 'string', 'max:100'],
            'industry'         => ['nullable', 'string', 'max:100'],

            'contact_name'     => ['sometimes', 'required', 'string', 'max:255'],
            'email'            => ['sometimes', 'required', 'email:rfc,dns', 'max:255',
                                    Rule::unique('clients', 'email')->ignore($clientId)],
            'phone'            => ['nullable', 'string', 'max:30'],
            'secondary_phone'  => ['nullable', 'string', 'max:30'],
            'website'          => ['nullable', 'url', 'max:255'],

            'address'          => ['nullable', 'string', 'max:255'],
            'city'             => ['nullable', 'string', 'max:100'],
            'state'            => ['nullable', 'string', 'max:100'],
            'country'          => ['nullable', 'string', 'size:2'],
            'postal_code'      => ['nullable', 'string', 'max:20'],

            'status'           => ['sometimes', Rule::in(['active', 'inactive', 'prospect', 'suspended'])],
            'client_type'      => ['sometimes', Rule::in(['individual', 'company'])],
            'credit_limit'     => ['nullable', 'numeric', 'min:0'],
            'payment_terms'    => ['nullable', 'integer', 'min:0', 'max:365'],

            'account_manager_id' => ['nullable', 'exists:users,id'],

            'notes'            => ['nullable', 'string'],
            'metadata'         => ['nullable', 'array'],
            'onboarded_at'     => ['nullable', 'date'],
        ];
    }
}