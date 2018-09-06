<?php

namespace Fico7489\Laravel\EloquentJoin\Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends BaseModel
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = ['number', 'seller_id'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function itemsWithTrashed()
    {
        return $this->hasMany(OrderItem::class)
            ->withTrashed();
    }

    public function itemsOnlyTrashed()
    {
        return $this->hasMany(OrderItem::class)
            ->onlyTrashed();
    }
    
    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }
    
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function watchers()
    {
        return $this->belongsToMany(User::class)->wherePivot('user_type', '=', 'watchers');
    }
}
