<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadAppointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_date',
        'client_id',
        'service_or_product',
        'sport_type',
        'service_type',
        'trainer',
        'training_date',
        'training_time',
        'status',
    ];
}