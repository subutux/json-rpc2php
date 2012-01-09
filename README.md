json-rpc2php
============
json-rpc2php ?
--------------

json-rpc2php is a php and javascript library.
contains a php json-rpc version 2.0 server and a javascript json-rpc version 2.0 client


Features php server
--------------------
* simple registration of php classes

* simple use

* catches exceptions and translates them in json-rcp version 2.0 errors

* supports rpc.listMethods : array of class => methods


Features javascript client library
----------------------------------
* simple configuration (only needs an endpoint)

* automatic creation of javascript objects for each method available (using the rpc.listMethods)

* uses the jQuery library

* also available as jQuery library

Example server
--------------
* api.php

		<?php
		require_once('my.class.php');
		require_once('jsonrpc2php.php');
		$myClass = new myClass();
		$jsonRpc = new jsonrpcphp();
		$jsonRpc->registerClass($myClass);
		$jsonRpc->handle() or die('no request');
		?>

* my.class.php

		<?php
		class myClass {
			public function ping($msg) {
				return "pong:" . $msg;
			}
		}
		?>

Example javascript client
------------------------

	<script type="javascript/text" src="jsonrpc2php.client.js"></script>
	<script>
	var rpc = new jsonrpcphp('api.php',function(){
		rpc.myClass.ping("hello world!",function(jsonRpcObj){
			alert(jsonRpcObj.return);
		});
	});
	</script>
