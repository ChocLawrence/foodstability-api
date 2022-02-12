<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class ArchivesController extends Controller
{
    
    use ApiResponser;
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function getVolumes()
    {

        try{
            $volumes=Post::with(['user','categories'])
                        ->select('volume')->groupBy('volume')
                        ->orderBy('volume','desc')
                        ->get();

            return $this->successResponse($volumes);
        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }

    }

    public function getVolumeIssues($volume)
    {

        try{
            $issues=Post::select('issue')->where('volume',$volume)->groupBy('issue')
                    ->orderBy('issue','desc')
                    ->get();
            return $this->successResponse($issues);        

        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }

    }

    public function getPostsByVolumeAndIssue($volume,$issue)
    {
        try{
            $posts = Post::where('issue',$issue)
                        ->where('volume',$volume)
                        ->get();

            return $this->successResponse($posts);        

        }catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 404);
        }

    }
}
