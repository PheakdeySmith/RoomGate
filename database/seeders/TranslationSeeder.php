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
            'menu.settings' => ['en' => 'Settings', 'km' => '????????'],
            'menu.notifications' => ['en' => 'Notifications', 'km' => 'ការជូនដំណឹង'],
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
