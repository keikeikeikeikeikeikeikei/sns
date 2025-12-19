<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class VerifyEmailJa extends VerifyEmail
{
    /**
     * Get the verify email notification mail message for the given URL.
     *
     * @param  string  $url
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject(Lang::get('メールアドレスの確認'))
            ->line(Lang::get('以下のボタンをクリックして、メールアドレスを確認してください。'))
            ->action(Lang::get('メールアドレスを確認'), $url)
            ->line(Lang::get('もしアカウントを作成していない場合は、このメールを無視してください。'));
    }
}
