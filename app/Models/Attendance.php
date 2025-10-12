<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    /**
     * Kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
    'employee_id',
    'date',
    'check_in_time',
    'check_in_latitude',
    'check_in_longitude',
    'check_in_location',
    'check_in_photo_path',
    'check_out_time',
    'check_out_latitude',
    'check_out_longitude',
    'check_out_location',
    'check_out_photo_path',
    'checkout_reason', // ⬅️ tambahkan ini
    'status',
    'notes',
];


    /**
     * Tipe data casting otomatis.
     */
    protected $casts = [
        'date'              => 'date',
        'check_in_time'     => 'datetime:H:i',
        'check_out_time'    => 'datetime:H:i',
        'check_in_latitude' => 'float',
        'check_in_longitude'=> 'float',
        'check_out_latitude'=> 'float',
        'check_out_longitude'=> 'float',
    ];

    /**
     * Relasi ke model Employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
