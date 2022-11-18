<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CronController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CodeCheckController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\ResetPasswordController;
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
use App\Http\Controllers\Api\UsersNotificationsController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\VideoPlayerController;
use Illuminate\Support\Facades\Auth;
use App\Permissions\Permission;

use Aws\Athena\AthenaClient;

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

Route::middleware(['auth:sanctum'])->group(function () {

    Route::middleware('throttle:auth')->group(function () {
        Route::get('/auth/user', function (Request $request) {
            return $request->user();
        
        });
        Route::delete('auth/logout', [AuthController::class,'logout']);
        
        Route::get('report/daily_report', [ReportController::class, 'daily_report']);
        Route::get('report/monthly_report', [ReportController::class, 'monthly_report']);
        Route::get('report/weekly_report', [ReportController::class, 'weekly_report']);
        
        Route::get('users/{user}/companies', [UserController::class, 'getCompanies'])->middleware(['ability:'.Permission::CAN_ALL.','.Permission::CAN_USER_GET_COMPANIES]);
        Route::get('users/{user}/groups', [UserController::class, 'getGroups'])->middleware(['ability:'.Permission::CAN_ALL.','.Permission::CAN_USER_GET_GROUPS]);

        Route::get('companies/{company}/users', [CompanyController::class, 'getUsers'])->middleware(['ability:'.Permission::CAN_ALL.','.Permission::CAN_COMPANY_GET_USERS]);
        Route::post('companies/add-user', [CompanyController::class, 'addUsertoCompany'])->middleware(['ability:'.Permission::CAN_ALL.','.Permission::CAN_COMPANY_ADD_USER]);
        Route::delete('companies/delete-user', [CompanyController::class, 'deleteUserfromCompany'])->middleware(['ability:'.Permission::CAN_ALL.','.Permission::CAN_COMPANY_DELETE_USER]);
        Route::post('companies/add-group', [CompanyController::class, 'addGrouptoCompany'])->middleware(['ability:'.Permission::CAN_ALL.','.Permission::CAN_COMPANY_ADD_GROUP]);
        
        Route::get('groups/{group}/users', [GroupController::class, 'getUsers'])->middleware(['ability:'.Permission::CAN_ALL.','.Permission::CAN_GROUP_GET_USERS]);
        Route::post('groups/add-user', [GroupController::class, 'addUsertoGroup'])->middleware(['ability:'.Permission::CAN_ALL.','.Permission::CAN_GROUP_ADD_USER]);
        Route::delete('groups/delete-user', [GroupController::class, 'deleteUserfromGroup'])->middleware(['ability:'.Permission::CAN_ALL.','.Permission::CAN_GROUP_DELETE_USER]);
        
        Route::post('users_notifications/getNotifications', [UsersNotificationsController::class, 'getNotifications']);
        Route::post('users_notifications/getUsers', [UsersNotificationsController::class, 'getUsers']);
        Route::post('users_notifications/addUsertoNotification', [UsersNotificationsController::class, 'addUsertoNotification']);
        Route::delete('users_notifications/deleteUserfromNotification', [UsersNotificationsController::class, 'deleteUserfromNotification']);
        
        Route::get('payments/auto_renew_user_payment/{id}', [PaymentController::class, 'auto_renew_user_payment']);
        Route::get('payments/i  pn', [PaymentController::class, 'ipn']);
        Route::post('payments/addPaymentMethod', [PaymentController::class, 'addPaymentMethod']);
        Route::post('payments/getMyStripeProfile', [PaymentController::class, 'getMyStripeProfile']);
        Route::post('payments/getMyStripePaymentMethods', [PaymentController::class, 'getMyStripePaymentMethods']);
        
        Route::post('sms/sendMessage', [SmsController::class, 'sendMessage']);
        Route::post('sms/sendUserVerificationMessage', [SmsController::class, 'sendUserVerificationMessage']);
    
        //Video
        Route::post('videos/upload', [VideoController::class, 'uploadVideo'])->middleware(['ability:'.Permission::CAN_ALL.','.Permission::CAN_VIDEO_UPLOAD]);
        Route::post('videos/test', [VideoController::class, 'test']);
        Route::get('videos/status/{video}', [VideoController::class, 'getStatus'])->middleware(['ability:'.Permission::CAN_ALL.','.Permission::CAN_VIDEO_STATUS]);
        Route::get('videos/thumbnails/{video}', [VideoController::class, 'getThumbnailsList'])->middleware(['ability:'.Permission::CAN_ALL.','.Permission::CAN_VIDEO_THUMBNAILS]);
        Route::get('videos/by-path', [VideoController::class, 'getVideosByPath'])->middleware(['ability:'.Permission::CAN_ALL.','.Permission::CAN_VIDEO_BYPATH]);
        Route::get('videos/admin/by-path', [VideoController::class, 'getVideosByPathAdmin']);

        Route::apiResource('users', UserController::class);
        Route::apiResource('notifications', NotificationController::class);
        Route::apiResource('keys', KeyController::class);
        Route::apiResource('keys_codes', KeysCodeController::class);
        Route::apiResource('keys_refs', KeysRefController::class);
        Route::apiResource('keys_smses', KeysSmsController::class);
        Route::apiResource('crons', CronController::class);
        Route::apiResource('auto_renew', AutoRenewController::class);
        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('epds', EpdController::class);
        Route::apiResource('groups', GroupController::class);
        Route::apiResource('http_settings', HttpSettingController::class);
        Route::apiResource('limits', LimitController::class);
        Route::apiResource('orders', OrderController::class);
        Route::apiResource('countries', CountryController::class);
        Route::apiResource('sms', SmsController::class);
        Route::apiResource('videos', VideoController::class)->except([
            'store'
        ]);
        Route::apiResource('video_players', VideoPlayerController::class)->except([
            'show'
        ]);
    });

    Route::middleware('throttle:security')->group(function () {
        //Place route here
    });
});
Route::post('videos/test-noauth', [VideoController::class, 'test']);

Route::get('/test', function (Request $request) {
    $options = [
        'version' => 'latest',
        'region'  => env('AWS_DEFAULT_REGION', 'eu-north-1'),
        'credentials' => [
           'key'    => env('AWS_ACCESS_KEY_ID', ""),
           'secret' => env('AWS_SECRET_ACCESS_KEY', "")
        ]
    ];
    $athenaClient = new Aws\Athena\AthenaClient($options);
    
    $databaseName = 's3_access_logs_db';
    $catalog = 'AwsDataCatalog';
    $sql = 'SELECT * FROM mybucket_logs limit 10';
    $outputS3Location = 's3://veri-vod-cloudfrontcloudfrontloggingbuckete23c521-bxz8dj0ke1vf/';
    
     $startQueryResponse = $athenaClient->startQueryExecution([
        'QueryExecutionContext' => [
            'Catalog' => $catalog,
            'Database' => $databaseName
        ],
        'QueryString' => $sql,
        'ResultConfiguration'   => [
            'OutputLocation' => $outputS3Location
        ]
     ]);
    
    $queryExecutionId = $startQueryResponse->get('QueryExecutionId');
     // var_dump($queryExecutionId);
    
     $waitForSucceeded = function () use ($athenaClient, $queryExecutionId, &$waitForSucceeded) {
        $getQueryExecutionResponse = $athenaClient->getQueryExecution([
            'QueryExecutionId' => $queryExecutionId
        ]);
        $status = $getQueryExecutionResponse->get('QueryExecution')['Status']['State'];
        // print("[waitForSucceeded] State=$status\n");
        return $status === 'SUCCEEDED' || $waitForSucceeded();
     };
     $waitForSucceeded();
    
     $getQueryResultsResponse = $athenaClient->getQueryResults([
        'QueryExecutionId' => $queryExecutionId
     ]);
    return response()->json($getQueryResultsResponse->get('ResultSet'));    
});
    
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('login_required', [AuthController::class, 'login_required'])->name('login');
    Route::post('email_verification', [AuthController::class, 'email_verification']);
	Route::post('token', [AuthController::class, 'loginWithApiKey']);
    Route::post('password/email', ForgotPasswordController::class);
    Route::post('password/code/check', CodeCheckController::class);
    Route::post('password/reset', ResetPasswordController::class); 
});    

Route::post('videos/hook-receive', [VideoController::class, 'hookVideoUploaded']);
Route::get('videos/playback-url/{video}', [VideoController::class, 'getPlaybackUrl']);
Route::apiResource('video_players', VideoPlayerController::class)->only([
    'show'
]);

