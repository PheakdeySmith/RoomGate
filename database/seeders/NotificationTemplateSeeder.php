<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'rent_invoice_created',
                'name' => 'Rent invoice created',
                'channel' => 'email',
                'subject' => 'Invoice {{invoice_number}} created',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'Your rent invoice {{invoice_number}} has been created for {{property_name}} / Room {{room_number}}.' . "\n"
                    . 'Amount due: ${{amount_due}}' . "\n"
                    . 'Due date: {{due_date}}' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'rent_invoice_overdue',
                'name' => 'Rent invoice overdue',
                'channel' => 'email',
                'subject' => 'Invoice {{invoice_number}} is overdue',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'Your rent invoice {{invoice_number}} is overdue.' . "\n"
                    . 'Amount due: ${{amount_due}}' . "\n"
                    . 'Due date: {{due_date}}' . "\n\n"
                    . 'Please make a payment as soon as possible.',
            ],
        ];

        foreach ($templates as $template) {
            MessageTemplate::updateOrCreate(
                ['tenant_id' => null, 'key' => $template['key']],
                array_merge($template, ['is_active' => true])
            );
        }
    }
}
