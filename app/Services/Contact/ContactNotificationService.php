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
        $inquiryId = sprintf('#INQ-%s-%04d', $contact->created_at?->format('Ymd') ?? now()->format('Ymd'), $contact->id);
        $adminPanelUrl = MailNotificationHelper::frontendUrl('admin/contacts/'.$contact->id);

        $details = array_filter([
            'Inquiry ID' => $inquiryId,
            'Name' => $contact->name,
            'Email' => $contact->email,
            'Company' => $contact->company_name,
            'Type' => $contact->inquiry_type,
            'Status' => $contact->is_read ? 'Read' : 'New',
        ]);

        foreach (MailNotificationHelper::adminRecipients() as $admin) {
            MailNotificationHelper::sendIfEmail($admin, function (string $email) use ($contact, $details, $adminPanelUrl, $inquiryId): void {
                $this->mailingService->send($email, MailTemplate::AdminNewInquiry, [
                    'initials' => MailNotificationHelper::initials($contact->name),
                    'contactName' => $contact->name.($contact->company_name ? ' — '.$contact->company_name : ''),
                    'contactMeta' => $contact->inquiry_type,
                    'message' => $contact->message,
                    'details' => $details,
                    'adminPanelUrl' => $adminPanelUrl,
                ]);
            });
        }
    }
}
