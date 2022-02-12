<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
     /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
     {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('posts');
         Schema::create('posts', function (Blueprint $table) {
             $table->bigIncrements('id');
             $table->string('slug')->unique();
             $table->string('date');
             $table->text('title');
             $table->text('author');
             $table->text('abstract');
             $table->text('practical');
             $table->string('keywords');
             $table->string('volume');
             $table->string('issue');
             $table->string('doi')->nullable();
             $table->integer('view_count')->default(0);
             $table->string('image')->default('post_default.jpg');
             $table->string('pdf');
             $table->foreignId('category_id')
             ->constrained('categories')
             ->onDelete('cascade');
             $table->foreignId('user_id')
             ->constrained('users')
             ->onDelete('cascade');
             $table->timestamps();
         });
         Schema::enableForeignKeyConstraints();
     }
 
     /**
      * Reverse the migrations.
      *
      * @return void
      */
     public function down()
     {
         Schema::disableForeignKeyConstraints();
         Schema::dropIfExists('posts');
         Schema::enableForeignKeyConstraints();
     }
}
