<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryCost extends Model
{
    use HasFactory;
    protected $table = "category_costs";

    protected $fillable = [
        'main_category_id',
        'cost',
        'director_id'
    ];

    public function additionalCosts()
    {
        return $this->hasMany(CategoryCostAdditional::class, 'category_cost_id');
    }
}
