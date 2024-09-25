<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class JellyfinConnectDeviceAction extends AbstractAction
{
    public function getTitle()
    {
        return 'Activar en Dispositivo';
    }

    public function getIcon()
    {
        return 'voyager-tv';
    }

    public function getPolicy()
    {
        return 'read';
    }

    public function shouldActionDisplayOnDataType()
    {
        return $this->dataType->slug == 'jellyfindemos' || $this->dataType->slug == 'jellyfincustomers';
    }


    public function getAttributes()
    {
        return [
            'class' => 'btn btn-success connect-device',
            'data-id'=>$this->data->id
        ];
    }

    public function getDefaultRoute()
    {
        return "#";
    }
}