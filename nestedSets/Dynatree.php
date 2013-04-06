<?php
namespace yiiExtensions\dynatree\nestedSets;

/**
 * A facade to the wrapper of Dynatree plugin, allowing manipulate nested sets easily.
 *
 * @author Alexander Bolshakov <a.bolshakov.coder@gmail.com>
 */
class Dynatree extends \CWidget
{
    const DATAPROVIDER = 'dataprovider';
    const AJAX         = 'ajax';

    /**
     * @var string Data source for initialization. Valid values are 'ajax' and 'dataprovider'.
     * Defaults to 'ajax', meaning root nodes will be fetched via AJAX after page loads.
     */
    public $initFrom = self::AJAX;

    /**
     * @var \CActiveDataProvider
     */
    public $dataProvider;

    /**
     * @var array Parameters of AJAX requests, that will be performed to fetch nodes from server, including
     *      initial request in case of {@link initFrom} is set as 'ajax'.
     *
     * Example:
     * array(
     *  // this is where your AJAX requests go to
     *  'url' => array('/controller/action', 'paramName'=>'paramValue', ...),
     * )
     * @see jQuery.ajax
     * @see CHtml::normalizeUrl when defining 'url' parameter
     */
    public $ajaxOptions = array();

    /**
     * @var bool Whether to allow loading of children nodes via AJAX if the parent node has positive "isLazy" parameter.
     * Defaults to true.
     */
    public $ajaxChildrenLoad = true;

    /**
     * @var array Options that will be passed to Javascript constructor of Dynatree plugin.
     * Some of them may be overriden in init section of this widget: initAjax, onLazyRead.
     * You can specify here parameters 'model' and 'attribute', they will be set as attributes of Dynatree wrapper widget.
     */
    public $options = array();

    /**
     * @var int|array|null An ID of node that should be initially selected (active). Defaults to null, meaning
     * no actions will be performed.
     */
    public $initiallySelected;

    /**
     * @var bool Whether to use value of an attribute of model, which can be specified at {@link options}
     * as an ID of initially selected node. Defaults to true.
     */
    public $autoSetInitiallySelectedNode = true;


    /**
     * @var bool Whether to enable manipulation over nodes.
     */
    public $enableManipulation = false;

    /**
     * @var array A route of AJAX manipulation action.
     * @see CHtml::normalizeUrl
     */
    public $manipulationAction;


    /**
     * @var array A route of action that will response via node-based view, that will be AJAX-loaded and
     *      appended to specified container. Defaults to null, meaning no loading will be performed.
     * @see CHtml::normalizeUrl
     */
    public $manipulationLoadNodeViewAction = null;

    /**
     * @var string|null jQuery selector, meaning container which will contain AJAX-loaded view of node.
     */
    public $manipulationNodeViewContainer;

    /**
     * @var array Names of AJAX-requests' parameters:
     * - loadingNodeID: an ID of node, which view is being loaded;
     * - savingTargetNodeID: an ID of target node while saving changes to server;
     * - savingSourceNodeID: an ID of source node while saving changes to server;
     * - savingHitMode: type of interaction between nodes while saving changes to server;
     */
    public $manipulationParamNames = array(
        'loadingNodeID'      => 'id',
        'savingTargetNodeID' => 'targetNodeID',
        'savingSourceNodeID' => 'sourceNodeID',
        'savingHitMode'      => 'hitMode',
    );

    protected $init = array();

    /**
     * Initializes the widget.
     * This method is called by {@link CBaseController::createWidget}
     * and {@link CBaseController::beginWidget} after the widget's
     * properties have been initialized.
     */
    public function init()
    {
        $this->ajaxOptions['url'] = \CHtml::normalizeUrl($this->ajaxOptions['url']);
        $this->handleFormInteraction();
        $this->resolveInitSource();
        if ($this->enableManipulation) {
            $this->initManipulation();
        }
        if ($this->ajaxChildrenLoad) {
            $this->initAjaxChildrenLoading();
        }
        $this->init['id'] = $this->getId();
    }

    protected function handleFormInteraction()
    {
        if (isset($this->options['form']['model'], $this->options['form']['attribute'])) {
            $this->init['model']     = $this->options['form']['model'];
            $this->init['attribute'] = $this->options['form']['attribute'];
            if ($this->autoSetInitiallySelectedNode) {
                $attributeValue = $this->init['model']->getAttribute($this->init['attribute']);
                if (!empty($attributeValue)) {
                    $this->initiallySelected = $attributeValue;
                }
            }
            unset($this->options['form']);
        }
    }

    protected function resolveInitSource()
    {
        if ($this->initFrom == self::AJAX) {
            $this->initFromAjax();
        } elseif ($this->initFrom == self::DATAPROVIDER) {
            $this->initFromDataProvider();
        } else {
            throw new \CException('Invalid value of "initAjax" parameter.');
        }
    }

    /**
     * @todo Realise interaction with dataprovider
     */
    protected function initFromDataProvider()
    {
    }

    protected function initFromAjax()
    {
        $this->options['initAjax'] = $this->ajaxOptions;
        if ($this->initiallySelected !== null) {
            $this->handleAjaxInitiallySelection();
        }
    }

    protected function handleAjaxInitiallySelection()
    {
        if (!is_array($this->initiallySelected)) {
            $this->initiallySelected = array($this->initiallySelected);
        }
        if (isset($this->options['initAjax']['data'])) {
            $this->options['initAjax']['data']['initiallySelected'] = $this->initiallySelected;
        } else {
            $this->options['initAjax']['data'] = array(
                'initiallySelected' => $this->initiallySelected
            );
        }
        $this->options['onPostInit'] = new \CJavaScriptExpression('
function(isReloading, isError)
{
    var initiallySelected = ' . \CJavaScript::encode($this->initiallySelected) . ';
    var tree = this.$tree;
    $.each(initiallySelected, function(key, value){
        var node =  tree.dynatree("getTree").getNodeByKey(value);
        if (node) {
            node.select();
            node.makeVisible();
        }
    });
}
');
    }

    protected function initAjaxChildrenLoading()
    {
        $lazyAjaxOptions   = $this->ajaxOptions;
        $dataKeyDefinition = new \CJavaScriptExpression('node.data.key');

        if (isset($lazyAjaxOptions['data'])) {
            $lazyAjaxOptions['data']['key'] = $dataKeyDefinition;
        } else {
            $lazyAjaxOptions['data'] = array('key' => $dataKeyDefinition);
        }
        $lazyAjaxOptions = \CJavaScript::encode($lazyAjaxOptions);

        $onLazyRead                  = <<<JS
function onLazyRead(node){
    node.appendAjax({$lazyAjaxOptions});
}
JS;
        $this->options['onLazyRead'] = new \CJavaScriptExpression($onLazyRead);
    }

    protected function initManipulation()
    {
        $this->manipulationAction             = \CHtml::normalizeUrl($this->manipulationAction);
        $this->manipulationLoadNodeViewAction = \CHtml::normalizeUrl($this->manipulationLoadNodeViewAction);

        if (!isset($this->options['dnd'])) {
            $this->options['dnd'] = array();
        }
        $this->options['onClick']            = new \CJavaScriptExpression('manipulateNestedSets.onClick');
        $this->options['dnd']['onDragStart'] = new \CJavaScriptExpression('manipulateNestedSets.onDragStart');
        $this->options['dnd']['onDragOver']  = new \CJavaScriptExpression('manipulateNestedSets.onDragOver');
        $this->options['dnd']['onDragEnter'] = new \CJavaScriptExpression('manipulateNestedSets.onDragEnter');
        $this->options['dnd']['onDrop']      = new \CJavaScriptExpression('manipulateNestedSets.onDrop');

        $manipulateNestedSets = <<<JS
    var manipulateNestedSets = {
        contentContainer: $('#loadedContentContainer'),
        onClick: function (node) {
            manipulateNestedSets.contentContainer.empty();
            manipulateNestedSets.contentContainer.load(
                '{$this->manipulationLoadNodeViewAction}',
                $.param({
                    '{$this->manipulationParamNames["loadingNodeID"]}': node.data.key
                })
            );
        },
        onDragStart: function (node) {
            return true;
        },
        onDragEnter: function (node) {
            return true;
        },
        onDrop: function (node, sourceNode, hitMode, ui, draggable) {
            sourceNode.move(node, hitMode);

            manipulateNestedSets.sendChangesToServer(
                node, sourceNode, hitMode,
                function (targetNode, sourceNode, hitMode) {
                    if (hitMode == "over") {
                        var wasItLazy = targetNode.isLazy();
                        if (!wasItLazy) {
                            targetNode.data.isLazy = true;
                            targetNode.resetLazy();
                        }
                        targetNode.reloadChildren();
                        targetNode.expand();
                    }
                }
            );
        },
        onDragOver: function (node, sourceNode, hitMode) {
            if (node.isDescendantOf(sourceNode)) {
                return false;
            }

            // Root nodes can not be sorted
            if (sourceNode.getLevel() == 1 && node.getLevel() == 1 && hitMode != "over") {
                return "over";
            }
            return true;
        },

        sendChangesToServer: function (targetNode, sourceNode, hitMode, successCallback) {
            successCallback = successCallback || $.noop;

            jQuery.ajax({
                'url': '{$this->manipulationAction}',
                'type': 'POST',
                'cache': false,
                'data': {
                    '{$this->manipulationParamNames["savingTargetNodeID"]}': targetNode.data.key,
                    '{$this->manipulationParamNames["savingSourceNodeID"]}': sourceNode.data.key,
                    '{$this->manipulationParamNames["savingHitMode"]}': hitMode
                },
                'success': function (data, code, xhr) {
                    successCallback(targetNode, sourceNode, hitMode);
                }
            });
        }
    };
JS;

        \Yii::app()->clientScript->registerScript(
            'manipulateNestedSets',
            $manipulateNestedSets,
            \CClientScript::POS_READY
        );
    }

    public function run()
    {
        if (isset($this->options['updateInputValueEvent'])) {
            $this->init['updateInputValueEvent'] = $this->options['updateInputValueEvent'];
            unset($this->options['updateInputValueEvent']);
        }
        $this->init['options'] = $this->options;
        $this->widget('yiiExtensions\dynatree\Dynatree', $this->init);
    }
}