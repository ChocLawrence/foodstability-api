<?php

namespace App\Http\Controllers;

use App\Category;
use App\Post;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Auth;
use Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AuthorSubmission;
use App\Events\AuthorSubmissionEvent;
use App\Mail\SubmissionMail;

class SubmissionController extends Controller
{
    use ApiResponser;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function addSubmission(Request $request)
    {

        $validator = $this->validateSubmission();
        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $data = [
            'full_name' => $request->full_name,
            'email' =>  $request->email,
            'designation' => $request->designation,
            'contact'=> $request->contact,
            'specialization' => $request->specialization,
            'uni_org'=> $request->uni_org,
            'article_type'=>$request->article_type,
            'article_title'=> $request->article_title,
            'cover'=>$request->cover,
            'manuscript'=>$request->manuscript,
            'supplementary'=>$request->supplementary,
        ];


          //  Log::info($data);


         Notification::route('mail','j.food.stability@gmail.com')
         ->notify(new  AuthorSubmission($data,$data['cover'],$data['manuscript'],$data['supplementary']));



        return $this->successResponse(null,'Submitted successfully',200);
    
  }

  public function validateSubmission(){
    return Validator::make(request()->all(), [
        'full_name' => 'required|string',
        'email' => 'required|email',
        'designation' => 'required',
        'specialization'=>'required',
        'uni_org'=>'required',
        'article_type'=>'required',
        'article_title'=>'required',
        'cover'=>'required|mimes:pdf,doc,docx',
        'manuscript'=>'required|mimes:pdf,doc,docx'
    ]);
  }
}
