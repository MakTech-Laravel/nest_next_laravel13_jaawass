<?php

namespace App\Models;

use App\Enums\TicketDepartmentType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'status',
    'department_type',
    'assigned_to',
    'priority',
    'subject',
])]
class Ticket extends Model
{
    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return TicketStatus::values();
    }

    /**
     * @return array<int, string>
     */
    public static function priorities(): array
    {
        return TicketPriority::values();
    }

    /**
     * @return array<int, string>
     */
    public static function departmentTypes(): array
    {
        return TicketDepartmentType::values();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<TicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'department_type' => TicketDepartmentType::class,
            'priority' => TicketPriority::class,
        ];
    }
}
