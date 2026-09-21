<?php

declare(strict_types=1);

namespace Nqphp\Core\Validation;

/**
 * Marker interface for validation rules that should be evaluated even when the field
 * value is empty or null (e.g. PresentRule, ProhibitedRule, RequiredRule).
 */
interface ImplicitRuleInterface extends RuleInterface
{
}
