<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class JellyfinDemo extends Model
{
    use HasFactory;

    protected $table = "jellyfindemos";

    public function save($options = []){
        $dates = $this->sumHours($this->hours);
        $this->date_to = $dates['end'];
        if(empty($this->user_id)){
            $this->user_id = Auth::user()->id;
        }
        parent::save();
    }

    public function sumHours($hours){
        $startDate = new \DateTime();
        $hoursToAdd = $hours;
        $startDate->modify("+{$hoursToAdd} hours");
        $endDate = $startDate->format('Y-m-d H:i:s');
        return ['start'=>date('Y-m-d H:i:s'), 'end'=>$endDate];
    }

    public function jellyfinserver(){
        return $this->belongsTo('App\Models\JellyfinServer');
    }

    public function jellyfinpackage(){
        return $this->belongsTo('App\Models\JellyfinPackage');
    }

    public function scopeByUser($query){
       if(Auth::user()->role_id == 3 || Auth::user()->role_id == 5 || Auth::user()->role_id == 4){
            $query->where('user_id',Auth::user()->id);
       }
    }
}
