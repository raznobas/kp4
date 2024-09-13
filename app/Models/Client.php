<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $fillable = [
        'surname',
        'name',
        'patronymic',
        'birthdate',
        'workplace',
        'phone',
        'email',
        'telegram',
        'instagram',
        'address',
        'gender',
        'ad_source',
        'director_id',
        'is_lead',
    ];
}
