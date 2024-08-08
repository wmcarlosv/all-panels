<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class MasiveChangeServerAction extends AbstractAction
{
    public function getTitle()
    {
        return 'Masivo';
    }

    public function getIcon()
    {
        return 'voyager-double-right';
    }

    public function getPolicy()
    {
        return 'read';
    }

    public function shouldActionDisplayOnDataType()
    {
        return $this->dataType->slug == 'servers';
    }


    public function getAttributes()
    {
        return [
            'class' => 'btn btn-success change-masive-server',
            'data-server-id'=>$this->data->id,
            'data-server-name'=>$this->data->name_and_local_name
        ];
    }

    public function getDefaultRoute()
    {
        return "#";
    }
}