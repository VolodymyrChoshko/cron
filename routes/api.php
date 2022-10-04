<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CronController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\LimitController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\KeyController;
use App\Http\Controllers\Api\KeysCodeController;
use App\Http\Controllers\Api\KeysRefController;
use App\Http\Controllers\Api\KeysSmsController;
use App\Http\Controllers\Api\AutoRenewController;
use App\Http\Controllers\Api\EpdController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\HttpSettingController;
use App\Http\Controllers\Api\SmsController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UsersCompaniesController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\UsersGroupsController;

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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth', function (Request $request) {
        return $request->user();
    
    });
    Route::delete('auth/logout', [AuthController::class,'logout']);
});
Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});

// Test mode (should be in auth:sanctum)
Route::apiResource('users', UserController::class);
Route::apiResource('notifications', NotificationController::class);
Route::apiResource('keys', KeyController::class);
Route::apiResource('keys_codes', KeysCodeController::class);
Route::apiResource('keys_refs', KeysRefController::class);
Route::apiResource('keys_smses', KeysSmsController::class);
Route::apiResource('crons', CronController::class);
Route::apiResource('payments', CronController::class);
Route::apiResource('auto_renew', AutoRenewController::class);
Route::apiResource('companies', CompanyController::class);
Route::apiResource('epds', EpdController::class);
Route::apiResource('groups', GroupController::class);
Route::apiResource('http_settings', HttpSettingController::class);
Route::apiResource('limits', LimitController::class);
Route::apiResource('orders', OrderController::class);
Route::apiResource('videos', VideoController::class);
Route::apiResource('country', CountryController::class);

Route::post('videos/new/upload', [VideoController::class, 'uploadVideo']);
Route::post('videos/hook-receive', [VideoController::class, 'hookVideoUploaded']);
Route::post('videos/test', [VideoController::class, 'test']);
Route::apiResource('sms', SmsController::class);

Route::get('report/daily_report', [ReportController::class, 'daily_report']);
Route::get('report/monthly_report', [ReportController::class, 'monthly_report']);
Route::get('report/weekly_report', [ReportController::class, 'weekly_report']);

Route::post('users_companies/getCompanies', [UsersCompaniesController::class, 'getCompanies']);
Route::post('users_companies/getUsers', [UsersCompaniesController::class, 'getUsers']);
Route::post('users_companies/addUsertoCompany', [UsersCompaniesController::class, 'addUsertoCompany']);
Route::post('users_companies/deleteUserfromCompany', [UsersCompaniesController::class, 'deleteUserfromCompany']);
Route::post('users_companies/addGrouptoCompany', [UsersCompaniesController::class, 'addGrouptoCompany']);

Route::post('users_groups/getGroups', [UsersGroupsController::class, 'getGroups']);
Route::post('users_groups/getUsers', [UsersGroupsController::class, 'getUsers']);
Route::post('users_groups/addUsertoGroup', [UsersGroupsController::class, 'addUsertoGroup']);
Route::post('users_groups/deleteUserfromGroup', [UsersGroupsController::class, 'deleteUserfromGroup']);