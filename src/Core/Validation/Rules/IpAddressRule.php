<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;

/**
 * Validates that the given field is a valid IP address (IPv4, IPv6, or either).
 */
class IpAddressRule implements RuleInterface
{
    public const TYPE_ANY = 'any';
    public const TYPE_V4 = 'v4';
    public const TYPE_V6 = 'v6';

    /**
     * @param string $type IP address version ('any', 'v4', 'v6')
     * @param bool $allowPrivate Whether to accept private network IP ranges
     * @param bool $allowReserved Whether to accept reserved IP ranges
     */
    public function __construct(
        private readonly string $type = self::TYPE_ANY,
        private readonly bool $allowPrivate = true,
        private readonly bool $allowReserved = true
    ) {
    }

    public function passes(mixed $value, string $field): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        $flags = 0;

        if ($this->type === self::TYPE_V4) {
            $flags |= FILTER_FLAG_IPV4;
        } elseif ($this->type === self::TYPE_V6) {
            $flags |= FILTER_FLAG_IPV6;
        }

        if (!$this->allowPrivate) {
            $flags |= FILTER_FLAG_NO_PRIV_RANGE;
        }

        if (!$this->allowReserved) {
            $flags |= FILTER_FLAG_NO_RES_RANGE;
        }

        return filter_var($value, FILTER_VALIDATE_IP, $flags) !== false;
    }

    public function message(string $field): string
    {
        return match ($this->type) {
            self::TYPE_V4 => "Поле {$field} должно быть корректным IPv4-адресом.",
            self::TYPE_V6 => "Поле {$field} должно быть корректным IPv6-адресом.",
            default => "Поле {$field} должно быть корректным IP-адресом.",
        };
    }
}
