<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryCostAdditional extends Model
{
    use HasFactory;
    protected $table = "category_cost_additional";
    protected $fillable = [
        'category_cost_id',
        'additional_category_id',
    ];

    public function categoryCost()
    {
        return $this->belongsTo(CategoryCost::class, 'category_cost_id');
    }
}
