<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';
    protected $primary_key = 'id';
    protected $fillable = [
        'view_count',
        'slug',
        'title',
        'date',
        'title',
        'author',
        'abstract',
        'keywords',
        'volume',
        'issue',
        'doi',
        'image',
        'pdf',
        'category_id',
        'user_id',
        'created_at',
        'updated_at',
    ];
    
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function categories()
    {
        return $this->belongsToMany('App\Models\Category');
    }
    
    public function tags()
    {
        return $this->belongsToMany('App\Models\Tag');
    }

    public function favorite_to_users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 1);
    }
}
