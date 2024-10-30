<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;
use Auth;

class DisableEnableAction extends AbstractAction
{
    private $title = "Inactivar";
    private $icon = "voyager-frown";
    private $color = "btn-danger";

    public function getTitle()
    {
        return $this->title;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function getPolicy()
    {
        return 'read';
    }

    public function shouldActionDisplayOnDataType()
    {
        return $this->dataType->slug == 'jellyfincustomers'; 
    }


    public function getAttributes()
    {
        if(!$this->data->status){
            $this->title = "Activar";
            $this->color = "btn-success";
            $this->icon = "voyager-smile";
        }

        return [
            'class' => 'btn '.$this->color.' enable-disable-user',
            'data-id'=>$this->data->id,
            'data-status'=>$this->data->status
        ];
    }

    public function getDefaultRoute()
    {
        return "#";
    }
}