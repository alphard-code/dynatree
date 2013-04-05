<?php
namespace yiiExtensions\dynatree\nestedSets;

use CAction, Yii, CActiveRecord, CHttpException, CActiveDataProvider;

class AjaxDataSourceAction extends CAction
{
    /**
     * @var \CActiveRecord
     */
    public $modelClass;

    protected $initiallySelectedID;
    protected $initiallySelected;

    protected $outputNodeList = array();

    public function run($key = null)
    {
        $initiallySelected         = Yii::app()->getRequest()->getQuery('initiallySelected');
        // arrays are not supported
        // @todo fix
        $this->initiallySelectedID = is_array($initiallySelected) ? null : $initiallySelected;
        $nodeListFinderModel       = $this->modelClass;

        if ($key == null) {
            if ($this->initiallySelectedID == null) {
                $nodeListFinderModel = $this->modelClass->roots()->allSorted();
            } else {
                $this->initiallySelected = $this->modelClass->findByPk((int)$this->initiallySelectedID);
                if ($this->initiallySelected instanceof $this->modelClass) {
                    if (!$this->initiallySelected->isRoot()) {
                        $nodeListFinderModel = $this->initiallySelected->rootsWithDiveToCurrentNode();
                    } else {
                        $nodeListFinderModel = $this->modelClass->roots()->allSorted();
                    }
                }
            }
        } else {
            $objRoot = $this->modelClass->findByPk((int)$key);
            if ($objRoot instanceof $this->modelClass) {
                $nodeListFinderModel = $objRoot->children()->allSorted();
            } else {
                throw new CHttpException(404);
            }
        }

        $converter                      = new NodeConverter(new CActiveDataProvider($nodeListFinderModel));
        $converter->isSliceOfTree       = ($key != null);
        $converter->initiallySelectedID = $this->initiallySelectedID;
        $this->outputNodeList           = $converter->getOrderedNodes();

        if (!headers_sent()) {
            header('Content-Type: application/json;');
        }
        echo \CJSON::encode($this->outputNodeList);
    }


}