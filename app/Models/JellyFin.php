<?php 

	namespace App\Models;
	use Havenstd06\LaravelJellyfin\Services\Jellyfin as JellyfinClient;
	use App\Models\JellyFinServer;
	use App\Models\JellyfinCustomer;
	use App\Models\JellyfinDemo;
	use Auth;
	use App\Models\User;
	use DB;

	class JellyFin {
		public $provider;

		public function __construct(){
			$this->provider = new JellyfinClient;
		}

		public function setCredentials(JellyFinServer $server){
			$validate = false;
			$config = [
			    'server_url'        => $server->host,
			    'token'             => $server->api_key,
			    
			    'application'       => 'Carlos Vargas Laravel Jellyfin / v1.0', // optional
			    'version'           => '10.8.8', // optional
			    
			    'validate_ssl'      => true,
			];

			$this->provider->setApiCredentials($config);

			$response = $this->provider->getSystemServerInformations();
			if(is_array($response)){
				$validate = true;
			}

			return $validate;
		}

		public function get_libaries(){
			$data = [];
			$data = $this->provider->getMediaFolders();
			return $data;
		}

		public function createUser(JellyfinCustomer $customer){
			$isValid = false;
			$jsonData = [];
			$laData = [];
			if($customer){
				$verify = $this->verifyUser($customer->name);

				if(!$verify){
					$jsonData = json_encode($this->provider->createUser($customer->name, $customer->password));
					$customer->json_data = $jsonData;
					$customer->save();
					$isValid = true;
				}else{
					$jsonData = json_encode($verify);
					$customer->json_data = $jsonData;
					$customer->save();
					$isValid = true;
				}
			}

			if($customer->jellyfinpackage){
				$laData = json_decode($customer->json_data);
				$libraries = explode(',',$customer->jellyfinpackage->libraries);

				$library_access_data = array(
					"AuthenticationProviderId"=>"Jellyfin.Server.Implementations.Users.DefaultAuthenticationProvider",
					"PasswordResetProviderId"=>"Jellyfin.Server.Implementations.Users.DefaultPasswordResetProvider",
				    "EnableAllFolders"=>false,
				    "EnabledFolders"=>$libraries,
				   	"MaxActiveSessions"=>$customer->screens
				);

				$this->provider->updateUserPolicy($laData->Id, $library_access_data);
			}

			return $isValid;
		}

		public function createDemo(JellyfinDemo $demo){
			$isValid = false;
			if($demo){
				$verify = $this->verifyUser($demo->name);
				if(!$verify){
					$demo->json_data = json_encode($this->provider->createUser($demo->name, $demo->password));
					$demo->save();
					$isValid = true;
				}
			}

			if($demo->jellyfinpackage){
				$laData = json_decode($demo->json_data);
				$libraries = explode(',',$demo->jellyfinpackage->libraries);

				$library_access_data = array(
					"AuthenticationProviderId"=>"Jellyfin.Server.Implementations.Users.DefaultAuthenticationProvider",
					"PasswordResetProviderId"=>"Jellyfin.Server.Implementations.Users.DefaultPasswordResetProvider",
				    "EnableAllFolders"=>false,
				    "EnabledFolders"=>$libraries
				);

				$this->provider->updateUserPolicy($laData->Id, $library_access_data);
			}

			return $isValid;
		}

		public function verifyUser($name){
			$dataUser = null;
			$data = $this->provider->getUsers();
			foreach($data as $user){
				if( trim($user['Name']) == trim($name) ){
					$dataUser = $user;
					break;
				}
			}
			return $dataUser;
		}

		public function deleteCredit($customerJF){
			$data = ['success'=>true, 'message'=>'Creditos descontados con exito!!'];

			$amount = $customerJF->duration->months;
	        if(!empty($customerJF->duration->amount)){
	            if($customerJF->duration->amount > 0){
	                $amount = intval($customerJF->duration->amount);
	            }
	        }

			if(Auth::user()->role_id == 3 || Auth::user()->role_id == 5){

               $user = User::findorfail(Auth::user()->id);
               if($user->total_credits >= $amount){
               		$current_credit = $user->total_credits;
	               DB::table('users')->where('id',$user->id)->update([
	                    'total_credits'=>($current_credit - intval($amount))
	               ]);
               }else{
               	$data = ['success'=>false, 'message'=>'No tienes suficientes creditos para realizar esta operacion!!'];
               }
               
	        }
	        return $data;
		}
	}