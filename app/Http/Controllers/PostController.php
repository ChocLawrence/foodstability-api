<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Notifications\AuthorPostApproved;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use App\Models\Tag;
use Illuminate\Support\Str;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use DB;


class PostController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPosts(Request $request){

        try{
            $post_query = Post::with(['user','categories']);

            if($request->keyword){
                $post_query->where('title','LIKE','%'.$request->keyword.'%');
            }

            if($request->category){
                $post_query->whereHas('category',function($query) use($request){
                    $query->where('slug',$request->category);
                });
            }

            if($request->user_id){
                $post_query->where('user_id',$request->user_id);
            }

            if($request->sortBy && in_array($request->sortBy,['id','created_at'])){
                $sortBy = $request->sortBy;
            }else{
                $sortBy = 'id';
            }

            if($request->sortOrder && in_array($request->sortOrder,['asc','desc'])){
                $sortOrder = $request->sortOrder;
            }else{
                $sortOrder = 'desc';
            }

            if($request->page_size){
                $page_size = $request->page_size;
            }else{
                $page_size = 5;
            }

            if($request->start_date){
                $validator = $this->validateStartDate();
                if($validator->fails()){
                  return $this->errorResponse($validator->messages(), 422);
                }
                $start_date = $request->start_date;
            }else{
                $start_date =  Carbon::now()->subMonth(1)->format('Y-m-d');
            }

            if($request->end_date){
                $validator = $this->validateEndDate();
                if($validator->fails()){
                  return $this->errorResponse($validator->messages(), 422);
                }
                $end_date = $request->end_date;
            }else{
                $end_date = Carbon::now()->format('Y-m-d');
            }


            if($request->page){

                $start_date = Carbon::parse($start_date);
                $start_date->addHours(00)
                ->addMinutes(00);

                $end_date = Carbon::parse($end_date);
                $end_date->addHours(23)
                ->addMinutes(59);

                $posts = $post_query->orderBY($sortBy,$sortOrder)->whereBetween('created_at', array($start_date, $end_date))->paginate($page_size);
           
            }else{
                $posts = $post_query->orderBY($sortBy,$sortOrder)->get();
            }
  
            return $this->successResponse($posts);
        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }
       
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function getPost($id) {

        try{
            $post= Post::where('id', $id)->firstOrFail();
            return $this->successResponse($post);
        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }

    }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function getPostBySlug($slug) {

        try{
            $post= Post::where('slug', $slug)->firstOrFail();
            return $this->successResponse($post);
        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }

    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addPost(Request $request)
    {

        try{

            $validator = $this->validatePost();
            if($validator->fails()){
              return $this->errorResponse($validator->messages(), 422);
            }

            $image = $request->file('image');
            $pdf = $request->file('pdf');
            $slug = Str::slug($request->title);
    
            if(isset($image))
            {
             // make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imageName  = $slug.'-'.$currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();
    
                if(!Storage::disk('public')->exists('post'))
                {
                    Storage::disk('public')->makeDirectory('post');
                }
    
                $postImage = Image::make($image)->resize(1600,1066)->save();
                Storage::disk('public')->put('post/'.$imageName,$postImage);
    
            } else {
                $imageName = "default.png";
            }
    
            //check pdf
            if(isset($pdf))
            {
             // make unique name for pdf
                $currentDate = Carbon::now()->toDateString();
                $pdfName  = $slug.'-'.$currentDate.'-'.uniqid().'.'.$pdf->getClientOriginalExtension();
    
                if(!Storage::disk('public')->exists('post'))
                {
                    Storage::disk('public')->makeDirectory('post');
                }
    
                // compress pdf
                //$postPdf = Image::make($pdf)->resize(1600,1066)->save();
                Storage::disk('public')->put('post/'.$pdfName,file_get_contents($pdf));
    
            } 
    
            $post = new Post();
            $post->user_id = Auth::id();
            $post->title = $request->title;
            $post->slug = $slug;
            $post->image = $imageName;
            $post->pdf = $pdfName;
            $post->date = Carbon::now()->format('jS F Y');
            $post->category_id = $request->category_id;
            $post->volume = $request->volume;
            $post->issue = $request->issue;
            $post->doi = $request->doi;
            $post->practical = $request->practical;
            $post->author = $request->authors;
            $post->keywords = $request->keywords;
            $post->abstract = $request->abstract;
            $post->save();
    
            Log::info($post->date);
    

            $category = Category::find($request->category_id);
            $post->categories()->attach($category);


            $tag = Tag::find($request->tag);
            $post->tags()->attach($tag);

            return $this->successResponse($post,"Posted successfully", 200);

        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }

    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function updatePost(Request $request, Post $id)
    {
        try{


            $post= Post::find($id)->first();

            $validator = $this->validatePostUpdate();
            if($validator->fails()){
              return $this->errorResponse($validator->messages(), 422);
            }

            $image = $request->file('image');
            $pdf = $request->file('pdf');
            $slug = Str::slug($request->title);
    
            if(isset($image))
            {
             // make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imageName  = $slug.'-'.$currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();
    
                if(!Storage::disk('public')->exists('post'))
                {
                    Storage::disk('public')->makeDirectory('post');
                }
    
                $postImage = Image::make($image)->resize(1600,1066)->save();
                Storage::disk('public')->put('post/'.$imageName,$postImage);
    
            }else {
                $imageName = $post->image;
            }
    
            //check pdf
            if(isset($pdf))
            {
             // make unique name for pdf
                $currentDate = Carbon::now()->toDateString();
                $pdfName  = $slug.'-'.$currentDate.'-'.uniqid().'.'.$pdf->getClientOriginalExtension();
    
                if(!Storage::disk('public')->exists('post'))
                {
                    Storage::disk('public')->makeDirectory('post');
                }
    
                // compress pdf
                //$postPdf = Image::make($pdf)->resize(1600,1066)->save();
                Storage::disk('public')->put('post/'.$pdfName,file_get_contents($pdf));
    
            } else {
                $pdfName = $post->pdf;
            }
    
            
            $post->user_id = Auth::id();
            $post->title = $request->title;
            $post->slug = $slug;
            $post->category_id = $request->category_id;
            $post->volume = $request->volume;
            $post->issue = $request->issue;
            $post->date = $post->date;
            $post->doi = $request->doi;
            $post->practical = $request->practical;
            $post->author = $request->authors;
            $post->pdf = $pdfName;
            $post->image = $imageName;
            $post->keywords = $request->keywords;
            $post->abstract = $request->abstract;
            $post->save();
    
            Log::info($post->date);
    

            $category = Category::find($request->category_id);
            $post->categories()->attach($category);

            $tag = Tag::find($request->tag);
            $post->tags()->attach($tag);
        
    
            return $this->successResponse($post,"Post Updated successfully", 200);

        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }
          
    }


      /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function updatePostCount(Request $request, Post $id)
    {
        try{


            $post= Post::find($id)->first();

            $validator = $this->validatePostCountUpdate();
            if($validator->fails()){
              return $this->errorResponse($validator->messages(), 422);
            }
            
            $newPostCount = (int)$post->view_count + (int)$request->view_count;
            $post->view_count = $newPostCount;
            $post->save();
        
            return $this->successResponse(null, "", 200);

        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }
          
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function deletePost(Post $id)
    {

        try{

            $post = Post::find($id)->first();

            // delete image
            if (Storage::disk('public')->exists('post/'.$post->image))
            {
                Storage::disk('public')->delete('post/'.$post->image);
            }

            //delete pdf
            if (Storage::disk('public')->exists('post/'.$post->pdf))
            {
                Storage::disk('public')->delete('post/'.$post->pdf);
            }

            $post->categories()->detach();
            $post->tags()->detach();
            $post->delete();

            return $this->successResponse(null,"Post Deleted successfully", 200);
            

        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }

    }

    public function validatePost(){
        return Validator::make(request()->all(), [
            'title' => 'required|min:15|',
            'image' => 'required|image',
            'pdf' => 'required|mimes:pdf',
            'category_id' => 'required',
            'tag' => 'required',
            'volume' => 'required',
            'issue' => 'required',
            'authors' => 'required',
            'practical' => 'required',
            'keywords' => 'required',
            'abstract' => 'required',
        ]);
    }

    public function validatePostUpdate(){
        return Validator::make(request()->all(), [
            'title' => 'required|min:15',
            'image' => 'nullable|image',
            'pdf' => 'nullable|mimes:pdf',
            'category_id' => 'required',
            'tag' => 'nullable',
            'volume' => 'required',
            'issue' => 'required',
            'authors' => 'required',
            'practical' => 'required',
            'keywords' => 'required',
            'abstract' => 'required',
        ]);
    }

    public function validatePostCountUpdate(){
        return Validator::make(request()->all(), [
            'view_count' => 'in:1',
        ]);
    }

    public function validateEndDate(){
        return Validator::make(request()->all(), [
           'end_date'=>'required|date|after:start_date',
        ]);
    }

    public function validateStartDate(){
        return Validator::make(request()->all(), [
           'start_date'=>'required|date|before:end_date',
        ]);
    }


}
