<?php
/**
 * 「正の整数(ゼロを含む)」バリデーションに着目したテスト
 */
class PositiveIntegerTest extends \PHPUnit\Framework\TestCase {
    function provideParams() {
        return [
            0 => ['0', true],
            1 => ['1', true],
            2 => ['0.0', false],
            3 => ['.123', false],
            4 => ['123.', false],
            5 => ['+1', false, '正数符号'],
            6 => ['+1.0', false, '正数符号と少数部'],
            7 => ['-1', false, '負数符号'],
            8 => ['-1.0', false, '負数符号と小数部'],
            9 => ['1 ', false, '1と半角スペース'],
            10 => ['a', false],
            11 => ['１', false, '全角'],
            12 => ['042', false, '8進表記'],
            13 => ['08', false, '8進表記の8'],
            14 => ['1e2', false, '指数表記の100'],
            15 => ['1.001e2', false, '指数表記の100.1'],
            16 => ['1e+2', false, '指数表記の100'],
            17 => ['1e-2', false, '指数表記の0.01'],
            18 => ['0x0A', false, '16進表記の10'],
            19 => [strval(PHP_INT_MAX), true, '64bit PHP_INT_MAX'],
            20 => [strval(gmp_add(PHP_INT_MAX, 1)), true, '64bit PHP_INT_MAX +1'],
            21 => [strval(PHP_INT_MIN), false, '64bit PHP_INT_MIN'],
            22 => [strval(gmp_sub(PHP_INT_MIN, 1)), false, '64bit PHP_INT_MIN -1'],
            23 => ['INF', false, 'INF'],
            24 => ['NAN', false, 'NAN'],
        ];
    }

    /**
     * is_numeric() は基本使えない
     *
     * 少数も16進表記もPHP_INT_MAX超えもtrue
     * @dataProvider provideParams
     */
    function testIsNumeric($a, $ex) {
        $this->assertSame($ex, is_numeric($a), "'$a'");
    }

    /**
     * ctype_digit()
     *
     * 「数字かどうか」には使える。
     * 頭ゼロを許可してしまうのを良しとするかどうか。
     * @dataProvider provideParams
     */
    function testCtypeDigit($a, $ex) {
        $this->assertSame($ex, ctype_digit($a), "'$a'");
    }

    /**
     * filter_var(x, FILTER_VALIDATE_INT) returns int|false
     *
     * スペース含みが許可される
     * 正の整数に+符号が許可される
     * -1が許可される
     * INT範囲外がNG
     * @dataProvider provideParams
     */
    function testFilterVar($a, $ex) {
        $ex2 = ($ex === true) ? intval($a) : false;
        $this->assertSame($ex2, filter_var($a, FILTER_VALIDATE_INT), "'$a'");
    }

    /**
     * CakePHP3のバリデータ
     *
     * 負数が許可される
     * 頭ゼロが許可される
     * @dataProvider provideParams
     */
    function testCake3($a, $ex) {
        $this->assertSame($ex, \Cake\Validation\Validation::isInteger($a), "'$a'");
    }

    /**
     * 拙作
     * @dataProvider provideParams
     */
    function testIsGteZeroInteger($a, $ex) {
        $this->assertSame($ex, \Puyo\Util\Strings::isGteZeroInteger($a), "'$a'");
    }

    /**
     * Laravel5.5 integer
     *
     * +-符号やスペースが許可される
     * INT範囲外がNG
     * @dataProvider provideParams
     */
    function testLaravelInteger($a, $ex) {
        $loader = new \Illuminate\Translation\ArrayLoader();
        $translator = new \Illuminate\Translation\Translator($loader, 'ja');

        $inputs = ['a' => $a];
        $rules = ['a' => 'integer'];
        $validator = new \Illuminate\Validation\Validator($translator, $inputs, $rules);
        $this->assertSame($ex, !$validator->fails(), "'$a'");
    }
}
