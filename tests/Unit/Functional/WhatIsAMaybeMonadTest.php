<?php
/*****
https://www.youtube.com/watch?v=t1e8gqXLbsU

data Expr = Val Int | Div Expr Expr

Math            Haskell
1               Val 1
6 / 2           Div (Val 6) (Val 2)
6 / (3 / 4)     Div (Val 6) (Div (Val 3) (Val 4))

eval :: Expr -> Int
eval (Val n) = n
eval (Div x y) = eval x / eval y

safeDiv :: Int -> Int -> Maybe Int
safeDiv n m = if m == 0 then
                Nothing
              else
                Just (n / m)

eval :: Expr -> Maybe Int
eval (Val n) = Just n
eval (Div x y) = case eval x of
                    Nothing -> Nothing
                    Just n -> case eval y of
                        Nothing -> Nothing
                        Just m -> safeDiv n m

-- extract pattern
-- m is Maybe, f is function
m >>= f = case m of
    Nothing -> Nothing
    Just x -> f x

eval :: Expr -> Maybe Int
eval (Val n) = return n
eval (Div x y) = eval x >>= (\n ->
                 eval y >>= (\m ->
                 safeDiv n m))

eval :: Expr -> Maybe Int
eval (Val n) = return n
eval (Div x y) = do n <- eval x
                    m <- eval y
                    safeDiv n m

-- The Maybe Monad
return :: a -> Maybe a
>>=    :: Maybe a -> (a -> Maybe b) -> Maybe b
*****/

declare(strict_types=1);

namespace example;

use Symfony\Component\HttpKernel\EventListener\DumpListener;
use Widmogrod\Functional as f;
use Widmogrod\Monad\Maybe\Maybe;
use Widmogrod\Monad\Maybe\Just;
use Widmogrod\Monad\Maybe\Nothing;

use function Widmogrod\Functional\fromIterable;
use function Widmogrod\Functional\fromValue;
use function Widmogrod\Monad\Maybe\just;
use function Widmogrod\Monad\Maybe\nothing;

/**
 * 9.1  Monadic Classes
 * The Prelude contains a number of classes defining monads are they are used in Haskell. These classes are based on the monad construct in category theory; whilst the category theoretic terminology provides the names for the monadic classes and operations, it is not necessary to delve into abstract mathematics to get an intuitive understanding of how to use the monadic classes.
 * A monad is constructed on top of a polymorphic type such as IO. The monad itself is defined by instance declarations associating the type with the some or all of the monadic classes, Functor, Monad, and MonadPlus. None of the monadic classes are derivable. In addition to IO, two other types in the Prelude are members of the monadic classes: lists ([]) and Maybe.
 *
 * Mathematically, monads are governed by set of laws that should hold for the monadic operations. This idea of laws is not unique to monads: Haskell includes other operations that are governed, at least informally, by laws. For example, x /= y and not (x == y) ought to be the same for any type of values being compared. However, there is no guarantee of this: both == and /= are separate methods in the Eq class and there is no way to assure that == and =/ are related in this manner. In the same sense, the monadic laws presented here are not enforced by Haskell, but ought be obeyed by any instances of a monadic class. The monad laws give insight into the underlying structure of monads: by examining these laws, we hope to give a feel for how monads are used.
 *
 * The Functor class, already discussed in section 5, defines a single operation: fmap. The map function applies an operation to the objects inside a container (polymorphic types can be thought of as containers for values of another type), returning a container of the same shape. These laws apply to fmap in the class Functor:
 *
 * fmap id	=	id
 * fmap (f . g)	=	fmap f . fmap g
 * These laws ensure that the container shape is unchanged by fmap and that the contents of the container are not re-arranged by the mapping operation.
 *
 * The Monad class defines two basic operators: >>= (bind) and return.
 *
 * infixl 1  >>, >>=
 * class  Monad m  where
 *     (>>=)            :: m a -> (a -> m b) -> m b
 *     (>>)             :: m a -> m b -> m b
 *     return           :: a -> m a
 *     fail             :: String -> m a
 *
 *     m >> k           =  m >>= \_ -> k
 *
 * The bind operations, >> and >>=, combine two monadic values while the return operation injects a value into the monad (container). The signature of >>= helps us to understand this operation: ma >>= \v -> mb combines a monadic value ma containing values of type a and a function which operates on a value v of type a, returning the monadic value mb. The result is to combine ma and mb into a monadic value containing b. The >> function is used when the function does not need the value produced by the first monadic operator.
 *
 * The precise meaning of binding depends, of course, on the monad. For example, in the IO monad, x >>= y performs two actions sequentially, passing the result of the first into the second. For the other built-in monads, lists and the Maybe type, these monadic operations can be understood in terms of passing zero or more values from one calculation to the next. We will see examples of this shortly.
 *
 * The do syntax provides a simple shorthand for chains of monadic operations. The essential translation of do is captured in the following two rules:
 *
 *   do e1 ; e2      =        e1 >> e2
 *   do p <- e1; e2  =        e1 >>= \p -> e2
 *
 * When the pattern in this second form of do is refutable, pattern match failure calls the fail operation. This may raise an error (as in the IO monad) or return a "zero" (as in the list monad). Thus the more complex translation is
 *
 *    do p <- e1; e2  =   e1 >>= (\v -> case v of p -> e2; _ -> fail "s")
 *
 * where "s" is a string identifying the location of the do statement for possible use in an error message. For example, in the I/O monad, an action such as 'a' <- getChar will call fail if the character typed is not 'a'. This, in turn, terminates the program since in the I/O monad fail calls error.
 *
 * The laws which govern >>= and return are:
 *
 * return a >>= k	=	k a
 * m >>= return	=	m
 * xs >>= return . f	=	fmap f xs
 * m >>= (\x -> k x >>= h)	=	(m >>= k) >>= h
 * The class MonadPlus is used for monads that have a zero element and a plus operation:
 *
 * class  (Monad m) => MonadPlus m  where
 *     mzero             :: m a
 *     mplus             :: m a -> m a -> m a
 *
 * The zero element obeys the following laws:
 *
 * m >>= \x -> mzero	=	mzero
 * mzero >>= m	=	mzero
 * For lists, the zero value is [], the empty list. The I/O monad has no zero element and is not a member of this class.
 *
 * The laws governing the mplus operator are as follows:
 *
 * m `mplus` mzero	=	m
 * mzero `mplus` m	=	m
 * The mplus operator is ordinary list concatenation in the list monad.
 */
class WhatIsAMaybeMonadTest extends \PHPUnit\Framework\TestCase
{
    /**
     * laws for >>= and return
     *
     * return a >>= k	=	k a
     * m >>= return	=	m
     * xs >>= return . f	=	fmap f xs
     * m >>= (\x -> k x >>= h)	=	(m >>= k) >>= h
     */

    // return :: a -> m a
    public function maybe_return($x)
    {
        return just($x);
    }

    public function test_maybe_return()
    {
        $this->assertEquals(just(42), $this->maybe_return(42));
    }

    // >>= aka bind
    // (>>=) :: m a -> (a -> m b) -> m b
    public function test_maybe_bind()
    {
        $this->assertEquals(just(43), just(42)->bind(fn($x) => just($x + 1)));
    }

    // Map applies a function to a wrapped value
    // map :: (a -> b) m a -> m b
    public function test_maybe_map()
    {
        $sqrt = fn($x) => sqrt($x);

        // map over nothing
        $this->assertEquals(nothing(), nothing()->map($sqrt));

        // map over just(a)
        $this->assertEquals(just(3), just(9)->map($sqrt));
        $this->assertEquals(just(0), just(0)->map($sqrt));
        $this->assertEquals(just('NAN'), just(-9)->map($sqrt));

        // a loose (==) match on value passes
        $this->assertEquals(just(3), just(9)->map($sqrt));

        // not really a strict match on value
        $this->assertEquals(just(3.0), just(9)->map($sqrt));
        // a truely strict match on value
        $this->assertTrue(just(3.0)->extract() === (just(9)->map($sqrt))->extract());
    }

    // Applicatives apply a wrapped function to a wrapped value
    // ap :: m a -> (a -> b) -> m b
    public function test_maybe_ap()
    {
        // apply a just(fn) to a just(val)
        $add3 = fn($x) => $x + 3;
        $res = just($add3)->ap(just(2));
        $this->assertEquals(just(5), $res);

        // for Applicatives
        // apply a just(fn) to 2 just(val)
        // (*) <$> Just 5 <*> Just 3)
        // liftA2 (*) (Just 5) (Just 3)
        $mul = (fn($x, $y) => $x * $y);
        $res = f\liftA2($mul, just(5), just(3));
        $this->assertEquals(just(15), $res);

        // same thing for Monads
        $res = f\liftM2($mul, just(5), just(3));
        $this->assertEquals(just(15), $res);
    }
}
