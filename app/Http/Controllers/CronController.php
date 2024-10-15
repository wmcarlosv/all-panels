<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Plex;
use App\Models\JellyFin;
use App\Models\JellyfinCustomer;
use App\Models\JellyfinDemo;
use App\Models\Demo;
use DB;

class CronController extends Controller
{
    private $plex;
    private $jellyfin;

    public function __construct(){
        $this->plex = new Plex();
        $this->jellyfin = new JellyFin();
    }

    public function verifySubscriptions(){
            $total = 0;
            $total_no_invited_id = 0;
            $plex = $this->plex;

            $customers = Customer::where('status', 'active')
            ->where(function ($query) {
                $query->where('date_to', '<', date('Y-m-d'));
            })->limit(5)->get();


            foreach ($customers as $data) {
                $server = $data->server;
                if(!empty($data->server->url) and !empty($data->server->token)){
                    $plex->setServerCredentials($server->url, $server->token);
                    if(isset($data->invited_id) and !empty($data->invited_id)){
                        if(strtotime($data->date_to) < strtotime(date('Y-m-d'))){

                            if(setting('admin.only_remove_libraries')){
                                $this->plex->managerLibraries($data->id, "delete");
                            }else{
                                $plex->provider->removeFriend($data->invited_id);

                                $data_user = $this->plex->loginInPlex($data->server->url, $data->server->token);
                                if(is_array($data_user)){
                                   $this->plex->removeClienteFromServer($data_user, $data->email); 
                                }

                            }
                           
                           DB::table('customers')->where('id',$data->id)->update(['status'=>'inactive']);
                           $total++; 
                        }
                    }else{
                        DB::table('customers')->where('id',$data->id)->update(['status'=>'inactive']);
                        $total_no_invited_id++;
                    }
                }
            }

            print "Total Cancelados: ".$total."\n Total Sin Invited ID: ".$total_no_invited_id."\n";

            $total_demos = 0;
            $total_demos_no_invited_id = 0;

            $demos = Demo::where('end_date','<',now())->limit(5)->get();

            foreach($demos as $demo){
                $server = $demo->server;
                if(!empty($demo->server->url) and !empty($demo->server->token)){
                    $plex->setServerCredentials($server->url, $server->token);
                    if(isset($demo->invited_id) and !empty($demo->invited_id)){
                        if(strtotime($demo->end_date) < strtotime(date('Y-m-d H:i:s'))){
                           $plex->provider->removeFriend($demo->invited_id);

                           $data_user = $this->plex->loginInPlex($demo->server->url, $demo->server->token);

                            if(is_array($data_user)){
                               $this->plex->removeClienteFromServer($data_user, $demo->email); 
                            }

                           DB::table('demos')->where('id',$demo->id)->delete();
                           $total_demos++; 
                        }
                    }else{
                        DB::table('demos')->where('id',$demo->id)->delete();
                        $total_demos_no_invited_id++;
                    }
                }
            }

            print "Total Demos Cancelados: ".$total_demos."\n Total Demos Sin Invited ID: ".$total_demos_no_invited_id."\n";


            //Ejecutando de Jellyfin
            //Customers
            $contadorCJS = 0;
            $customersJF = JellyfinCustomer::where('date_to', '<', date('Y-m-d'))->where('status',1)->limit(5)->get();

            foreach($customersJF as $cjf){
                if(strtotime($cjf->date_to) < strtotime(date('Y-m-d'))){
                    if(!empty($cjf->jellyfinserver)){
                        $this->jellyfin->setCredentials($cjf->jellyfinserver);
                        $user = json_decode($cjf->json_data, true);
                        if(is_array($user)){
                            if(array_key_exists('Id', $user)){
                                $this->jellyfin->provider->deleteUser($user['Id']);
                            }
                            $cjf->status = false;
                            $cjf->save();
                            $contadorCJS++;
                        } 
                    }else{
                        $cjf->status = false;
                        $cjf->save();
                        $contadorCJS++;
                    }
                }
            }

            //Demos
            $contadorDJS = 0;
            $demosJF = JellyfinDemo::where('date_to','<',now())->limit(5)->get();

            foreach($demosJF as $djf){
                if(strtotime($djf->date_to) < strtotime(date('Y-m-d H:i:s'))){
                    if(!empty($djf->jellyfinserver)){
                        $this->jellyfin->setCredentials($djf->jellyfinserver);
                        $user = json_decode($djf->json_data, true);
                        if(is_array($user)){
                            if(array_key_exists('Id', $user)){
                                $this->jellyfin->provider->deleteUser($user['Id']);
                            }
                            $this->jellyfin->provider->deleteUser($user['Id']);
                            $djf->delete();
                            $contadorDJS++;
                        } 
                    }else{
                        $djf->delete();
                        $contadorDJS++;
                    }
                }
            }

            if(setting("admin.limit_the_sessions")){
                 $this->plex->getSessionsAllServers();
            }

            print "Total Clientes Jellyfin Cancelados: ".$contadorCJS."\n Total Demos Jellyfin Cancelados: ".$contadorDJS."\n";
    }
}
