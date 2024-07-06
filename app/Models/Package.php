<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class Package extends Model
{
    use HasFactory;

    protected $table = 'packages';

    public function scopeByUser($query){
        if(Auth::user()->role_id != 1){
            $servers = Auth::user()->servers->pluck('id')->toArray();
            return $query->whereIn('server_id',$servers);
        }
    }
}
