<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddVariantNameAndImageOrder extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('product_variants', 'variant_name')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->string('variant_name')->nullable()->after('type');
            });
        }

        if (!Schema::hasColumn('product_images', 'sort_order')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('product_variant_id');
            });
        }

        $groups = DB::table('product_images')
            ->select('product_id', 'product_variant_id')
            ->distinct()
            ->get();

        foreach ($groups as $group) {
            $imageIds = DB::table('product_images')
                ->where('product_id', $group->product_id)
                ->where('product_variant_id', $group->product_variant_id)
                ->orderBy('id')
                ->pluck('id');

            foreach ($imageIds as $index => $imageId) {
                DB::table('product_images')
                    ->where('id', $imageId)
                    ->update(['sort_order' => $index + 1]);
            }
        }

        DB::table('settings')
            ->where('variable', 'date_format')
            ->update(['value' => 'm/d/Y']);
    }

    public function down()
    {
        DB::table('settings')
            ->where('variable', 'date_format')
            ->where('value', 'm/d/Y')
            ->update(['value' => 'd-m-Y']);

        if (Schema::hasColumn('product_images', 'sort_order')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }

        if (Schema::hasColumn('product_variants', 'variant_name')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('variant_name');
            });
        }
    }
}
