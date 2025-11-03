<?php

use App\Models\ExternalContact;
use App\Models\Meeting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('external_contact_meeting', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ExternalContact::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Meeting::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['external_contact_id', 'meeting_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_contact_meeting');
    }
};
