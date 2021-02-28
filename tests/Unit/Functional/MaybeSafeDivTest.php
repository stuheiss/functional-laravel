<?php

declare(strict_types=1);

namespace example;

use Widmogrod\Functional as f;
use Widmogrod\Monad\Maybe;
use function Widmogrod\Functional\valueOf;
use function Widmogrod\Monad\Free\liftF;
use Widmogrod\Monad\Free\Pure;
use function Widmogrod\Functional\compose;
use function Widmogrod\Functional\flip;
use function Widmogrod\Functional\join;
use function Widmogrod\Monad\Maybe\just;
use function Widmogrod\Monad\Maybe\nothing;

class MaybeSafeDivTest extends \PHPUnit\Framework\TestCase
{
    /*
    data Expr = Val Int | Div Expr Expr

    safeDiv :: Int -> Int -> Maybe Int
    safeDiv n m = if m == 0 then Nothing
                  else (Just n / m)

    eval :: Expr -> Maybe Int
    eval (Val n) = Just n
    eval (Div x y) = case eval x of
                        Nothing -> Nothing
                        Just n -> case eval y of
                                    Nothing -> Nothing
                                    Just m -> safeDiv n m
    */

    // maybeSafeDiv :: Int -> Int -> Maybe Int
    private function maybeSafeDiv($dividend, $divisor)
    {
        return $divisor == 0
            ? nothing()
            : just($dividend / $divisor);
    }

    // Val :: Int -> Just Int
    private function Val($val)
    {
        return just($val);
    }

    // Div :: expr -> expr -> Maybe Int
    private function Div($dividend, $divisor)
    {
        return ($divisor instanceof Maybe\Nothing) || ($dividend instanceof Maybe\Nothing)
            ? nothing()
            : $this->maybeSafeDiv($dividend->extract(), $divisor->extract());
    }

    public function test_maybe_div_expressions()
    {
        $this->assertEquals(just(0),   $this->Div($this->Val(0), $this->Val(1)));
        $this->assertEquals(nothing(), $this->Div($this->Val(1), $this->Val(0)));
        $this->assertEquals(just(4),   $this->Div($this->Val(8), $this->Val(2)));
        $this->assertEquals(just(5),   $this->Div($this->Val(10), $this->Div($this->Val(4), $this->Val(2))));
        $this->assertEquals(nothing(), $this->Div($this->Val(10), $this->Div($this->Val(4), $this->Val(0))));
        $this->assertEquals(just(0.2), $this->Div($this->Div($this->Val(4), $this->Val(2)), $this->Val(10)));
        $this->assertEquals(nothing(), $this->Div($this->Div($this->Val(4), $this->Val(0)), $this->Val(10)));
    }

    public function test_maybe_prevents_divide_by_zero()
    {
        $unsafeResult = $this->maybeSafeDiv(1, 0);
        $this->assertInstanceOf(Maybe\Nothing::class, $unsafeResult);
        $this->assertEquals(nothing(), $unsafeResult);
    }

    public function test_maybe_allows_divide_when_not_zero()
    {
        $safeResult = $this->maybeSafeDiv(1, 1);
        $safeQuotient = 1;
        $this->assertInstanceOf(Maybe\Just::class, $safeResult);
        $this->assertEquals(just($safeQuotient), $safeResult);
    }
}
