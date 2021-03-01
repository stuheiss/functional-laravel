<?php

declare(strict_types=1);

namespace example;

use Widmogrod\Functional as f;
use Widmogrod\Monad\Maybe\Just;
use Widmogrod\Monad\Maybe\Nothing;
use Widmogrod\Monad\Maybe\Maybe;
use function Widmogrod\Monad\Maybe\just;
use function Widmogrod\Monad\Maybe\nothing;

class MaybeSafeDivideTest extends \PHPUnit\Framework\TestCase
{
    public function maybeSafeDivide($dividend, $divisor): Maybe
    {
        return $divisor == 0
            ? nothing()
            : just($dividend / $divisor);
    }

    public function test_maybe_prevents_divide_by_zero()
    {
        $unsafeResult = $this->maybeSafeDivide(1, 0);
        $this->assertInstanceOf(Nothing::class, $unsafeResult);
        $this->assertEquals(nothing(), $unsafeResult);
    }

    public function test_maybe_allows_divide_when_not_zero()
    {
        $safeResult = $this->maybeSafeDivide(1, 1);
        $safeQuotient = 1;
        $this->assertInstanceOf(Just::class, $safeResult);
        $this->assertEquals(just($safeQuotient), $safeResult);
    }
}
