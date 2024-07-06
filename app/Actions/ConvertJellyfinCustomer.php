<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class ConvertJellyfinCustomer extends AbstractAction
{
    public function getTitle()
    {
        return 'Convertir a Cliente';
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
        return $this->dataType->slug == 'jellyfindemos';
    }


    public function getAttributes()
    {
        return [
            'class' => 'btn btn-success convert-customer',
            'data-id'=>$this->data->id
        ];
    }

    public function getDefaultRoute()
    {
        return "#";
    }
}