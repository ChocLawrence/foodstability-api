<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
     {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('categories');
        Schema::create('categories', function (Blueprint $table) {
             $table->bigIncrements('id');
             $table->string('name');
             $table->string('slug')->unique();
             $table->string('image')->default('default.png');
             $table->timestamps();
         });
     }
 
     /**
      * Reverse the migrations.
      *
      * @return void
      */
     public function down()
     {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('categories');
        Schema::enableForeignKeyConstraints();
     }
}
