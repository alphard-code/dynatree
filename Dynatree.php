<?php
namespace yiiExtensions\dynatree;

use CHtml;

/**
 * A simple wrapper of Dynatree plugin.
 * Widget can be associated with a data model and an attribute, if so hidden input with automatically generated
 * name and ID will be inserted into plugin container. The value of input will be updated by the handler of an event,
 * specified as {@link updateInputValueEvent}.
 *
 * @author Alexander Bolshakov <a.bolshakov.coder@gmail.com>
 */
class Dynatree extends \CWidget
{

    const SELECT_MODE_SINGLE     = 1;
    const SELECT_MODE_MULTI      = 2;
    const SELECT_MODE_MULTI_HIER = 3;
    /**
     * @var array Options that will be passed to Javascript constructor of Dynatree plugin.
     * Note that you should wrap values responding for callback functions with {@link CJavaScriptExpression} to prevent it
     * from being encoded as a string. Defaults to empty array.
     */
    public $options = array();

    /**
     * @var \CModel The data model associated with this widget.
     * @see \CInputWidget::model
     */
    public $model;

    /**
     * @var string The attribute associated with this widget.
     * @see \CInputWidget::model
     */
    public $attribute;

    /**
     * @var string Name of an event that will trigger update of hidden input value. Defaults to 'onActivate'.
     * If you provide custom handler for this event at {@link options}, it will be inserted at the end of
     * automatically generated handler. You should not use function declaration (i.e. "function() {}") at your handler.
     */
    public $updateInputValueEvent = 'onActivate';

    /**
     * @var string Additional content to be inserted to plugin container.
     */
    public $divContent = '';

    public function init()
    {
        $this->registerScriptFiles();
        $this->handleFormInteraction();

        $options = \CJavaScript::encode($this->options);
        $jsInit  = "$('#{$this->getId()}').dynatree({$options});";

        \Yii::app()->clientScript->registerScript('dynatree' . $this->getId(), $jsInit, \CClientScript::POS_READY);
    }

    public function registerScriptFiles()
    {
        /** @var $cs \CClientScript */
        $cs = \Yii::app()->clientScript;
        /** @var $am \CAssetManager */
        $am = \Yii::app()->assetManager;

        $basePath = __DIR__ . DIRECTORY_SEPARATOR . 'assets';
        $baseUrl  = $am->publish($basePath);

        $package = array(
            'basePath' => $basePath,
            'baseUrl'  => $baseUrl,
        );

        $package = \CMap::mergeArray($package, $this->getScriptsPackage());
        $cs->addPackage('dynatree', $package)->registerPackage('dynatree');
    }

    protected function handleFormInteraction()
    {
        if ($this->model instanceof \CModel && $this->attribute != null) {

            $htmlOptions = array();
            \CHtml::resolveNameID($this->model, $this->attribute, $htmlOptions);

            switch ($this->updateInputValueEvent) {
                case 'onSelect':
                    $funcDeclaration = 'flag, node';
                    break;
                default:
                    $funcDeclaration = 'node';
                    break;
            }

            if (isset($this->options[$this->updateInputValueEvent])) {
                $userEventHandler = $this->options[$this->updateInputValueEvent];
            } else {
                $userEventHandler = '';
            }

            // default value provided by Dynatree
            if (!isset($this->options['selectMode'])) {
                $this->options['selectMode'] = self::SELECT_MODE_MULTI;
            }

            if ($this->options['selectMode'] == self::SELECT_MODE_SINGLE) {
                $eventHandler = "function ({$funcDeclaration}) {
                    $('#{$htmlOptions['id']}').val(node.data.key);
                    {$userEventHandler}
                }";
                $this->divContent .= \CHtml::activeHiddenField($this->model, $this->attribute);
            } elseif ($this->options['selectMode'] == self::SELECT_MODE_MULTI) {
                $htmlOptions['name'] .= '[]';
                $eventHandler = <<<JS
function ({$funcDeclaration}) {
    var existingInput = $('#{$htmlOptions['id']} input[value="' + node.data.key + '"]');
    var itExists = !(existingInput.length == 0);
    var needToAddInput = !itExists;

    // onSelect event
    if (typeof(flag) != "undefined") {
        needToAddInput = flag;
    }
    if (needToAddInput) {
        $('#{$htmlOptions['id']}').append($('<input/>',{
            'type':'checkbox',
            'value': node.data.key,
            'name': '${htmlOptions['name']}',
            'checked':true,
            'style':'display:none;'
        }));
    } else if(itExists) {
        existingInput.remove();
    }
    {$userEventHandler}
}
JS;
                $this->divContent .= CHtml::checkBoxList($htmlOptions['name'], null, array());
            }
            $this->options[$this->updateInputValueEvent] = new \CJavaScriptExpression($eventHandler);
        }
    }

    public function getScriptsPackage()
    {
        return array(
            'js'      => array(
                YII_DEBUG ? 'jquery.dynatree-1.2.2.js' : 'jquery.dynatree.min.js'
            ),
            'css'     => array(
                'skin/ui.dynatree.css'
            ),
            'depends' => array('jquery', 'jquery.ui'),
        );
    }

    public function run()
    {
        echo "<div>{$this->divContent}<div id='{$this->getId()}'></div></div>";
    }


}