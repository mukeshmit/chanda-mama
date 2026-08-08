<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHighlightsToProductsTables extends Migration
{
    public function up()
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'highlights')) {
            Schema::table('products', function (Blueprint $table) {
                $table->longText('highlights')->nullable()->after('description');
            });
        }

        if (Schema::hasTable('product_translations') && !Schema::hasColumn('product_translations', 'highlights')) {
            Schema::table('product_translations', function (Blueprint $table) {
                $table->longText('highlights')->nullable()->after('description');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('product_translations') && Schema::hasColumn('product_translations', 'highlights')) {
            Schema::table('product_translations', function (Blueprint $table) {
                $table->dropColumn('highlights');
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'highlights')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('highlights');
            });
        }
    }
}
