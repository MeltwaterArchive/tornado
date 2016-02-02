<?php

namespace Test\Tornado\Project\Worksheet;

use Tornado\Project\Worksheet\FilterCsdlGenerator;

/**
 * FilterCsdlGenerator Test
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers \Tornado\Project\Worksheet\FilterCsdlGenerator
 */
class FilterCsdlGeneratorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * DataProvider for testGetFilterFromTargetProvider
     *
     * @return array
     */
    public function getFilterFromTargetProvider()
    {
        return [
            [ // #0
                'target' => 'fb.author.age',
                'expected' => 'age'
            ],
            [ // #1
                'target' => 'fb.all.content',
                'expected' => 'keywords'
            ],
            [
                'target' => 'fb.content',
                'expected' => false
            ]
        ];
    }

    /**
     * @dataProvider getFilterFromTargetProvider
     *
     * @covers \Tornado\Project\Worksheet\FilterCsdlGenerator::getFilterFromTarget
     *
     * @param type $target
     * @param type $expected
     */
    public function testGetFilterFromTarget($target, $expected)
    {
        $generator = new FilterCsdlGenerator();
        $this->assertEquals($expected, $generator->getFilterFromTarget($target));
    }

    public function testGenerateFromNull()
    {
        $generator = new FilterCsdlGenerator();
        $this->assertEquals('', $generator->generate(null));
    }

    /**
     * @dataProvider provideFilters
     */
    public function testGenerate(array $filters, $expectedCsdl)
    {
        $generator = new FilterCsdlGenerator();
        $this->assertEquals($expectedCsdl, $generator->generate($filters));
    }

    public function provideFilters()
    {
        return [
            [ // #0 - simple single filter
                'filters' => [
                    'gender' => ['male'],
                ],
                'expectedCsdl' => implode("\n", [
                    'fb.author.gender in "male"',
                ])
            ],
            [ // #1 - compound simple filter
                'filters' => [
                    'region' => ['Texas', 'Ohio', 'Wales'],
                    'gender' => ['female'],
                    'age' => ['18-24', '25-34']
                ],
                'expectedCsdl' => implode("\n", [
                    'fb.author.region in "Texas,Ohio,Wales"',
                    'AND',
                    'fb.author.gender in "female"',
                    'AND',
                    'fb.author.age in "18-24,25-34"'
                ])
            ],
            [ // #2 - just csdl query
                'filters' => [
                    'csdl' => 'fb.content contains "canon"'
                ],
                'expectedCsdl' => implode("\n", [
                    'fb.content contains "canon"'
                ])
            ],
            [ // #3 - filters & simple csdl query
                'filters' => [
                    'region' => ['Texas', 'Ohio', 'Wales'],
                    'gender' => ['female'],
                    'age' => ['18-24', '25-34'],
                    'csdl' => 'fb.content contains "canon"'
                ],
                'expectedCsdl' => implode("\n", [
                    'fb.author.region in "Texas,Ohio,Wales"',
                    'AND',
                    'fb.author.gender in "female"',
                    'AND',
                    'fb.author.age in "18-24,25-34"',
                    'AND',
                    'fb.content contains "canon"'
                ])
            ],
            [ // #4 - filters & csdl query with comments
                'filters' => [
                    'region' => ['Texas', 'Ohio', 'Wales'],
                    'gender' => ['female'],
                    'age' => ['18-24', '25-34'],
                    'csdl' => implode("\n", [
                        '// filter for canon',
                        'fb.content contains "canon"'
                    ])
                ],
                'expectedCsdl' => implode("\n", [
                    'fb.author.region in "Texas,Ohio,Wales"',
                    'AND',
                    'fb.author.gender in "female"',
                    'AND',
                    'fb.author.age in "18-24,25-34"',
                    'AND',
                    'fb.content contains "canon"'
                ])
            ],
            [ // #5 - filters & complicated csdl query
                'filters' => [
                    'region' => ['Texas', 'Ohio', 'Wales'],
                    'gender' => ['female'],
                    'age' => ['18-24', '25-34'],
                    'csdl' => implode("\n", [
                        'fb.content contains "canon"',
                        '    // only interested in accessories',
                        '    AND (fb.content contains "lens" ',
                        '        OR fb.content contains "accessories")  '
                    ])
                ],
                'expectedCsdl' => implode("\n", [
                    'fb.author.region in "Texas,Ohio,Wales"',
                    'AND',
                    'fb.author.gender in "female"',
                    'AND',
                    'fb.author.age in "18-24,25-34"',
                    'AND',
                    'fb.content contains "canon"',
                    '',
                    'AND (fb.content contains "lens"',
                    'OR fb.content contains "accessories")'
                ])
            ],
            [ // #6 - empty
                'filters' => [],
                'expectedCsdl' => ''
            ],
            [ // #7 - filters & csdl query with just a comment
                'filters' => [
                    'region' => ['Texas', 'Ohio', 'Wales'],
                    'gender' => ['female'],
                    'age' => ['18-24', '25-34'],
                    'csdl' => implode("\n", [
                        '// add something here...'
                    ])
                ],
                'expectedCsdl' => implode("\n", [
                    'fb.author.region in "Texas,Ohio,Wales"',
                    'AND',
                    'fb.author.gender in "female"',
                    'AND',
                    'fb.author.age in "18-24,25-34"'
                ])
            ],
            [ // #8 - filters & csdl query with just a multiline comment
                'filters' => [
                    'region' => ['Texas', 'Ohio', 'Wales'],
                    'gender' => ['female'],
                    'age' => ['18-24', '25-34'],
                    'csdl' => implode("\n", [
                        '// add something here',
                        '    // but maybe when we figure it out'
                    ])
                ],
                'expectedCsdl' => implode("\n", [
                    'fb.author.region in "Texas,Ohio,Wales"',
                    'AND',
                    'fb.author.gender in "female"',
                    'AND',
                    'fb.author.age in "18-24,25-34"'
                ])
            ],
            [ // #9 - filters & classifier csdl query
                'filters' => [
                    'region' => ['Texas', 'Ohio', 'Wales'],
                    'gender' => ['female'],
                    'age' => ['18-24', '25-34'],
                    'csdl' => implode("\n", [
                        '// the tagging logic',
                        'tag "iPhone" { bitly.user.agent substr "iPhone"',
                        '               OR interaction.source contains "iPhone" }',
                        'tag "Blackberry" { bitly.user.agent substr "Blackberry"',
                        '                   OR interaction.source contains "Blackberry" }',
                        'tag "iOS" { bitly.user.agent substr "iPhone" ',
                        '            OR bitly.user.agent substr "iPad"',
                        '            OR interaction.source contains_any "iPhone,iPad" }',
                        '',
                        '// the filtering logic',
                        'return {}'
                    ])
                ],
                'expectedCsdl' => implode("\n", [
                    'tag "iPhone" { bitly.user.agent substr "iPhone"',
                    'OR interaction.source contains "iPhone" }',
                    'tag "Blackberry" { bitly.user.agent substr "Blackberry"',
                    'OR interaction.source contains "Blackberry" }',
                    'tag "iOS" { bitly.user.agent substr "iPhone"',
                    'OR bitly.user.agent substr "iPad"',
                    'OR interaction.source contains_any "iPhone,iPad" }',
                    '',
                    '',
                    'return {',
                    'fb.author.region in "Texas,Ohio,Wales"',
                    'AND',
                    'fb.author.gender in "female"',
                    'AND',
                    'fb.author.age in "18-24,25-34"',
                    '}'
                ])
            ],
            [ // #10 - filters & classifier csdl query with some filters
                'filters' => [
                    'region' => ['Texas', 'Ohio', 'Wales'],
                    'gender' => ['female'],
                    'age' => ['18-24', '25-34'],
                    'csdl' => implode("\n", [
                        '// the tagging logic',
                        'tag "iPhone" { bitly.user.agent substr "iPhone"',
                        '               OR interaction.source contains "iPhone" }',
                        'tag "Blackberry" { bitly.user.agent substr "Blackberry"',
                        '                   OR interaction.source contains "Blackberry" }',
                        'tag "iOS" { bitly.user.agent substr "iPhone" ',
                        '            OR bitly.user.agent substr "iPad"',
                        '            OR interaction.source contains_any "iPhone,iPad" }',
                        '',
                        '// the filtering logic',
                        'return {',
                        '    interaction.content contains "Venice" OR links.title contains "Venice"',
                        '}'
                    ])
                ],
                'expectedCsdl' => implode("\n", [
                    'tag "iPhone" { bitly.user.agent substr "iPhone"',
                    'OR interaction.source contains "iPhone" }',
                    'tag "Blackberry" { bitly.user.agent substr "Blackberry"',
                    'OR interaction.source contains "Blackberry" }',
                    'tag "iOS" { bitly.user.agent substr "iPhone"',
                    'OR bitly.user.agent substr "iPad"',
                    'OR interaction.source contains_any "iPhone,iPad" }',
                    '',
                    '',
                    'return {',
                    'interaction.content contains "Venice" OR links.title contains "Venice"',
                    'AND',
                    'fb.author.region in "Texas,Ohio,Wales"',
                    'AND',
                    'fb.author.gender in "female"',
                    'AND',
                    'fb.author.age in "18-24,25-34"',
                    '}'
                ])
            ],
            [ // #11 - filters & classifier csdl query without return statement
                'filters' => [
                    'region' => ['Texas', 'Ohio', 'Wales'],
                    'gender' => ['female'],
                    'age' => ['18-24', '25-34'],
                    'csdl' => implode("\n", [
                        '// the tagging logic',
                        'tag "iPhone" { bitly.user.agent substr "iPhone"',
                        '               OR interaction.source contains "iPhone" }',
                        'tag "Blackberry" { bitly.user.agent substr "Blackberry"',
                        '                   OR interaction.source contains "Blackberry" }',
                        'tag "iOS" { bitly.user.agent substr "iPhone" ',
                        '            OR bitly.user.agent substr "iPad"',
                        '            OR interaction.source contains_any "iPhone,iPad" }'
                    ])
                ],
                'expectedCsdl' => implode("\n", [
                    'tag "iPhone" { bitly.user.agent substr "iPhone"',
                    'OR interaction.source contains "iPhone" }',
                    'tag "Blackberry" { bitly.user.agent substr "Blackberry"',
                    'OR interaction.source contains "Blackberry" }',
                    'tag "iOS" { bitly.user.agent substr "iPhone"',
                    'OR bitly.user.agent substr "iPad"',
                    'OR interaction.source contains_any "iPhone,iPad" }',
                    'return {',
                    'fb.author.region in "Texas,Ohio,Wales"',
                    'AND',
                    'fb.author.gender in "female"',
                    'AND',
                    'fb.author.age in "18-24,25-34"',
                    '}'
                ])
            ],
            [ // #12 - filters & classifier csdl query that embeds a classifier
                'filters' => [
                    'region' => ['Texas', 'Ohio', 'Wales'],
                    'gender' => ['female'],
                    'age' => ['18-24', '25-34'],
                    'csdl' => implode("\n", [
                        'tags "0d516544550c3c9cee2099872a8c07dd"'
                    ])
                ],
                'expectedCsdl' => implode("\n", [
                    'tags "0d516544550c3c9cee2099872a8c07dd"',
                    'return {',
                    'fb.author.region in "Texas,Ohio,Wales"',
                    'AND',
                    'fb.author.gender in "female"',
                    'AND',
                    'fb.author.age in "18-24,25-34"',
                    '}'
                ])
            ],
            [ // #13 - country and region should be additive
                'filters' => [
                    'country' => ['Japan', 'China', 'Poland'],
                    'region' => ['Texas', 'Ohio', 'Wales'],
                    'gender' => ['female'],
                    'age' => ['18-24', '25-34']
                ],
                'expectedCsdl' => implode("\n", [
                    'fb.author.gender in "female"',
                    'AND',
                    'fb.author.age in "18-24,25-34"',
                    'AND',
                    '(',
                    'fb.author.country in "Japan,China,Poland"',
                    'OR',
                    'fb.author.region in "Texas,Ohio,Wales"',
                    ')'
                ])
            ],
        ];
    }
}
