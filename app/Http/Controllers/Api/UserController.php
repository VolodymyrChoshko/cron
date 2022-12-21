<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Billing;
use App\Models\billingdetails;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Auth;
use function Ramsey\Uuid\v1;
use App\Permissions\Permission;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        if ($user->tokenCan(Permission::CAN_ALL) || $user->tokenCan(Permission::CAN_USER_INDEX)) {
            $users = User::all();
            return response()->json($users);
        }
        else{
            return response()->json([
                'error'=> 'Error',
                'message' => 'Not Authorized.'
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->tokenCan(Permission::CAN_ALL) || $user->tokenCan(Permission::CAN_USER_STORE)) {
            $input = $request->all();

            $validator = Validator::make($input, [
                'name' => 'required|string|min:3|max:50',
                'email' => 'required|string|email|max:100|unique:users',
                'first_name' => 'required|string|min:3|max:50',
                'last_name' => 'required|string|min:3|max:50',
                'country_id' => 'required|numeric',
                'password' => 'required|string|min:8|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
                'language' => 'required|string|min:3|max:50',
            ]);
            if($validator->fails()){
                return response()->json([
                    "error" => "Validation Error",
                    "code"=> 0,
                    "message"=> $validator->errors()
                ]);
            }

            $stripe = new \Stripe\StripeClient(env('STRIPE_KEY'));
            $customer_info = $stripe->customers->create([
                'description' => 'Veri User',
                'email' => $request->email,
                'phone' => $request->phone,
            ]);

            $newdata = [
                'name'=> $request->name,
                'email'=> $request->email,
                'first_name'=> $request->first_name,
                'last_name'=> $request->last_name,
                'country_id'=> $request->country_id,
                'password'=> bcrypt($request->password),
                'phone'=> $request->phone,
                'language'=> $request->language,
                'stripe_cust_id' => $customer_info->id,
                'balance' => '{"GBP":50}',
            ];

            $user = User::create($newdata);

            $sms = new SmsController;
            $sms->sendUserVerificationMessage_core(['user_id' => $user->id]);

            return response()->json($user);
        }
        else{
            return response()->json([
                'error'=> 'Error',
                'message' => 'Not Authorized.'
            ]);
        }     
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $auth_user = Auth::user();
        if ($auth_user->tokenCan(Permission::CAN_ALL) || $auth_user->tokenCan(Permission::CAN_USER_SHOW)) {
            if($auth_user->isAdmin())
            {
                return response()->json($user);
            }
            else if($auth_user->id == $user->id){
                return response()->json(
                    [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'active' => $user->active,
                        'phone' => $user->phone,
                        'rate' => $user->rate,
                        'balance' => $user->balance,
                        'is_verify' => $user->is_verify,
                        'language' => $user->language,
                        'country_id' => $user->country_id,
                    ]
                );
            }
            else{
                return response()->json([
                    'error'=> 'Error',
                    'message' => 'User is not owner.'
                ]);
            }
        }
        else{
            return response()->json([
                'error'=> 'Error',
                'message' => 'Not Authorized.'
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $auth_user = Auth::user();
        if ($auth_user->tokenCan(Permission::CAN_ALL) || $auth_user->tokenCan(Permission::CAN_USER_UPDATE)) {
            if($auth_user->isAdmin() || $auth_user->id == $user->id)
            {
                $input = $request->all();
                $validator = Validator::make($input, [
                    'name' => 'nullable|string|min:3|max:50',
                    'email' => 'nullable|string|email|max:100|unique:users',
                    'first_name' => 'nullable|string|min:3|max:50',
                    'last_name' => 'nullable|string|min:3|max:50',
                    'country_id' => 'nullable|numeric',
                    'password' => 'nullable|string|min:8|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
                    'phone' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
                    'language' => 'nullable|string|min:3|max:50',
                    'balance' => 'nullable|numeric',
                ]);
                if($validator->fails()){
                    return response()->json([
                        "error" => "Validation Error",
                        "code"=> 0,
                        "message"=> $validator->errors()
                    ]);
                }

                $newdata = [];
                if($request->name){
                    $newdata['name'] = $request->name;
                }
                if($request->email){
                    $newdata['email'] = $request->email;
                }
                if($request->first_name){
                    $newdata['first_name'] = $request->first_name;
                }
                if($request->last_name){
                    $newdata['last_name'] = $request->last_name;
                }
                if($request->company_id){
                    $newdata['company_id'] = $request->company_id;
                }
                if($request->password){
                    $newdata['password'] = bcrypt($request->password);
                }
                if($request->phone){
                    $newdata['phone'] = $request->phone;
                }
                if($request->country_id){
                    $newdata['country_id'] = $request->country_id;
                }
                if($request->language){
                    $newdata['language'] = $request->language;
                }
                if($request->balance){
                    $newdata['balance'] = $request->balance;
                }
                $result = $user->update($newdata);

                if($request->email)
                {
                    $sms = new SmsController;
                    $sms->sendUserVerificationMessage_core(['user_id' => $user->id]);
                }

                return response()->json($user);
            }
            else{
                return response()->json([
                    'error'=> 'Error',
                    'message' => 'User is not owner.'
                ]);
            }
        }
        else{
            return response()->json([
                'error'=> 'Error',
                'message' => 'Not Authorized.'
            ]);
        }       
    }

    /**
     * Update the balance in storage
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function updateBalance($email, $type, $size = 1, $method = 0)
    {
        $field = 'email';
        if($method) $field = 'id';
        $user = User::firstWhere($field, $email);
        $billtype = Billing::firstWhere('type', $type);
        $balance = $billtype->amount;

        $userBalance = $this->getBalanceBySymbol($user, new Request(['symbol' => 'GBP']));

        billingdetails::create([
            "type" => $billtype->id,
            "amount" => $size,
            "user_id" => $user->id
        ]);

        $billingdetail = billingdetails::where('type', $billtype->type)->where('user_id', $user->id);
        if ($billingdetail) {
            $size = $size + $billingdetail->amount;
            $billingdetail->update(['amount' => $size]);
        } else {
            billingdetails::create([
                "type" => $billtype->id,
                "amount" => $size,
                "user_id" => $user->id
            ]);
        }

        if ($billtype->type == 'Bandwidth') {
            $billedSize = $billingdetail ? $billingdetail->amount / $billtype->amount : 0;
            if (($size / 1024 / 1024 / 1024 - $billedSize) * $billtype->amount >= 0.01) {
                $bal = floor($size / 1024 / 1024 / 1024 * $billtype->amount * 100) / 100;
                $newBalance = floatval($userBalance) - $bal;
            }
        } else {
            $newBalance = floatval($userBalance) - $balance * $size;
        }

        if ($billtype->type != 'Bandwidth' && $newBalance < 0) {
            return false;
        }

        $this->setBalanceBySymbol($user, new Request(['symbol' => 'GBP', 'amount' => $newBalance]));

        return true;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {        
        $auth_user = Auth::user();
        if ($auth_user->isAdmin()) {
            $user->delete();
            return response()->json([
                'message'=> 'Success'
            ]);
        }
        else{
            return response()->json([
                'error'=> 'Error',
                'message' => 'Not Authorized.'
            ]);
        }
    }

    public function getCompanies(User $user)
    {
        return response()->json($user->companies);
    }
    public function getGroups(User $user)
    {
        return response()->json($user->groups);
    }

    public function getBalanceBySymbol(User $user, Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'symbol' => 'required|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "error" => "Validation Error",
                "code" => 0,
                "message" => $validator->errors()
            ]);
        }

        $symbol = $request->symbol;
        $balanceBySymbol = 0;
        $userBalance = json_decode($user->balance, true);
        if (isset($userBalance[$symbol])) {
            $balanceBySymbol = $userBalance[$symbol];
        }

        return $balanceBySymbol;
    }

    public function setBalanceBySymbol(User $user, Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'symbol' => 'required|string|size:3',
            'amount' => 'required|numeric|between:0,99999.99',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "error" => "Validation Error",
                "code" => 0,
                "message" => $validator->errors()
            ]);
        }

        $symbol = $request->symbol;
        $amount = $request->amount;
        $userBalance = json_decode($user->balance, true);
        if (isset($userBalance[$symbol])) {
            $userBalance[$symbol] = floatval($amount);
        } else {
            $newBalance = [$symbol => floatval($amount)];
            $userBalance = array_merge($userBalance, $newBalance);
        }

        $newdata = [];
        $newdata['balance'] = $userBalance;
        $user->update($newdata);

        return response()->json($user->balance);
    }
}
