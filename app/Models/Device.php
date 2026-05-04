<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'devices';

    // Kolom yang boleh diisi secara massal
    protected $fillable = [
        'name',
        'ip_address',
        'ssh_user',
        'ssh_pass',
        'snmp_community',
        'description',
        'type',
        'is_active',
    ];

    // Jika kamu ingin casting status aktif ke boolean
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
