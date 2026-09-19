<?php

declare(strict_types=1);

namespace Nqphp\Core\Mailer;

final class Email
{
    private string $from = '';
    /** @var list<string> */
    private array $to = [];
    /** @var list<string> */
    private array $cc = [];
    /** @var list<string> */
    private array $bcc = [];
    private string $subject = '';
    private string $textBody = '';
    private string $htmlBody = '';
    /** @var array<string, string> */
    private array $headers = [];

    public function from(string $from): self
    {
        $this->from = $from;
        return $this;
    }

    public function to(string ...$to): self
    {
        $this->to = array_values(array_unique(array_merge($this->to, $to)));
        return $this;
    }

    public function cc(string ...$cc): self
    {
        $this->cc = array_values(array_unique(array_merge($this->cc, $cc)));
        return $this;
    }

    public function bcc(string ...$bcc): self
    {
        $this->bcc = array_values(array_unique(array_merge($this->bcc, $bcc)));
        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function text(string $text): self
    {
        $this->textBody = $text;
        return $this;
    }

    public function html(string $html): self
    {
        $this->htmlBody = $html;
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @return list<string>
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * @return list<string>
     */
    public function getCc(): array
    {
        return $this->cc;
    }

    /**
     * @return list<string>
     */
    public function getBcc(): array
    {
        return $this->bcc;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getTextBody(): string
    {
        return $this->textBody;
    }

    public function getHtmlBody(): string
    {
        return $this->htmlBody;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
