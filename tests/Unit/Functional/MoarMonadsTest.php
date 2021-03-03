<?php

declare(strict_types=1);

namespace example;

use Symfony\Component\HttpKernel\EventListener\DumpListener;
use Widmogrod\Functional as f;
use Widmogrod\Monad as m;
use Widmogrod\Monad\Writer as W;
use Widmogrod\Primitive\Stringg as S;
use function Widmogrod\Monad\Either\right;
use function Widmogrod\Functional\foldM;
use function Widmogrod\Functional\fromIterable;
use function Widmogrod\Functional\fromNil;
use function Widmogrod\Monad\Maybe\just;
use function Widmogrod\Monad\Maybe\nothing;

class MoarMonadsTest extends \PHPUnit\Framework\TestCase
{
    // filterM :: (Monad m) => (a -> m Bool) -> [a] -> m [a]

    // kind of pointless when everything is a just()
    public function test_it_should_filter_with_maybe()
    {
        $data = f\fromIterable([1, 10, 15, 20, 25]);

        $filter = function ($i) {
            if ($i % 2 == 1) {
                return just(false);
            } elseif ($i > 15) {
                return just(false);
            }
            return just(true);
        };

        $result = f\filterM($filter, $data);

        $this->assertEquals(
            just(f\fromIterable([10])),
            $result
        );
    }

    // kind of pointless when everything is a right()
    public function test_it_should_filter_with_either()
    {
        $data = f\fromIterable([1, 10, 15, 20, 25]);

        $filter = function ($i) {
            if ($i % 2 == 1) {
                return right(false);
            } elseif ($i > 15) {
                return right(false);
            }
            return right(true);
        };

        $result = f\filterM($filter, $data);

        $this->assertEquals(
            right(f\fromIterable([10])),
            $result
        );
    }

    // Writer Monad
    public function test_it_should_filter_with_logs()
    {
        $data = f\fromIterable([1, 10, 15, 20, 25]);

        $filter = function ($i) {
            if ($i % 2 == 1) {
                return W::of(false, S::of("Reject odd number $i.\n"));
            } elseif ($i > 15) {
                return W::of(false, S::of("Reject $i because it is bigger than 15\n"));
            }

            return W::of(true);
        };

        list($result, $log) = f\filterM($filter, $data)->runWriter();

        $this->assertEquals(
            f\fromIterable([10]),
            $result
        );
        $this->assertEquals(
            'Reject odd number 1.
Reject odd number 15.
Reject 20 because it is bigger than 15
Reject odd number 25.
',
            $log->extract()
        );
    }

    // foldM :: (Monad m) => (a -> b -> m a) -> a -> [b] -> m a
    public function test_it_folds_over_maybe_list()
    {
        // returns nothing if acc > 10, else sum of vals as monad
        $fn = fn($acc, $i) => $acc > 10 ? nothing() : just($acc + $i);

        $res = f\foldM($fn, 0, f\fromIterable([1,2,3,4,5]));
        $this->assertEquals(nothing(), $res);

        $res = f\foldM($fn, 0, f\fromIterable([1,2,3,4]));
        $this->assertEquals(just(10), $res);
    }

    /**
     * @dataProvider provideData
     */
    public function test_it_should_work_with_maybe(
        $list,
        $expected
    ) {
        $addSingleDigit = function ($acc, $i) {
            return $i > 9 ? nothing() : just($acc + $i);
        };
        $this->assertEquals(
            $expected,
            foldM($addSingleDigit, 0, $list)
        );
    }

    public function provideData()
    {
        return [
            'just' => [
                '$list' => fromIterable([1, 3, 5, 7]),
                '$expected' => just(16)
            ],
            'nothing' => [
                '$list' => fromIterable([1, 3, 42, 7]),
                '$expected' => nothing(),
            ],
            'empty array' => [
                '$list' => fromNil(),
                '$expected' => fromNil(),
            ],
            'traversable' => [
                '$list' => fromIterable(new \ArrayIterator([1, 3, 5, 7])),
                '$expected' => just(16)
            ],
        ];
    }
}
