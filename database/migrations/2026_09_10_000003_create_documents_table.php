<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_id')->unique();
            $table->string('filename');
            $table->string('session_name');
            $table->string('type'); 
            $table->string('session_id'); 
            $table->date('session_date'); 
            $table->string('sponsors')->nullable(); 
            $table->string('tags')->nullable(); 
            $table->string('file_path');
            $table->longText('extracted_text')->nullable(); 
            
            // Missing columns added below:
            $table->boolean('is_public')->default(true);
            $table->string('status')->default('published');
            $table->softDeletes(); // Automatically adds the 'deleted_at' timestamp
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
