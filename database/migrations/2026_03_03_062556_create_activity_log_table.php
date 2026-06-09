<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::create(config('activitylog.table_name'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->string('event')->nullable(); // Memastikan kolom event juga ada
            $table->text('description');

            // Relasi UUID (Penting untuk sistem kita!)
            $table->nullableUuidMorphs('subject', 'subject');
            $table->nullableUuidMorphs('causer', 'causer');

            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable(); // Ini yang tadi bikin bentrok
            $table->timestamps();

            $table->index('log_name');
        });
    }
    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))->dropIfExists(config('activitylog.table_name'));
    }
}
