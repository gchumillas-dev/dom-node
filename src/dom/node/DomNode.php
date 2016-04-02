<?php
/**
 * This file contains the DomNode class.
 *
 * PHP Version 5.3
 *
 * @author  Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license https://github.com/soloproyectos-php/dom-node/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/dom-node
 */
namespace soloproyectos\dom\node;
use \DOMDocument;
use \DOMElement;
use \DOMXPath;
use soloproyectos\arr\Arr;
use soloproyectos\css\parser\CssParser;
use soloproyectos\dom\Dom;
use soloproyectos\dom\node\exception\DomNodeException;
use soloproyectos\dom\node\DomNodeIterable;
use soloproyectos\dom\node\DomNodeAttributeCapable;
use soloproyectos\dom\node\DomNodeClassCapable;
use soloproyectos\dom\node\DomNodeCssCapable;
use soloproyectos\dom\node\DomNodeContentCapable;
use soloproyectos\dom\node\DomNodeDataCapable;
use soloproyectos\text\Text;

/**
 * DomNode class.
 *
 * @package Dom\Node
 * @author  Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license https://github.com/soloproyectos-php/dom-node/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/dom-node
 */
class DomNode extends DomNodeIterable
{
    use DomNodeAttributeCapable;
    use DomNodeClassCapable;
    use DomNodeCssCapable;
    use DomNodeContentCapable;
    use DomNodeDataCapable;

    /**
     * Default chararacter set.
     * @var string
     */
    private $_defaultCharset = "ISO-8859-1";

    /**
     * Internal DOM document.
     * @var DOMDocument
     */
    protected $document;

    /**
     * Creates a node.
     *
     * Examples:
     * ```php
     * // creates a simple node with two attributes and inner text
     * $item = new DomNode("item", array("id" => 101, "title" => "Title 101"), "Inner text here...");
     * echo $item;
     *
     * // creates a complex node
     * // in this case we use a callback function to add complex structures into the node
     * $root = new DomNode("root", function ($target) {
     * // adds three subnodes
     *     for ($i = 0; $i < 3; $i++) {
     *         $target->append(
     *              new DomNode("item", array("id" => $i, "title" => "Title $i"), "This is the item $i")
     *         );
     *     }
     *
     *     // appends some XML code
     *     $target->append("<text>This text is added to the end.</text>");
     *
     *     // prepends some XML code
     *     $target->prepend("<text>This text is added to the beginning</text>");
     * });
     * echo $root;
     * ```
     *
     * @param string $nodeName   Node name (not required)
     * @param string $document   Document context (not required)
     * @param array  $attributes List of attributes (not required)
     * @param string $text       Inner text (not required)
     * @param string $callback   Callback function (not required)
     */
    public function __construct(
        $nodeName = null, $document = null, $attributes = null, $text = null, $callback = null
    ) {
        $args = Arr::fetch(
            func_get_args(),
            array(
                "nodeName" => "string",
                "document" => "\DOMDocument",
                "attributes" => "array",
                "text" => "scalar",
                "callback" => "function"
            )
        );

        // creates a document
        $this->document = $args["document"] === null
            ? new DOMDocument("1.0", $this->_defaultCharset)
            : $args["document"];
        $this->document->preserveWhiteSpace = false;
        $this->document->formatOutput = true;

        // creates a DOM element
        if ($args["nodeName"] !== null) {
            $elem = $this->document->createElement($args["nodeName"]);
            $this->document->appendChild($elem);
            array_push($this->elements, $elem);
        }

        // sets attributes
        if ($args["attributes"] !== null) {
            foreach ($args["attributes"] as $name => $value) {
                $this->attr($name, $value);
            }
        }

        // sets inner text
        if ($args["text"] !== null) {
            $this->text($args["text"]);
        }

        // calls callback function
        if ($args["callback"] !== null) {
            $args["callback"]($this);
        }
    }

    /**
     * Creates an instance from a given string.
     *
     * @param string $str         Well formed document
     * @param string $contentType Content Type (default is "text/xml")
     *
     * @return DomNode
     */
    public static function createFromString($str, $contentType = "text/xml")
    {
        $doc = new DOMDocument("1.0");
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        // use internal errors
        $useInternalErrors = libxml_use_internal_errors(true);

        if ($contentType == "text/html") {
            $doc->loadHTML($str);
        } else {
            $doc->loadXML($str);
        }

        // retrieves the errors
        $text = "";
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            $message = trim($error->message);
            $text = Text::concat(
                "\n", $text, "$message on line {$error->line}, column {$error->column}"
            );
        }
        libxml_clear_errors();

        // restores internal errors status
        libxml_use_internal_errors($useInternalErrors);

        if (!Text::isEmpty($text)) {
            throw new DomNodeException($text);
        }

        $node = new static();
        $node->document = $doc;
        $node->elements = array($doc->documentElement);

        return $node;
    }

    /**
     * Creates an instance from a given DOM element.
     *
     * @param DOMElement $element DOM element
     *
     * @return DomNode
     */
    public static function createFromElement($element)
    {
        $node = new static();
        $node->document = $element->ownerDocument;
        $node->elements = array($element);

        return $node;
    }

    /**
     * Creates an instance from a given DOM document.
     *
     * @param DOMDocument $document DOM document
     *
     * @return DomNode
     */
    public static function createFromDocument($document)
    {
        $node = new static();
        $node->document = $document;
        $node->elements = array($document->documentElement);

        return $node;
    }

    /**
     * Creates an instance from a given DomNode object.
     *
     * @param DomNode $node Node
     *
     * @return DomNode
     */
    public static function createFromNode($node)
    {
        $instance = new static();
        $instance->document = $node->document();
        $instance->elements = $node->elements();
        return $instance;
    }

    /**
     * Gets the DOM document.
     *
     * @return DOMDocument
     */
    public function document()
    {
        return $this->document;
    }

    /**
     * Gets the list of DOM elements.
     *
     * A DomNode node can represent zero, one or more elements. This function returns
     * the internal DOM elements.
     *
     * @return array of DOMElement
     */
    public function elements()
    {
        return $this->elements;
    }

    /**
     * Gets or sets the node name.
     *
     * @return DomNode|string
     */
    public function name($value = null)
    {
        if (func_num_args() > 0) {
            return $this->_setName($value);
        }
        
        return $this->_getName();
    }

    /**
     * Gets the parent of the node.
     *
     * This function returns a `null` value if the node has no parent.
     *
     * @return DomNode|null
     */
    public function parent()
    {
        foreach ($this->elements as $element) {
            // searches the first parent which is instance of DOMElement
            do {
                $element = $element->parentNode;
            } while ($element != null && !($element instanceof DOMElement));

            if ($element != null) {
                return DomNode::createFromElement($element);
            }

            break;
        }

        return null;
    }

    /**
     * Gets the root node.
     *
     * @return DomNode
     */
    public function root()
    {
        return DomNode::createFromElement($this->document->documentElement);
    }

    /**
     * Removes the node from the document.
     *
     * @return DomNode
     */
    public function remove()
    {
        foreach ($this->elements as $element) {
            $parent = $element->parentNode;

            if ($parent !== null) {
                $parent->removeChild($element);
            }
        }

        return $this;
    }

    /**
     * Finds nodes.
     *
     * @param string $cssSelectors List of css selector separated by commas
     *
     * @return DomNode
     */
    public function query($cssSelectors)
    {
        $elements = array();

        foreach ($this->elements as $element) {
            $parser = new CssParser($element);
            $items = $parser->query($cssSelectors);
            $elements = Dom::mergeNodes($elements, $items->getArrayCopy());
        }

        $node = new DomNode();
        $node->document = $this->document;
        $node->elements = $elements;
        return $node;
    }

    /**
     * Finds nodes.
     *
     * This function is identical to 'query' except it uses XPath expressions, instead of
     * CSS selectors.
     *
     * @param string $expression XPath expression
     *
     * @return DomNode
     */
    public function xpath($expression)
    {
        $elements = array();

        foreach ($this->elements as $element) {
            $xpath = new DOMXPath($element->ownerDocument);
            $items = $xpath->query($expression, $element);

            // converts DOMNodeList to array
            $nodes = array();
            foreach ($items as $item) {
                array_push($nodes, $item);
            }

            $elements = Dom::mergeNodes($elements, $nodes);
        }

        $node = new DomNode();
        $node->document = $this->document;
        $node->elements = $elements;
        return $node;
    }
    
    /**
     * Gets the line number of current node.
     * 
     * @return integer
     */
    public function lineNumber()
    {
        foreach ($this->elements as $element) {
            return $element->getLineNo();
        }

        return 0;
    }
    
    /**
     * Gets the node name.
     * 
     * @return string
     */
    private function _getName()
    {
        foreach ($this->elements as $element) {
            return $element->nodeName;
        }

        return "";
    }
    
    /**
     * Sets the node name.
     * 
     * @param string $value Node name
     * 
     * @return DomNode
     */
    private function _setName($value)
    {
        foreach ($this->elements as $i => $element) {
            $document = $element->ownerDocument;
            $parent = $element->parentNode;
            
            if ($parent !== null) {
                $newElement = $document->createElement($value);
                
                // clones attributes
                foreach ($element->attributes as $attr) {
                    $newElement->appendChild($attr->cloneNode());
                }
                
                // clones childrens
                foreach ($element->childNodes as $child) {
                    $newElement->appendChild($child->cloneNode(true));
                }
                
                $parent->replaceChild($newElement, $element);
                $this->elements[$i] = $newElement;
            }
        }
        
        return $this;
    }

    /**
     * Gets a string representation of the node.
     *
     * @return string
     */
    public function __toString()
    {
        $ret = "";

        foreach ($this->elements as $element) {
            $ret = Text::concat("\n", $ret, Dom::dom2str($element));
        }

        return $ret;
    }
}
