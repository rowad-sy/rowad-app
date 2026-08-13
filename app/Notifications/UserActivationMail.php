<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserActivationMail extends Notification
{
    use Queueable;

    public function __construct(public string $activationUrl)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تفعيل حسابك - مؤسسة الرواد')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line('تم إنشاء حسابك في نظام مؤسسة الرواد.')
            ->line('لتفعيل حسابك، يرجى الضغط على الرابط التالي:')
            ->action('تفعيل حسابي', $this->activationUrl)
            ->line('إذا لم تقم أنت بإنشاء هذا الحساب، يرجى تجاهل هذه الرسالة.');
    }
}
