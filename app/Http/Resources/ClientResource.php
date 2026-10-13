<?php
// app/Http/Resources/ClientResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'business_name'   => $this->business_name,
            'trade_name'      => $this->trade_name,
            'tax_id'          => $this->tax_id,
            'tax_regime'      => $this->tax_regime,
            'industry'        => $this->industry,

            'contact' => [
                'name'            => $this->contact_name,
                'email'           => $this->email,
                'phone'           => $this->phone,
                'secondary_phone' => $this->secondary_phone,
                'website'         => $this->website,
            ],

            'address' => [
                'street'      => $this->address,
                'city'        => $this->city,
                'state'       => $this->state,
                'country'     => $this->country,
                'postal_code' => $this->postal_code,
            ],

            'status'         => $this->status,
            'client_type'    => $this->client_type,
            'credit_limit'   => $this->credit_limit,
            'payment_terms'  => $this->payment_terms,

            'account_manager' => $this->whenLoaded('accountManager', fn () => [
                'id'   => $this->accountManager->id,
                'name' => $this->accountManager->name,
            ]),

            'projects_count' => $this->when(isset($this->projects_count), $this->projects_count),
            'projects'       => ProjectResource::collection($this->whenLoaded('projects')),

            'notes'        => $this->notes,
            'metadata'     => $this->metadata,
            'onboarded_at' => optional($this->onboarded_at)->toIso8601String(),

            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
            'deleted_at'   => $this->deleted_at?->toIso8601String(),
        ];
    }
}