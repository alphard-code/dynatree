<?php
namespace yiiExtensions\dynatree;

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
            $eventHandler = "
function (node)
{
    $('#{$htmlOptions['id']}').val(node.data.key);
}
";
            $this->divContent .= \CHtml::activeHiddenField($this->model, $this->attribute);
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