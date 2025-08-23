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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string("user");
            $table->string("user_avatar")->nullable();
            $table->boolean("is_safe")->default(false);

            $table->integer("count_deals")->default(0);
            $table->decimal("remain", 30, 18);
            $table->string("payment_method")->default("sbp");

            $table->integer("price");

            $table->unsignedBigInteger("currency_id");
            $table->index("currency_id");
            $table->foreign("currency_id")->references("id")->on("currencies")->onDelete("cascade");

            $table->unsignedBigInteger("fiat_currency_id");
            $table->index("fiat_currency_id");
            $table->foreign("fiat_currency_id")->references("id")->on("fiat_currencies")->onDelete("cascade");

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
