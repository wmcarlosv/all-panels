<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('get-months-duration/{duration_id}',[ApiController::class, 'get_months_duration']);
Route::get('get-extend-month-durations/{startdate}/{months}',[ApiController::class, 'get_extend_months_duration']);
Route::post('login-customer',[ApiController::class, 'loginCustomer']);
Route::post('get-libraries', [ApiController::class, 'getLibraries']);
Route::post('get-libraries-ids', [ApiController::class, 'getLibrariesIds'])->name('get_libraries');
Route::post('get-library', [ApiController::class, 'getLibrary']);
Route::post('search-library', [ApiController::class, 'searchLibrary']);
Route::get('get-active-sessions/{server_id}/{user_id?}', [ApiController::class, 'get_active_sessions']); 
Route::post('get-jellyfin-libraries', [ApiController::class, 'get_jellyfin_libraries'])->name('get_jellyfin_libraries');
Route::get('get-customers-by-server/{server_id}', [ApiController::class, 'getCustomersByServer']);
Route::post('move-customers-massive', [ApiController::class, 'move_massive_customer'])->name("move_customers_massive");
Route::post('view-sessions-by-user-jellyfin',[ApiController::class, 'view_sessions_by_user_jellyfin'])->name('view_sessions_by_user_jellyfin');
Route::post('disable-enable-customers', [ApiController::class, 'disable_enable_customer'])->name('disable_enable_customer');
