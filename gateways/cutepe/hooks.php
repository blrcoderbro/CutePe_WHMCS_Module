<?php
if (!defined("WHMCS")) {
    die("This hook cannot be run directly");
}

use WHMCS\Database\Capsule;

add_hook('AfterModuleCreate', 1, function($vars) {
    // Ensure this hook only applies to your specific payment gateway module
    if ($vars['serviceid'] && $vars['module'] == 'cutepe') {
        // Check if the settings already exist
        $existingSettings = Capsule::table('tblpaymentgateways')
            ->where('gateway', 'cutepe')
            ->pluck('setting');

        $settingsToInsert = [
            ['gateway' => 'cutepe', 'setting' => 'name', 'value' => 'CutePe', 'order' => 1],
            ['gateway' => 'cutepe', 'setting' => 'type', 'value' => 'Payments', 'order' => 0],
            ['gateway' => 'cutepe', 'setting' => 'visible', 'value' => 'on', 'order' => 0],
            ['gateway' => 'cutepe', 'setting' => 'cutepe_api_key', 'value' => '', 'order' => 0],
            ['gateway' => 'cutepe', 'setting' => 'merchant_key', 'value' => '', 'order' => 0],
            ['gateway' => 'cutepe', 'setting' => 'cutepe_module_auth_token', 'value' => '', 'order' => 0],
            ['gateway' => 'cutepe', 'setting' => 'convertto', 'value' => '', 'order' => 0],
        ];

        // Filter out settings that already exist
        $settingsToInsert = array_filter($settingsToInsert, function($setting) use ($existingSettings) {
            return !in_array($setting['setting'], $existingSettings);
        });

        // Insert new settings
        if (!empty($settingsToInsert)) {
            Capsule::table('tblpaymentgateways')->insert($settingsToInsert);
            logActivity("Payment gateway 'cutepe' settings updated automatically."); // Log for debugging
        } else {
            logActivity("Payment gateway 'cutepe' settings already exist. No update needed."); // Log for debugging
        }
    }
});