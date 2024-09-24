<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class RefreshServerLibrariesAction extends AbstractAction
{
    public function getTitle()
    {
        return 'Refrescar Librerias';
    }

    public function getIcon()
    {
        return 'voyager-refresh';
    }

    public function shouldActionDisplayOnDataType()
    {
        // show or hide the action button, in this case will show for posts model
        return $this->dataType->slug == 'servers';
    }

    public function getPolicy()
    {
        return 'read';
    }

    public function getAttributes()
    {
        return [
            'class' => 'btn btn-sm btn-success pull-right view-refresh-server-libraries',
            'data-id'=>$this->data->id,
            'data-name'=>$this->data->name_and_local_name
        ];
    }

    public function getDefaultRoute()
    {
        return "#";
    }
}