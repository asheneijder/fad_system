<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\User;
use App\Models\ModelType;
use App\Models\CategoryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    protected $table = 'assets';

    protected $guarded = [];

    public function modelType(): BelongsTo
    {
        return $this->belongsTo(ModelType::class, 'model_type_id');
    }

    public function categoryType(): BelongsTo
    {
        return $this->belongsTo(CategoryType::class, 'category_type_id');
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y H:i:s');
    }

    public function setPurchaseDateAttribute($value)
    {
        $this->attributes['purchase_date'] = Carbon::parse($value)->format('Y-m-d');
    }
}
