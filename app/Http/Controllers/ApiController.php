<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Duration;
use App\Models\Customer;
use App\Models\Plex;
use App\Models\JellyFin;
use App\Models\Server;
use App\Models\JellyfinServer;
use App\Models\JellyfinCustomer;
use Havenstd06\LaravelPlex\Classes\FriendRestrictionsSettings;
use DB;
use File;
use App\Models\Proxy;
use Session;
use App\Models\User;
use App\Models\Movement;
use App\Models\Demo;
use Auth;
use Storage;
use Carbon\Carbon;

class ApiController extends Controller
{
    private $plex;
    private $jellyfin;

    public function __construct(){
        $this->plex = new Plex();
        $this->jellyfin = new JellyFin();
    }

    public function move_massive_customer(Request $request){
        $data = [];
        $server_from_id = $request->server_from_id;
        $server_to_id = $request->server_to_id;
        $customer_id = $request->customer_id;
        $delete_old_server = $request->delete_old_server;
        $generate_new_email = $request->generate_new_email;
        $how_set_package = $request->how_set_package;
        $package_id = $request->package_id;
        $server_is_baned = $request->server_is_baned;

        $server_from = Server::find($server_from_id);
        $server_to = Server::find($server_to_id);
        $customer = Customer::find($customer_id);

        $newPackage = null;

        if($how_set_package == "compare"){
              if(!empty($customer->package_id)){
                foreach($server_to->packages as $package){
                    if( trim(strtolower($package->name)) == trim(strtolower($customer->package->name)) ){
                        $newPackage = $package->id;
                        break;
                    }
                }
            }
        }else if ($how_set_package == "default_package"){
            if(!empty($package_id)){
                $newPackage = $package_id;
            }
        }

        //Updated Package
        $customer->package_id = $newPackage;
        $customer->save();

        $customer = Customer::find($customer_id);

        if($server_is_baned == 'Y'){
            $this->plex->setServerCredentials($server_to->url, $server_to->token);
            $user = $this->plex->loginInPlex($customer->email, $customer->password);

            if(!is_array($user)){
                $data['success'] = false;
                $data['error']['login_in_plex'] = "las credenciales de las cuenta no son correctas, es posible que el correo o la clave hayan sido cambiadas";
                $data['message'] = "Error de Cambio.";
            }else{
                $this->plex->createPlexAccountNotCredit($customer->email, $customer->password, $customer, false);
                $the_data = DB::table('customers')->select('invited_id')->where('id',$customer->id)->get();
                if(empty($the_data[0]->invited_id)){
                    $data['success'] = false;
                    $data['error'] = "Ocurrio un error al crear la cuenta";
                    $data['message'] = "Error de Cambio.";
                }else{
                    $customer->server_id = $server_to->id;
                    $customer->save();

                    $customer = Customer::with("package")->find($customer_id);

                    $data['success'] = true;
                    $data['message'] = "Cambio Realizado con Exito!!";
                    $data['customer'] = $customer;
                }
            }
        }else{
             //Delete from Old Server
            $this->plex->setServerCredentials($server_from->url, $server_from->token);
            $this->plex->provider->removeFriend($customer->invited_id);

            //Add to New Server
            $this->plex->setServerCredentials($server_to->url, $server_to->token);
            $user = $this->plex->loginInPlex($customer->email, $customer->password);

            if(!is_array($user)){
                $data['success'] = false;
                $data['error'] = "las credenciales de las cuenta no son correctas, es posible que el correo o la clave hayan sido cambiadas";
                $data['message'] = "Error de Cambio.";
            }else{
                $this->plex->createPlexAccountNotCredit($customer->email, $customer->password, $customer, false);
                $the_data = DB::table('customers')->select('invited_id')->where('id',$customer->id)->get();
                if(empty($the_data[0]->invited_id)){
                    $data['success'] = false;
                    $data['error'] = "Ocurrio un error al crear la cuenta";
                    $data['message'] = "Error de Cambio.";
                }else{
                    $customer->server_id = $server_to->id;
                    $customer->save();

                    $customer = Customer::with("package")->find($customer_id);

                    $data['success'] = true;
                    $data['message'] = "Cambio Realizado con Exito!!";
                    $data['customer'] = $customer;
                }
            }

        }

        return response()->json($data);
    }

    public function get_months_duration($duration_id){
        $data = Duration::findorfail($duration_id);
        $date = $this->addMonthsToCurrentDate($data->months);
        return response()->json(['new_date'=>$date, 'screes'=>$data->screens]);
    }

    public function addMonthsToCurrentDate($monthsToAdd) {
        // Get the current date
        $startDate = new \DateTime();

        // Add the specified number of months
        $startDate->modify("+$monthsToAdd months");

        // Format the updated date as YYYY-MM-DD
        $updatedDate = $startDate->format("Y-m-d");

        return $updatedDate;
    }

    public function get_extend_months_duration($actualToDate, $monthsToAdd) {
        // Get the current date
        $startDate = new \DateTime($actualToDate);
        // Add the specified number of months
        $startDate->modify("+$monthsToAdd months");

        // Format the updated date as YYYY-MM-DD
        $updatedDate = $startDate->format("Y-m-d");

        return response()->json(['date'=>$updatedDate]);
    }

    public function loginCustomer(Request $request){
        $email = $request->email;
        $password = $request->password;
        $data = [];
        $customer = Customer::where('email',$email)->where('password',$password)->first();

        if($customer){
            if($customer->status == 'active'){
                $data = ['message'=>'Bienvenido!!', 'success'=>true, 'data'=>$customer];
            }else{
                $data = ['message'=>'Tu subscripcion esta inactiva, por favor contacta con tu vendedor!!', 'success'=>false];
            }
        }else{
            $data = ['message'=>'Email o Password Incorrectos!!', 'success'=>false];
        }

        return response()->json($data);
    }

    public function getLibraries(Request $request){
        $server_id = $request->server_id;
        $this->activeServer($server_id);
        return response()->json(['response'=>$this->plex->provider->getLibraries()['MediaContainer']['Directory'], 'server_data'=>$this->plex->serverData]);
    }

    public function getLibrariesIds(Request $request){
        $server_id = $request->server_id;
        $server = Server::findorfail($server_id);

        $this->plex->setServerCredentials($server->url, $server->token);

        $libraries = [];
        $libraries_array = $this->plex->provider->getServerDetail();

        if(is_array($libraries_array)){
            if(intval($libraries_array['MediaContainer']['size']) > 0){
                if(array_key_exists("children", $this->plex->provider->getServerDetail()['MediaContainer']['children'][0]['Server'])){
                    $libraries = $this->plex->provider->getServerDetail()['MediaContainer']['children'][0]['Server']['children'];
                }
            }
        }

        return response()->json(['response'=>$libraries]);
    }

    public function getLibrary(Request $request){
        $server_id = $request->server_id;
        $library_key = $request->library_key;
        $this->activeServer($server_id);
        return response()->json(['response'=>$this->plex->provider->getLibrary($library_key)['MediaContainer']['Metadata'], 'server_data'=>$this->plex->serverData]);
    }

    public function activeServer($server_id){
        $server = Server::findorfail($server_id);
        $this->plex->setServerCredentials($server->url, $server->token);
    }

    public function searchLibrary(Request $request){
        $server_id = $request->server_id;
        $q = $request->q;
        $this->activeServer($server_id);
        return response()->json(['response'=>$this->plex->provider->searchLibrary($q, 20), 'server_data'=>$this->plex->serverData]);
    }

    public function change_server(Request $request){
        $data = [];
        $customer = Customer::findorfail($request->id);
        $server_to = Server::findorfail($request->server_id);

        $this->plex->setServerCredentials($server_to->url, $server_to->token);

        if($customer->password != "#5inCl4ve#"){
            $user = $this->plex->loginInPlex($customer->email, $customer->password);
            if(!is_array($user)){
                $data =[
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
        }else{
            $user = $this->plex->provider->validateUser($customer->email);

            if($user['response']['status'] != "Valid user"){
                $data =[
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
        }

        if(isset($customer->invited_id) and !empty($customer->invited_id)){
            /*Remove Before Server*/
            $server = Server::findorfail($customer->server_id);
            $this->plex->setServerCredentials($server->url, $server->token);
            $this->plex->provider->removeFriend($customer->invited_id);

            /* Add to New Server*/
            $this->plex->setServerCredentials($server_to->url, $server_to->token);
            $plex_data = $this->plex->provider->getAccounts();
            if(!is_array($plex_data)){
                $data = [
                    'success'=>false,
                    'message'=>"El servidor a donde quieres mover al cliente, tiene problemas con sus credenciales, por favor verificalas y vuelve a intentar!!"
                ];   
            }else{
                if($customer->password != "#5inCl4ve#"){
                    $this->plex->createPlexAccountNotCredit($customer->email, $customer->password, $customer);
                }else{
                    $this->plex->createPlexAccountNoPasswordNoCredit($customer->email, $customer);
                }
                
                $the_data = DB::table('customers')->select('invited_id')->where('id',$customer->id)->get();
                if(empty($the_data[0]->invited_id)){
                    $data = [
                        'success'=>false,
                        'message'=>"Ocurrio un error al momento de realizar el cambio de servidor, por favor utilice la opcion de reparar cuenta para solventar este problema!!"
                        ];
                }else{
                    if(!empty($server_to->limit_accounts)){
                        $tope = (intval($server_to->limit_accounts)-intval($server_to->customers->count()));
                        if($tope == 0){
                            /*$server_to->status = 0;
                            $server_to->save();*/
                            DB::table('servers')->where('id',$server->id)->update([
                                'status'=>0
                            ]);
                        }
                    }

                    $data = [
                        'success'=>true,
                        'message'=>"El cambio de servidor se ha realizado con exito, esta pagina se recargara en breve!!"
                    ];

                    $customer->server_id = $server_to->id;
                    $customer->save();
                }
            }
        }else{
            $data = [
                'success'=>false,
                'message'=>"El cliente no estar correctamente vinculado con plex, por favor verifica bien los datos!!"
            ];
        }

        return response()->json($data);
    }

    public function updateLibraries(Request $request,$server_id){
        $data = [];
        $librarySectionIds = [];
        $server = Server::findorfail($server_id);

        $this->plex->setServerCredentials($server->url, $server->token);
        $libraries = $request->libraries;
        $cont = 0;
        foreach($libraries as $library){
            $this->plex->refreshLibraries($library);
            $cont++;
        }

        $data = [
            'success'=>true,
            'message'=>"Se Actualizaron: ".$cont." Librerias!!"
        ];

        return response()->json($data);
    }

    public function change_status($customer_id){
        $customer = Customer::findorfail($customer_id);
        $server = Server::findorfail($customer->server_id);
        $data = [];

        $this->plex->setServerCredentials($customer->server->url, $customer->server->token);

        if($customer->password != "#5inCl4ve#"){
            $user = $this->plex->loginInPlex($customer->email, $customer->password);
            if(!is_array($user)){
                $data =[
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
        }else{
            $user = $this->plex->provider->validateUser($customer->email);
            if(is_array($user)){
                if($user['response']['status'] != "Valid user"){
                    $data =[
                        'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                        'success'=>false
                    ];
                    return response()->json($data);
                }
            }else{
                $data = [
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
            
        }

        if($customer->status == "active"){
            $this->plex->setServerCredentials($server->url, $server->token);

            if(setting('admin.only_remove_libraries')){
                $this->plex->managerLibraries($customer->id, "delete");
            }else{
                $this->plex->provider->removeFriend($customer->invited_id);
                $customer->plex_user_name = null;
                $customer->plex_user_token = null;
                $customer->invited_id = null;
            }
            
            $customer->status = "inactive";
            $customer->save();

            $this->plex->addMovement("Inactivacion de Cuenta",$customer);

            $data = [
                'success'=>true,
                'message'=>'Cliente Inhabhilitado con Exito!!'
            ];
            
        }else{
            $this->plex->setServerCredentials($server->url, $server->token);
            $plex_data = $this->plex->provider->getAccounts();
            if(!is_array($plex_data)){
                $data = [
                    'success'=>false,
                    'message'=>"El servidor a donde quieres mover al cliente, tiene problemas con sus credenciales, por favor verificalas y vuelve a intentar!!"
                ];   
            }else{

                if($customer->password != "#5inCl4ve#"){
                    if(setting('admin.only_remove_libraries')){
                        $this->plex->managerLibraries($customer->id);
                    }else{
                       $this->plex->createPlexAccountNotCredit($customer->email, $customer->password, $customer);
                    }
                }else{
                    if(setting('admin.only_remove_libraries')){
                         $this->plex->managerLibraries($customer->id);
                    }else{
                        $this->plex->createPlexAccountNoPasswordNoCredit($customer->email, $customer);
                    }
                }
                
                $the_data = DB::table('customers')->select('invited_id')->where('id',$customer->id)->get();

                if(empty($the_data[0]->invited_id)){
                    $data = [
                        'success'=>false,
                        'message'=>"Ocurrio un error al momento de habilitar la cuenta, por favor utilice la opcion de reparar cuenta para solventar este problema!!"
                        ];
                }else{
                    if(!empty($server->limit_accounts)){
                        $tope = (intval($server->limit_accounts)-intval($server->customers->count()));
                        if($tope == 0){
                            DB::table('servers')->where('id',$server->id)->update([
                                'status'=>0
                            ]);
                        }
                    }

                    $data = [
                        'success'=>true,
                        'message'=>"Cliente Habilitado con Exito!"
                    ];

                    $customer->status = "active";
                    $customer->server_id = $server->id;
                    $customer->save();
                }
            }
        }

        return response()->json($data);
    }

    public function import_proxies(Request $request){
        $proxies = [];
        $file = $request->file('proxies');
        $content = $file->get();
        $cont = 0;
        $message = "";
        foreach(explode(PHP_EOL, $content) as $proxy) {
            $arr_proxy = explode(':', $proxy);
            if(!empty($arr_proxy[0]) and !empty($arr_proxy[1])){
                $ip = $arr_proxy[0];
                $port = str_replace("\r","",$arr_proxy[1]);
                $verify = Proxy::where("ip",$ip)->where("port",$port)->first();
                if(!$verify){
                    $proxy = new Proxy();
                    $proxy->ip = $ip;
                    $proxy->port = $port;
                    $proxy->save();
                    $cont++;
                }
            }
        }

        $redirect = redirect()->route("voyager.proxies.index");

        if($cont == 0){
            $message = "No se importo ningun proxy Nuevo!!";
        }else{
            $message = "Se importaron: ".$cont.", proxies de manera exitosa!!";
        }

        return $redirect->with([
                'message'    => $message,
                'alert-type' => 'success',
            ]);
    }

    public function convert_iphone(Request $request){
        $customer = Customer::findorfail($request->pp_customer_id);
        $server = Server::findorfail($request->server_pp_id);
        $pin = $request->pin;

        if($customer->password != "#5inCl4ve#"){
            $user = $this->plex->loginInPlex($customer->email, $customer->password);
            if(!is_array($user)){
                $data =[
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
        }else{
            $user = $this->plex->provider->validateUser($customer->email);

            if($user['response']['status'] != "Valid user"){
                $data =[
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
        }
        
        $isValid = $this->plex->createHomeUser($server, $customer, $pin);

        if(!$isValid){
            return redirect()->route("voyager.customers.index")->with([
                'message'=>'Ocurrio un error a tratar de convertir esta cuenta en iphone, posiblemente sobrepasate el limite de 15 usuarios como usuarios administrados!!',
                'alert-type'=>'error'
            ]);
        }

        if($customer->server_id != $server->id){
            //Eliminamos del server anterior
            $this->plex->setServerCredentials($customer->server->url, $customer->server->token);
            $this->plex->provider->removeFriend($customer->invited_id);
            //Insertamos en el server Nuevo
            $this->plex->createPlexAccountNotCredit($server->url, $server->token, $customer);
        }

        

        $customer->server_id = $server->id;
        $customer->pin = $pin;
        $customer->save();

        $la_data = Customer::findorfail($customer->id);
        Session::flash('modal',$la_data);

        return redirect()->route("voyager.customers.index")->with([
            'message'=>'Cuenta Convertida a Iphone de Manera Exitosa!!',
            'alert-type'=>'success'
        ]);
    }

    public function remove_iphone($customer_id){
        $customer = Customer::findorfail($customer_id);
        $servers = [];
        $selectedServer = null;

        if( setting('admin.dynamic_server') ){
            $servers = Server::where('status',1)->server()->get();
            if($servers->count() > 1){
                $selectedServer = $servers[ rand(0, ($servers->count() - 1)) ];
            }else{
                $selectedServer = $servers[0];
            }
        }else{
            $selectedServer = $customer->server;
        }

        $userPin = $this->plex->loginInPlex($customer->email, $customer->password);
        $this->plex->removeHomeUserPin($userPin, $customer->pin);
        $this->plex->removeHomeUser($customer);

        //Remove Actual Server
        $this->plex->setServerCredentials($customer->server->url, $customer->server->token);
        $this->plex->provider->removeFriend($customer->invited_id);

        //Add New Server
        $this->plex->setServerCredentials($selectedServer->url, $selectedServer->token);
        $this->plex->createPlexAccount($customer->email, $customer->password, $customer);

        $customer->server_id = $selectedServer->id;
        $customer->pin = null;
        $customer->save();

        return redirect()->route("voyager.customers.index")->with([
            'message'=>'Cuenta Removida de Iphone de Manera Exitosa!!',
            'alert-type'=>'success'
        ]);
    }

    public function repair_account($customer_id){
        $customer = Customer::findorfail($customer_id);
        $this->plex->setServerCredentials($customer->server->url, $customer->server->token);
        $user = null;

        if($customer->password != "#5inCl4ve#"){
            $user = $this->plex->loginInPlex($customer->email, $customer->password);
            if(!is_array($user)){
                return redirect()->route("voyager.customers.index")->with([
                    'message'=>'la cuenta que intenta reparar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'alert-type'=>"error"
                ]);
            }
        }else{
            $user = $this->plex->provider->validateUser($customer->email);
            if(is_array($user)){
                if($user['response']['status'] != "Valid user"){

                    return redirect()->route("voyager.customers.index")->with([
                        'message'=>'la cuenta que intenta Reparar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                        'alert-type'=>"error"
                    ]);
                }
            }else{

                return redirect()->route("voyager.customers.index")->with([
                    'message'=>'la cuenta que intenta reparar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'alert-type'=>"error"
                ]);
            }
            
        }

        $this->plex->setServerCredentials($customer->server->url, $customer->server->token);

        $plex_data = $this->plex->provider->getAccounts();

        if(!is_array($plex_data)){

            return redirect()->route("voyager.customers.index")->with([
                'message'=>'El servidor a donde quieres mover al cliente, tiene problemas con sus credenciales, por favor verificalas y vuelve a intentar!!',
                'alert-type'=>'error'
            ]);

        }else{
            
            //Remove Plex
            if(!empty($customer->invited_id)){
                $this->plex->provider->removeFriend($customer->invited_id);
                if($customer->password != "#5inCl4ve#"){
                    $this->plex->createPlexAccountNotCredit($customer->email, $customer->password, $customer);
                }else{
                    $this->plex->createPlexAccountNoPasswordNoCredit($customer->email, $customer);
                }
                
            }else{
                //Add Plex
                if($customer->password != "#5inCl4ve#"){
                    $this->plex->createPlexAccount($customer->email, $customer->password, $customer);
                }else{
                    $this->plex->createPlexAccountNoPassword($customer->email, $customer);
                }
                
            }

            $the_data = DB::table('customers')->select('invited_id')->where('id',$customer->id)->get();
            if(empty($the_data[0]->invited_id)){
                return redirect()->route("voyager.customers.index")->with([
                    'message'=>'Ocurrio un Error al Reparar la cuenta por favor Contacte con el Administrador!!',
                    'alert-type'=>'error'
                ]);
            }else{
                return redirect()->route("voyager.customers.index")->with([
                    'message'=>'Cuenta Reparada con Exito!!',
                    'alert-type'=>'success'
                ]);
            }
        }
    }

    public function change_password_user_plex(Request $request){
        $customer = Customer::findorfail($request->chp_customer_id);

        if($customer->password != "#5inCl4ve#"){
            $user = $this->plex->loginInPlex($customer->email, $customer->password);
            if(!is_array($user)){
                return redirect()->route("voyager.customers.index")->with([
                    'message'=>'La cuenta que intenta cambiar la clave es invalida!!',
                    'alert-type'=>'error'
                ]);
            }
        }else{
            $user = $this->plex->provider->validateUser($customer->email);

            if($user['response']['status'] != "Valid user"){
                return redirect()->route("voyager.customers.index")->with([
                    'message'=>'La cuenta que intenta cambiar la clave es invalida!!',
                    'alert-type'=>'error'
                ]);
            }
        }
        
        $response = $this->plex->changeUserPlexPassword($request->chp_current_password, $request->chp_new_password, $request->remove_all_devices, $user);

        if($response){
            return redirect()->route("voyager.customers.index")->with([
                'message'=>'Ocurrio un error al tratar de actualizar la clave, por favor verifica que el formato sea el corecto, mensaje de plex:"'.(string) $response->error->attributes()->{'message'}.'"',
                'alert-type'=>'error'
            ]);
        }else{
            $customer->password = $request->chp_new_password;
            $customer->save();

            return redirect()->route("voyager.customers.index")->with([
                'message'=>'La clave fue cambiada de manera exitosa, y tambien se cerro session en todos los dispositivos!',
                'alert-type'=>'success'
            ]);
        }
    }

    public function change_user(Request $request){

        $user = User::findorfail($request->user_asigned_id);
        $customer = Customer::findorfail($request->user_asigned_customer_id);
        $duration = Duration::findorfail($customer->duration_id);

        $amount = $duration->months;
        if(!empty($duration->amount)){
            if($duration->amount > 0){
                $amount = intval($duration->amount);
            }
        }

        if($user->role_id == 3 || $user->role_id == 5){
           if($user->total_credits == 0 || $user->total_credits < $amount){
            return redirect()->route("voyager.customers.index")->with([
                'message'    => __('El usuario que intenta asignar no cuenta con los creditos suficientes!!'),
                'alert-type' => 'error',
            ]);
           }
        }

        $this->removeCredit($customer, $duration, $user);

        $customer->user_id = $user->id;
        $customer->update();

        return redirect()->route("voyager.customers.index")->with([
            'message'=>'La asignacion del usuario se realizo de manera satisfactoria!!',
            'alert-type'=>'success'
        ]);
    }

    public function removeCredit(Customer $customer, Duration $duration, $user){

        $amount = $duration->months;
        if(!empty($duration->amount)){
            if($duration->amount > 0){
                $amount = intval($duration->amount);
            }
        }

        if($user->role_id == 3 || $user->role_id == 5){
           if(!empty($customer->invited_id)){
               $user = User::findorfail($user->id);
               $current_credit = $user->total_credits;
               DB::table('users')->where('id',$user->id)->update([
                    'total_credits'=>($current_credit - intval($amount))
               ]);

               $customer->duration_id = $duration->id;
               $customer->save();
           }
        }
        
    }

    public function import_from_plex(Request $request){
        $accounts = $request->accounts_for_import;
        $contador = 0;
        foreach($accounts as $account){

            $acc = json_decode($account, true);
            $date_from = $request->input('date_from_'.$acc['id']);
            $date_to = $request->input('date_to_'.$acc['id']);

            if(!empty($date_from) and !empty($date_to)){

                $verifyAccount = Customer::where("email", $acc['email'])->get();

                if( $verifyAccount->count() == 0 ){
                   $customer = new Customer();
                    $customer->email = !empty($acc['email']) ? $acc['email'] : $acc['username']."@pending";
                    $customer->plex_user_name = $acc['username'];
                    $customer->invited_id = $acc['id'];
                    $customer->date_from = $date_from;
                    $customer->date_to = $date_to;
                    $customer->duration_id = Duration::first()->id;
                    $customer->password = "#5inCl4ve#";
                    $customer->server_id = $request->server_id;
                    $customer->save();
                    $contador++; 
                }
            }
        }


        $redirect = redirect()->back();
        return $redirect->with([
            'message'    => __('Se Importaron '.$contador.' cuentas de manera exitosa!!'),
            'alert-type' => 'success',
        ]);
    }

    public function get_active_sessions($server_id, $user_id=null){
        $server = Server::findorfail($server_id);
        $data = [];
        $cont = 0;
        $user = null;
        $this->plex->setServerCredentials($server->url, $server->token);

        $customers = [];

        $sessions = $this->plex->provider->getNowPlaying();

        if($user_id){
            $customers = Customer::where('status','active')->where('user_id',$user_id)->pluck('plex_user_name')->toArray();
            $user = User::findorfail($user_id);

            if(is_array($sessions)){
                if(intval($sessions['MediaContainer']['size']) > 0){
                    $server_url = $this->plex->serverData['scheme']."://".$this->plex->serverData['address'].":".$this->plex->serverData['port'];

                    foreach($sessions['MediaContainer']['Metadata'] as $session){

                        if($user->role_id != 1 && $user->role_id != 4){
                            if( !in_array($session['User']['title'], $customers) ){
                                continue;
                            }
                        }

                        $data[$cont]['user'] = [
                            'avatar'=> $session['User']['thumb'],
                            'name'=> $session['User']['title']
                        ];

                        $data[$cont]['player'] = [
                            'ip'=>$session['Player']['address'],
                            'device'=>!empty($session['Player']['title']) ? $session['Player']['title'] : $session['Player']['product']
                        ];

                        $data[$cont]['media'] = [
                            'title'=>!empty($session['originalTitle']) ? $session['originalTitle']: $session['title'],
                            'cover'=>$server_url.(isset($session['art']) ? $session['art'] : $session['thumb'])."?X-Plex-Token=".$this->plex->serverData['token']
                        ];

                        $cont++;
                    }
                }            
            }

        }else{
            if(is_array($sessions)){
                if(intval($sessions['MediaContainer']['size']) > 0){
                    $server_url = $this->plex->serverData['scheme']."://".$this->plex->serverData['address'].":".$this->plex->serverData['port'];
                    foreach($sessions['MediaContainer']['Metadata'] as $session){

                        $data[$cont]['user'] = [
                            'avatar'=> $session['User']['thumb'],
                            'name'=> $session['User']['title']
                        ];

                        $data[$cont]['player'] = [
                            'ip'=>$session['Player']['address'],
                            'device'=>!empty($session['Player']['title']) ? $session['Player']['title'] : $session['Player']['product']
                        ];

                        $data[$cont]['media'] = [
                            'title'=>!empty($session['originalTitle']) ? $session['originalTitle']: $session['title'],
                            'cover'=>$server_url.(isset($session['art']) ? $session['art'] : $session['thumb'])."?X-Plex-Token=".$this->plex->serverData['token']
                        ];

                        $cont++;
                    }
                }            
            }
        }

        return response()->json($data);
    }

    public function activate_device(Request $request){
        $type = "customer";
        
        if(isset($request->type) and !empty($request->type)){
            $type = "demo";
        }

        if($type == "customer"){
            $data = Customer::find($request->customer_id);
        }else{
            $data = Demo::find($request->customer_id);
        }
        
        $code = $request->code;
        $response = $this->plex->activateDevice($code, $data);

        $redirect = redirect()->back();

        if($response['success']){
            return $redirect->with([
                'message'=>'Cuenta activada en el dispositivo de manera satisfactoria!!',
                'alert-type'=>'success'
            ]);
        }else{
            return $redirect->with([
                'message'=>$response['message'],
                'alert-type'=>'error'
            ]);
        }
    }

    public function remove_libraries($customer_id){

        $this->plex->managerLibraries($customer_id, "delete");

        $redirect = redirect()->back();
        return $redirect->with([
            'message'    => __('Librerias Removidas con Exito!!'),
            'alert-type' => 'success',
        ]);
    }

    public function add_libraries($customer_id){

        $this->plex->managerLibraries($customer_id);

        $redirect = redirect()->back();
        return $redirect->with([
            'message'    => __('Librerias Agregadas con Exito!!'),
            'alert-type' => 'success',
        ]);
    }

    public function resend_invitation($id){
        $customer = Customer::findorfail($id);
        $redirect = redirect()->back();
        $this->plex->setServerCredentials($customer->server->url, $customer->server->token);
        $this->plex->createPlexAccountNoPasswordNoCredit($customer->email, $customer);
        Session::flash('modal',$customer);
        return $redirect->with([
            'message'    => "Invitacion Reenviada con Exito!!",
            'alert-type' => 'success'
        ]);
    }

    public function import_customer_from_magic(Request $request){
        $redirect = redirect()->back();
        if($request->hasFile("customers")){
            $file = $request->file('customers');
            $fileContents = file($file->getPathname());
            $index = 0;
            $importados = 0;
            $actualizados = 0;
             foreach ($fileContents as $line) {
                $data = str_getcsv($line,';');
                if($index > 0){
                    DB::beginTransaction();
                    $customer = new Customer();
                    try {
                        $verifyCustomer = Customer::where('email',$data[4])->first();
                        if(!$verifyCustomer){
                            $customer->name = $data[2];
                            $customer->user_id = $data[1];
                            $customer->phone = $data[3];
                            $customer->email = $data[4];
                            $customer->plex_user_id = "importado_de_magic";
                            $customer->password = $data[5];
                            $customer->plex_user_name = $data[10];
                            $customer->plex_user_token = $data[11];
                            $customer->invited_id = $data[12];
                            $customer->duration_id = Duration::first()->id;
                            $customer->server_id = intval($data[16]);
                            $customer->screens = intval($data[17]);
                            $customer->date_to = date('Y-m-d',strtotime($this->convert_date($data[15])));
                            $customer->date_from = date('Y-m-d');
                            $customer->save();
                            $importados++;
                        }else{
                            $verifyCustomer->date_from = date('Y-m-d');
                            $verifyCustomer->date_to = date('Y-m-d',strtotime($this->convert_date($data[15])));
                            $verifyCustomer->update();
                            $actualizados++;
                        }
                        DB::commit();
                    } catch (\Exception $e) {
                        dd($e->getMessage());
                        DB::rollback();
                    }
                }
                $index++;
            }
        }

        return $redirect->with([
            'message'    => $importados." Clientes Importados con Exito y ".$actualizados." Clientes Actualizados!!",
            'alert-type' => 'success'
        ]);
    }

    public function get_jellyfin_libraries(Request $request){
        $data = [];
        if($request->server_id){
            $server = JellyfinServer::find($request->server_id);
            $response = $this->jellyfin->setCredentials($server);
            if($response){
               $data = $this->jellyfin->get_libaries(); 
            }   
        }

        return response()->json($data);
    }

    public function convert_date($string_date){
        $dateString = $string_date;
        $dateString = str_replace(['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'],
            ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'], $dateString);
        $date = Carbon::createFromFormat('d-F-y', $dateString);
        $formattedDate = $date->format('Y/m/d');
        return $formattedDate;
    }

    public function extend_membership_jellyfin(Request $request){

        $customerJF = JellyfinCustomer::find($request->jellyfin_customer_id);
        $redirect = redirect()->back();
        $server = JellyfinServer::find($customerJF->jellyfinserver_id);
        $response = $this->jellyfin->setCredentials($server);

        if(!$response){
            return $redirect->with([
                'message'    => "Ocurrio un error al conectarse al servidor, verifica que las credenciales sean las Correctas",
                'alert-type' => 'error',
            ]);
        }

         DB::beginTransaction();

         $oldDateTo = $customerJF->date_to;

         $customerJF->duration_id = $request->duration_id;
         $customerJF->date_to = $request->date_to;
         $customerJF->update();

        if( strtotime($oldDateTo) < strtotime(now()) ){
            if( !$this->jellyfin->createUser($customerJF) ){
                DB::rollback();
                return $redirect->with([
                    'message'    => "Ocurrio un error al crear el usuario, por favor asegurese que el nombre de usuario ya no este en uso!!",
                    'alert-type' => 'error',
                ]);
            }else{
                $respuesta = $this->jellyfin->deleteCredit($customerJF);
                if($respuesta['success']){
                    DB::commit();
                }else{
                   DB::rollback();
                   return $redirect->with([
                        'message'    => $respuesta['message'],
                        'alert-type' => 'error',
                    ]);
                }
            }
        }else{
            $respuesta = $this->jellyfin->deleteCredit($customerJF);
            if($respuesta['success']){
                DB::commit();
            }else{
               DB::rollback();
               return $redirect->with([
                    'message'    => $respuesta['message'],
                    'alert-type' => 'error',
                ]);
            }
        }

        return $redirect->with([
            'message'    => "Subscripcion Extendedia con Exito!!",
            'alert-type' => 'success',
        ]);

    }

    public function removeFromServer($server_id, $invited_id){
        $server = Server::find($server_id);
        $userData = $this->plex->loginInPlex($server->url, $server->token);
        $this->plex->removeServer($userData, $invited_id);
        return response()->json(['success'=>true]);
    }

    public function getCustomersByServer($server_id = null){
        $customers = [];
        if($server_id){
            $customers = Customer::with(['package'])->where('server_id', $server_id)->where('status','active')->get();
        }
        return response()->json($customers);
    }

}