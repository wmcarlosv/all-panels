<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class ChangePasswordJellyfinCustomerAction extends AbstractAction
{
    public function getTitle()
    {
        return 'Cambiar Clave';
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
        return $this->dataType->slug == 'jellyfincustomers';
    }


    public function getAttributes()
    {
        return [
            'class' => 'btn btn-success change-password',
            'data-id'=>$this->data->id
        ];
    }

    public function getDefaultRoute()
    {
        return "#";
    }
}