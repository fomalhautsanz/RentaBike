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

class IssueReport extends Model
{
    protected $table = 'issue_report';
    protected $primaryKey = 'issue_id';
    public $timestamps = false;

    protected $fillable = [
        'bike_id',
        'staff_id',
        'issue_type',
        'description',
        'photo_ref',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function bike()
    {
        return $this->belongsTo(Bicycle::class, 'bike_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}