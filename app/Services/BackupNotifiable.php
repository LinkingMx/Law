<?php

namespace App\Services;

use Illuminate\Notifications\Notifiable;

class BackupNotifiable
{
    use Notifiable;

    protected array $emails;
    protected ?string $slackWebhookUrl;

    public function __construct(array $emails, ?string $slackWebhookUrl = null)
    {
        $this->emails = $emails;
        $this->slackWebhookUrl = $slackWebhookUrl;
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail($notification)
    {
        return $this->emails;
    }

    /**
     * Route notifications for the slack channel.
     */
    public function routeNotificationForSlack($notification)
    {
        return $this->slackWebhookUrl;
    }
}