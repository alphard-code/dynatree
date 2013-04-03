<?php
namespace yiiExtensions\dynatree\nestedSets;

use CAction, Yii, CActiveRecord, CHttpException, Exception;

class AjaxManipulateAction extends CAction
{
    const INSERT_INTO   = 'over';
    const INSERT_AFTER  = 'after';
    const INSERT_BEFORE = 'before';

    /**
     * @var CActiveRecord
     */
    public $modelClass;

    /**
     * @var CActiveRecord|\NestedSetBehavior
     */
    protected $targetNode;

    /**
     * @var CActiveRecord|\NestedSetBehavior
     */
    protected $sourceNode;

    /**
     * @var string
     */
    protected $mode;

    public function run()
    {
        if (!isset($_POST['targetNodeID'], $_POST['sourceNodeID'], $_POST['hitMode'])) {
            throw new CHttpException(500, 'Incorrect request.');
        }
        $this->loadNodes($_POST['targetNodeID'], $_POST['sourceNodeID']);
        $this->mode = $this->checkMode($_POST['hitMode']);
        try {
            $this->performChanges();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    protected function loadNodes($targetNodeID, $sourceNodeID)
    {
        $this->targetNode = $this->modelClass->findByPk((int)$targetNodeID);
        $this->sourceNode = $this->modelClass->findByPk((int)$sourceNodeID);
        if (!$this->targetNode instanceof $this->modelClass || !$this->sourceNode instanceof $this->modelClass) {
            throw new CHttpException(404, 'The requested node does not exist.');
        }
    }

    protected function checkMode($mode)
    {
        $allowed = array(self::INSERT_AFTER, self::INSERT_BEFORE, self::INSERT_INTO);
        if (!in_array($mode, $allowed)) {
            throw new CHttpException(500, 'Incorrect mode.');
        }

        return $mode;
    }

    protected function performChanges()
    {
        switch ($this->mode) {
            case self::INSERT_INTO:
                $this->sourceNode->moveAsLast($this->targetNode);
                break;
            case self::INSERT_BEFORE:
                if ($this->targetNode->isRoot()) {
                    $this->sourceNode->moveAsRoot();
                } else {
                    $this->sourceNode->moveBefore($this->targetNode);
                }
                break;
            case self::INSERT_AFTER:
                if ($this->targetNode->isRoot()) {
                    $this->sourceNode->moveAsRoot();
                } else {
                    $this->sourceNode->moveAfter($this->targetNode);
                }
                break;
        }
    }
}