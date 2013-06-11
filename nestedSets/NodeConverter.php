<?php
namespace yiiExtensions\dynatree\nestedSets;

use CComponent, CJSON, CActiveDataProvider, CActiveRecord;

class NodeConverter extends CComponent
{
    public $isSliceOfTree = false;

    public $initiallySelectedID;

    /**
     * @var CActiveDataProvider
     */
    protected $dataProvider;

    /**
     * @var null|array
     */
    protected $_orderedNodes = null;

    public function __construct($dataProvider)
    {
        $this->setDataProvider($dataProvider);
    }

    public function setDataProvider(CActiveDataProvider $dataProvider)
    {
        $dataProvider->sort       = false;
        $dataProvider->pagination = false;
        $this->dataProvider       = $dataProvider;
    }


    public function getOrderedNodes()
    {
        if ($this->_orderedNodes === null) {
            $data = $this->dataProvider->getData();
            if (is_array($data) && !empty($data)) {
                if ($this->isSliceOfTree) {
                    $this->simplyOrderNodes($data);
                } else {
                    $this->orderNodes($data);
                }
                $this->_orderedNodes = $this->convertNodes($this->_orderedNodes);
            } else {
                $this->_orderedNodes = array();
            }
        }

        return $this->_orderedNodes;
    }

    public function simplyOrderNodes($nodes)
    {
        foreach ($nodes as $node) {
            $this->_orderedNodes[] = array('node' => $node, 'children' => array());
        }
    }

    public function orderNodes($nodes)
    {
        $currentLevel = 0;
        $previousNode = null;
        $stack        = array();
        $level        = null;

        foreach ($nodes as $k => $node) {
            $level     = ($level === null) ? $node->levelAttribute : $level;
            $nodeLevel = $node->$level;

            // root node
            if ($nodeLevel == 1) {
                $currentLevel = 1;
                array_push($stack, array('node' => $node, 'children' => array()));
            } else {
                if ($nodeLevel == $currentLevel) {

                } elseif ($nodeLevel > $currentLevel) {
                    $lastStackItem = array_pop($stack);
                    if ($lastStackItem !== null) {
                        array_pop($lastStackItem['children']);
                        array_push($stack, $lastStackItem);
                    }
                    if (isset ($previousNode) && $previousNode->level != 1) {
                        array_push($stack, array('node' => $previousNode, 'children' => array()));
                    }
                    $currentLevel = $nodeLevel;
                } elseif ($nodeLevel < $currentLevel) {

                    for ($i = 0; $i < $currentLevel - $nodeLevel; $i++) {
                        $tmp                                    = array_pop($stack);
                        $stack[count($stack) - 1]['children'][] = $tmp;
                    }

                    $currentLevel = $nodeLevel;
                }

                // If node is not root, we always push it to children of the last stack item.
                $lastStackItem = array_pop($stack);
                if ($lastStackItem != null) {
                    array_push($lastStackItem['children'], array('node' => $node, 'children' => array()));
                    array_push($stack, $lastStackItem);
                }
            }
            $previousNode = $node;

        }

        $this->_orderedNodes = $stack;
        unset($stack);
    }

    protected function convertNodes(array $nodes)
    {
        foreach ($nodes as $k => $node) {
            $nodes[$k] = $this->convertNode($node['node'], $node['children']);
        }

        return $nodes;
    }

    public function convertNode(CActiveRecord $node, array $children)
    {
        $arrOutput = array(
            'title' => $node->getAttribute('name'),
            'key'   => $node->getAttribute('id'),
        );
//        if ($this->initiallySelectedID == $node->getAttribute('id')) {
//            $arrOutput['activate'] = true;
//            $arrOutput['focus'] = false;
//            $arrOutput['select'] = true;
//        }
        if (!empty($children)) {
            $arrOutput['children'] = $this->convertNodes($children);
        } elseif ($node->getNumOfChildren() != 0) {
            $arrOutput['isLazy'] = true;
        }

        return $arrOutput;
    }
}