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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('contentTitle');
            $table->text('content');
            $table->string('contentImg');
            $table->string('contentPreview');
            $table->string('slug');
            // Setzt Fremdschlüssel für User_id, costrained macht automatisch die verknüpfung, onDelete'cascade' löscht alle posts wenn user gelöscht wird
            $table->foreign('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
