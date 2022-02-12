<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;


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

            //get form image
            $image= $request->file('image');
            $slug= Str::slug($request->name);

            if(isset($image)){
                //make unique name for image
                $currentDate= Carbon::now()->toDateString();
                $imagename=$slug.'-'.$currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();

                //check if dir exists
                if(!Storage::disk('public')->exists('category')){

                    Storage::disk('public')->makeDirectory('category');
                }

                //resize image for category and upload
                $category= Image::make($image)->resize(1600,479)->save();
                Storage::disk('public')->put('category/'.$imagename,$category);
                
                //check if category slider dir exists
                if(!Storage::disk('public')->exists('category/slider')){

                    Storage::disk('public')->makeDirectory('category/slider');
                }

                //resize image for category slider and upload
                $slider= Image::make($image)->resize(500,333)->save();
                Storage::disk('public')->put('category/slider/'.$imagename,$slider);
                


            }else{
                $imagename="default.png";
            }

            $category= new Category();
            $category->name= $request->name;
            $category->slug=$slug;
            $category->image=$imagename;
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

            $request->headers->set('Content-Type', '');

            $validator = $this->validateCategory();
            if($validator->fails()){
              return $this->errorResponse($validator->messages(), 422);
            }

             // get form image
            $image = $request->file('image');
            $slug = Str::slug($request->name);
            $category = Category::find($id);
            if (isset($image))
            {
    //            make unique name for image
                $currentDate = Carbon::now()->toDateString();
                $imagename = $slug.'-'.$currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();
    //            check category dir is exists
                if (!Storage::disk('public')->exists('category'))
                {
                    Storage::disk('public')->makeDirectory('category');
                }
    //            delete old image
                if (Storage::disk('public')->exists('category/'.$category->image))
                {
                    Storage::disk('public')->delete('category/'.$category->image);
                }
    //            resize image for category and upload
                $categoryimage = Image::make($image)->resize(1600,479)->save();
                Storage::disk('public')->put('category/'.$imagename,$categoryimage);

                //            check category slider dir is exists
                if (!Storage::disk('public')->exists('category/slider'))
                {
                    Storage::disk('public')->makeDirectory('category/slider');
                }
                //            delete old slider image
                if (Storage::disk('public')->exists('category/slider/'.$category->image))
                {
                    Storage::disk('public')->delete('category/slider/'.$category->image);
                }
                //            resize image for category slider and upload
                $slider = Image::make($image)->resize(500,333)->save();
                Storage::disk('public')->put('category/slider/'.$imagename,$slider);

            } else {
                $imagename = $category->image;
            }

            $category->name = $request->name;
            $category->slug = $slug;
            $category->image = $imagename;
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
            if (Storage::disk('public')->exists('category/'.$category->image))
            {
                Storage::disk('public')->delete('category/'.$category->image);
            }

            if (Storage::disk('public')->exists('category/slider/'.$category->image))
            {
                Storage::disk('public')->delete('category/slider/'.$category->image);
            }

            $category->delete();

            return $this->successResponse(null,"Deleted successfully", 200);

        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function validateCategory(){
        return Validator::make(request()->all(), [
           'name'=>'required|unique:categories',
           'image'=>'required|mimes:jpeg,bmp,png,jpg'
        ]);
    }
}
