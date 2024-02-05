<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Users;

class UserEvents extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['event_id', 'user_id'];
}
