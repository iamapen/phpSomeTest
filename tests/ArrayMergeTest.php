<?php
use Cake\Utility\Hash;

/**
 * 配列マージのテスト
 */
class ArrayMergeTest extends \PHPUnit\Framework\TestCase {

    /**
     * 数値keyと文字列keyで挙動が異なるのでややこしい
     *
     * * $a に足りないものだけを足すなら +演算子、
     * * 数値keyは追加、文字列keyは上書きで、
     *   * 数値keyを振りなおしていいなら array_merge()
     *   * 数値keyを振りなおさないなら Cake\Utility\Hash::merge()
     * * 数値keyは追加、文字列keyは配列なら array_merge_recursive()
     */
    function testTest() {
        $a = [0=>'1-0', 1=>'1-1', 'a'=>'1-a', 3=>'1-3'];
        $b = [1=>'2-1', 2=>'2-2',  3=>'2-3', 'a'=>'2-a', 'b'=>'2-b'];

        // +演算子 の場合は $a の後に $b が追加され、重複時は先勝ち
        $this->assertSame(
            [0=>'1-0', 1=>'1-1', 'a'=>'1-a', 3=>'1-3', 2=>'2-2', 'b'=>'2-b'],
            $a+$b,
            '+'
        );

        // array_merge() の場合は $a の 後に $b が追加され、
        // 数値keyは振りなおされつつ全てが追加され、文字列keyは重複時上書きされる
        $this->assertSame(
            [0=>'1-0', 1=>'1-1', 'a'=>'2-a', 2=>'1-3', 3=>'2-1', 4=>'2-2', 5=>'2-3', 'b'=>'2-b'],
            array_merge($a, $b),
            'array_merge()'
        );

        // array_merge_recursive() の場合は $a の 後に $b が追加され、
        // 数値keyは振りなおされつつ全てが追加され、文字列keyは重複時配列になる
        $this->assertSame(
            [0=>'1-0', 1=>'1-1', 'a'=>['1-a', '2-a'], 2=>'1-3', 3=>'2-1', 4=>'2-2', 5=>'2-3', 'b'=>'2-b'],
            array_merge_recursive($a, $b),
            'array_merge_recursive()'
        );

        $a = [0=>'1-0', 1=>'1-1', 'a'=>'1-a', 3=>'1-3'];
        $b = [1=>'2-1', 2=>'2-2',  3=>'2-3', 'a'=>'2-a', 'b'=>'2-b'];
        // Cake\Utility\Hash::merge() の場合は、 $a の後に $b が追加され、
        // 数値keyは重複時新しく振られて追加され、文字列keyは重複時上書きされる
        $this->assertSame(
            [0=>'1-0', 1=>'1-1', 'a'=>'2-a', 3=>'1-3', 4=>'2-1', 2=>'2-2', 5=>'2-3', 'b'=>'2-b'],
            Hash::merge($a, $b),
            'Cake\Utility\Hash::merge()'
        );
    }
}
