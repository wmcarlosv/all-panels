<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class Duration extends Model
{
    use HasFactory;

    protected $table = 'durations';

    public function scopeByUser($query){
        if(Auth::user()->role_id == 3 || Auth::user()->role_id == 6 || Auth::user()->role_id == 4){
          return $query->where('user_parent_id',Auth::user()->id);
        }
    }

    public function scopeOnlyPlex($query){
        return $query->where('service','all')->orWhere('service','plex');
    }

    public function scopeOnlyJelly($query){
        return $query->where('service','all')->orWhere('service','jellyfin');
    }
}
