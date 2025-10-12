<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'title',
        'message',
        'type',
        'is_read',
        'meta',
        'by_user_id'    ];

        protected $casts = [
        'is_read' => 'boolean',
        'meta'    => 'array',   // penting: auto-array
        ];

    /**
     * Relasi ke tabel employees
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
