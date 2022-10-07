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
                $end_date = Carbon::createFromFormat('Y-m-d',  $request->end_date)->endOfDay();
            }else{
                $end = Carbon::now()->format('Y-m-d');
                $end_date = Carbon::createFromFormat('Y-m-d',  $end)->endOfDay();
            }


            if($request->page){

                $start_date = Carbon::parse($start_date);
                $start_date->addHours(00)
                ->addMinutes(00);

                $endDate = Carbon::parse($end_date);
                $endDate->addHours(23)
                ->addMinutes(59);

                $posts = $post_query->orderBY($sortBy,$sortOrder)->whereBetween('created_at', array($start_date, $endDate))->paginate($page_size);
           
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
                $path = $image->getRealPath();
                $realImage = file_get_contents($path);
                $imageName = base64_encode($realImage);
    
            } else {
                $imageName = "default.png";
            }

            //check pdf
            if(isset($pdf))
            {
                $pdfPath = $pdf->getRealPath();
                $realPdf = file_get_contents($pdfPath);
                $pdfName = base64_encode($realPdf);
    
            }else{
                $pdfName = null;
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
    public function updatePost(Request $request, $id)
    {
        try{


            $post= Post::findOrFail($id);

            $validator = $this->validatePostUpdate();
            if($validator->fails()){
              return $this->errorResponse($validator->messages(), 422);
            }

            $image = $request->file('image');
            $pdf = $request->file('pdf');

            if($request->title){
                $slug = Str::slug($request->title);
            }else{
                $slug = $post->slug;
            }
    
           
            if(isset($image))
            {
                $path = $image->getRealPath();
                $realImage = file_get_contents($path);
                $imageName = base64_encode($realImage);
            }else {
                $imageName = $post->image;
            }

            //check pdf
            if(isset($pdf))
            {
                $pdfPath = $pdf->getRealPath();
                $realPdf = file_get_contents($pdfPath);
                $pdfName = base64_encode($realPdf);
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
    public function updatePostCount(Request $request, $id)
    {
        try{

            //$post= Post::find($id)->first();
            Post::where('id',$id)
            ->increment('view_count', 1);

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
