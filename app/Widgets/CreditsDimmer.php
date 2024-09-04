<?php
  namespace App\Widgets;
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Str;
  use TCG\Voyager\Facades\Voyager;
  use Arrilot\Widgets\AbstractWidget;

  class CreditsDimmer extends AbstractWidget
  {

     protected $config = [];

     public function run()
     {
       $count = Auth::user()->total_credits;
       $string = trans_choice('Creditos', $count);

       return view('voyager::dimmer', array_merge($this->config, [
        'icon'   => 'voyager-diamond',
        'title'  => "{$count} {$string}",
        'text'   => __('Creditos', ['count' => $count, 'string' => Str::lower($string)]),
        'button' => [
            'text' => __('Solicitar Creditos'),
            'link'=>setting('admin.credits_number')
        ],
        'image' => voyager_asset('images/widget-backgrounds/02.jpg'),
      ]));
   }

   public function shouldBeDisplayed(){
      $role = Auth::user()->role_id;
      if($role == 5 || $role == 3){
        return true;
      }else{
        return false;
      }
   }
   
 }