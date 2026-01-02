<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorktimeRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worktime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worktime_id')->constrained()->onDelete('cascade');
            $table->datetime('requested_start_time')->nullable();
            $table->datetime('requested_end_time')->nullable();
            $table->text('reason');
            $table->unsignedTinyInteger('approval_status')->default(0);
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
        Schema::dropIfExists('worktime_requests');
    }
}
