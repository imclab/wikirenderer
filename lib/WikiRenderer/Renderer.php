<?php
/**
 * Wikirenderer is a wiki text parser. It can transform a wiki text into xhtml or other formats
 * @package WikiRenderer
 * @author Laurent Jouanneau
 * @contributor  Amaury Bouchard
 * @copyright 2003-2013 Laurent Jouanneau
 * @link http://wikirenderer.jelix.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public 2.1
 * License as published by the Free Software Foundation.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
namespace WikiRenderer;

/**
 * Main class of WikiRenderenr. You should instantiate like this:
 *      $ctr = new \WikiRenderer\Renderer();
 *      $htmlText = $ctr->render($wikiText);
 * @package WikiRenderer
 * @subpackage  core
 */
class Renderer
{
    /** @var   string   Contains the final content. */
    protected $_newtext;
    /** @var \WikiRenderer\Block The currently opened block element. */
    protected $_currentBlock = null;
    /** @var \WikiRenderer\Block The previous opened block element. */
    protected $_previousBloc = null;
    /** @var array      List of all possible blocks. */
    protected $_blockList = array();

    /** @var WikiRendererBloc the default bloc used for unrecognized line */
    protected $_defaultBlock = null;

    /** @var \WikiRenderer\InlineParser   The parser for inline content. */
    public $inlineParser = null;
    /** List of lines which contain an error. */
    public $errors = array();
    /** @var \WikiRenderer\Config  Current configuration object. */
    protected $config = null;

    /**
     * Constructor. Prepare the engine.
     * @param \WikiRenderer\Config $config  A configuration object. If it is not present, it uses wr3_to_xhtml rules.
     */
    function __construct($config = null)
    {
        if (isset($config)) {
            if (is_subclass_of($config, '\WikiRenderer\Config'))
                $this->config = $config;
            else
                throw new \Exception('WikiRenderer: Bad configuration.');
        } else
            $this->config = new \WikiRenderer\Markup\WR3Html\Config();

        $this->inlineParser = new InlineParser($this->config);

        foreach ($this->config->blocktags as $name) {
            $this->_blockList[] = new $name($this);
        }
        if ($this->config->defaultBlock) {
            $name = $this->config->defaultBlock;
            $this->_defaultBlock = new $name($this);
        }
    }

    /**
     * Main method to call to convert a wiki text into an other format, according to the
     * rules given to the constructor.
     * @param   string  $text The wiki text to convert.
     * @return  string  The converted text.
     */
    public function render($text)
    {
        $text = $this->config->onStart($text);

        $lignes = preg_split("/\015\012|\015|\012/",$text); // we split the text at all line feeds

        $this->_newtext = array();
        $this->errors = array();
        $this->_currentBlock = null;
        $this->_previousBloc = null;

        // we loop over all lines
        foreach ($lignes as $num => $ligne) {
            if ($this->_currentBlock) {
                // a block is already open
                if ($this->_currentBlock->detect($ligne, true)) {
                    $s = $this->_currentBlock->getRenderedLine();
                    if ($s !== false)
                        $this->_newtext[] = $s;
                } else {
                    $this->_newtext[count($this->_newtext)-1] .= $this->_currentBlock->close();
                    $found = false;
                    foreach ($this->_blockList as $block) {
                        if ($block->detect($ligne, true)) {
                            $found = true;
                            // we open the new block
                            if ($block->closeNow()) {
                                // if we have to close now the block, we close.
                                $this->_newtext[] = $block->open() . $block->getRenderedLine() . $block->close();
                                $this->_previousBloc = $block;
                                $this->_currentBlock = null;
                            } else {
                                $this->_previousBloc = $this->_currentBlock;
                                $this->_currentBlock = clone $block; // careful ! it MUST be a copy here !
                                $this->_newtext[] = $this->_currentBlock->open() . $this->_currentBlock->getRenderedLine();
                            }
                            break;
                        }
                    }
                    if (!$found) {
                        if (trim($ligne) == '') {
                            $this->_newtext[] = '';
                        }
                        else if ($this->_defaultBlock) {
                            $this->_defaultBlock->detect($ligne);
                            $this->_newtext[] = $this->_defaultBlock->open().$this->_defaultBlock->getRenderedLine().$this->_defaultBlock->close();
                        }
                        else {
                            $this->_newtext[]= $this->inlineParser->parse($ligne);
                        }
                        $this->_previousBloc = $this->_currentBlock;
                        $this->_currentBlock = null;
                    }
                }
            } else {
                $found = false;
                // no opened block, we saw if the line correspond to a block
                foreach ($this->_blockList as $block) {
                    if ($block->detect($ligne)) {
                        $found = true;
                        if ($block->closeNow()) {
                            $this->_newtext[] = $block->open() . $block->getRenderedLine() . $block->close();
                            $this->_previousBloc = $block;
                        } else {
                            if ($block->mustClone()) {
                                $this->_currentBlock = clone $block; // careful ! it MUST be a copy here !
                            }
                            else {
                                $this->_currentBlock = $block;
                            }
                            $this->_newtext[] = $this->_currentBlock->open() . $this->_currentBlock->getRenderedLine();
                        }
                        break;
                    }
                }
                if (!$found) {
                    if (trim($ligne) == '') {
                        $this->_newtext[] = '';
                    }
                    else if ($this->_defaultBlock) {
                        $this->_defaultBlock->detect($ligne);
                        $this->_newtext[] = $this->_defaultBlock->open().$this->_defaultBlock->getRenderedLine().$this->_defaultBlock->close();
                    }
                    else {
                        $this->_newtext[]= $this->inlineParser->parse($ligne);
                    }
                }
            }
            if ($this->inlineParser->error) {
                $this->errors[$num + 1] = $ligne;
            }
        }
        if ($this->_currentBlock) {
            $this->_newtext[count($this->_newtext) - 1] .= $this->_currentBlock->close();
        }
        return $this->config->onParse(implode("\n",$this->_newtext));
    }

    /**
     * Returns the current configuration object.
     * @return  \WikiRenderer\Config    The current configuration object.
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function getPreviousBloc() {
        return $this->_previousBloc;
    }

}

