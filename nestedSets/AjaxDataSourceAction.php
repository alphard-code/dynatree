<?php
namespace yiiExtensions\dynatree\nestedSets;

use CAction, Yii, CActiveRecord, CHttpException, CActiveDataProvider;

class AjaxDataSourceAction extends CAction
{
    /**
     * @var \CActiveRecord|\NestedSetBehavior
     */
    public $modelClass;

    /**
     * @var array List of primary keys of nodes that should be initially selected (loaded, visible)
     */
    protected $initiallySelectedPks;

    /**
     * @var \CActiveRecord[]|\NestedSetBehavior[] List of models that should be initially selected
     */
    protected $initiallySelectedModels;

    protected $outputNodeList = array();

    public function run($key = null)
    {
        $initiallySelected = Yii::app()->getRequest()->getQuery('initiallySelected', null);

        $this->initiallySelectedPks = is_array($initiallySelected) ? $initiallySelected : null;

        // model instance given to dataprovider
        $nodeListFinderModel = $this->modelClass;

        $givenFinderCriteria = clone $nodeListFinderModel->getDbCriteria();

        // Find children nodes of concrete node.
        if ($key != null) {
            $parentNode = $this->modelClass->findByPk((int)$key);
            if ($parentNode instanceof $this->modelClass) {
                $nodeListFinderModel = $parentNode->children()->allSorted();
            } else {
                throw new CHttpException(404);
            }
        } // Get a list of root nodes. If specified, list will contain diving to some nodes, named "initially selected".
        else {
            // No initially selection - just find root nodes and sort them
            if ($this->initiallySelectedPks == null) {
                $nodeListFinderModel->roots()->allSorted();
            } // Need to dive into the tree to get chain of nodes, leading to initially selected ones.
            else {
                $nodeListFinderModel->rootsWithDiveTo($this->initiallySelectedPks);
            }
        }

        $converter                = new NodeConverter(new CActiveDataProvider($nodeListFinderModel, array('criteria' => $givenFinderCriteria)));
        $converter->isSliceOfTree = ($key != null);
        $this->outputNodeList     = $converter->getOrderedNodes();

        $this->getController()->disableProfiler();
        if (!headers_sent()) {
            header('Content-Type: application/json;');
        }
        echo \CJSON::encode($this->outputNodeList);
    }


}