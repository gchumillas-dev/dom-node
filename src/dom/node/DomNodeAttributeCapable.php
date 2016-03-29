<?php
/**
 * This file contains the DomNodeAttributeCapable trait.
 *
 * PHP Version 5.3
 *
 * @author  Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license https://github.com/soloproyectos-php/dom-node/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/dom-node
 */
namespace soloproyectos\dom\node;
use soloproyectos\arr\Arr;
use soloproyectos\dom\node\DomNode;

/**
 * DomNodeAttributeCapable trait.
 *
 * @package Dom\Node
 * @author  Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license https://github.com/soloproyectos-php/dom-node/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/dom-node
 */
trait DomNodeAttributeCapable
{
    /**
     * List of elements.
     *
     * @return array of DOMElement
     */
    abstract public function elements();

    /**
     * Gets or sets an attribute.
     *
     * Example 1:
     * ```php
     * // sets an atribute value
     * $node->attr("class", "my-class");
     * // prints the attribute value
     * echo $node->attr("class");
     * ```
     *
     * Example 2:
     * ```php
     * // sets a list of CSS attribute values
     * $node->attr(array("class" => "my-class", "id" => "my-id"));
     * ```
     *
     * @param string|array $name  Attribute name or list of attributes
     * @param string       $value Attribute value (not required)
     *
     * @return DomNode|string
     */
    public function attr($name, $value = null)
    {
        $numArgs = func_num_args();
        $isAssoc = is_array($name) && Arr::isAssociative($name);

        if ($isAssoc && $numArgs < 2 || $numArgs > 1) {
            $attrs = $isAssoc? $name : array(trim($name) => $value);
            foreach ($attrs as $attr => $value) {
                $this->_setAttribute($attr, $value);
            }

            return $this;
        }

        return $this->_getAttribute($name);
    }

    /**
     * Has the node an attribute?
     *
     * @param string $name Attribute name
     *
     * @return boolean
     */
    public function hasAttr($name)
    {
        foreach ($this->elements() as $element) {
            return $element->hasAttribute($name);
        }

        return false;
    }
    
    /**
     * Removes an attribute.
     * 
     * @param string $name Attribute name
     * 
     * @return DomNode
     */
    public function removeAttr($name)
    {
        foreach ($this->elements() as $element) {
            $element->removeAttribute($name);
        }

        return $this;
    }

    /**
     * Gets an attribute.
     *
     * @param string $name Attribute name
     *
     * @return string
     */
    private function _getAttribute($name)
    {
        foreach ($this->elements() as $element) {
            return $element->getAttribute($name);
        }

        return "";
    }

    /**
     * Sets an attribute.
     *
     * @param string $name  Attribute name
     * @param string $value Attribute value
     *
     * @return DomNode
     */
    private function _setAttribute($name, $value)
    {
        foreach ($this->elements() as $element) {
            $element->setAttribute($name, $value);
        }

        return $this;
    }
}
