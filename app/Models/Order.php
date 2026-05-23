<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'number_of_person',
        'table_id',
        'shift_worker_id',
        'status_order_id'
    ];

    public function table()
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    public function status_order()
    {
        return $this->belongsTo(StatusOrder::class, 'status_order_id');
    }

    public function shift_worker()
    {
        return $this->belongsTo(ShiftWorker::class, 'shift_worker_id');
    }

    public function items()
    {
        return $this->belongsToMany(Menu::class, 'order_menus', 'order_id', 'menu_id')
            ->withPivot('id', 'count');
    }

    public function orderMenus(): HasMany
    {
        return $this->hasMany(OrderMenu::class, 'order_id');
    }
}
