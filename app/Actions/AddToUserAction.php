<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;
use Auth;

class AddToUserAction extends AbstractAction
{
    public function getTitle()
    {
        return 'Asignar a Usuario';
    }

    public function getIcon()
    {
        return 'voyager-person';
    }

    public function getPolicy()
    {
        return 'read';
    }

    public function shouldActionDisplayOnDataType()
    {
        $allow = false;
        if(Auth::user()->role_id == 1 || Auth::user()->role_id == 4){
            if($this->dataType->slug == 'jellyfincustomers'){
                $allow = true;
            }
        }
        return $allow;
    }


    public function getAttributes()
    {
        return [
            'class' => 'btn btn-success asing-to-user',
            'data-id'=>$this->data->id
        ];
    }

    public function getDefaultRoute()
    {
        return "#";
    }
}