<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUploadedDocumentsTable extends Migration
{
    public function up()
    {
        Schema::create('uploaded_documents', function (Blueprint $table) {
            $table->id();
            $table->string('documentable_type');
            $table->unsignedBigInteger('documentable_id');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('filename');
            $table->string('file_path', 500);
            $table->bigInteger('file_size');
            $table->string('mime_type', 100);
            $table->integer('version')->default(1);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['documentable_type', 'documentable_id']);
            $table->foreign('parent_id')->references('id')->on('uploaded_documents')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('uploaded_documents');
    }
}
