<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\Rules\Password as RulesPassword;
use App\Traits\ApiResponser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use DB;
use Illuminate\Support\Facades\Validator;
use Auth;
use Hash;

class UserController extends Controller
{
    //
    use ApiResponser;

    public function root()
    {
       //root route
    }

    public function login(Request $request){

        try{

            $validator = $this->validateLogin();
            if($validator->fails()){
                return $this->errorResponse($validator->messages(), 422);
            }

            $user = User::where('email',$request->email)->first();
            if(!$user || !Hash::check($request->password,$user->password)){
                return $this->errorResponse("Not a valid user", 404);
            }

            $token = $user->createToken((string)$request->device_name)->plainTextToken;

            $response = [
                "user"=>$user,
                "token"=>$token
            ];

            return $this->successResponse($response,"Login successful", 200);

        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

     //this method adds new users
     public function signup(Request $request)
     {

        try{
           
            $validator = $this->validateRegister();
            if($validator->fails()){
               return $this->errorResponse($validator->messages(), 422);
            }
   
   
            $user = User::create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'gender' => $request->gender,
                'role_id' => '1',
                'password' => Hash::make($request->password),
                'email' => $request->email
            ]);
    
            $response = [
               'token' => $user->createToken((string)$request->device_name)->plainTextToken
            ];

            $user->sendEmailVerificationNotification();
   
            //send verification email
        //    $url = env("API_URL", "http://localhost:8001").'/api/email/verification-notification';
        //    Http::withToken($response['token'])->post($url, []);
   
           //dd($response['token']);
   
           return $this->successResponse($response, "Signup successful.Check mail for verification link", 201);
   
            
        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }

    }

     // this method signs out users by removing tokens
    public function logout()
    {
        try{

            auth()->user()->tokens()->delete();
            $response = [
                'message' => 'Tokens Revoked'
            ];
            return $this->successResponse($response,"Logout Successful",200);

        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }
      
    }

    public function forgotPassword(Request $request)
    {

        $validator = $this->validateForgotPassword();
        if($validator->fails()){
           return $this->errorResponse($validator->messages(), 422);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            $response = [
                'status' => __($status)
            ];
            return $this->successResponse($response,"Reset link has been sent", 200);
        }

        return $this->errorResponse(trans($status), 422);
    }

    public function reset(Request $request)
    {
        $validator = $this->validateResetPassword();
        if($validator->fails()){
           return $this->errorResponse($validator->messages(), 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return $this->successResponse(null,"Password reset successfully", 200);
        }

        return $this->errorResponse(__($status),500);

    }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function getUsers(){
        try{
            $users= User::latest()->get();
            return $this->successResponse($users);
        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function getUser($id) {

        try{
            $user= User::where('id', $id)->firstOrFail();
            return $this->successResponse($user);
        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }
        
    }


     /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteUser($id)
    {
        try{

            User::findOrFail($id)->delete();
            return $this->successResponse(null,"Deleted successfully", 200);

        }catch(\Exception $e){
            return $this->errorResponse( $e->getMessage(), 404);
        }
    }

    public function updateProfile(Request $request)
    {
        try{

            $validator = $this->validateProfile();
            if($validator->fails()){
               return $this->errorResponse($validator->messages(), 422);
            }


            $image = $request->file('image');
            $slug = Str::slug($request->firstname);
            $user = User::findOrFail(Auth::id());
            if (isset($image))
            {
                $path = $image->getRealPath();
                $realImage = file_get_contents($path);
                $imageName = base64_encode($realImage);
            } else {
                $imageName = $user->image;
            }
            
            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->gender = $request->gender;
            $user->address = $request->address;
            $user->phone = $request->phone;
            $user->email = $request->email;

            if (isset($image)){
                $user->image = $imageName;
            }
        
            $user->save();

            return $this->successResponse($user,"Updated successfully", 200);

        }catch(\Exception $e){
            return $this->errorResponse( $e->getMessage(), 404);
        }

    }


    public function validateProfile(){
        return Validator::make(request()->all(), [
            'firstname' => 'required|string|min:2|max:50',
            'lastname' => 'required|string|min:2|max:50',
            'gender' => 'required|in:male,female', 
            'email' => 'required|email|max:255|unique:users,email,' .Auth::id(),
        ]);
    }




    public function validateLogin(){
        return Validator::make(request()->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);
    }

    public function validateRegister(){
        return Validator::make(request()->all(), [
            'firstname' => 'required|string|max:100',
            'lastname' => 'required|string|max:100',
            'gender' => 'required|in:male,female', 
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed'
        ]);
    }

    public function validateForgotPassword(){
        return Validator::make(request()->all(), [
            'email' => 'required|string|email|max:255'
        ]);
    }

    public function validateResetPassword(){
        return Validator::make(request()->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', RulesPassword::defaults()]
        ]);
    }
    

}
