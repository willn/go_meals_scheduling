    /**
     * @dataProvider provideGetFoo
     */
    public function testGetFoo($input, $expected) {
        $result = foobar();
        $this->assertEquals($expected, $result);
    }

    public function provideGetFoo() {
        return [
            [1, 0],
        ];
    }
