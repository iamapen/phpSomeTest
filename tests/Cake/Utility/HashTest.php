<?php
use Cake\Utility\Hash;

class PositiveIntegerTest extends \PHPUnit\Framework\TestCase {

    function getRecords() {
        return [
            ['id'=>'01', 'lastName'=>'norimaki', 'firstName'=>'arare', 'gender'=>'female', 'age'=>'18'],
            ['id'=>'02', 'lastName'=>'soramame', 'firstName'=>'taro', 'gender'=>'male', 'age'=>'20'],
            ['id'=>'03', 'lastName'=>'kimidori', 'firstName'=>'akane', 'gender'=>'female', 'age'=>'18'],
            ['id'=>'04', 'lastName'=>'norimaki', 'firstName'=>'senbe', 'gender'=>'male', 'age'=>'32'],
            ['id'=>'05', 'lastName'=>'yamabuki', 'firstName'=>'midori', 'gender'=>'female', 'age'=>'27'],
        ];
    }

    /**
     * 抽出/射影。よく使う。
     */
    function testExtract() {
        $arr = $this->getRecords();

        $ex = ['arare', 'taro', 'akane', 'senbe', 'midori'];
        $this->assertSame($ex, Hash::extract($arr, '{n}.firstName'));

        // 文字列一致
        $ex = ['arare', 'akane', 'midori'];
        $this->assertSame($ex, Hash::extract($arr, '{n}[gender=female].firstName'));

        // 文字列不一致
        $ex = ['taro', 'senbe'];
        $this->assertSame($ex, Hash::extract($arr, '{n}[gender!=female].firstName'));

        // 算術
        $ex = ['senbe', 'midori'];
        $this->assertSame($ex, Hash::extract($arr, '{n}[age>20].firstName'));
        // 算術
        $ex = ['taro', 'senbe', 'midori'];
        $this->assertSame($ex, Hash::extract($arr, '{n}[age>=20].firstName'));

        // regexp
        $ex = ['norimaki', 'kimidori', 'norimaki', 'yamabuki'];
        $this->assertSame($ex, Hash::extract($arr, '{n}[lastName=/ki/].lastName'));
    }

    function test_expression() {
        $arr = [
            1 => 'one', 2 => 'two',
            'Three' => 'three', 'Four' => 'four'
        ];

        $this->assertSame(array_values($arr), Hash::extract($arr, '{*}'));
        $this->assertSame(['one', 'two'], Hash::extract($arr, '{n}'));
        $this->assertSame(['three', 'four'], Hash::extract($arr, '{s}'));
        $this->assertSame(['three'], Hash::extract($arr, 'Three'));
    }

    /**
     * ソート。よく使う。
     */
    function testSort() {
        $arr = $this->getRecords();
        $result = Hash::sort($arr, '{n}.id', 'desc');
        $this->assertSame(['05', '04', '03', '02', '01'], Hash::extract($result, '{n}.id'));

        // type
        $arr = ['1', 'a', '2', '10'];
        $this->assertSame(
            ['1', '2', '10', 'a'],
            Hash::sort($arr, '{n}', 'asc')
        );
        $this->assertSame(
            ['a', '1', '2', '10'],
            Hash::sort($arr, '{n}', 'asc', 'numeric')
        );
        $this->assertSame(
            ['1', '10', '2', 'a'],
            Hash::sort($arr, '{n}', 'asc', 'string')
        );
        $this->assertSame(
            ['1', '2', '10', 'a'],
            Hash::sort($arr, '{n}', 'asc', 'natural')
        );
    }

    /**
     * マージ。よく使う。
     */
    function testMerge() {
        $array = [
            [
                'id' => '48c2570e-dfa8-4c32-a35e-0d71cbdd56cb',
                'name' => 'mysql raleigh-workshop-08 < 2008-09-05.sql ',
                'description' => 'Importing an sql dump'
            ],
            [
                'id' => '48c257a8-cf7c-4af2-ac2f-114ecbdd56cb',
                'name' => 'pbpaste | grep -i Unpaid | pbcopy',
                'description' => 'Remove all lines that say "Unpaid".',
            ]
        ];
        $arrayB = 4;
        $arrayC = [0 => "test array", "cats" => "dogs", "people" => 1267];
        $arrayD = ["cats" => "felines", "dog" => "angry"];
        $ex = [
            [
                'id' => '48c2570e-dfa8-4c32-a35e-0d71cbdd56cb',
                'name' => 'mysql raleigh-workshop-08 < 2008-09-05.sql ',
                'description' => 'Importing an sql dump'
            ],
            [
                'id' => '48c257a8-cf7c-4af2-ac2f-114ecbdd56cb',
                'name' => 'pbpaste | grep -i Unpaid | pbcopy',
                'description' => 'Remove all lines that say "Unpaid".',
            ],
            4,
            'test array',
            'cats' => 'felines',
            "people" => 1267,
            'dog' => 'angry',
        ];

        $this->assertSame($ex, Hash::merge($array, $arrayB, $arrayC, $arrayD));
    }

    function testCombine() {
        $arr = $this->getRecords();
        $ex = ['01'=>'arare', '02'=>'taro', '03'=>'akane', '04'=>'senbe', '05'=>'midori'];
        $this->assertSame($ex, Hash::combine($arr, '{n}.id', '{n}.firstName'));
    }

    /**
     * グルーピングや正規化に使える
     */
    function testCombine2() {
        $arr = $this->getRecords();

        // grouping
        $ex = [
            'female'=>[
                '01' => ['id' => '01', 'lastName' => 'norimaki', 'firstName' => 'arare', 'gender' => 'female', 'age' => '18'],
                '03' => ['id' => '03', 'lastName' => 'kimidori', 'firstName' => 'akane', 'gender' => 'female', 'age' => '18'],
                '05' => ['id' => '05', 'lastName' => 'yamabuki', 'firstName' => 'midori', 'gender' => 'female', 'age' => '27'],
            ],
            'male'=>[
                '02' => ['id' => '02', 'lastName' => 'soramame', 'firstName' => 'taro', 'gender' => 'male', 'age' => '20'],
                '04' => ['id' => '04', 'lastName' => 'norimaki', 'firstName' => 'senbe', 'gender' => 'male', 'age' => '32'],
            ],
        ];
        $this->assertSame($ex, Hash::combine($arr, '{n}.id', '{n}', '{n}.gender'));


        // valueを特定カラムだけ
        $ex = [
            'female'=>[
                '01'=>'arare', '03'=>'akane', '05'=>'midori',
            ],
            'male'=>[
                '02'=>'taro', '04'=>'senbe',
            ],
        ];
        $this->assertSame($ex, Hash::combine($arr, '{n}.id', '{n}.firstName', '{n}.gender'));


        // keyPath を配列にすると書式を指定できる
        $ex = [
            'female'=>[
                'arare_norimaki'=>'arare', 'akane_kimidori'=>'akane', 'midori_yamabuki'=>'midori',
            ],
            'male'=>[
                'taro_soramame'=>'taro', 'senbe_norimaki'=>'senbe',
            ],
        ];
        $this->assertSame(
            $ex,
            Hash::combine(
                $arr,
                ['%s_%s', '{n}.firstName', '{n}.lastName'],
                '{n}.firstName',
                '{n}.gender'
            )
        );
    }

    function testInsert() {
        $arr = $this->getRecords();
        $a = [
            'foo' => ['FOO'=>'FOOOO'],
            'bar' => ['BAR'=>'BARRR'],
        ];
        $result = Hash::insert($arr, '{n}.newKey', $a);
        $this->assertSame(count($arr), count($result));
        for($i=0; $i<count($result); $i++) {
            $this->assertSame($a, $result[$i]['newKey']);
        }

        // matcherも指定可能
        $result = Hash::insert($arr, '{n}[gender=male].newKey', $a);
        $this->assertNotSame([], Hash::extract($result, '{n}[gender=male].newKey'));
        $this->assertSame([], Hash::extract($result, '{n}[gender=female].newKey'));
    }

    function testRemove() {
        $arr = $this->getRecords();
        $result = Hash::remove($arr, '{n}.lastName');
        $this->assertSame([], Hash::extract($result, '{n}.lastName'));

        // matcherも指定可能
        $result = Hash::remove($arr, '{n}[gender=male].lastName');
        $this->assertSame([], Hash::extract($result, '{n}[gender=male].lastName'));
        $this->assertNotSame([], Hash::extract($result, '{n}[gender=female].lastName'));
    }

    function testFormat() {
        $arr = $this->getRecords();
        $ex = [
            'arare norimaki (female)', 'taro soramame (male)', 'akane kimidori (female)',
            'senbe norimaki (male)', 'midori yamabuki (female)',
        ];
        $this->assertSame(
            $ex,
            Hash::format($arr, ['{n}.firstName', '{n}.lastName', '{n}.gender'], '%s %s (%s)')
        );
    }

    /**
     * 含まれているか
     */
    function testContains() {
        $a = [
            0 => ['name' => 'main'],
            1 => ['name' => 'about']
        ];
        $b = [
            0 => ['name' => 'main'],
            1 => ['name' => 'about'],
            2 => ['name' => 'contact'],
            'a' => 'b'
        ];

        $this->assertTrue(Hash::contains($a, $a));
        $this->assertFalse(Hash::contains($a, $b));
        $this->assertTrue(Hash::contains($b, $a));
    }

    /**
     * 微妙に緩い比較なので注意。
     * null と 空配列 が false となる。
     */
    function testCheck() {
        $a = [
            'string' => 'abc',
            'emptyString' => '',
            'zero' => 0,
            'zeroString' => '0',
            'true' => true,
            'false' => false,
            'null' => null,
            'array' => ['a','b','c'],
            'emptyArray' => [],
        ];
        $this->assertTrue(Hash::check($a, 'string'));
        $this->assertTrue(Hash::check($a, 'emptyString'));
        $this->assertFalse(Hash::check($a, 'null'));
        $this->assertTrue(Hash::check($a, 'zero'));
        $this->assertTrue(Hash::check($a, 'zeroString'));
        $this->assertTrue(Hash::check($a, 'true'));
        $this->assertTrue(Hash::check($a, 'false'));
        $this->assertTrue(Hash::check($a, 'array'));
        $this->assertTrue(Hash::check($a, 'array.1'));
        $this->assertFalse(Hash::check($a, 'array.3'));
        $this->assertFalse(Hash::check($a, 'emptyArray'));
        $this->assertFalse(Hash::check($a, 'undef'));
    }

    function testFilter() {
        $arr = [
            0=>0, 1=>1, 2=>'0', 3=>'1', 4=>true, 5=>false,
            6=>'', 7=>'a', 8=>[], 9=>null
        ];
        $ex = [
            0=>0, 1=>1, 2=>'0', 3=>'1', 4=>true,
            7=>'a',
        ];
        $this->assertSame($ex, Hash::filter($arr));


        $result = Hash::filter($arr, function($v){
            // falseを返せば取り除かれる
            if($v !== 'a') {
                return false;
            }
            return true;
        });
        $this->assertSame([7=>'a'], $result);
    }

    /**
     * これはほぼCake専用かな
     */
    function testFlatten() {
        $arr = [
            [
                'Post' => ['id' => '1', 'title' => 'First Post'],
                'Author' => ['id' => '1', 'user' => 'Kyle'],
            ],
            [
                'Post' => ['id' => '2', 'title' => 'Second Post'],
                'Author' => ['id' => '3', 'user' => 'Crystal'],
            ],
        ];
        $ex = [
            '0.Post.id' => '1',
            '0.Post.title' => 'First Post',
            '0.Author.id' => '1',
            '0.Author.user' => 'Kyle',
            '1.Post.id' => '2',
            '1.Post.title' => 'Second Post',
            '1.Author.id' => '3',
            '1.Author.user' => 'Crystal',
        ];
        $this->assertSame($ex, Hash::flatten($arr));
    }

    function testExpand() {
        $arr = [
            '0.Post.id' => '1',
            '0.Post.title' => 'First Post',
            '0.Author.id' => '1',
            '0.Author.user' => 'Kyle',
            '1.Post.id' => '2',
            '1.Post.title' => 'Second Post',
            '1.Author.id' => '3',
            '1.Author.user' => 'Crystal',
        ];
        $ex = [
            [
                'Post' => ['id' => '1', 'title' => 'First Post'],
                'Author' => ['id' => '1', 'user' => 'Kyle'],
            ],
            [
                'Post' => ['id' => '2', 'title' => 'Second Post'],
                'Author' => ['id' => '3', 'user' => 'Crystal'],
            ],
        ];
        $this->assertSame($ex, Hash::expand($arr));
    }

    /**
     * ドット区切りでアクセス。
     * 設定ファイルやコンテナに使える。
     */
    function testGet() {
        $arr = $this->getRecords();
        $this->assertSame($arr[2], Hash::get($arr, 2));

        $this->assertSame('akane', Hash::get($arr, '2.firstName'));
    }

    function testNumeric() {
        $this->assertTrue(Hash::numeric([0, 1, '0', '1', '+1', '-1', '012']));
        $this->assertFalse(Hash::numeric(['0x0A']));
    }

    function testDimensions() {
        $data = ['one', '2', 'three'];
        $this->assertSame(1, Hash::dimensions($data));
        $this->assertSame(1, Hash::maxDimensions($data));

        $data = ['1' => '1.1', '2', '3'];
        $this->assertSame(1, Hash::dimensions($data));
        $this->assertSame(1, Hash::maxDimensions($data));

        $data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => '3.1.1']];
        $this->assertSame(2, Hash::dimensions($data));
        $this->assertSame(2, Hash::maxDimensions($data));

        $data = ['1' => '1.1', '2', '3' => ['3.1' => '3.1.1']];
        $this->assertSame(1, Hash::dimensions($data));
        $this->assertSame(2, Hash::maxDimensions($data));

        $data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
        $this->assertSame(2, Hash::dimensions($data));
        $this->assertSame(3, Hash::maxDimensions($data));
    }

    /**
     * matcherでフィルタした上で、array_map() にかけられる
     */
    function testMap() {
        $arr = $this->getRecords();
        // 男性に要素追加
        $result = Hash::map($arr, '{n}[gender=male]', function($item){
            $item['mark'] = 'X';
            return $item;
        });
        $this->assertSame(['X', 'X'], Hash::extract($result, '{n}.mark'));
    }

    /**
     * Hash::map() とあまり変わらない？
     */
    function testReduce() {
        $arr = $this->getRecords();
        // 男性に要素追加
        $result = Hash::reduce($arr, '{n}[gender=male]', function($carry, $item){
            $item['mark'] = 'X';
            $carry[] = $item;
            return $carry;
        });
        $this->assertSame(['X', 'X'], Hash::extract($result, '{n}.mark'));
    }

    /**
     *
     */
    function testApply() {
        $arr = $this->getRecords();
        $result = Hash::apply($arr, '{n}.age', function($arr){
            $sum = 0;
            $numOfItems = count($arr);
            foreach($arr as $v) {
                $sum += $v;
            }
            return (int) ceil($sum / $numOfItems);
        });
        $this->assertSame(23, $result);
    }

    function testDiff() {}

    /**
     * いつ使うのだろう、フォームと相性がいいのか？
     * Cake専用かもしれない
     */
    function testNormalize() {
        $a = ['Tree', 'CounterCache',
            'Upload' => [
                'folder' => 'products',
                'fields' => ['image_1_id', 'image_2_id']
            ]
        ];
        $ex = [
            'Tree' => null,
            'CounterCache' => null,
            'Upload' => [
                'folder' => 'products',
                'fields' => [
                    '0' => 'image_1_id',
                    '1' => 'image_2_id',
                ]
            ]
        ];
        $this->assertSame($ex, Hash::normalize($a));


        $b = [
            'Cacheable' => ['enabled' => false],
            'Limit',
            'Bindable',
            'Validator',
            'Transactional'
        ];
        $ex = [
            'Cacheable' => [
                'enabled' => false,
            ],
            'Limit' => null,
            'Bindable' => null,
            'Validator' => null,
            'Transactional' => null,
        ];
        $this->assertSame($ex, Hash::normalize($b));
    }

    /**
     * Cake専用かもしれない
     */
    function testNest() {
        $data = [
            ['ThreadPost' => ['id' => 1, 'parent_id' => null]],
            ['ThreadPost' => ['id' => 2, 'parent_id' => 1]],
            ['ThreadPost' => ['id' => 3, 'parent_id' => 1]],
            ['ThreadPost' => ['id' => 4, 'parent_id' => 1]],
            ['ThreadPost' => ['id' => 5, 'parent_id' => 1]],
            ['ThreadPost' => ['id' => 6, 'parent_id' => null]],
            ['ThreadPost' => ['id' => 7, 'parent_id' => 6]],
            ['ThreadPost' => ['id' => 8, 'parent_id' => 6]],
            ['ThreadPost' => ['id' => 9, 'parent_id' => 6]],
            ['ThreadPost' => ['id' => 10, 'parent_id' => 6]]
        ];
        $ex = [
            0 => [
                'ThreadPost' => [
                    'id' => 6,
                    'parent_id' => null
                ],
                'children' => [
                    0 => [
                        'ThreadPost' => [
                            'id' => 7,
                            'parent_id' => 6
                        ],
                        'children' => []
                    ],
                    1 => [
                        'ThreadPost' => [
                            'id' => 8,
                            'parent_id' => 6
                        ],
                        'children' => []
                    ],
                    2 => [
                        'ThreadPost' => [
                            'id' => 9,
                            'parent_id' => 6
                        ],
                        'children' => []
                    ],
                    3 => [
                        'ThreadPost' => [
                            'id' => 10,
                            'parent_id' => 6
                        ],
                        'children' => []
                    ]
                ]
            ]
        ];
        $this->assertSame($ex, Hash::nest($data, ['root' => 6]));
    }
}