<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorktimeRequestBreaksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('worktime_request_breaks', function (Blueprint $table) {
        $table->id();
        $table->foreignId('worktime_request_id')
              ->constrained()
              ->onDelete('cascade');
        $table->datetime('break_start')->nullable();
        $table->datetime('break_end')->nullable();
        $table->timestamps(); });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::dropIfExists('worktime_request_breaks');
    }
}
