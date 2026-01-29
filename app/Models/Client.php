<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Authenticatable
{
    use SoftDeletes;
    protected $fillable = [
        'cpf',
        'phone',
        'gender',
        'user_id',
        'birth_date',
        'status',
        'email',
    ];

//    protected $hidden = [
//        'password',
//        'remember_token',
//    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
