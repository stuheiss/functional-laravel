<?php

declare(strict_types=1);

namespace example;

use Widmogrod\Functional as f;
use Widmogrod\Monad\Either;

function eitherSafeDivide($dividend, $divisor)
{
    return $divisor == 0
        ? Either\Left::of('Error: divide by zero')
        : Either\Right::of($dividend / $divisor);
}

class EitherSafeDivideTest extends \PHPUnit\Framework\TestCase
{
    public function test_either_prevents_divide_by_zero()
    {
        $unsafeResult = eitherSafeDivide(1, 0);
        $this->assertInstanceOf(Either\Left::class, $unsafeResult);
        $this->assertEquals(Either\Left::of('Error: divide by zero'), $unsafeResult);
    }

    public function test_either_allows_divide_when_not_zero()
    {
        $safeResult = eitherSafeDivide(1, 1);
        $safeQuotient = 1;
        $this->assertInstanceOf(Either\Right::class, $safeResult);
        $this->assertEquals(Either\Right::of($safeQuotient), $safeResult);
    }
}
