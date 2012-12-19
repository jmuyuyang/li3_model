#li3_model

#### A more flexible model that based on the [Lithium](https://github.com/UnionOfRAD/lithium) framework

## Model Creation and Configurtion
li3_model is extended as lithium plugin that provide a more flexible,lighter,and faster model and is only 30kbs 

###install
Checkout the code to either of your library directories:  
```php
cd libraries
git clone https://github.com/jmuyuyang/li3_model.git
```  
Include the library in in your /app/config/bootstrap/libraries.php  
```
Libraries::add('li3_model'); 
```  
We know you want to store and manage blog posts in your database. According to Lithium conventions, you create a new file called `Posts.php` in `app/models`. The basic structure looks like this:

###Useage
```php
<?php

namespace app\models;

class Posts extends \li3_model\data\Model {
}

?>
```

If you don't specify otherwise, your model will use the `default` connection specified in libraris path `li3_model/config/dbConfig.php`. Such as:

```php
<?php
return array(
	'default' => array(
			'host'=>'localhost',
			'port' => 3306,
			'user'=>'username',
			'pass'=>'password',
			'database'=>'yourdatabase')
);
?>
```

All these defaults are stored in the model's `$_meta` variable. 

### Model definition:
Once your model's `$_meta` property has been configured, Li3_model merges it with the default settings at runtime.  
In the `$_meta`,`source` is mapped to one of your table of the database,`key` should be mapped to a primary key that exits in your table which specified in the `source`.

also the `$_adapter` property will be defined as MySql or MongoDb,is which database adapter you want to use  
```php
<?php

namespace app\models;

class Posts extends \lithium\data\Model {

	protected static $_adapter = "MySql";
	protected static $_meta = array(
			'connection' => 'default',
			'source' => 'posts',
			'key' => 'id'
	);

}  
?>
```

Of course,you can be overridden if the defaults don't fit your needs.Let's say you want to use the `myConnection` connection instead of the default one.Just like it:

```php
<?php
	protected static $_meta = array(
			'connection' => 'myConnection',
	);
?>
```
Use the `source` property can select the table which the model use.
You can alse use the `filter` proerty to define a volidator,and use it with the code like

```php
<?php  
	$filter = $this->adapter()->filter();  
?>
```

## Basic CURD

#### Introduce several new features which make the freamwork more flexible and awsome.Now let's start.

### Create

Persisting data means that new data is being stored. Before you can save your data, you have to initialize a `Model`. If you should create a new record—with the `create()` method. You then can add your data-with the `save()` method. Here is an easy example:

```php
<?php
// Create a new post,add title and author,then save
$data = array(
		'title' => 'the first blog post';
		'author' => 'asturn';
		);
$success = $this->create($data);
if ($success) {
	$id = $success->save();
} else {
	return $this->errors('some words about errors');
}
?>
```
__If you set `display_errors` in php.ini `On`,you can hand the error message flexibly with the function `errors()`__.

If the creation succeed,it will return the last insert id,depending on the result of the validation.Now below is something description about validate mechanism 

```php
<?php
namespace app\models;

class Users extends \lithium\data\Model {
	public $validator = array(
			'name' => array('notEmpty', 'message' => 'name cannot be empty.'),
			'password' => array('notEmpty', 'message' => 'password cannot be empty.'),
	);
?>
```

Maybe you can use the `insert()` function directly like so:

```php
<?php
// Create a new post,add title and author,then save
$data = array(
		'title' => 'the second blog post';
		'author' => 'asturn';
		);
//return boolen `true` or `false`
$success = $this->insert($data);
?>
```

There is a more flexible function `sql()` you can exploit it with yourself `SQL` statements.Now let us glimpse it.

```php
<?php
//prepare sql statement
$success = $user->sql("select * from where name=:name",array(":name",$name));
?>
```
This type can use the pdo filter the data.

```php
<?php
//prepare sql statement
$filter = $this->adapter()->filter();
$success = $user->sql("select * from where name=".$filter($name));
?>
```
There's the type you can use the filter of model to filter the data.

### Update

This section showed you how to save documents/records in your database. If these records existed previously, you are also updating them. Utilise `update()` method which updates records directly in your database (similar to the sql `UPDATE` command).
The first argument provides the data to change, the second (optional) one is a set of conditions to narrow down your selection. Here is an example:

```php
<?php
public function updateTitle($id, $newTitle) {
	return $this->update(
			array('title' => $newTitle),
			array('id' => $id)
			);
}
?>
```

This new version also allow you use syntax like that `save('update')`,before it be done with the `find()` methods.Here is an othere example:

```php
<?php
// Find the post record
$fields = array('id', 'title', 'author');
$post = $this->find('first',$fields);
$post->title  = 'my first post';
$post->author = 'asturn';
$post->save('update');
?>
```

**Note**:In the array `fields`,you must specify the primary key,such as `id`,which depend on your table.
### Read
 
####Use function `find` to read data from database. 

The first param can be `all` or `first` decided to the data will be a collection or a record.  

example:

```php
<?php  
	$data = $this->find('all');    
	if($data){  
		$data = $data->data();  
	}  
?>
```  

The function `find()` return an Object when it has finded data.Else,return false.When it has finded,you can use the function `data()` to get the data,the data's type is array.  
You can also use the function `fetch()` or `fetchAll()` to get the data.  
example:

```php
<?php  
	$data = $this->find('all');    
	if($data){  
		while($arr =$data->fetch('NUM')){ //YOU CAN CHOOSE `NUM` ,`ASSOC`,`BOTH`   
			//do something.
		}  
	}  
?>
<?php  
	$data = $this->find('all');    
	if($data){  
		$data = $data->fetchAll('NUM');/YOU CAN CHOOSE `NUM` ,`ASSOC`,`BOTH`
	}
?>
```  
If you use the `first`,you can use the record like a project to get or rewrite the data.
example:
```php
<?php  
	$data = $this->find('first');    
	if($data){  
		$title = $data->title;
        $data->content = 'change';  
	}  
?>
```

####Use the finite word to find the data you want.

#####The finite word `where`

example:

```php
<?php
	$where = array('id'=>1);    	
	$data = $this->find('first',compact('where'));  
	if($data){  
		$data = $data->data();
	}  
?>
```

The finite word where will help us to find the record we want.The example will return the record which `id` equal 1.
#####The finite word `whereOr`

example:

```php
<?php
	$whereOr = array('name'=>'Bob','age'=>15);     	
	$data = $this->find('first',compact('whereOr'));  
	if($data){  
		$data = $data->data();
	}  
?>
```

If you want to find some records that match one of the limits you give.Use `whereOr` will help you.
#####The finite word `whereAnd`  

example:
 
```php
<?php
	$whereAnd = array('name'=>'Bob','age'=>15);    
	$data = $this->find('first',compact('whereAnd'));  
	if($data){  
		$data = $data->data();
	}  
?>
``` 

If you want to find some records that match all of the limits you give.Use `whereOr` will help you.

#####The other finite words

You can also use the other finite words we give to help you to find the record you want.There is the list.When you use the `mongodb`,you should plus $ to the front of the finite words,such as `$lt`. 

* 'lt'      =>     '<'  
* 'gt'      =>     '>'  
* 'lte'     =>     '<='  
* 'gte'     =>     '>='  
* 'like'    =>     'LIKE'  
* 'inc'     =>     '+',  
* 'notLike' =>     'NOT LIKE',  
* 'in'      =>     'IN'  

Their function like you use them in sql.
example:
```php
<?php    
	$data = $this->find('all',array('where'=>array('id'=>array('lt'=>6))));  
	if($data){  
		$data = $data->data();
	}  
?>

``` 
You can get the data where the data's id<6.
#####The fields

example:
 
```php
<?php  
	$where = array('id'=>1); 
	$fields = array('name','age');  
	$data = $this->find('first',compact('where','fields'));    
	if($data){  
		$data = $data->data();  
	}  
?>
```


#####The limit
```php
<?php    
	$data = $this->find('all',array('limit'=>5,'offset'=>0));    
	if($data){  
		$data = $data->data();  
	}  
?>
```
You can use `limit` to limit how many the records that return,and the `offset` will choose which record is the start. 

Use the finite word fields can help you to limit the fields you want the record finded have.

### Delete

Removing data works similar to updating data, now use the `delete()` function you can remove a subset of your database entries that meet rules you given.

```php
<?php
// Delete all posts with an empty title
$result = $this->delete(array('title' => ''));

// Delete a record with some conditions
$result = $this->delete(array('id' => 1, 'name' => 'asturn'));
?>
```

Be careful with this.If you don't provide any arguments to `delete()`, then all documents/rows in your database will be deleted!

##Other new function 

###Change the table you want 

example:

```php
<?php  
	$test = $this->table('testTable');  
?>
```

You can use the function `table()` to change the table you want to use.When do a CURD action it will back to the basic table of the model use.

###Change the database you want

example:
 
```php
<?php  
	$test = $this->db('testDb');  
?>
```

You can also use the function `db()` to change the database you want to use , the database must be specified in `app/config/dbConfig.php`.   

###Get the model's instance

```php
<?php
	$test = TestModel::instance();  
?>  
```

Use the static function `instance()` to get a model's instance in controller.

###Close the connection
example:

```php
<?php  
	TestModel::instance()->close();
?>  
```
 
Use the function `instance()` to close the connection to save the resources.

###Work with two tables 
example:

```php
<?php  
		$message = Test::instance()->find('all',array(
			'alias' => array('attachment' => 'm'),
			'where' => array('m.cid' => 2),
			'fields' => array('message.cid','m.uid','m.content','m.create'),
			'leftJoin' => array('table' => 'attachment','pKey' => 'id','fKey' => 'mid'),
		));
?>  
```
The `alias` is the alias of the table of `attachment`.You can use it like `m.id` pointing to the attachment's `id` ,and you can use `leftJoin` to get the data from the table `attachment`.The `table` must be the table you want to get data from.The `pKey` is the primary key,and the `fKey` is the foreign key in the `attachment`.


