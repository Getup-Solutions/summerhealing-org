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
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->longText('description')->nullable();
            $table->longText('excerpt')->nullable();
            $table->string('thumbnail')->default('assets/static/img/subscriptionplan.png');
            $table->foreignId('agegroup_id')->nullable();
            $table->string('video_url')->default('sTANio_2E0Q');
            $table->integer('size')->default(10);
            $table->string('level')->nullable();
            $table->boolean('published')->default(true);
            $table->float('price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
