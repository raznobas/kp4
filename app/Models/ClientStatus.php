<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientStatus extends Model
{
    use HasFactory;

    protected $table = 'client_status_history';

    protected $fillable = [
        'client_id',
        'status_to',
        'director_id'
    ];
}
