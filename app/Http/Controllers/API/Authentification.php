<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Member;
use App\Models\User;
use App\Notifications\NewRegistration;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\User as UserResource;

class Authentification extends BaseController
{
    public function Login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $user = new \App\Models\User();
        $check = $user->where('email',$request->email)->exists();
        if ($check) {
            $users = $user->where('email',$request->email)->first();
            if (Hash::check($request->password, $users->password)) {
                if ($users->email_verified_at !=null){
                    config(['auth.guards.api.provider' => 'user']);
                    $token = $users->createToken('ESIG-Dine-07',['user'])->accessToken;
                    $users=[
                        'id' => $users->id,
                        'name' => $users->name,
                        'email' => $users->email,
                        'phone_number' => $users->phone_number,
                        'entreprise' => $users->entreprise,
                        'email_verified_at' => $users->email_verified_at,
                        'created_at' => $users->created_at,
                        'updated_at' => $users->updated_at,
                        'token' => $token,
                    ];
                    return response()->json(['success'=>true,'message'=>'User account login successfully.','data' => $users],200);
                }
                else{
                    $response = ["message" =>'Unactivated account'];
                    return response($response, 422);
                }

            } else {
                $response = ["message" => "Incorrect identifiers"];
                return response($response, 422);
            }
        } else {
            $response = ["message" =>'Incorrect identifiers'];
            return response($response, 422);
        }
    }

    public function Register(Request $request){
        dd('hhh');
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|unique:users,phone_number',
            'entreprise' => 'nullable|string',
            'password' => 'required|string|min:8|confirmed',
            'member_id' => 'nullable|numeric',

        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $input = $request->all();
        echo 'validation';
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        echo 'user created';

        if(isset($request->member_id)){
            if($request->member_id !=''){
             $member=Member::find($request->member_id);
             $member->membre=$user->id;
             $member->save();
            }
            echo 'member created';
        }

        $ciphering = "AES-128-CTR";

        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;

        $encryption_iv = '1234567891011121';

        $encryption_key = "EsignDineCode";

        $encryption = openssl_encrypt(
            $request->email,
            $ciphering,
            $encryption_key,
            $options,
            $encryption_iv
        );
        $t = base64_encode($request->email);
        echo 'b64 encoding';
        Notification::route('mail', $user->email)
            ->notify(new NewRegistration($t));

        echo 'notify';
        return $this->sendResponse(new UserResource($user), 'User register successfully.');
    }

    public function activate_account($token){
        $ciphering = "AES-128-CTR";
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        $decryption_iv = '1234567891011121';
        $decryption_key = "EsignDineCode";
//        $email=base64_decode(openssl_decrypt ($token, $ciphering,
//            $decryption_key, $options, $decryption_iv));
        $email=base64_decode($token);
        $user =  User::where('email',$email)->first();
        if(!is_null($user)){
            $user->email_verified_at =date('Y-m-d H:i:s');
            $user ->save();
            return Redirect::to(env('FRONT_URL').'/login');
            //return $this->sendResponse([], 'Account activated');
        }
        else{
           return  $this->sendError('Unknown email adress',[]);
        }
    }

    protected function sendResetLinkResponse(Request $request)
    {
        $input = $request->only('email');
        $validator = Validator::make($input, [
            'email' => "required|email"
        ]);

        if ($validator->fails()) {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $response =  Password::sendResetLink($input);

        if($response == Password::RESET_LINK_SENT){
            $message = "Mail send successfully";
        }else{
            $message = "Email could not be sent to this email address";
        }
        //$message = $response == Password::RESET_LINK_SENT ? 'Mail send successfully' : GLOBAL_SOMETHING_WANTS_TO_WRONG;
        $response = ['success'=>true,'data'=>'','message' => $message];
        return response($response, 200);
    }

    protected function sendResetResponse(Request $request){

        $input = $request->only('email','token', 'password', 'password_confirmation');
        $validator = Validator::make($input, [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);
        if ($validator->fails()) {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $response = Password::reset($input, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->save();
            //$user->setRememberToken(Str::random(60));
            event(new PasswordReset($user));
        });
        if($response == Password::PASSWORD_RESET){
            $message = "Password reset successfully";
        }else{
            $message = "Email could not be sent to this email address";
        }
        $response = ['data'=>'','message' => $message];
        return response()->json($response);
    }

    public function logout(Request $request){
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }
}
