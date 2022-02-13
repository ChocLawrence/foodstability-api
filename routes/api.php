<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\ArchivesController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\EmailVerificationController;

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
Route::middleware(['cors'])->group(function () {


  Route::get('/', [UserController::class,'root'])->name('root');
  Route::post('login', [UserController::class,'login']);
  Route::post('signup', [UserController::class,'signup']);


  Route::post('forgot-password', [UserController::class, 'forgotPassword']);
  Route::post('reset-password', [UserController::class, 'reset']);


  //->middleware('role:editor,approver');


  //archives without authentication
  Route::get('archives',[ArchivesController::class, 'getVolumes']);
  Route::get('archives/{volume}',[ArchivesController::class, 'getVolumeIssues']);
  Route::get('archives/{volume}/{issue}',[ArchivesController::class, 'getPostsByVolumeAndIssue']);


  //posts without authentication
  Route::get('posts',[PostController::class, 'getPosts']);
  Route::get('posts/{id}',[PostController::class, 'getPost']);
  Route::get('posts/slug/{id}',[PostController::class, 'getPostBySlug']);
  Route::put('posts/view/{id}',[PostController::class, 'updatePostCount']);

  //categories without authentication
  Route::get('categories',[CategoryController::class, 'getCategories']);
  Route::get('categories/{id}',[CategoryController::class, 'getCategory']);

  //tags without authentication
  Route::get('tags',[TagController::class, 'getTags']);
  Route::get('tags/{id}',[TagController::class, 'getTag']);

  //subscribers without authentication
  Route::post('subscribers',[SubscriberController::class,'addSubscriber']);


  Route::middleware(['auth:sanctum'])->group(function () {   
    Route::post('email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail']);
    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify');
  });

  Route::middleware(['auth:sanctum', 'verified'])->group(function () {   

    Route::post('logout', [UserController::class, 'logout']);

    //settings
    Route::post('users/update',[UserController::class, 'updateProfile']);
    Route::get('users/{id}',[UserController::class, 'getUser']);


    //author
    Route::middleware('role:author')->group(function () {   
      //submissions
      Route::post('submissions',[SubmissionController::class, 'addSubmission']);

    });


    //admin
    Route::middleware('role:admin')->group(function () {   
      Route::post('send-mail', [MailController::class, 'sendMail']);

      //users
      Route::get('users',[UserController::class, 'getUsers']);
      Route::delete('users/{id}',[UserController::class,'deleteUser']);

      //tags
      Route::post('tags',[TagController::class,'addTag']);
      Route::put('tags/{id}',[TagController::class,'updateTag']);
      Route::delete('tags/{id}',[TagController::class,'deleteTag']);

      //categories
      Route::post('categories',[CategoryController::class,'addCategory']);
      Route::post('categories/{id}',[CategoryController::class,'updateCategory']);
      Route::delete('categories/{id}',[CategoryController::class,'deleteCategory']);

      //posts
      Route::post('posts',[PostController::class,'addPost']);
      Route::post('posts/{id}',[PostController::class,'updatePost']);
      Route::delete('posts/{id}',[PostController::class,'deletePost']);

      //subscribers
      Route::get('subscribers',[SubscriberController::class, 'getSubscribers']);
      Route::get('subscribers/{id}',[SubscriberController::class, 'getSubscriber']);
      Route::put('subscribers/{id}',[SubscriberController::class,'updateSubscriber']);
      Route::delete('subscribers/{id}',[SubscriberController::class,'deleteSubscriber']);


    });

  
  });

});
 

