<?php
/**
 * Tests unitaires
 *
 * @package wikirenderer
 * @subpackage tests
 * @author Laurent Jouanneau
 * @copyright 2006-2013 Laurent Jouanneau
 */

class WR3TestsBlocks extends PHPUnit_Framework_TestCase {

    var $listblocks = array(
        'b1'=>0,
        'b2'=>0,
        'wr3_list1'=>0,
        'wr3_pre'=>0,
        'wr3_footnote'=>0,
        'wr3_bug12894'=>0
    );

    function testBlock() {
        $wr = new \WikiRenderer\Renderer(new \WikiRenderer\Markup\WR3Html\Config());
        foreach($this->listblocks as $file=>$nberror){
            $sourceFile = 'datasblocks/'.$file.'.src';
            $resultFile = 'datasblocks/'.$file.'.res';

            $source = file_get_contents($sourceFile);

            $result = file_get_contents($resultFile);

            $res = $wr->render($source);

            if($file=='wr3_footnote'){
                $conf = & $wr->getConfig();
                $res=str_replace('-'.$conf->footnotesId.'-', '-XXX-',$res);
            }
            $this->assertEquals($result, $res, "error on $file");
            $this->assertEquals($nberror, count($wr->errors), "Errors detected by wr!");
        }
    }

    function testOther() {

        $wr = new \WikiRenderer\Renderer(new \WikiRenderer\Markup\WR3Html\Config());

        $source = '<code>foo</code>';
        $expected = '<pre>foo</pre>';

        $result = $wr->render($source);
        $this->assertEquals($expected, $result);
        $this->assertEquals(0, count($wr->errors),"Errors detected by wr !");

        $source = "<code>foo</code>
__bar__";
        $expected = "<pre>foo</pre>
<p><strong>bar</strong></p>";

        $result = $wr->render($source);
        $this->assertEquals($expected, $result);
        $this->assertEquals(0, count($wr->errors),"Errors detected by wr !");

        $source = '';
        $expected = '';
        $source = "__bar__
<code>foo</code>";
        $expected = "<p><strong>bar</strong></p>
<pre>foo</pre>";

        $result = $wr->render($source);
        $this->assertEquals($expected, $result);
        $this->assertEquals(0, count($wr->errors),"Errors detected by wr !");

    }
}
