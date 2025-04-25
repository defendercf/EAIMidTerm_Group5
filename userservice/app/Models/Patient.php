<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable; // For login (optional)
use Illuminate\Notifications\Notifiable;

class Patient extends Model
{
  use HasFactory, Notifiable;

  protected $fillable = [
    'patient_name',
    'username',
    'email',
    'password',
    'date_of_birth',
    'gender',
  ];

  protected $hidden = [
    'password',
  ];
}
