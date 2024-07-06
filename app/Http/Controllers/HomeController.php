<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class HomeController extends VoyagerBaseController
{
    public function massiveChangeServer(){
        $this->authorize('browse_admin');

        return view('vendor.voyager.massive_change_server');
    }
}
