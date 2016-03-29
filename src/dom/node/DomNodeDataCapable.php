<?php
/**
 * This file contains the DomNodeDataCapable trait.
 *
 * PHP Version 5.3
 *
 * @author  Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license https://github.com/soloproyectos-php/dom-node/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/dom-node
 */
namespace soloproyectos\dom\node;
use \StdClass;
use \UnexpectedValueException;
use soloproyectos\dom\node\DomNode;

/**
 * DomNodeDataCapable trait.
 *
 * @package Dom\Node
 * @author  Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license https://github.com/soloproyectos-php/dom-node/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/dom-node
 */
trait DomNodeDataCapable
{
    /**
     * Data namespace.
     * @var string
     */
    private static $_namespace = "__phpQueryData__";

    /**
     * List of elements.
     *
     * @return array of DOMElement
     */
    abstract public function elements();

    /**
     * Gets or sets arbitrary data.
     *
     * @param string $name  Data name
     * @param mixed  $value Mixed value (not required)
     *
     * @return DomNode|mixed
     */
    public function data($name, $value = null)
    {
        if (func_num_args() > 1) {
            return $this->_setData($name, $value);
        }

        return $this->_getData($name);
    }

    /**
     * Gets data from the node.
     *
     * @param string $name Data name
     *
     * @return mixed
     */
    private function _getData($name)
    {
        foreach ($this->elements() as $element) {
            $data = $this->_getDataObject($element);

            return property_exists($data, $name)? $data->$name: null;
        }

        return null;
    }

    /**
     * Sets data into the node.
     *
     * @param string $name  Data name
     * @param mixed  $value Arbitrary data
     *
     * @return DomNode
     */
    private function _setData($name, $value)
    {
        foreach ($this->elements() as $element) {
            $data = $this->_getDataObject($element);
            $data->$name = $value;
            $this->_setDataObject($element, $data);
        }

        return $this;
    }

    /**
     * Gets the data object.
     *
     * @param DOMElement $element DOM element
     *
     * @return object
     */
    private function _getDataObject($element)
    {
        $attr = $element->getAttribute(self::$_namespace);
        $data = strlen($attr) > 0? json_decode($attr): new StdClass();

        if (!is_object($data)) {
            throw new UnexpectedValueException("The node does not contain valid data");
        }

        return $data;
    }

    /**
     * Sets te data object.
     *
     * @param DOMElement $element DOM element
     * @param object     $data    Data
     *
     * @return void
     */
    private function _setDataObject($element, $data)
    {
        $element->setAttribute(self::$_namespace, json_encode($data));
    }
}
