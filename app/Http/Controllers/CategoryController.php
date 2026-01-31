<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Auth;


class CategoryController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCategories(){

        try{
            $categories= Category::latest()->get();
            return $this->successResponse($categories);
        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }
       
    }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function getCategory($id) {

        try{
            $category= Category::where('id', $id)->firstOrFail();
            return $this->successResponse($category);
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
    public function addCategory(Request $request)
    {

        try{

            $validator = $this->validateCategory();
            if($validator->fails()){
              return $this->errorResponse($validator->messages(), 422);
            }

            $image = $request->file('image');
            $slug = Str::slug($request->name);

            $imagePath = $image ? $image->store('images/categories', 'public') : null;

            if (Auth::check())
            {
                $id = Auth::id();
            }

            $category = new Category();
            $category->name = $request->name;
            $category->slug = $slug;
            $category->image = $imagePath;
            $category->save();

            return $this->successResponse($category,"Saved successfully", 200);

        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateCategory(Request $request, $id)
    {

        try{

            if(count($request->all()) == 0){
                return $this->errorResponse("Nothing to update.Pass fields", 404);  
            }

            $request->headers->set('Content-Type', '');

            $validator = $this->validateUpdateCategory($id);
            if($validator->fails()){
              return $this->errorResponse($validator->messages(), 422);
            }

            $image = $request->file('image');
            $category = Category::find($id);

            $imagePath = $category->image;
            if ($image) {
                $this->deleteStoredFileIfPath($category->image, 'images/');
                $imagePath = $image->store('images/categories', 'public');
            }

            if (Auth::check())
            {
                $id = Auth::id();
            }

            if ($request->name) {
              $slug = Str::slug($request->name);
              $category->name = $request->name;
              $category->slug = $slug;
            }

            $category->image = $imagePath;
            $category->save();

            return $this->successResponse($category);
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
    public function deleteCategory($id)
    {
        try{
            $category = Category::find($id);
            $category->delete();

            return $this->successResponse(null,"Deleted successfully", 200);

        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Delete a stored file from public disk if the given value is a path (not base64).
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

    public function validateCategory(){
        return Validator::make(request()->all(), [
           'name'=>'required|unique:categories',
           'image'=>'required|mimes:jpeg,bmp,png,jpg'
        ]);
    }

    public function validateUpdateCategory($id)
    {
        return Validator::make(request()->all(), [
            'name' => ['nullable', Rule::unique('categories')->ignore($id)],
            'image' => 'nullable|mimes:jpeg,bmp,png,jpg'
        ]);
    }
}
