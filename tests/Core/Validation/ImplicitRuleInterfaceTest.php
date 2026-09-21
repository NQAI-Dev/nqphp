<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation;

use Nqphp\Core\Validation\ImplicitRuleInterface;
use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\PresentRule;
use Nqphp\Core\Validation\Rules\ProhibitedRule;
use Nqphp\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ImplicitRuleInterfaceTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testPresentRuleIsEvaluatedEvenWhenValueIsEmptyStringOrNull(): void
    {
        $dataMissing = [];
        $rule = new PresentRule($dataMissing);

        $errors = $this->validator->validate($dataMissing, [
            'note' => [$rule],
        ]);

        $this->assertArrayHasKey('note', $errors);
        $this->assertSame('Поле note обязательно должно присутствовать в запросе.', $errors['note'][0]);
    }

    public function testPresentRulePassesWhenFieldPresentWithNull(): void
    {
        $dataWithNull = ['note' => null];
        $rule = new PresentRule($dataWithNull);

        $errors = $this->validator->validate($dataWithNull, [
            'note' => [$rule],
        ]);

        $this->assertArrayNotHasKey('note', $errors);
    }

    public function testOrdinaryRuleIsSkippedWhenValueIsNull(): void
    {
        $ordinaryRule = new class implements RuleInterface {
            public bool $executed = false;

            public function passes(mixed $value, string $field): bool
            {
                $this->executed = true;
                return false;
            }

            public function message(string $field): string
            {
                return 'Ordinary error';
            }
        };

        $errors = $this->validator->validate(['field' => null], [
            'field' => [$ordinaryRule],
        ]);

        $this->assertFalse($ordinaryRule->executed);
        $this->assertSame([], $errors);
    }

    public function testImplicitRuleIsAlwaysExecutedEvenWhenValueIsNull(): void
    {
        $implicitRule = new class implements ImplicitRuleInterface {
            public bool $executed = false;

            public function passes(mixed $value, string $field): bool
            {
                $this->executed = true;
                return false;
            }

            public function message(string $field): string
            {
                return 'Implicit error';
            }
        };

        $errors = $this->validator->validate(['field' => null], [
            'field' => [$implicitRule],
        ]);

        $this->assertTrue($implicitRule->executed);
        $this->assertArrayHasKey('field', $errors);
        $this->assertSame('Implicit error', $errors['field'][0]);
    }
}
