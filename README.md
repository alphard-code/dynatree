Yii framework integration of Dynatree plugin
============================================

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

Usage
-----
  
* Simple usage of plain dynatree plugin:
        
```php
$this->widget('yiiExtensions\dynatree\Dynatree', array(
	'options' => array(
  	    /**
         * Options that will be passed to Javascript constructor of Dynatree plugin.
         * @see http://wwwendt.de/tech/dynatree/doc/dynatree-doc.html#h4.1
		 */
	)
));
```

* Nested sets displaying and manipulation:
