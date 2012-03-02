json-rpc2php
============
json-rpc2php ?
--------------

json-rpc2php is a php and javascript library.
contains a php json-rpc version 2.0 server and a javascript json-rpc version 2.0 client

Open Source
------------

json-rpc2php is open source under GPLv2 licence. Please consider a [donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=J8RZGZC5WPZDU&lc=BE&item_name=Subutux&item_number=TRANSRSS&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted) so I can keep working on this.

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

Features PHP Client *NEW*
-------------------------
* Simple usage: Directly call object function from client class

Example server
--------------
* api.php

		<?php
		require_once('my.class.php');
		require_once('jsonRPC2Server.php');
		$myClass = new myClass();
		$jsonRpc = new jsonRPCServer();
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
Example PHP client
------------------

	 <?php
	 require_once 'jsonRPC2Client.php';
	 $myClass = new jsonRPCClient('http://server.hosting.api/api.php','myClass');
	 print_r($myClass->ping('testing one 2 three.'));
	 /* Outputs:
	 	Array (
	 		[0] => "pong:testing one 2 three"
	 	)
	 */
	 ?>