<?php
namespace Elastica\Test\Aggregation;

use Elastica\Aggregation\Sum;
use Elastica\Document;
use Elastica\Query;
use Elastica\Script\Script;

class ScriptTest extends BaseAggregationTest
{
    protected function _getIndexForTest()
    {
        $index = $this->_createIndex();

        $index->getType('test')->addDocuments([
            new Document('1', ['price' => 5]),
            new Document('2', ['price' => 8]),
            new Document('3', ['price' => 1]),
            new Document('4', ['price' => 3]),
        ]);

        $index->refresh();

        return $index;
    }

    /**
     * @group functional
     */
    public function testAggregationScript()
    {
        $this->_checkScriptInlineSetting();
        $agg = new Sum('sum');
        // x = (0..1) is groovy-specific syntax, to see if lang is recognized
        $script = new Script("x = (0..1); return doc['price'].value", null, Script::LANG_GROOVY);
        $agg->setScript($script);

        $query = new Query();
        $query->addAggregation($agg);
        $results = $this->_getIndexForTest()->search($query)->getAggregation('sum');

        $this->assertEquals(5 + 8 + 1 + 3, $results['value']);
    }

    /**
     * @group functional
     */
    public function testAggregationScriptAsString()
    {
        $this->_checkScriptInlineSetting();
        $agg = new Sum('sum');
        $agg->setScript(new Script("doc['price'].value", null, Script::LANG_GROOVY));

        $query = new Query();
        $query->addAggregation($agg);
        $results = $this->_getIndexForTest()->search($query)->getAggregation('sum');

        $this->assertEquals(5 + 8 + 1 + 3, $results['value']);
    }

    /**
     * @group unit
     */
    public function testSetScript()
    {
        $aggregation = 'sum';
        $string = "doc['price'].value";
        $params = [
            'param1' => 'one',
            'param2' => 1,
        ];
        $lang = 'groovy';

        $agg = new Sum($aggregation);
        $script = new Script($string, $params, $lang);
        $agg->setScript($script);

        $array = $agg->toArray();

        $expected = [
            $aggregation => [
                'script' => [
                    'inline' => $string,
                    'params' => $params,
                    'lang' => $lang,
                ],
            ],
        ];
        $this->assertEquals($expected, $array);
    }
}
