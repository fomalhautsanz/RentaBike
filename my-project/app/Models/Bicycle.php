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

class Bicycle extends Model
{
    protected $table = 'bicycle';
    protected $primaryKey = 'bike_id';
    public $timestamps = false;

    protected $fillable = [
        'qr_code',
        'model',
        'make',
        'bike_type',
        'status',
        'condition',
    ];

    public function rentals()
    {
        return $this->hasMany(Rental::class, 'bike_id');
    }
}