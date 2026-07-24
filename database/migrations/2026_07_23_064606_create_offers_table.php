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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->integer('academy_id')->unsigned();
            $table->text('title', 100);
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('academy_id')->references('id')->on('academies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
