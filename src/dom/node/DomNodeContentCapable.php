<?php
/**
 * This file contains the DomNodeContentCapable trait.
 *
 * PHP Version 5.3
 *
 * @author  Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license https://github.com/soloproyectos-php/dom-node/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/dom-node
 */
namespace soloproyectos\dom\node;
use \DomainException;
use soloproyectos\dom\Dom;
use soloproyectos\dom\node\DomNode;
use soloproyectos\dom\node\exception\DomNodeException;
use soloproyectos\text\Text;

/**
 * DomNodeContentCapable trait.
 *
 * @package Dom\Node
 * @author  Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license https://github.com/soloproyectos-php/dom-node/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/dom-node
 */
trait DomNodeContentCapable
{
    /**
     * List of elements.
     *
     * @return array of DOMElement
     */
    abstract public function elements();

    /**
     * Gets or sets inner HTML code.
     *
     * @param string $value Inner HTML code (not required)
     *
     * @return DomNode|string
     */
    public function html($value = null)
    {
        if (func_num_args() > 0) {
            return $this->_setInnerHtml($this->_decodeHtmlEntities($value));
        }

        return $this->_getInnerHtml();
    }

    /**
     * Gets or sets inner text.
     *
     * @param string $value Inner text
     *
     * @return DomNode|string
     */
    public function text($value = null)
    {
        if (func_num_args() > 0) {
            return $this->_setInnerText($value);
        }

        return $this->_getInnerText();
    }

    /**
     * Appends contents to the node.
     *
     * @param string $html Inner contents.
     *
     * @return DomNode
     */
    public function append($html)
    {
        $html = $this->_decodeHtmlEntities("$html");
        if (!Text::isEmpty($html)) {
            foreach ($this->elements() as $element) {
                $doc = $element->ownerDocument;
                $fragment = $doc->createDocumentFragment();
                @$fragment->appendXML($html);
                $node = @$element->appendChild($fragment);

                if ($node === false) {
                    throw new DomNodeException("Invalid XML fragment");
                }
            }
        }

        return $this;
    }

    /**
     * Prepends contents to the node.
     *
     * @param string $html Inner contents.
     *
     * @return DomNode
     */
    public function prepend($html)
    {
        $html = $this->_decodeHtmlEntities("$html");
        if (!Text::isEmpty($html)) {
            foreach ($this->elements() as $element) {
                $doc = $element->ownerDocument;
                $fragment = $doc->createDocumentFragment();
                @$fragment->appendXML($html);
                $node = @$element->insertBefore($fragment, $element->firstChild);

                if ($node === false) {
                    throw new DomNodeException("Invalid XML fragment");
                }
            }
        }

        return $this;
    }

    /**
     * Removes all child nodes.
     *
     * @return DomNode
     */
    public function clear()
    {
        foreach ($this->elements() as $element) {
            while ($element->hasChildNodes()) {
                $element->removeChild($element->firstChild);
            }
        }

        return $this;
    }
    
    /**
     * Replaces the current node.
     * 
     * @param string $value HTML fragment
     * 
     * @return DomNode
     */
    public function replace($value)
    {
        foreach ($this->elements() as $element) {
            $parent = $element->parentNode;
            if ($parent !== null) {
                $doc = $element->ownerDocument;
                $fragment = $doc->createDocumentFragment();
                if (@$fragment->appendXML($value) === false) {
                    throw new DomNodeException("Invalid XML fragment");
                }
                $parent->replaceChild($fragment, $element);
            }
        }
        
        return $this;
    }

    /**
     * Decodes HTML entities.
     *
     * This function decodes all HTML entities, but not XML entities. XML entities are:
     * &quot; &amp; &apos; &lt; &gt;
     *
     * @param string $text Text
     *
     * @return string
     */
    private function _decodeHtmlEntities($text)
    {
        return preg_replace_callback(
            '/\&(nbsp)\;/',
            function ($matches) {
                $ret = $matches[0];
                $xmlEntities = array("quot", "amp", "apos", "lt", "gt");
                $entity = $matches[1];

                if (!array_search($entity, $xmlEntities)) {
                    $ret = html_entity_decode("&$entity;");
                }

                return $ret;
            },
            $text
        );
    }

    /**
     * Gets inner text.
     *
     * @return string
     */
    private function _getInnerText()
    {
        foreach ($this->elements() as $element) {
            return $element->nodeValue;
        }

        return "";
    }

    /**
     * Sets inner text.
     *
     * @param string $value Inner text
     *
     * @return DomNode
     */
    private function _setInnerText($value)
    {
        $this->clear();

        if (!Text::isEmpty($value)) {
            foreach ($this->elements() as $element) {
                $doc = $element->ownerDocument;
                $element->appendChild($doc->createTextNode($value));
            }
        }

        return $this;
    }

    /**
     * Gets inner HTML code.
     *
     * @return string
     */
    private function _getInnerHtml()
    {
        $ret = "";

        foreach ($this->elements() as $element) {
            $childNodes = $element->childNodes;

            $str = "";
            foreach ($childNodes as $node) {
                $str .= Dom::dom2str($node);
            }

            $ret = Text::concat("\n", $ret, $str);
        }

        return $ret;
    }

    /**
     * Sets inner HTML code.
     *
     * @param string $value Inner HTML code
     *
     * @return DomNode
     */
    private function _setInnerHtml($value)
    {
        $this->clear();

        if (!Text::isEmpty($value)) {
            foreach ($this->elements() as $element) {
                $doc = $element->ownerDocument;
                $fragment = $doc->createDocumentFragment();
                @$fragment->appendXML($value);
                $node = @$element->appendChild($fragment);

                if ($node === false) {
                    throw new DomNodeException("Invalid XML fragment");
                }
            }
        }

        return $this;
    }
}
