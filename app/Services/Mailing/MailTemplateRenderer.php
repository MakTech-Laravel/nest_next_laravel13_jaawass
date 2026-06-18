<?php

namespace App\Services\Mailing;

use App\Exceptions\Mailing\UnknownMailTemplateException;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\View;

class MailTemplateRenderer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $template, array $data = []): string
    {
        $definition = $this->definition($template);

        if ($definition['markdown']) {
            return app(Markdown::class)->render($definition['view'], $data);
        }

        return View::make($definition['view'], $data)->render();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function subject(string $template, array $data = []): string
    {
        $definition = $this->definition($template);

        $replacements = array_filter(
            $data,
            fn (mixed $value): bool => is_scalar($value) || $value === null,
        );

        return __($definition['subject'], $replacements);
    }

    /**
     * @return array{view: string, subject: string, markdown: bool}
     */
    public function definition(string $template): array
    {
        $definition = config("mailing.templates.{$template}");

        if (! is_array($definition)) {
            throw UnknownMailTemplateException::forTemplate($template);
        }

        return [
            'view' => (string) $definition['view'],
            'subject' => (string) $definition['subject'],
            'markdown' => (bool) ($definition['markdown'] ?? false),
        ];
    }
}
