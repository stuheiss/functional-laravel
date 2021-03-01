<?php

declare(strict_types=1);

namespace example;

use Widmogrod\Functional as f;
use Widmogrod\Monad\Either\Left;
use Widmogrod\Monad\Either\Right;
use Widmogrod\Monad\Either\Either;
use function Widmogrod\Monad\Either\left;
use function Widmogrod\Monad\Either\right;
class EitherSafeDivideTest extends \PHPUnit\Framework\TestCase
{
    public function eitherSafeDivide($dividend, $divisor): Either
    {
        return $divisor == 0
            ? left('Error: divide by zero')
            : right($dividend / $divisor);
    }

        public function test_either_prevents_divide_by_zero()
    {
        $unsafeResult = $this->eitherSafeDivide(1, 0);
        $this->assertInstanceOf(Left::class, $unsafeResult);
        $this->assertEquals(left('Error: divide by zero'), $unsafeResult);
    }

    public function test_either_allows_divide_when_not_zero()
    {
        $safeResult = $this->eitherSafeDivide(1, 1);
        $safeQuotient = 1;
        $this->assertInstanceOf(Right::class, $safeResult);
        $this->assertEquals(right($safeQuotient), $safeResult);
    }
}
