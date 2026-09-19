<?php

declare(strict_types=1);

final class IntegrationService
{
    private string $instagramToken;
    private string $instagramAccountId;
    private string $whatsappNumber;

    public function __construct()
    {
        $this->instagramToken = (string) (getenv('INSTAGRAM_ACCESS_TOKEN') ?: '');
        $this->instagramAccountId = (string) (getenv('INSTAGRAM_BUSINESS_ACCOUNT_ID') ?: '');
        $this->whatsappNumber = preg_replace('/[^0-9]/', '', (string) (getenv('WHATSAPP_NUMBER') ?: ''));
    }

    public function instagramEnabled(): bool
    {
        return $this->instagramToken !== '' && $this->instagramAccountId !== '';
    }

    public function publishApprovedContent(array $content): bool
    {
        if (!$this->instagramEnabled() || empty($content['media_path'])) {
            return false;
        }
        return false;
    }

    public function whatsappUrl(string $message = ''): ?string
    {
        if ($this->whatsappNumber === '') {
            return null;
        }
        return 'https://wa.me/' . $this->whatsappNumber . ($message !== '' ? '?text=' . rawurlencode($message) : '');
    }

    public function smtpConfigured(): bool
    {
        return (string) (getenv('SMTP_HOST') ?: '') !== '' && (string) (getenv('SMTP_FROM_EMAIL') ?: '') !== '';
    }
}
