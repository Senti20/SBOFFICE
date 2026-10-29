<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->unique();
            $table->string('requester_name');
            $table->string('contact_number'); // Must be formatted for SMS (e.g., 09123456789)
            $table->string('document_id'); // Links to the requested document
            $table->integer('copies')->default(1);
            $table->string('reason');
            $table->date('pickup_date');
            $table->string('valid_id_path'); // Path to uploaded Valid ID image
            $table->string('status')->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
