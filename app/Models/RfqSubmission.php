<?php

namespace App\Models;

use App\Enums\RfqSubmissionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'rfq_number',
    'buyer_id',
    'manufacturer_id',
    'product_id',
    'conversation_id',
    'quantity',
    'quantity_unit',
    'target_price',
    'target_currency_code',
    'required_delivery_date',
    'shipping_terms',
    'destination_country',
    'destination_port_city',
    'packaging_details',
    'additional_requirements',
    'manufacturer_reply',
    'quoted_price',
    'quote_currency_code',
    'minimum_order_quantity',
    'lead_time_days',
    'lead_time',
    'quote_valid_until',
    'quote_shipping_terms',
    'quote_payment_terms',
    'sample_cost',
    'sample_lead_time',
    'quote_packaging_details',
    'quote_certifications',
    'quote_notes',
    'quoted_at',
    'first_manufacturer_response_at',
    'buyer_action_at',
    'status',
])]
class RfqSubmission extends Model
{
    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return RfqSubmissionStatus::values();
    }

    protected function casts(): array
    {
        return [
            'status' => RfqSubmissionStatus::class,
            'required_delivery_date' => 'date',
            'target_price' => 'decimal:2',
            'quoted_price' => 'decimal:2',
            'minimum_order_quantity' => 'integer',
            'lead_time_days' => 'integer',
            'lead_time' => 'string',
            'quote_valid_until' => 'date',
            'quote_certifications' => 'array',
            'quoted_at' => 'datetime',
            'first_manufacturer_response_at' => 'datetime',
            'buyer_action_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manufacturer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function quoteAttachments(): HasMany
    {
        return $this->hasMany(RfqQuoteAttachment::class, 'rfq_submission_id', 'id');
    }
}
