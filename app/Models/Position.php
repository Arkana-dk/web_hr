<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Position extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi massal
    protected $fillable = [
        'department_id',
        'name',
    ];

    /**
     * Position belongsTo Department
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Satu Position memiliki banyak Employee
     * (menggunakan foreign key position_id pada tabel employees)
     */
    public function getPrimaryDepartmentIdAttribute(): ?int
    {
    return $this->sections()->value('department_id');
    }
    public function employees()
    {
        return $this->hasMany(Employee::class, 'position_id');
    }
    public function sections()
    {
    return $this->belongsToMany(Section::class, 'position_section')->withTimestamps();
    }

}
