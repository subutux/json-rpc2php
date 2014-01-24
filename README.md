json-rpc2php
============
json-rpc2php ?
--------------

json-rpc2php is a flexible PHP JSON-RPC2 server.
it contains the following:
* The json-RPC2 PHP server

* a json-RPC2 client in PHP

* a json-RPC2 client in Javascript (using jQuery)

* a json-RPC2 client in Python (using urllib2 and json)

* *NEW* a json-RPC2 client library in Vala (using json-glib-1.0 and libsoup-2.4)

Open Source
------------

json-rpc2php is open source under GPLv2 licence. Please consider a donation so I can keep working on this.

[![donation](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=J8RZGZC5WPZDU&lc=BE&item_name=Stijn%20Van%20Campenhout%20Develops&item_number=CODE%20DONATION&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted)

Features php server
--------------------
* simple registration of php classes

* simple use

* catches exceptions and translates them in json-rcp version 2.0 errors

* supports rpc.listMethods : array of class => methods

* Supports simple authentication (using session header in requests)

By default, the server doesn't use authentication. Authentication is only used when there is minimum one defined user. You can define users by using the "jsonRPC2PHP->registerUser(user,pass)" function.

Features javascript client library
----------------------------------
* simple configuration (only needs an endpoint)

* automatic creation of javascript objects for each method available (using the rpc.listMethods)

* uses the jQuery library

Authentication is is used when you define a username and password variable in the Options

Features PHP Client 
-------------------
* Simple usage: Directly call object function from client class

Authentication is used when you pass an array containing "username" => "" and "password" => "" to the class init as the last parameter

Features Python Client
----------------------
* Simple usage: Directly call object function from client class (the same as the PHP client)

Authentication is is used when you define a username and password variable in the Options

Features Vala (or raw C) Client *NEW*
-------------------------------------

Overhall functionallity is the same as the php & python client, except for
executing the request. this is done by `JsonRPC2.JsonRPC2client.request(string method, string[] params)`

Supports authentication using the `JsonRPC2.JsonRPC2client.authenticate(username,password)`

Compilation of the library is as followed:

```bash
valac --pkg json-glib-1.0 --pkg libsoup-2.4 --library=JsonRPC2 -H JsonRPC2.h jsonrpc2.vala -X -fPIC -X -shared -o JsonRPC2.so
```

Compilation against the library:

```bash
valac --pkg json-glib-1.0 --pkg libsoup-2.4 JsonRPC2.vapi your-vala-project.vala -X JsonRPC2.so -X -I . -o your-vala-project
```

You'll need some extra packages to use/compile JsonRPC2. For ex. Ubuntu & Debian:
```bash
sudo apt-get install libjson-glib-1.0 libjson-glib-1.0 libsoup-2.4 libsoup-2.4-dev #I think
```

This is my first Vala library. Any improvements are welcome.


Example server
--------------
* api.php

```php
<?php
require_once('my.class.php');
require_once('jsonRPC2Server.php');
$myClass = new myClass();
$jsonRpc = new jsonRPCServer();
$jsonRpc->registerClass($myClass);
$jsonRpc->handle() or die('no request');
?>
```
* my.class.php

```php
<?php
class myClass {
	public function ping($msg) {
		return "pong:" . $msg;
	}
}
?>
```

Example javascript client
------------------------
```html
<script type="text/javascript" src="jsonrpc2php.client.js"></script>
<script>
var rpc = new jsonrpcphp('api.php',function(){
	rpc.myClass.ping("hello world!",function(jsonRpcObj){
		alert(jsonRpcObj.result);
	});
});
</script>
```
Example PHP client
------------------

```php
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
```

Example Python client
---------------------

```python
import jsonrpc2php-pyclient
myClass = jsonrpc2client("http://server.hosting.api/api.php",'myClass')
print myClass.ping("testing one 2 three")
"""
Outputs:
{u'error': None, u'jsonrpc': u'2.0', u'id': 1, u'result': [u'pong:testing one 2 three']}
"""
```
Example Vala client
-------------------

See examples/example.vala
