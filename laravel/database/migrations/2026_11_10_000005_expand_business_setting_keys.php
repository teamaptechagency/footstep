<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
class ExpandBusinessSettingKeys extends Migration
{
    public function up(){DB::statement('ALTER TABLE `business_settings` MODIFY `type` VARCHAR(191) NOT NULL');}
    // Narrowing again would truncate account/settings keys and lose references.
    public function down(){}
}
