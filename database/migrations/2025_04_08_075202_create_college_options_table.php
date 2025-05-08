<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollegeOptionsTable extends Migration 
{
    public function up()
    {
        Schema::create('colleges', function (Blueprint $table) { 
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // For trash functionality
        });
    }

    public function down()
    {
        Schema::dropIfExists('colleges'); 
    }
}