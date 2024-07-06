<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class Domain extends Model
{
    use HasFactory;

    protected $table = "domains";

    public function scopeByUser($query){
        if(Auth::user()->role_id == 3 || Auth::user()->role_id == 6 || Auth::user()->role_id == 4){
          return $query->where('user_parent_id',Auth::user()->id);
        }
    }
}
