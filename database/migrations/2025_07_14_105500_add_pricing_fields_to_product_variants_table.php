<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPricingFieldsToProductVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'purchase_price')) {
                $table->float('purchase_price', 11, 2)->default(0)->after('price');
            }
            if (!Schema::hasColumn('product_variants', 'discount_percentage')) {
                $table->float('discount_percentage', 11, 2)->default(0)->after('discounted_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'discount_percentage')) {
                $table->dropColumn('discount_percentage');
            }
        });
    }
}
