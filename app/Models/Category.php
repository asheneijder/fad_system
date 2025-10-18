<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Category extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
        'parent_id',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the items for this category.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class); // Adjust based on your item model name
    }

    /**
     * Get the assets for this category.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class); // If you have an Asset model
    }

    /**
     * Get the stationary items for this category.
     */
    public function stationaryItems(): HasMany
    {
        return $this->hasMany(StationaryItem::class); // If you have a StationaryItem model
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope a query to only include categories of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include root categories (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include categories with parent.
     */
    public function scopeWithParent($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Check if category has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if category has items.
     */
    public function hasItems(): bool
    {
        return $this->items()->exists();
    }

    /**
     * Get the full category path (breadcrumb).
     */
    public function getFullPathAttribute(): string
    {
        $path = [];
        $category = $this;

        while ($category) {
            $path[] = $category->name;
            $category = $category->parent;
        }

        return implode(' > ', array_reverse($path));
    }

    /**
     * Get all descendant categories (children, grandchildren, etc.).
     */
    public function getAllDescendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * Get all descendant IDs including self.
     */
    public function getAllDescendantIds(): array
    {
        $ids = [$this->id];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getAllDescendantIds());
        }

        return $ids;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default sort order if not provided
        static::creating(function ($category) {
            if (empty($category->sort_order)) {
                $maxOrder = static::where('type', $category->type)
                    ->where('parent_id', $category->parent_id)
                    ->max('sort_order');
                $category->sort_order = $maxOrder ? $maxOrder + 1 : 1;
            }
        });

        // Prevent circular parent relationships
        static::saving(function ($category) {
            if ($category->parent_id) {
                $parent = static::find($category->parent_id);

                // Check if the parent is a descendant of this category
                if ($parent && in_array($category->id, $parent->getAllDescendantIds())) {
                    throw new \Exception('Cannot set parent: Circular relationship detected.');
                }
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'type', 'status', 'parent_id', 'sort_order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Category {$eventName}");
    }
}
