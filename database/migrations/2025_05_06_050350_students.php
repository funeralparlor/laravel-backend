<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('course');
            $table->string('college');
            $table->string('campus');
            $table->string('year_level');
            $table->string('gender');
            $table->string('birthday');
            $table->string('birth_place');
            $table->string('comp_address');
            $table->string('barangay');
            $table->string('town');
            $table->string('province');
            $table->string('email');
            $table->string('number');
            $table->string('father_name');
            $table->string('father_occup');
            $table->string('mother_name');
            $table->string('mother_occup');
            $table->string('student_status');
            $table->string('last_sem')->nullable();
            $table->string('section');
            $table->string('approved');
            $table->string('scholar_ship');
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('students');
    }
};
