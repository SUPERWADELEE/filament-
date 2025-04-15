<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;

    const STATUS_AVAILABLE = 'available';
    const STATUS_BOOKED = 'booked';

    const TITLE = [
        'DOCTOR_BOOKED' => '預約：',
        'PATIENT_AVAILABLE' => '可預約：',
        'PATIENT_MY_BOOKING' => '我的預約：',
        'PATIENT_BOOKED' => '已被預約：',
    ];
    const ROLE = [
        'DOCTOR' => 'doctor',
        'PATIENT' => 'patient',
    ];
    protected $fillable = [
        'title',
        'starts_at',
        'ends_at',
        'description',
        'doctor_id',
        'patient_id',
        'status',
        'appointment_type',
        'patient_notes',
        'doctor_notes',
        'type',
        'location',
        'line_user_id',
        'patient_name',
    ];
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_all_day' => 'boolean',
    ];
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
