<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class Movement extends Model
{
    use HasFactory;

    protected $table = "movements";

    public static function getAllMovements(){
        $data = Movement::all();
        return $data;
    }

    public function scopeByUser($query){
        if(Auth::user()->role_id == 3 || Auth::user()->role_id == 6 || Auth::user()->role_id == 4){
          return $query->where('user_parent_id',Auth::user()->id);
        }
    }

    public function scopeMyMovements($query){
        return $query->where('parent_user_id',Auth::user()->id);
    }

    public function save($attributes = []){
        if(empty($this->parent_user_id)){
            $this->parent_user_id = Auth::user()->id;
        }

        parent::save();
    }
}
