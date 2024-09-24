<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class ExtendSubscriptionAction extends AbstractAction
{
    public function getTitle()
    {
        return 'Extender Membresia';
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
            'class' => 'btn btn-sm btn-success pull-right extend-subscription',
            'data-id'=>$this->data->id,
            'data-date_to'=>$this->data->date_to,
            'style'=>"margin-right:5px;"
        ];
    }

    public function getDefaultRoute()
    {
        return "#";
    }
}