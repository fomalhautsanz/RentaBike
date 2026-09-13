<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mag-gamit ta og model kay kinahanglan gyud siya para makakuha ta
 * og data gikan sa database gamit ang klaro nga PHP command imbes
 * literal SQL. Mas sayon ra pud siya basahon, tapos mao ni ang
 * gitawag sa controller (pananglitan `DashboardController`) para
 * makakuha og real data gikan sa mga table, gets?
 */

class Rental extends Model
{
    protected $table = 'rental';
    protected $primaryKey = 'rental_id';
    public $timestamps = false;

    protected $fillable = [
        'borrower_id',
        'bike_id',
        'staff_id',
        'start_time',
        'end_time',
        'rental_duration_hrs',
        'base_fee',
        'overtime_fee',
        'total_fee',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function borrower()
    {
        return $this->belongsTo(Borrower::class, 'borrower_id');
    }

    public function bike()
    {
        return $this->belongsTo(Bicycle::class, 'bike_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'rental_id');
    }

    public function overtime()
    {
        return $this->hasOne(Overtime::class, 'rental_id');
    }
}