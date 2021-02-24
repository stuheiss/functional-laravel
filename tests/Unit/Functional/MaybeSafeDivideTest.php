<?php

declare(strict_types=1);

namespace example;

use Widmogrod\Functional as f;
use Widmogrod\Monad\Maybe;

function maybeSafeDivide($dividend, $divisor)
{
    return $divisor == 0
        ? Maybe\Nothing()
        : Maybe\Just::of($dividend / $divisor);
}

class MaybeSafeDivideTest extends \PHPUnit\Framework\TestCase
{
    public function test_maybe_prevents_divide_by_zero()
    {
        $unsafeResult = maybeSafeDivide(1, 0);
        $this->assertInstanceOf(Maybe\Nothing::class, $unsafeResult);
        $this->assertEquals(Maybe\Nothing(), $unsafeResult);
    }

    public function test_maybe_allows_divide_when_not_zero()
    {
        $safeResult = maybeSafeDivide(1, 1);
        $safeQuotient = 1;
        $this->assertInstanceOf(Maybe\Just::class, $safeResult);
        $this->assertEquals(Maybe\Just::of($safeQuotient), $safeResult);
    }
}
