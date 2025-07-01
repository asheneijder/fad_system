<?php

namespace App\Enums;

enum AssetStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ASSIGNED = 'assigned';
    case AVAILABLE = 'available';
    case IN_MAINTENANCE = 'in_maintenance';
    case DAMAGED = 'damaged';
    case LOST = 'lost';
    case RETIRED = 'retired';
    case DISPOSED = 'disposed';
    case PENDING = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::ASSIGNED => 'Assigned',
            self::AVAILABLE => 'Available',
            self::IN_MAINTENANCE => 'In Maintenance',
            self::DAMAGED => 'Damaged',
            self::LOST => 'Lost',
            self::RETIRED => 'Retired',
            self::DISPOSED => 'Disposed',
            self::PENDING => 'Pending',
        };
    }

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label()
        ], self::cases());
    }
}
