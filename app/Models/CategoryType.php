<?php

namespace App\Models;

use App\Models\ModelType;
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
        return $this->created_at->format('d-m-Y H:i');
    }
}
