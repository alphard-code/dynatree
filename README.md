Installation
------------
1.  Download archive https://github.com/alphard-code/dynatree/archive/master.zip and extract it to your application's
extensions directory.
2.  Add the alias to your application configuration:  

    ```php
    'aliases' => array(
      'yiiExtensions' => 'path.to.extensions.dir',
    ),
    ```

3.	If you are needed to use functionality of displaying and managing nested sets, you will also need to install my fork of [Nested Set Behavior](https://github.com/alphard-code/nested-set-behavior).

Usage
-----
  
* 	Simple usage of plain dynatree plugin:

	```php
	$this->widget('yiiExtensions\dynatree\Dynatree', array(
		'options' => array(
	         // Options that will be passed to Javascript constructor of Dynatree plugin.
	         // @see http://wwwendt.de/tech/dynatree/doc/dynatree-doc.html#h4.1
		),		
	));
	```
	
* 	Widget can be associated with a data model and an attribute, if so hidden input with automatically generated
	name and ID will be inserted into plugin container. This behavior is similar to standart CInputWidget.
	The value of input will be updated with node key by the handler of an event, specified as "updateInputValueEvent":

	```php
	$this->widget('yiiExtensions\dynatree\Dynatree', array(
		'model' => $model, // instance of CModel
		'attribute' => 'attributeName', 
		
		// Name of an event that will trigger update of hidden input value. Defaults to 'onActivate'.
		// @see http://wwwendt.de/tech/dynatree/doc/dynatree-doc.html#h5.1
		'updateInputValueEvent' => 'onActivate', 
	));
	```
	

* Nested set displaying and manipulation:

