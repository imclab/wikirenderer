<?php
/**
 * classic wikirenderer syntax to xhtml
 *
 * @package WikiRenderer
 * @subpackage rules
 * @author Laurent Jouanneau
 * @copyright 2003-2006 Laurent Jouanneau
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
namespace WikiRenderer\Markup\WRHtml;

/**
 * ???
 * @package	WikiRenderer
 * @subpackage	WR3Html
 */
class P extends \WikiRenderer\Block
{
    public $type = 'p';
    protected $_openTag = '<p>';
    protected $_closeTag = '</p>';

    public function detect($string, $inBlock = false)
    {
//echo "~~~~~para\n";
        if ($string == '')
            return false;

        if (preg_match('/^={4,} *$/',$string))
            return false;
        $c = $string[0];

        if (strpos("*#-!| \t>;" ,$c) === false) {
//echo "   found\n";
            $this->_detectMatch = array($string, $string);
            return true;
        }
        else {
            return false;
        }
    }
}

