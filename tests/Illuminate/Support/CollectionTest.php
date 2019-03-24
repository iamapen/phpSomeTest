<?php
declare(strict_types=1);

use Illuminate\Support\Collection;

/**
 * 元のコレクションを不変のまま、チェーンできる
 * @see https://readouble.com/laravel/5.5/ja/collections.html
 */
class CollectionTest extends \PHPUnit\Framework\TestCase
{
    function testToArray()
    {
        $src = [
            ['first' => 'Arare', 'last' => 'Norimaki'],
            ['first' => 'Taro', 'last' => 'Soramame'],
            new Collection(['first' => 'Akane', 'last' => 'Kimidori']),
        ];
        $ex = [
            ['first' => 'Arare', 'last' => 'Norimaki'],
            ['first' => 'Taro', 'last' => 'Soramame'],
            ['first' => 'Akane', 'last' => 'Kimidori'],
        ];
        $collection = new Collection($src);

        // toArray() は再帰的にすべてを配列にする
        $this->assertSame($ex, $collection->toArray());

        // all() は Collection::$items をただ単に返す。配列。
        $this->assertSame($src, $collection->all());
    }



    // region 分割

    /**
     * 要素数で分割
     */
    function testChunk()
    {
        $src = [
            0 => 'a', 1 => 'b', 2 => 'c', 3 => 'd', 4 => 'e',
            5 => 'f', 6 => 'g', 7 => 'h', 8 => 'i', 9 => 'j',
        ];
        $ex = [
            [0 => 'a', 1 => 'b', 2 => 'c'],
            [3 => 'd', 4 => 'e', 5 => 'f'],
            [6 => 'g', 7 => 'h', 8 => 'i'],
            [9 => 'j'],
        ];

        $collection = new Collection($src);
        $this->assertSame($ex, $collection->chunk(3)->toArray());
        $this->assertSame($src, (new Collection($ex))->collapse()->toArray());
    }

    /**
     * テストの合否で2つに分割
     */
    function testPartition()
    {
        $a = [0 => 'a', 1 => 'B', 2 => 'c', 3 => 'D', 4 => 'e'];
        $exSmall = [0 => 'a', 2 => 'c', 4 => 'e'];
        $exLarge = [1 => 'B', 3 => 'D'];

        list($small, $large) = (new Collection($a))->partition(function ($item) {
            return $item === strtolower($item);
        });
        $this->assertSame($exSmall, $small->toArray());
        $this->assertSame($exLarge, $large->toArray());
    }
    // endregion

    /**
     * 差分
     */
    function testDiffAssoc()
    {
        $a = new Collection([
            'aaa' => 'notSame',
            'bbb' => 'Same',
            'ccc' => 'Only A',
        ]);
        $b = new Collection([
            'aaa' => 'xxx',
            'bbb' => 'Same',
            'ddd' => 'Only B',
        ]);

        // 「Bにだけある」は抽出されない
        $ex = ['aaa' => 'notSame', 'ccc' => 'Only A'];
        $this->assertSame($ex, $a->diffAssoc($b)->all());
    }

    // region カラム抽出系

    /**
     * 特定のカラム群のみを抽出
     */
    function testOnly()
    {
        $src = ['key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3'];
        $collection = new Collection($src);
        $ex = ['key1' => 'val1', 'key3' => 'val3'];

        // カラム指定
        $this->assertSame($ex, $collection->only(['key1', 'key3'])->all());
        // カラム除外
        $this->assertSame($ex, $collection->except(['key2'])->all());
        $this->assertSame($src, $collection->all(), '非破壊的');
    }

    /**
     * 特定のカラムのみを抽出 (table)
     */
    function testPluck()
    {
        $rows = [
            ['id' => '10', 'first' => 'Arare', 'last' => 'Norimaki', 'gender' => 'f'],
            ['id' => '11', 'first' => 'Taro', 'last' => 'Soramame', 'gender' => 'm'],
            ['id' => '12', 'first' => 'Akane', 'last' => 'Kimidori', 'gender' => 'f'],
        ];
        $colelction = new Collection($rows);

        $this->assertSame(['f', 'm', 'f'], $colelction->pluck('gender')->toArray());
        // index指定
        $this->assertSame(['10' => 'f', '11' => 'm', '12' => 'f'], $colelction->pluck('gender', 'id')->toArray());
        $this->assertSame($rows, $colelction->all(), '非破壊的');
    }

    // endregion

    // region 破壊的
    /**
     * 先頭に要素を追加
     *
     * 先頭: prepend() / shift()
     * 末尾: push() / pop()
     * キー指定: put() / pull()
     */
    function testPrepend()
    {
        $src = [1 => 'A', 2 => 'B', 3 => 'C'];

        $collection = new Collection($src);
        $this->assertSame([0 => 'x', 1 => 'A', 2 => 'B', 3 => 'C'], $collection->prepend('x')->toArray());
        $this->assertNotSame($src, $collection->all(), '破壊的');

        $collection = new Collection($src);
        $this->assertSame(['zero' => 'x', 1 => 'A', 2 => 'B', 3 => 'C'], $collection->prepend('x', 'zero')->toArray());
        $this->assertNotSame($src, $collection->all(), '破壊的');
    }

    /**
     * コレクション要素にcallbackを適用する。破壊的。
     * @see testMap()
     */
    function testTransoform()
    {
        $src = [1, 2, 3];
        $collection = new Collection($src);

        $result = $collection->transform(function ($item, $key) {
            return $item * 2;
        });
        $this->assertSame([2, 4, 6], $result->all());
        $this->assertSame($result->all(), $collection->all(), '破壊的');
    }

    function testSplice()
    {
        $src = [0 => 'a', 1 => 'B', 2 => 'c', 3 => 'D', 4 => 'e'];

        // 開始位置
        $collection = new Collection($src);
        $chunk = $collection->splice(2);
        $this->assertSame(['c', 'D', 'e'], $chunk->all());
        $this->assertSame(['a', 'B'], $collection->all());
        $this->assertNotSame($src, $collection->all(), '破壊的');

        // 開始位置と長さ
        $collection = new Collection($src);
        $chunk = $collection->splice(2, 1);
        $this->assertSame(['c'], $chunk->all());
        $this->assertSame(['a', 'B', 'D', 'e'], $collection->all());
        $this->assertNotSame($src, $collection->all(), '破壊的');

        // 置換
        $collection = new Collection($src);
        $chunk = $collection->splice(2, 1, ['XXX', 'YYY']);
        $this->assertSame(['c'], $chunk->all());
        $this->assertSame(['a', 'B', 'XXX', 'YYY', 'D', 'e'], $collection->all());
        $this->assertNotSame($src, $collection->all(), '破壊的');
    }

    // endregion


    // region 抽出系

    /**
     * フィルタして取り除く
     */
    function testFilter()
    {
        $src = [0 => 'a', 1 => 'B', 2 => 'c', 3 => 'D', 4 => 'e'];
        $collection = new Collection($src);
        $ex = [0 => 'a', 2 => 'c', 4 => 'e'];

        $this->assertSame($ex, $collection->filter(function ($val, $key) {
            return $val === strtolower($val);
        })->toArray(), 'whitelist');
        $this->assertSame($ex, $collection->reject(function ($val, $key) {
            return $val !== strtolower($val);
        })->toArray(), 'blacklist');
        $this->assertSame($src, $collection->all(), '非破壊的');
    }

    /**
     * 検索して先頭1件を返す
     */
    function testSearch()
    {
        $collection = new Collection([0 => 'a', 1 => 'B', 2 => 'c', 3 => 'D', 4 => 'e']);

        $this->assertSame(1, $collection->search('B', true));
        $this->assertSame(1, $collection->search(function ($item, $key) {
            return $item === 'B';
        }));
    }

    /**
     * key/valueペアで検索
     */
    function testWhere()
    {
        $src = [
            ['first' => 'Arare', 'last' => 'Norimaki'],
            ['first' => 'Taro', 'last' => 'Soramame'],
            ['first' => 'Akane', 'last' => 'Kimidori'],
            ['first' => 'Senbe', 'last' => 'Norimaki'],
        ];
        $collection = new Collection($src);

        $ex = [
            ['first' => 'Arare', 'last' => 'Norimaki'],
            ['first' => 'Senbe', 'last' => 'Norimaki'],
        ];
        $filterd = $collection->whereStrict('last', 'Norimaki');
        $this->assertSame($ex, $filterd->values()->all());
        $this->assertSame($src, $collection->all(), '非破壊的');
    }

    /**
     * key/valueペアで検索 (IN)
     */
    function testWhereIn()
    {
        $src = [
            ['first' => 'Arare', 'last' => 'Norimaki'],
            ['first' => 'Taro', 'last' => 'Soramame'],
            ['first' => 'Akane', 'last' => 'Kimidori'],
            ['first' => 'Senbe', 'last' => 'Norimaki'],
        ];
        $collection = new Collection($src);

        $ex = [
            ['first' => 'Arare', 'last' => 'Norimaki'],
            ['first' => 'Akane', 'last' => 'Kimidori'],
            ['first' => 'Senbe', 'last' => 'Norimaki'],
        ];
        $filterd = $collection->whereInStrict('last', ['Norimaki', 'Kimidori']);
        $this->assertSame($ex, $filterd->values()->all());
        $filterd = $collection->whereNotInStrict('last', ['Soramame', 'Tsun']);
        $this->assertSame($ex, $filterd->values()->all());
        $this->assertSame($src, $collection->all(), '非破壊的');
    }

    // endregion

    /**
     * 内部順を逆にする
     */
    function testReverse()
    {
        $collection = new Collection([0 => 'a', 1 => 'B', 2 => 'c', 3 => 'D', 4 => 'e']);
        $ex = [4 => 'e', 3 => 'D', 2 => 'c', 1 => 'B', 0 => 'a'];

        $this->assertSame($ex, $collection->reverse()->toArray());
    }

    /**
     * 再index
     */
    function testValues()
    {
        $a = [4 => 'e', 3 => 'D', 2 => 'c', 1 => 'B', 'X' => 'a'];
        $ex = [0 => 'e', 1 => 'D', 2 => 'c', 3 => 'B', 4 => 'a'];
        $this->assertSame($ex, (new Collection($a))->values()->all());
    }

    /**
     * ソート
     */
    function testSortBy()
    {
        $src = [
            ['id' => '10', 'first' => 'Arare', 'last' => 'Norimaki', 'gender' => 'f'],
            ['id' => '11', 'first' => 'Taro', 'last' => 'Soramame', 'gender' => 'm'],
            ['id' => '12', 'first' => 'Akane', 'last' => 'Kimidori', 'gender' => 'f'],
        ];
        $collection = new Collection($src);

        $ex = [
            ['id' => '12', 'first' => 'Akane', 'last' => 'Kimidori', 'gender' => 'f'],
            ['id' => '10', 'first' => 'Arare', 'last' => 'Norimaki', 'gender' => 'f'],
            ['id' => '11', 'first' => 'Taro', 'last' => 'Soramame', 'gender' => 'm'],
        ];
        $this->assertSame($ex, $collection->sortBy('first')->values()->all());
        $this->assertSame($ex, $collection->sortByDesc('first')->reverse()->values()->all());
        $this->assertSame($src, $collection->all(), '非破壊的');
    }


    function testSum()
    {
        // 単純な値
        $this->assertSame(6, (new Collection([1, 2, 3]))->sum());

        // key指定
        $a = [
            ['id' => '10', 'first' => 'Arare', 'last' => 'Norimaki', 'gender' => 'f'],
            ['id' => '11', 'first' => 'Taro', 'last' => 'Soramame', 'gender' => 'm'],
            ['id' => '12', 'first' => 'Akane', 'last' => 'Kimidori', 'gender' => 'f'],
        ];
        $collection = new Collection($a);
        $this->assertSame(33, $collection->sum('id'));

        // callback指定
        $this->assertSame(14, $collection->sum(function ($row) {
            return mb_strlen($row['first']);
        }));
    }

    // region callback適用

    /**
     * コレクション要素にcallbackを適用する。
     */
    function testMap()
    {
        $src = [1, 2, 3];
        $collection = new Collection($src);

        $result = $collection->map(function ($item, $key) {
            return $item * 2;
        });
        $this->assertSame([2, 4, 6], $result->all());
        $this->assertSame($src, $collection->all(), '非破壊的');
    }

    /**
     * 繰り返しながら単一値にする
     */
    function testReduce()
    {
        $src = [1, 2, 3];
        $collection = new Collection($src);
        $total = $collection->reduce(function ($carry, $item) {
            return $carry + $item;
        }, 0);
        $this->assertSame(6, $total);
        $this->assertSame($src, $collection->all(), '非破壊的');
    }

    /**
     * その時点のコレクション内容を利用するときに使う
     */
    function testTap()
    {
        $src = [2, 4, 3, 1, 5];
        $collection = new Collection($src);

        $collection
            ->sort()
            ->tap(function (Collection $collection) {
                // Log::debug('Values after sorting', $collection->values()->toArray()
            })
            ->shift();
        $this->assertSame($src, $collection->all(), '非破壊的');
    }

    /**
     * 指定回数callbackを実行して、新しいコレクションを作る
     */
    function testTimes()
    {
        $ex = [9, 18, 27, 36, 45, 54, 63, 72, 81, 90];

        $collection = Collection::times(10, function ($number) {
            return $number * 9;
        });

        $this->assertSame($ex, $collection->all());
    }

    /**
     * 全要素が条件を満たしているか
     */
    function testEvery()
    {
        $a = [1, 2, 3, 4, 5];
        $collection = new Collection($a);

        $this->assertTrue($collection->every(function ($val, $key) {
            return $val <= 5;
        }));
        $this->assertFalse($collection->every(function ($val, $key) {
            return $val <= 4;
        }));
    }

    // endregion

    function testUnion()
    {
        $src = [1 => ['a'], 2 => ['b']];
        $collection = new Collection($src);

        // keyが存在する場合は上書きしない
        $ex = [1 => ['a'], 2 => ['b'], 3 => ['c']];
        $union = $collection->union([3 => ['c'], 1 => ['OVERRIDED']]);
        $this->assertSame($ex, $union->all());
        $this->assertSame($src, $collection->all(), '非破壊的');
    }

    function testUnique()
    {
        // 単純
        $src = [0 => 'a', 1 => 'a', 2 => 'b', 3 => 'b', 4 => 'b', 5 => 'c'];
        $collection = new Collection($src);
        $this->assertSame(['a', 'b', 'c'], $collection->uniqueStrict()->values()->all());
        $this->assertSame($src, $collection->all(), '非破壊的');
    }

    function testUnique_table()
    {
        $src = [
            ['first' => 'Arare', 'last' => 'Norimaki'],
            ['first' => 'Taro', 'last' => 'Soramame'],
            ['first' => 'Akane', 'last' => 'Kimidori'],
            ['first' => 'Senbe', 'last' => 'Norimaki'],
        ];
        $ex = [
            ['first' => 'Arare', 'last' => 'Norimaki'],
            ['first' => 'Taro', 'last' => 'Soramame'],
            ['first' => 'Akane', 'last' => 'Kimidori'],
        ];
        $collection = new Collection($src);

        // カラム指定
        $this->assertSame($ex, $collection->uniqueStrict('last')->values()->all());

        // callback指定
        $this->assertSame($ex, $collection->uniqueStrict(function ($row) {
            return $row['last'];
        })->values()->all());
    }

    /**
     * 最初の引数が、
     * - true になるまで: unless()
     * - false になるまで: when()
     * callbackを実行する
     */
    function testWhen()
    {
        $this->markTestIncomplete('TODO 実装');
    }

    function testWrap()
    {
        $this->assertSame(['John Doe'], Collection::wrap('John Doe')->all());
        $this->assertSame(['John Doe'], Collection::wrap(['John Doe'])->all());
        $this->assertSame(['John Doe'], Collection::wrap(new Collection(['John Doe']))->all());

        $this->assertSame(['John Doe'], Collection::unwrap(new Collection(['John Doe'])));
        $this->assertSame(['John Doe'], Collection::unwrap(['John Doe']));
        $this->assertSame('John Doe', Collection::unwrap('John Doe'));
    }

    // region 生成系

    /**
     * 直積
     */
    function testCrossJoin()
    {
        $collection = new Collection(['カレー', 'ラーメン']);
        $ex = [
            ['カレー', 'スプーン'],
            ['カレー', '箸'],
            ['カレー', 'フォーク'],
            ['ラーメン', 'スプーン'],
            ['ラーメン', '箸'],
            ['ラーメン', 'フォーク'],
        ];
        $matrix = $collection->crossJoin(['スプーン', '箸', 'フォーク']);
        $this->assertSame($ex, $matrix->toArray());
    }

    /**
     * TODO 例が悪かった。考え直す。
     */
    function testZip()
    {
        $src = [
            0 => ['first' => 'Arare', 'last' => 'Norimaki'],
            2 => ['first' => 'Taro', 'last' => 'Soramame'],
            3 => ['first' => 'Akane', 'last' => 'Kimidori'],
        ];
        $collection = new Collection($src);
        $genderRows = [
            0 => ['gender' => 'f'],
            2 => ['gender' => 'm'],
            3 => ['gender' => 'f'],
        ];
        $ex = [
            [
                ['first' => 'Arare', 'last' => 'Norimaki'],
                ['gender' => 'f'],
            ],
            [
                ['first' => 'Taro', 'last' => 'Soramame'],
                ['gender' => 'm'],
            ],
            [
                ['first' => 'Akane', 'last' => 'Kimidori'],
                ['gender' => 'f'],
            ],
        ];

        $zipped = $collection->zip($genderRows);
        $this->assertSame($ex, $zipped->toArray());
        $this->assertSame($src, $collection->all(), '非破壊的');
    }

    // endregion

    // region Higher Order Message
    function testHigherOrderMessage()
    {
        $src = [
            ['id' => '11', 'first' => 'Arare', 'last' => 'Norimaki'],
            ['id' => '12', 'first' => 'Taro', 'last' => 'Soramame'],
            ['id' => '13', 'first' => 'Akane', 'last' => 'Kimidori'],
        ];
        $collection = new Collection($src);

        $this->assertSame(36, $collection->sum->id);

        //$collection->each->methodName();
    }
    // endregion
}
