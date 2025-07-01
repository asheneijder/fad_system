<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\User;
use App\Models\ModelType;
use App\Enums\AssetStatus;
use App\Models\CategoryType;
use App\Models\AssetAssignment;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Asset extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'assets';

    protected $guarded = [];

    protected $casts = [
        'status' => AssetStatus::class,
    ];

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

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function currentAssignment()
    {
        return $this->hasOne(AssetAssignment::class)
            ->whereNull('returned_at')
            ->latestOfMany();
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y H:i:s');
    }

    public function setPurchaseDateAttribute($value)
    {
        $this->attributes['purchase_date'] = Carbon::parse($value)->format('Y-m-d');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(368)
            ->height(232)
            ->sharpen(10);
    }
}
