<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'guest_name',
        'guest_email',
        'guest_count',
        'check_in',
        'check_out',
    ];

    protected $dates = ['check_in', 'check_out'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
