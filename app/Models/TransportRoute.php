<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRoute extends Model
{
    // Aktifkan mass assignment
    protected $fillable = ['route_name'];

    // Jika nama tabel tidak sesuai plural default
    // protected $table = 'transport_routes';

    // Optional: relasi ke OvertimeRequest jika diperlukan
    public function overtimeRequests()
    {
        return $this->hasMany(OvertimeRequest::class, 'transport_route', 'route_name');
    }
}
