<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVideoMetadataToSlidersTable extends Migration
{
    public function up()
    {
        Schema::table('sliders', function (Blueprint $table) {
            if (!Schema::hasColumn('sliders', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('sliders', 'display_location')) {
                $table->string('display_location')->default('hero_section')->after('media_type');
            }
        });
    }

    public function down()
    {
        Schema::table('sliders', function (Blueprint $table) {
            if (Schema::hasColumn('sliders', 'display_location')) {
                $table->dropColumn('display_location');
            }
            if (Schema::hasColumn('sliders', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
}
