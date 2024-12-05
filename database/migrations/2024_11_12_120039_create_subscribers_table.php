<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->bigIncrements('subscription_id');
            $table->unsignedBigInteger('t_id');
            $table->unsignedBigInteger('u_id');
            $table->unsignedBigInteger('plan_id');
            $table->date('expiry');
            $table->timestamps();

            $table->foreign('t_id')->references('transaction_id')->on('transactions')->onDelete('cascade');
            $table->foreign('u_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('plan_id')->references('plan_id')->on('plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
