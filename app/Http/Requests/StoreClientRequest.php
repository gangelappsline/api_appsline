<?php
// app/Http/Requests/StoreClientRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajusta según tu política de autorización
    }

    public function rules(): array
    {
        return [
            // Datos fiscales / empresa
            'business_name'    => ['required', 'string', 'max:255'],
            'trade_name'       => ['nullable', 'string', 'max:255'],
            'tax_id'           => ['required', 'string', 'max:50', 'unique:clients,tax_id'],
            'tax_regime'       => ['nullable', 'string', 'max:100'],
            'industry'         => ['nullable', 'string', 'max:100'],

            // Contacto principal
            'contact_name'     => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email:rfc,dns', 'max:255', 'unique:clients,email'],
            'phone'            => ['nullable', 'string', 'max:30'],
            'secondary_phone'  => ['nullable', 'string', 'max:30'],
            'website'          => ['nullable', 'url', 'max:255'],

            // Dirección
            'address'          => ['nullable', 'string', 'max:255'],
            'city'             => ['nullable', 'string', 'max:100'],
            'state'            => ['nullable', 'string', 'max:100'],
            'country'          => ['nullable', 'string', 'size:2'], // ISO 3166-1 alpha-2
            'postal_code'      => ['nullable', 'string', 'max:20'],

            // Datos comerciales
            'status'           => ['required', Rule::in(['active', 'inactive', 'prospect', 'suspended'])],
            'client_type'      => ['required', Rule::in(['individual', 'company'])],
            'credit_limit'     => ['nullable', 'numeric', 'min:0'],
            'payment_terms'    => ['nullable', 'integer', 'min:0', 'max:365'],

            // Relaciones
            'account_manager_id' => ['nullable', 'exists:users,id'],

            // Metadata
            'notes'            => ['nullable', 'string'],
            'metadata'         => ['nullable', 'array'],
            'onboarded_at'     => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_name.required' => 'La razón social es obligatoria.',
            'tax_id.unique'          => 'Ya existe un cliente con ese identificador fiscal.',
            'email.unique'           => 'Ya existe un cliente con ese correo.',
            'status.in'              => 'El estado seleccionado no es válido.',
            'client_type.in'         => 'El tipo de cliente no es válido.',
        ];
    }
}