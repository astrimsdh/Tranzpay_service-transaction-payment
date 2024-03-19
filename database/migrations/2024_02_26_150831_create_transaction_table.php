<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction', function (Blueprint $table) {
            $table->id();
            $table->integer('category_id');
            $table->string('transaction_code');
            $table->date('transaction_date');
            $table->time('transaction_time');
            $table->string('transaction_type');
            $table->string('transaction_number');
            $table->string('transaction_sku');
            $table->integer('transaction_total');
            $table->string('transaction_status');
            $table->string('transaction_message');
            $table->integer('transaction_user_id');
            $table->json('raw_response');
            $table->unique('transaction_code');
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
        Schema::dropIfExists('transaction');
    }
};
