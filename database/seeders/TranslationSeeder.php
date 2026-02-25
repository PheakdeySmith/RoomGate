<?php

namespace Database\Seeders;

use App\Models\Translation;
use App\Services\TranslationExporter;
use Illuminate\Database\Seeder;

class TranslationSeeder extends Seeder
{
    public function run(): void
    {
        $seed = [
            'menu.dashboard' => ['en' => 'Dashboard', 'km' => 'ផ្ទាំងគ្រប់គ្រង'],
            'menu.roles' => ['en' => 'Roles', 'km' => 'តួនាទី'],
            'menu.permissions' => ['en' => 'Permissions', 'km' => 'សិទ្ធិ'],
            'menu.audit_logs' => ['en' => 'Audit Logs', 'km' => 'កំណត់ហេតុផ្ទៀងផ្ទាត់'],
            'menu.translations' => ['en' => 'Translations', 'km' => 'ការបកប្រែ'],
            'menu.settings' => ['en' => 'Settings', 'km' => 'ការកំណត់'],
            'menu.maintenance' => ['en' => 'Maintenance', 'km' => 'ការថែទាំ'],
            'menu.subscriptions' => ['en' => 'Subscriptions', 'km' => 'ការជាវ'],
            'menu.subscription_invoices' => ['en' => 'Invoices', 'km' => 'វិក្កយបត្រ'],
            'menu.subscription_payments' => ['en' => 'Payments', 'km' => 'ការទូទាត់'],
            'menu.plan_usage' => ['en' => 'Plan Usage', 'km' => 'ការប្រើប្រាស់គម្រោង'],
            'menu.reports_analytics' => ['en' => 'Reports & Analytics', 'km' => 'របាយការណ៍ និងវិភាគ'],
            'menu.enterprise_assignments' => ['en' => 'Enterprise Assignments', 'km' => 'ការចាត់តាំងសហគ្រាស'],
            'menu.message_templates' => ['en' => 'Message Templates', 'km' => 'គំរូសារ'],
            'menu.outbound_messages' => ['en' => 'Outbound Messages', 'km' => 'សារចេញ'],
            'menu.system_setup' => ['en' => 'System Setup', 'km' => 'ការកំណត់ប្រព័ន្ធ'],
            'menu.system_setup_general' => ['en' => 'General', 'km' => 'ទូទៅ'],
            'menu.system_setup_two_factor' => ['en' => 'Two Factor', 'km' => 'ផ្ទៀងផ្ទាត់ពីរជាន់'],
            'menu.system_setup_email' => ['en' => 'Email', 'km' => 'អ៊ីមែល'],
            'menu.system_setup_sms' => ['en' => 'SMS', 'km' => 'សារ SMS'],
            'menu.system_setup_api_permission' => ['en' => 'API Permission', 'km' => 'សិទ្ធិ API'],
            'menu.system_setup_notifications' => ['en' => 'Notifications', 'km' => 'ការជូនដំណឹង'],
            'menu.system_setup_language' => ['en' => 'Language', 'km' => 'ភាសា'],
            'menu.system_setup_currency' => ['en' => 'Currency', 'km' => 'រូបិយប័ណ្ណ'],
            'menu.system_setup_utility' => ['en' => 'Utility', 'km' => 'សេវាប្រើប្រាស់'],
            'menu.system_setup_cron' => ['en' => 'Cron', 'km' => 'កាលវិភាគ Cron'],
            'menu.system_setup_backup' => ['en' => 'Backup', 'km' => 'បម្រុងទុក'],
            'menu.payment_method_settings' => ['en' => 'Payment Method Settings', 'km' => 'ការកំណត់វិធីទូទាត់'],
            'menu.feature_flags' => ['en' => 'Feature Flags', 'km' => 'ការបើកបិទមុខងារ'],
            'menu.ops_tooling' => ['en' => 'Ops Tooling', 'km' => 'ឧបករណ៍ប្រតិបត្តិការ'],
            'menu.notifications' => ['en' => 'Notifications', 'km' => 'ការជូនដំណឹង'],
            'menu.iot_control' => ['en' => 'IoT Control', 'km' => 'គ្រប់គ្រង IoT'],
            'menu.tenant_dashboard' => ['en' => 'Tenant Dashboard', 'km' => 'ផ្ទាំងអ្នកជួល'],
            'actions.filter' => ['en' => 'Filter', 'km' => 'តម្រង'],
            'actions.reset' => ['en' => 'Reset', 'km' => 'កំណត់ឡើងវិញ'],
            'actions.view' => ['en' => 'View', 'km' => 'មើល'],
            'actions.restore' => ['en' => 'Restore', 'km' => 'ស្ដារឡើងវិញ'],
            'labels.search' => ['en' => 'Search', 'km' => 'ស្វែងរក'],
            'labels.action' => ['en' => 'Action', 'km' => 'សកម្មភាព'],
            'labels.model' => ['en' => 'Model', 'km' => 'ម៉ូឌែល'],
            'labels.user' => ['en' => 'User', 'km' => 'អ្នកប្រើប្រាស់'],
            'labels.from' => ['en' => 'From', 'km' => 'ពី'],
            'labels.to' => ['en' => 'To', 'km' => 'ដល់'],
            'audit.title' => ['en' => 'Audit Logs', 'km' => 'កំណត់ហេតុផ្ទៀងផ្ទាត់'],
            'Roles' => ['en' => 'Roles', 'km' => 'តួនាទី'],
            'Permissions' => ['en' => 'Permissions', 'km' => 'សិទ្ធិ'],
        ];

        foreach ($seed as $key => $values) {
            foreach ($values as $locale => $text) {
                Translation::updateOrCreate(
                    ['key' => $key, 'locale' => $locale],
                    ['text' => $text]
                );
            }
        }

        TranslationExporter::exportLocales(['en', 'km']);
    }
}
