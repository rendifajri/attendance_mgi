<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_hour', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('shift');
            $table->tinyInteger('day');
            $table->time('start');
            $table->time('end');
            $table->unique(['day', 'shift']);
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
        Schema::dropIfExists('work_hour');
    }
}
