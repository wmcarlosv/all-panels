<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class JellyfinUserChangeServerAction extends AbstractAction
{
    public function getTitle()
    {
        return 'Cambiar de Servidor';
    }

    public function getIcon()
    {
        return 'voyager-resize-small';
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
            'class' => 'btn btn-sm btn-success pull-right change-server',
            'data-server_id'=>$this->data->jellyfinserver_id,
            'data-jellyfin_user_id'=>$this->data->id
        ];
    }

    public function getDefaultRoute()
    {
        return "#";
    }
}