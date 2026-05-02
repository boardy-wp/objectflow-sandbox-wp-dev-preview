<?php
if (!defined('ABSPATH')) { exit; }

class OFS_Activator {
    public static function activate(): void {
        OFS_DB::create_tables();
        OFS_Seeder::seed_if_empty();
    }
}
