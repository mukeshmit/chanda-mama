<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColorAndExpiryToProductVariantsTable extends Migration
{
    public function up()
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'color_variant')) {
                $table->string('color_variant')->nullable()->after('type');
            }
            if (!Schema::hasColumn('product_variants', 'expiry_date_from')) {
                $table->date('expiry_date_from')->nullable()->after('color_variant');
            }
            if (!Schema::hasColumn('product_variants', 'expiry_date_to')) {
                $table->date('expiry_date_to')->nullable()->after('expiry_date_from');
            }
        });
    }

    public function down()
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'expiry_date_to')) {
                $table->dropColumn('expiry_date_to');
            }
            if (Schema::hasColumn('product_variants', 'expiry_date_from')) {
                $table->dropColumn('expiry_date_from');
            }
            if (Schema::hasColumn('product_variants', 'color_variant')) {
                $table->dropColumn('color_variant');
            }
        });
    }
}
