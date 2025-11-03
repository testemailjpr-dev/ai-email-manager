<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class GmailAccount extends Model
{
    protected $table = 'google_accounts';
	
	protected $fillable = ['user_id','email','google_id','google_token','google_refresh_token','primary'];

    protected $casts = [
        'google_token' => 'array'
    ];

    public function user() { return $this->belongsTo(User::class); }
}
