<?php

namespace App\Actions;

use TCG\Voyager\Actions\AbstractAction;

class ViewActiveSessionsByUserJellyfinAction extends AbstractAction
{
    public function getTitle()
    {
        return 'Actividad';
    }

    public function getIcon()
    {
        return 'voyager-tv';
    }

    public function shouldActionDisplayOnDataType()
    {
        // show or hide the action button, in this case will show for posts model
        return $this->dataType->slug == 'jellyfincustomers';
    }

    public function getPolicy()
    {
        return 'read';
    }

    public function getAttributes()
    {
        $jd = json_decode($this->data->json_data, true);
        if(!empty($jd) && $this->data->status == true){
            return [
                'class' => 'btn btn-sm btn-primary pull-right view-active-sessions',
                'data-server_id'=>$this->data->jellyfinserver_id,
                'data-jellyfin_user_id'=>(!empty($jd['Id']) ? $jd['Id'] : null),
                'data-jellyfin_user'=>$this->data->name
            ];
        }else{
            return [
                'class' => 'btn btn-sm btn-primary pull-right view-active-sessions',
            ];
        }
        
    }

    public function getDefaultRoute()
    {
        return "#";
    }
}