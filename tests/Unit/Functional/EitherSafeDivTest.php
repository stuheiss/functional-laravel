<?php

declare(strict_types=1);

namespace example;

use Widmogrod\Functional as f;
use Widmogrod\Monad\Either\Left;
use Widmogrod\Monad\Either\Right;
use Widmogrod\Monad\Either\Either;
use function Widmogrod\Monad\Either\left;
use function Widmogrod\Monad\Either\right;

class EitherSafeDivTest extends \PHPUnit\Framework\TestCase
{
    // eitherSafeDivide :: Int -> Int -> Either Int
    public function eitherSafeDivide(int $dividend, int $divisor): Either
    {
        return $divisor == 0
            ? left('Error: divide by zero')
            : right($dividend / $divisor);
    }

    // Val :: Int -> Just Int
    private function Val(int $val): Either
    {
        return right($val);
    }

    // Div :: expr -> expr -> Maybe Int
    private function Div(Either $dividend, Either $divisor): Either
    {
        return ($divisor instanceof Left) || ($dividend instanceof Left)
            ? left('Error: divide by zero')
            : $this->eitherSafeDivide($dividend->extract(), $divisor->extract());
    }

    public function test_either_div_expressions()
    {
        $this->assertEquals(right(0),                      $this->Div($this->Val(0), $this->Val(1)));
        $this->assertEquals(left('Error: divide by zero'), $this->Div($this->Val(1), $this->Val(0)));
        $this->assertEquals(right(4),                      $this->Div($this->Val(8), $this->Val(2)));
        $this->assertEquals(right(5),                      $this->Div($this->Val(10), $this->Div($this->Val(4), $this->Val(2))));
        $this->assertEquals(left('Error: divide by zero'), $this->Div($this->Val(10), $this->Div($this->Val(4), $this->Val(0))));
        $this->assertEquals(right(0.2),                    $this->Div($this->Div($this->Val(4), $this->Val(2)), $this->Val(10)));
        $this->assertEquals(left('Error: divide by zero'), $this->Div($this->Div($this->Val(4), $this->Val(0)), $this->Val(10)));
    }

    public function test_either_prevents_divide_by_zero()
    {
        $unsafeResult = $this->eitherSafeDivide(1, 0);
        $this->assertInstanceOf(Left::class, $unsafeResult);
        $this->assertEquals(left('Error: divide by zero'), $unsafeResult);
    }

    public function test_either_allows_divide_when_not_zero()
    {
        $safeResult = $this->eitherSafeDivide(4, 2);
        $safeQuotient = 2;
        $this->assertInstanceOf(Right::class, $safeResult);
        $this->assertEquals(right($safeQuotient), $safeResult);
    }
}
