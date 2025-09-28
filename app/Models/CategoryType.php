<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryType extends Model
{
    protected $table = 'category_types';

    protected $guarded = [];

    protected $appends = ['created_at_formatted'];

    public function modelTypes(): HasMany
    {
        return $this->hasMany(ModelType::class, 'category_type_id');
    }

    public function getCreatedAtFormattedAttribute()
    {
        // Check if created_at exists and is not null
        if (! $this->created_at) {
            return 'N/A'; // or return null, or return a default message
        }

        return $this->created_at->format('d-m-Y H:i');
    }

    /**
     * Alternative: Get formatted created_at attribute with more options
     */
    public function getCreatedAtFormattedAttribute2()
    {
        return $this->created_at?->format('d-m-Y H:i') ?? 'N/A';
    }
}
