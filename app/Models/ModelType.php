<?php

namespace App\Models;

use App\Models\CategoryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelType extends Model
{
    protected $table = 'model_types';

    protected $guarded = [];

    public function categoryType(): BelongsTo
    {
        return $this->belongsTo(CategoryType::class, 'category_type_id');
    }
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'model_type_id');
    }
}
