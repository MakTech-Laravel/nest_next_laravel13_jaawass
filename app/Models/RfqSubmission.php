<?php

namespace App\Models;

use App\Enums\RfqSubmissionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    'quote_valid_until',
    'quoted_at',
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
            'quote_valid_until' => 'date',
            'quoted_at' => 'datetime',
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
}
