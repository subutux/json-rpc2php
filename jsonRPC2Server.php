<?php
//error_reporting(0);
/*
					COPYRIGHT

Copyright 2012 Stijn Van Campenhout <stijn.vancampenhout@gmail.com>

This file is part of JSON-RPC2PHP.

JSON-RPC2PHP is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

JSON-RPC2PHP is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with JSON-RPC2PHP; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * This class builds a json-RPC 2.0 Server
 * http://www.jsonrpc.org/spec.html
 *
 * original idea from jsonrpcphp class of Sergio Vaccaro <sergio@inservibile.org>, http://jsonrpcphp.org/
 * @author stijn <stijn.vancampenhout@gmail.com>
 * @version  1.2
 */
class jsonRPCServer {
	public $classes = array();
	public $request;
	public $extension;
	public $response;
	private $errorMessages = array(
		'-32700' => 'Parse error',
		'-32600' => 'Invalid request',
		'-32601' => 'Method not found',
		'-32602' => 'Invalid parameters',
		'-32603' => 'Internal error',
		'-32604' => 'Authentication error',
		'-32000' => 'Extension not found'
		);
	private $errorMessagesFull = array(
		'-32700' => 'Invalid JSON was received by the server. An error occurred on the server while parsing the JSON string.',
		'-32600' => 'The JSON sent is not a valid Request object.',
		'-32601' => 'The method does not exist / is not available.',
		'-32602' => 'Invalid method parameters.',
		'-32603' => 'Internal Server error.',
		'-32000' => 'The requested extension does not exist / is not available.',
		'-32604' => 'User unknown / Password / Session id incorrect.'
		);
	
	private $errorCodes = array(
		'parseError' 			=> '-32700',
		'invalidRequest'		=> '-32600',
		'methodNotFound'		=> '-32601',
		'invalidParameters'		=> '-32602',
		'internalError'			=> '-32603',
		'authenticationError'	=> '-32604',
		'extensionNotFound'		=> '-32000'
		);
	private $useAuthentication = false;
	private $users = array();

	/**
	 * Sets the Cross Origin Resource Sharing headers
	 * 
	 * This functions sets the needed CORS headers
	 * to allow other sites to access your json-rpc2 api.
	 * 
	 * @param bool $auth 	If you don't want to set the special auth variables
	 *						set this to false. (only if you don't plan to use auth)
	 * @param array $allow 	Defaults to '*' if empty, else sets the allowed sites from array
	 */
	public function setCORSheaders($auth=true,$allow=array()){
		if (empty($allow)){
			header('Access-Control-Allow-Origin: *');
		} else {
			header('Access-Control-Allow-Origin: '.join(', ',$allow));
		}
		if ($auth){
			header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, X-Rpc-Auth-Username, X-Rpc-Auth-Password, x-Rpc-Auth-Session');
			header('Access-Control-Expose-Headers: x-Rpc-Auth-Session');
		} else {
			header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
		}
	}
	/**
	 * Register a class as an extension
	 * methods will be available as [class].[method]
	 *
	 * @param object $obj
	 * return boolean
	 */
	public function registerClass($obj){
		$this->classes[get_class($obj)] = $obj;
		return true;
	}
	/**
	 * Adds a user that's allowed to access the RPC
	 * @param  string $user     Username
	 * @param  string $password Password
	 * @return Bool             Return true
	 */
	public function registerUser($user,$password){
		$this->users[$user] = $password;
		foreach ($this->users as $user => $pass){
		}
		return true;
	}
	/**
	 * Enables or disables authentication
	 * 
	 * @param bool $state set true for enable, false for disable
	 */
	public function enableAuthentication($state=""){
		if (is_bool($state)){
			$this->useAuthentication = $state;
		} else if ($state == ""){
			$this->useAuthentication = true;
		}
	}
	/**
	 * Verifies the authentication
	 *
	 * This function defaults to the buildin simple authentication
	 * so you can extend jsonRPC2Server and override this function
	 * with your own authentication mechanism.
	 *
	 * @param string $user username from header x-rpc-auth-username
	 * @param string $password password from header x-rpc-auth-password
	 */
	public function verifyAuth($user,$pass){
		if (!isset($this->users[$user])) return false;
		if ($this->users[$user] == $pass){
			return true;
		} else return false;
	}
	/**
	 * Handles the authentication
	 * @param  Array $HTTPHeaders Contains the apache_request_headers()
	 */
	private function authenticate($HTTPHeaders){
		foreach($HTTPHeaders as $i => $c){
			$HTTPHeaders[strtolower($i)] = $c;
		}
		if (isset($HTTPHeaders['x-rpc-auth-username']) && isset($HTTPHeaders['x-rpc-auth-password'])){
			if ($this->verifyAuth($HTTPHeaders['x-rpc-auth-username'],$HTTPHeaders['x-rpc-auth-password'])){
				session_start();
				$sid = session_id();
				$_SESSION["ip"] = $_SERVER["REMOTE_ADDR"];
				header('x-RPC-Auth-Session: ' . $sid);
			} else {
				throw new jsonRPCException("",$this->errorCodes['authenticationError']);
			}
		} else if (isset($HTTPHeaders['x-rpc-auth-session'])){
			session_id($HTTPHeaders['x-rpc-auth-session']);
			session_start();
			if ($_SESSION['ip'] == $_SERVER["REMOTE_ADDR"]){
				return true;
			} else {
				throw new jsonRPCException("",$this->errorCodes['authenticationError']);
			}
		} else {
				throw new jsonRPCException("",$this->errorCodes['authenticationError']);
		} 

	}
	/**
	 * responses to 'rpc.' calls.
	 *
	 */
	 public function rpcCalls() {

	 	if ($this->request['method'] == "listMethods"){
	 		foreach ($this->classes as $ext => $class){
	 			$methods[$ext] = get_class_methods($class);
	 		}
	 		if (isset($this->request['params']['extension'])){
	 			if (array_key_exists($this->request['params']['extension'],$this->classes)){
	 				$this->ok(array($this->request['params']['extension'] => $methods[$this->request['params']['extension']]));
	 				$this->sendResponse();
	 			} else {
	 				$this->error($this->errorCodes['extensionNotFound'],"requested extension not found in extension list." );
	 				$this->sendResponse();
	 			}
	 		} else {
	 				$this->ok($methods);
	 				$this->sendResponse();
	 		}
	 	} else {
	 		$this->error($this->errorCodes['methodNotFound']);
	 		$this->sendResponse();
	 	}
	 	return true;
	 }
	/**
	 * This function validates the incoming json string
	 * - checks the request method
	 * - checks if we can parse the json
	 * - checks if the extension exists
	 * - checks if the method exists in the given extension
	 *
	 * @return boolean
	 */
	private function validate() {


		try {
			if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_SERVER['CONTENT_TYPE']) || strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
				throw new jsonRPCException("",$this->errorCodes['invalidRequest']);
			}
			$this->request = json_decode(file_get_contents('php://input'),true);
			if (empty($this->request)){
				throw new jsonRPCException("",$this->errorCodes['parseError']);
			}
			$requestMethod = explode('.',$this->request['method']);
			$this->extension = $requestMethod[0];
			if (!isset($this->classes[$this->extension]) && $this->extension != "rpc"){
				throw new jsonRPCException("",$this->errorCodes['extensionNotFound']);
			}
			$this->request['method'] = $requestMethod[1];
			
			if ($this->extension != "rpc" && !method_exists($this->classes[$this->extension],$this->request['method'])){
				throw new jsonRPCException("",$this->errorCodes['methodNotFound']);
			};
	
		} catch (Exception $e) {
				$this->error($e->getMessage());
				$this->sendResponse();
				return false;
		}
		return true;
	}
	/**
	 * Builds the error response
	 *
	 * @todo make this in a execption class
	 * @param string $c the error code
	 * @param string $fmsg the full message of the error
	 */
	private function error($c,$fmsg=false,$errorData = Array('no data')){
		$this->response = array (
				'jsonrpc'	=> '2.0',
				'id' => (isset($this->request['id'])) ? $this->request['id'] : NULL,
				'result' => NULL,
				'error'	=> array(
					'code'	=> (int)$c,
					'message' => (isset($this->errorMessages[$c])) ? $this->errorMessages[$c] : 'internalError',
					
					'data'	=> array(
						'request' => (isset($this->request)) ? $this->request : NULL,
						'extension' => (isset($this->extension)) ? $this->extension : NULL,
						'fullMessage' => ($fmsg) ? $fmsg : $this->errorMessagesFull[$c],
						'debugData' => $errorData
						)
					)
				);
		return true;
	}
	private function toUtf8(array $array) { 
    $convertedArray = array(); 
    foreach($array as $key => $value) { 
      if(!mb_check_encoding($key, 'UTF-8')) $key = utf8_encode($key); 
      if(is_array($value)) $value = $this->toUtf8($value); 

      $convertedArray[$key] = $value; 
    } 
    return $convertedArray; 
  } 
	private function ok($result){
					//print_r($result);
					$this->response = array (
						'jsonrpc'	=> '2.0',
						'id' => $this->request['id'],
						'result' => $result,
						'error' => NULL
						);
	}
	/**
	 * check if there is a response needed & sends the response
	 *
	 */
	private function sendResponse(){
		if (!empty($this->request['id'])) { // notifications don't want response
			header('content-type: application/json');
			die( json_encode($this->response) );
		}
	}
	
	/**
	 * main class method. starts all the magic
	 *
	 */
	public function handle() {
		/* If there are no users defined, don't use authentication */
		if ($this->extension != "rpc"){
			$this->validate();
		}

		try {
			if ($this->useAuthentication){
	 			$this->authenticate(apache_request_headers());
	 		}
			if ($this->extension == "rpc"){
				$this->rpcCalls();
			}
			$obj = $this->classes[$this->extension];
		
			if (($result = call_user_func_array(array($obj,$this->request['method']),$this->request['params'])) !== false) {
				$this->ok((is_array($result)) ? $result : Array($result));
			} else {
				throw new jsonRPCException('Method function returned false.');
			}
		} catch (jsonRPCException $e) {
				$c = ($e->getCode() != 0) ? $e->getCode() : $this->errorCodes['internalError'];
				$this->error($c,$e->getMessage(),$e->_getErrorData());
		}
		$this->sendResponse();
		return true;
	}

}
Class jsonRPCException extends Exception {
	private $_errorData;

	public function __construct($message,
								$code = 0,
								Exception $previous = null,
								$errorData = Array('no data'))
	{
		parent::__construct($message,$code);
		$this->_errorData = $errorData;
	}

	public function _getErrorData(){
		return $this->_errorData;
	}
}
?>
