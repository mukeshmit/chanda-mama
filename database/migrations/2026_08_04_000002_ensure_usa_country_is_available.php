<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureUsaCountryIsAvailable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('countries')) {
            return;
        }

        $values = [
            'name' => 'USA',
            'dial_code' => '+1',
        ];

        if (Schema::hasColumn('countries', 'status')) {
            $values['status'] = 1;
        }

        $country = DB::table('countries')->where('code', 'US')->first();

        if ($country) {
            DB::table('countries')->where('id', $country->id)->update($values);
        } else {
            DB::table('countries')->insert(array_merge($values, ['code' => 'US']));
        }
    }

    public function down()
    {
        if (Schema::hasTable('countries')) {
            DB::table('countries')
                ->where('code', 'US')
                ->where('name', 'USA')
                ->update(['name' => 'United States']);
        }
    }
}
