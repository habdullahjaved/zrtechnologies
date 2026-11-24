<?php

namespace App\Notifications;

use App\Settings\BusinessSettings;
use App\Support\Messaging\MessageRenderer;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

/**
 * Builds a branded email from an editable message template.
 */
final class TemplatedMail
{
    /**
     * @param  array<string, string|int|HtmlString|null>  $variables
     */
    public static function make(string $templateKey, array $variables, ?string $actionText = null, ?string $actionUrl = null): ?MailMessage
    {
        $renderer = app(MessageRenderer::class);
        $template = $renderer->template($templateKey);

        if ($template === null) {
            return null;
        }

        $business = app(BusinessSettings::class);

        return (new MailMessage)
            ->subject($renderer->render((string) $template->subject, $variables, escape: false))
            ->replyTo($business->email, $business->trading_name)
            ->markdown('mail.templated', [
                'body' => $renderer->render($template->body, $variables),
                'actionText' => $actionText,
                'actionUrl' => $actionUrl,
                'businessName' => $business->trading_name,
            ]);
    }
}
