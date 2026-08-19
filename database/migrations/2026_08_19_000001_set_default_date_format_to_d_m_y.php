<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('settings')
            ->where('variable', 'date_format')
            ->update(['value' => 'd/m/Y']);
    }

    public function down()
    {
        DB::table('settings')
            ->where('variable', 'date_format')
            ->where('value', 'd/m/Y')
            ->update(['value' => 'm/d/Y']);
    }
};
