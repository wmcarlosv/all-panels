<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class JellyfinServer extends Model
{
    use HasFactory;

    protected $table = "jellyfinservers";

    public function scopeServerByUser($query){
        if(Auth::user()->role_id == 1 ){

        }else{
           return $query->where('user_id',Auth::user()->id); 
        }
        
    }
}