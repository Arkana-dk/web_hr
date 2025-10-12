<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'department_id',
    ];

    /**
     * Relasi ke model Department.
     * Setiap seksi dimiliki oleh satu departemen.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function positions()
    {
        return $this->belongsToMany(Position::class);
    }
    public function sections()
    {
        return $this->belongsToMany(Section::class);
    }


}
