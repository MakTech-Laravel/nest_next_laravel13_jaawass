<?php

namespace App\Services\Contact;

use App\Enums\MailTemplate;
use App\Models\Contact;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class ContactNotificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function notifyAdmins(Contact $contact): void
    {
        $contact->loadMissing('translations');
        $receivedAt = $contact->created_at ?? now();
        $inquiryId = sprintf('#INQ-%s-%04d', $receivedAt->format('Ymd'), $contact->id);
        $adminPanelUrl = MailNotificationHelper::frontendUrl('admin/contacts/'.$contact->id);
        $contactsListUrl = MailNotificationHelper::frontendUrl('admin/contacts');
        $statusLabel = $contact->is_read
            ? __('mail.admin_new_inquiry.status_read')
            : __('mail.admin_new_inquiry.status_new');

        $details = array_filter([
            'Inquiry ID' => $inquiryId,
            'Name' => $contact->name,
            'Email' => $contact->email,
            'Company' => $contact->company_name,
            'Type' => $contact->inquiry_type,
            'Received' => $receivedAt->format('F j, Y · g:i A T'),
            'Status' => $statusLabel,
        ]);

        $contactSubline = collect([
            $contact->inquiry_type,
            $contact->email,
        ])->filter(fn ($value) => is_string($value) && trim($value) !== '')->implode(' · ');

        $inquiryTags = array_values(array_filter([
            $contact->inquiry_type ? [
                'label' => __('mail.admin_new_inquiry.tag_type'),
                'value' => $contact->inquiry_type,
            ] : null,
            [
                'label' => __('mail.admin_new_inquiry.tag_status'),
                'value' => $statusLabel,
            ],
        ]));

        foreach (MailNotificationHelper::adminRecipients() as $admin) {
            MailNotificationHelper::sendIfEmail($admin, function (string $email) use (
                $contact,
                $details,
                $adminPanelUrl,
                $contactsListUrl,
                $contactSubline,
                $inquiryTags,
                $receivedAt,
            ): void {
                $this->mailingService->send($email, MailTemplate::AdminNewInquiry, [
                    'initials' => MailNotificationHelper::initials($contact->name),
                    'contactName' => $contact->name.($contact->company_name ? ' — '.$contact->company_name : ''),
                    'contactSubline' => $contactSubline,
                    'contactMeta' => $contact->inquiry_type,
                    'message' => $contact->message,
                    'details' => $details,
                    'inquiryTags' => $inquiryTags,
                    'receivedAt' => $receivedAt->format('M j · g:i A'),
                    'adminPanelUrl' => $adminPanelUrl,
                    'contactsListUrl' => $contactsListUrl,
                    'ctaUrl' => $adminPanelUrl,
                    'ctaLabel' => __('mail.admin_new_inquiry.cta'),
                ]);
            });
        }
    }
}
