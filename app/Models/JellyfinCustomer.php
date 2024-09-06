<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class JellyfinCustomer extends Model
{
    use HasFactory;

    protected $table = "jellyfincustomers";

    public function save($options = []){
        if(empty($this->user_id)){
            $this->user_id = Auth::user()->id;
        }
        parent::save();
    }

    public function jellyfinserver(){
        return $this->belongsTo('App\Models\JellyfinServer');
    }

    public function jellyfinpackage(){
        return $this->belongsTo('App\Models\JellyfinPackage');
    }

    public function duration(){
        return $this->belongsTo('App\Models\Duration');
    }

    public function scopeByUser($query){
       if(Auth::user()->role_id == 3 || Auth::user()->role_id == 5){
            $query->where('user_id',Auth::user()->id);
       }
    }
}