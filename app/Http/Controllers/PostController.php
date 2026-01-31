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

            // Handle keyword filter (backward compatibility) or new title filter
            if($request->keyword){
                $post_query->where('title','LIKE','%'.$request->keyword.'%');
            } elseif($request->title){
                $post_query->where('title','LIKE','%'.$request->title.'%');
            }

            // Handle DOI filter
            if($request->doi){
                $post_query->where('doi','LIKE','%'.$request->doi.'%');
            }

            // Handle Volume filter
            if($request->volume){
                $post_query->where('volume', $request->volume);
            }

            // Handle Issue filter
            if($request->issue){
                $post_query->where('issue', $request->issue);
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
                $start_date = $request->start_date;
                // Validate start_date is a valid date
                try {
                    Carbon::parse($start_date);
                } catch (\Exception $e) {
                    return $this->errorResponse(['start_date' => 'Invalid start date format'], 422);
                }
            }else{
                $start_date =  Carbon::now()->subMonth(1)->format('Y-m-d');
            }

            if($request->end_date){
                $end_date_input = $request->end_date;
                // Validate end_date is a valid date
                try {
                    $end_date = Carbon::createFromFormat('Y-m-d', $end_date_input)->endOfDay();
                } catch (\Exception $e) {
                    return $this->errorResponse(['end_date' => 'Invalid end date format'], 422);
                }
                // Validate end_date is after start_date if both are provided
                if($request->start_date && $end_date < Carbon::parse($start_date)) {
                    return $this->errorResponse(['end_date' => 'End date must be after start date'], 422);
                }
            }else{
                $end = Carbon::now()->format('Y-m-d');
                $end_date = Carbon::createFromFormat('Y-m-d',  $end)->endOfDay();
            }


            if($request->page){

                $start_date_parsed = Carbon::parse($start_date);
                $start_date_parsed->addHours(00)
                ->addMinutes(00);

                $endDate_parsed = Carbon::parse($end_date);
                $endDate_parsed->addHours(23)
                ->addMinutes(59);

                $posts = $post_query->orderBy('volume', 'desc')
                    ->orderBy('issue', 'desc')
                    ->orderBy($sortBy, $sortOrder)
                    ->whereBetween('created_at', array($start_date_parsed, $endDate_parsed))
                    ->paginate($page_size);
           
            }else{
                $posts = $post_query->orderBy('volume', 'desc')
                    ->orderBy('issue', 'desc')
                    ->orderBy($sortBy, $sortOrder)
                    ->get();
            }

            if($request->visibility == "0"){ 
                $posts->makeHidden(['keywords','abstract','user','categories','pdf','practical'])->toArray();
             }

            if($request->visibility == "11"){ 
                $posts->makeHidden(['image','title','','pdf'])->toArray();
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
     * Download post PDF by slug. Returns file with Content-Disposition: attachment so browser downloads instead of opening.
     */
    public function downloadPdf($slug)
    {
        try {
            $post = Post::where('slug', $slug)->firstOrFail();
            $pdf = $post->pdf;
            if (empty($pdf)) {
                return $this->errorResponse('No PDF for this post', 404);
            }
            $pathWithoutStorage = (substr($pdf, 0, 8) === 'storage/') ? substr($pdf, 8) : $pdf;
            $isFilePath = (substr($pdf, 0, 8) === 'storage/') || (strpos($pdf, '/') !== false) || preg_match('/\.pdf$/i', $pdf);
            if (!$isFilePath) {
                return $this->errorResponse('PDF not available for download', 400);
            }
            $fullPath = Storage::disk('public')->path($pathWithoutStorage);
            if (!is_file($fullPath)) {
                return $this->errorResponse('PDF file not found', 404);
            }
            $filename = $post->slug . '.pdf';
            return response()->download($fullPath, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Post not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
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

            $imagePath = $image ? $image->store('images/posts', 'public') : null;

            $pdfPath = null;
            if ($pdf) {
                $pdfPath = $pdf->store('pdfs/posts', 'public');
            }

            $post = new Post();
            $post->user_id = Auth::id();
            $post->title = $request->title;
            $post->slug = $slug;
            $post->image = $imagePath;
            $post->pdf = $pdfPath;
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
    

            $categoryId = is_array($request->category_id) ? ($request->category_id[0] ?? reset($request->category_id)) : $request->category_id;
            $tagId = is_array($request->tag) ? ($request->tag[0] ?? reset($request->tag)) : $request->tag;
            $category = Category::find($categoryId);
            $post->categories()->attach($category);

            $tag = Tag::find($tagId);
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

            if ($request->title) {
                $slug = Str::slug($request->title);
            } else {
                $slug = $post->slug;
            }

            $imagePath = $post->image;
            if ($image) {
                $this->deleteStoredFileIfPath($post->image, 'images/');
                $imagePath = $image->store('images/posts', 'public');
            }

            $pdfPath = $post->pdf;
            if ($pdf) {
                $this->deleteStoredFileIfPath($post->pdf, 'pdfs/');
                $pdfPath = $pdf->store('pdfs/posts', 'public');
            }

            $categoryId = is_array($request->category_id) ? ($request->category_id[0] ?? reset($request->category_id)) : $request->category_id;
            $tagId = $request->tag ? (is_array($request->tag) ? ($request->tag[0] ?? reset($request->tag)) : $request->tag) : null;

            $post->user_id = Auth::id();
            $post->title = $request->title;
            $post->slug = $slug;
            $post->category_id = $categoryId;
            $post->volume = $request->volume;
            $post->issue = $request->issue;
            $post->date = $post->date;
            $post->doi = $request->doi;
            $post->practical = $request->practical;
            $post->author = $request->authors;
            $post->pdf = $pdfPath;
            $post->image = $imagePath;
            $post->keywords = $request->keywords;
            $post->abstract = $request->abstract;
            $post->save();

            Log::info($post->date);

            $post->categories()->sync([$categoryId]);
            if ($tagId) {
                $post->tags()->sync([$tagId]);
            }
        
    
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
     * @param  int|string  $id  Post id
     * @return \Illuminate\Http\Response
     */
    public function deletePost($id)
    {
        try {
            $post = Post::findOrFail($id);

            // Remove stored image and PDF files before deleting the record
            $this->deleteStoredFileIfPath($post->image, 'images/');
            $this->deleteStoredFileIfPath($post->pdf, 'pdfs/');

            $post->categories()->detach();
            $post->tags()->detach();
            $post->delete();

            return $this->successResponse(null, "Post Deleted successfully", 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Delete a stored file from public disk if the given value is a path (not base64).
     *
     * @param string|null $pathOrValue
     * @param string $prefix e.g. 'images/' or 'pdfs/'
     * @return void
     */
    protected function deleteStoredFileIfPath($pathOrValue, $prefix)
    {
        if (empty($pathOrValue) || !is_string($pathOrValue)) {
            return;
        }
        $path = (substr($pathOrValue, 0, 8) === 'storage/') ? substr($pathOrValue, 8) : $pathOrValue;
        if (substr($path, 0, strlen($prefix)) === $prefix && strpos($path, '/') !== false) {
            Storage::disk('public')->delete($path);
        }
    }

    public function validatePost(){
        return Validator::make(request()->all(), [
            'title' => 'required|min:15|',
            'image' => 'required|image|max:1536',
            'pdf' => 'required|mimes:pdf|max:1536',
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
            'image' => 'nullable|image|max:1536',
            'pdf' => 'nullable|mimes:pdf|max:1536',
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
