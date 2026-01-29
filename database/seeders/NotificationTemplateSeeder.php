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
            [
                'key' => 'rent_invoice_created_admin',
                'name' => 'Rent invoice created (admin)',
                'channel' => 'email',
                'subject' => 'Invoice {{invoice_number}} generated',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'Invoice {{invoice_number}} was generated for {{occupant_name}}.' . "\n"
                    . 'Amount due: ${{amount_due}}' . "\n"
                    . 'Due date: {{due_date}}' . "\n"
                    . 'Property: {{property_name}} / Room {{room_number}}' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'rent_invoice_overdue_admin',
                'name' => 'Rent invoice overdue (admin)',
                'channel' => 'email',
                'subject' => 'Invoice {{invoice_number}} is overdue',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'Invoice {{invoice_number}} for {{occupant_name}} is overdue.' . "\n"
                    . 'Amount due: ${{amount_due}}' . "\n"
                    . 'Due date: {{due_date}}' . "\n"
                    . 'Property: {{property_name}} / Room {{room_number}}' . "\n\n"
                    . 'Please follow up.',
            ],
            [
                'key' => 'rent_invoice_due_soon',
                'name' => 'Rent invoice due soon',
                'channel' => 'email',
                'subject' => 'Invoice {{invoice_number}} is due soon',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'Your rent invoice {{invoice_number}} is due on {{due_date}}.' . "\n"
                    . 'Amount due: ${{amount_due}}' . "\n"
                    . 'Property: {{property_name}} / Room {{room_number}}' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'rent_invoice_due_soon_admin',
                'name' => 'Rent invoice due soon (admin)',
                'channel' => 'email',
                'subject' => 'Invoice {{invoice_number}} is due soon',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'Invoice {{invoice_number}} for {{occupant_name}} is due on {{due_date}}.' . "\n"
                    . 'Amount due: ${{amount_due}}' . "\n"
                    . 'Property: {{property_name}} / Room {{room_number}}' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'tenant_invitation_sent',
                'name' => 'Tenant invitation sent',
                'channel' => 'email',
                'subject' => 'You are invited to {{tenant_name}}',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'You have been invited to join {{tenant_name}} as {{role}} on RoomGate.' . "\n"
                    . 'Sign in with this email to access your account.' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'tenant_invitation_accepted',
                'name' => 'Tenant invitation accepted',
                'channel' => 'email',
                'subject' => '{{recipient_name}} accepted the invitation',
                'body' => 'Hello {{owner_name}},' . "\n\n"
                    . '{{recipient_name}} accepted the invitation to {{tenant_name}}.' . "\n"
                    . 'Role: {{role}}' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'tenant_user_disabled',
                'name' => 'Tenant user disabled',
                'channel' => 'email',
                'subject' => 'Your account has been disabled',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'Your access to {{tenant_name}} has been disabled.' . "\n"
                    . 'If this is unexpected, contact your administrator.' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'tenant_user_disabled_admin',
                'name' => 'Tenant user disabled (admin)',
                'channel' => 'email',
                'subject' => '{{user_name}} was disabled',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . '{{user_name}} was disabled in {{tenant_name}}.' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'subscription_created',
                'name' => 'Subscription created',
                'channel' => 'email',
                'subject' => 'Your subscription is active',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'Your {{plan_name}} subscription for {{tenant_name}} is active.' . "\n"
                    . 'Current period ends: {{period_end}}' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'subscription_trial_ending',
                'name' => 'Subscription trial ending',
                'channel' => 'email',
                'subject' => 'Your trial ends on {{trial_end}}',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'Your trial for {{tenant_name}} ends on {{trial_end}}.' . "\n"
                    . 'Choose a plan to continue service.' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'subscription_renewal_failed',
                'name' => 'Subscription renewal failed',
                'channel' => 'email',
                'subject' => 'Subscription renewal failed',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'We could not renew your subscription for {{tenant_name}}.' . "\n"
                    . 'Grace period ends: {{grace_end}}' . "\n\n"
                    . 'Please update payment to avoid interruption.',
            ],
            [
                'key' => 'subscription_renewal_succeeded',
                'name' => 'Subscription renewal succeeded',
                'channel' => 'email',
                'subject' => 'Subscription renewed',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'Your subscription for {{tenant_name}} has been renewed.' . "\n"
                    . 'Current period ends: {{period_end}}' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'subscription_cancelled',
                'name' => 'Subscription cancelled',
                'channel' => 'email',
                'subject' => 'Subscription cancelled',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'Your subscription for {{tenant_name}} has been cancelled.' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'subscription_expired',
                'name' => 'Subscription expired',
                'channel' => 'email',
                'subject' => 'Subscription expired',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'Your subscription for {{tenant_name}} has expired.' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'subscription_payment_received',
                'name' => 'Subscription payment received',
                'channel' => 'email',
                'subject' => 'Payment received for invoice {{invoice_number}}',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'We received a payment of {{amount}} {{currency}} for invoice {{invoice_number}}.' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'subscription_payment_failed',
                'name' => 'Subscription payment failed',
                'channel' => 'email',
                'subject' => 'Payment failed for invoice {{invoice_number}}',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'Payment failed for invoice {{invoice_number}}.' . "\n"
                    . 'Please retry your payment.' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'subscription_payment_refunded',
                'name' => 'Subscription payment refunded',
                'channel' => 'email',
                'subject' => 'Refund issued for invoice {{invoice_number}}',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'A refund was issued for invoice {{invoice_number}}.' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'maintenance_request_created',
                'name' => 'Maintenance request created',
                'channel' => 'email',
                'subject' => 'New maintenance request #{{request_id}}',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'A new maintenance request was created for {{tenant_name}}.' . "\n"
                    . 'Title: {{title}}' . "\n"
                    . 'Priority: {{priority}}' . "\n"
                    . 'Status: {{status}}' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'maintenance_request_status_changed',
                'name' => 'Maintenance request status changed',
                'channel' => 'email',
                'subject' => 'Maintenance request #{{request_id}} status updated',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'The maintenance request "{{title}}" was updated to {{status}}.' . "\n\n"
                    . 'Thank you.',
            ],
            [
                'key' => 'maintenance_request_comment_added',
                'name' => 'Maintenance request comment added',
                'channel' => 'email',
                'subject' => 'New comment on maintenance request #{{request_id}}',
                'body' => 'Hello {{recipient_name}},' . "\n\n"
                    . 'A new comment was added to "{{title}}".' . "\n"
                    . 'Comment: {{comment}}' . "\n\n"
                    . 'Thank you.',
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
