<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Models\User;
use App\Models\Key;
use App\Models\UsersLoginIps;

class AuthController extends Controller
{
    /**
     * Register a User.
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
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
        ];

        $user = User::create($newdata);

        // $token = $user->createToken('veri_token')->plainTextToken;
        $sms = new SmsController;
        $sms->sendUserVerificationMessage_core(['user_id' => $user->id]);

        return response()->json($user, 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => false,
                'message' => 'Invalid Inputs',
                'error' => $validator->errors()
            ], 422);
        }

        $userdata = array('email' => $request->email, 'password' => $request->password);

        if (Auth::attempt($userdata, $request->remember)) {
            $user = Auth::user();
            $abilities = [];
            foreach ($user->groups as $group) {
                $permissions = $group->permissions;
                if($permissions == null)
                    $permissions = [];
                $abilities = array_unique(array_merge($abilities, $permissions));
            }
            if(in_array("*", $abilities))
                $abilities = ["*"];
            
            $token = $user->createToken('veri_token', $abilities)->plainTextToken;
            $user_id = User::firstWhere('email', $request->email)->id;
            $user_ip = $request->ip();
            $configure = new ConfigureController;
            $whitelistenabled = $configure->isEnabled(['name' => 'whitelistip']);
            if ($whitelistenabled) {
                $whitelistips = new WhitelistIpController;
                if (!$whitelistips->isAllowed(['login_ip' => $user_ip])) {
                    return response()->json([
                        'code' => false,
                        'message' => 'IP check failed',
                    ], 400);
                }
            } else {
                $blacklistips = new BlacklistIpController;
                if ($blacklistips->isBlocked(['login_ip' => $user_ip])) {
                    return response()->json([
                        'code' => false,
                        'message' => 'IP check failed',
                    ], 400);
                }
            }

            if (!User::firstWhere('email', $request->email)->email_verified_at) {
                return response()->json([
                    "error" => "Verification Error",
                    "code"=> 0,
                    "message"=> "Email not verificated yet"
                ], 400);
            }

            $pintoken = new PinTokenController;
            $passcode = $pintoken->setPasscode(['user_email' => $request->email]);

            return response()->json([
                'api_token' => $token,
            ], 200);
        } else {
            return response()->json([
                'code' => false,
                'message' => 'Invalid Credentials',
            ], 400);
        }
    }

    public function sendVerifyEmail(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'user_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => false,
                'message' => 'Invalid Inputs',
                'error' => $validator->errors()
            ], 402);
        }

        $sms = new SmsController;
        $result = $sms->sendUserVerificationMessage_core(['user_email' => $request->user_email]);

        return $result;
    }

    public function verfiyWithPasscode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:100|unique:users',
            'passcode' => 'required|numeric|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => false,
                'message' => 'Invalid Inputs',
                'error' => $validator->errors()
            ], 422);
        }

        $passcode = PinToken::where('mobile_number', $request->email)->first()->passcode;

        if ($passcode = $request->passcode) {
            return response()->json([
                'message' => 'Success',
            ], 200);
        } else {
            return response()->json([
                'code' => false,
                'message' => 'Invalid Credentials',
            ], 400);
        }
    }

    public function loginWithApiKey(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string|size:32'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => false,
                'message' => 'Invalid Inputs',
                'error' => $validator->errors()
            ], 422);
        }

        $key = Key::where('key', $request->api_key)->first();

        if ($key != null) {
            $clientIpAddr = $request->ip();

            if($key->is_private_key && !in_array($clientIpAddr, $key->ip_address)){
                return response()->json([
                    'code' => false,
                    'message' => "Login from your localtion {$clientIpAddr} is not allowed.",
                ], 400);
            }

            $abilities = $key->permissions;
            Auth::login($key->user);
            $user = Auth::user();
            $token = $user->createToken('veri_token', ['apikey', ...$abilities])->plainTextToken;
            return response()->json([
                'api_token' => $token
            ], 200);
        } else {
            return response()->json([
                'code' => false,
                'message' => 'Invalid Credentials',
            ], 400);
        }
    }


    public function logout(Request $request)
    {
        if (! $request->user()) {
            return response()->json([
                'code' => false,
                'message' => 'User is not logged on.',
            ]);
        }

        Auth::logout();

        return response()->json([
            'code' => true,
            'message' => 'Successfully logged out.'
        ]);
    }

    public function email_verification(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'user_email' => 'required|email',
            'verification_code' => 'required|string|min:6|max:6',
        ]);
        
        if($validator->fails()){
            return response()->json([
                "error" => "Validation Error",
                "code"=> 0,
                "message"=> $validator->errors()
            ]);
        }

        $user_email = $input['user_email'];
        $ucode = $input['verification_code'];

        $userinfo = User::where('email', $user_email)->first();
        if(!$userinfo)
        {
            return response()->json([
                'code' => false,
                'error' => 'Failed to verify',
                'message' => 'User not found',
            ]);
        }
        if($userinfo->email_verified_at)
        {
            return response()->json([
                'code' => false,
                'error' => 'Failed to verify',
                'message' => 'Already Verified',
            ]);
        }
        if($userinfo->verification_code !== $ucode)
        {
            return response()->json([
                'code' => false,
                'error' => 'Failed to verify',
                'message' => 'Verification code doesn\'t match',
            ]);
        }
        if($userinfo->verification_code_expiry < time())
        {
            return response()->json([
                'code' => false,
                'error' => 'Failed to verify',
                'message' => 'Verification code expired',
            ]);
        }

        User::where('email', $user_email)->update(['email_verified_at' => date('Y-m-d H:i:s')]);
        return response()->json($userinfo);
    }

    public function login_required()
    {
        return response()->json(['error' => 'Login is required.']);
    }
}