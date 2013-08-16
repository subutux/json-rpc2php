<?
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
 * This class builds a json-RPC 2.0 Client
 * http://www.jsonrpc.org/spec.html
 *
 * original idea from jsonrpcphp class of Sergio Vaccaro <sergio@inservibile.org>, http://jsonrpcphp.org/
 * @author stijn <stijn.vancampenhout@gmail.com>
 * @version 1.2
 */
class jsonRPCClient {
    private $url;
    private $id;
    private $notification = false;
    private $class;
    private $auth;

    public function __construct($host,$class,$auth=array()){
        $this->url = $host;
        $this->class = $class;
        $this->id = 1;
        $this->auth = $auth;
    }
    private function constructHeaders(){
        $headers = array(
            "Content-type" => "application/json"
            );
        $rawHeader = "";
        if (!empty($this->auth)){
            if (isset($this->auth['sessionId'])){
                $headers["X-RPC-Auth-Session"] = $this->auth['sessionId'];
            } else {

                $headers["X-RPC-Auth-Username"] = $this->auth['username'];
                $headers["X-RPC-Auth-Password"] = $this->auth['password'];
            }
        }
        foreach ($headers as $h => $c){
            $rawHeader.=$h . ": " . $c . "\r\n";
        }
        return $rawHeader;
    }
    /**
     * Parses the $http_response_headers
     * @param  array $headers contains the http_response_headers array
     * @return array          proper header array
     */
    private function parseHeaders($headers){
        $nHeaders = array();
        foreach ($headers as $header) {
            $h = explode(": ", $header);
            $nHeaders[$h[0]] = $h[1];
        }
        return $nHeaders;
    }

    private function setNotification($notify = false){
        $this->notification = $notify;
    }
    public function batch() {
        return new jrpc2c_batch();
    }
    public function process($batch){
        $b = $batch->get_batchCalls();
        $results = array();
        $this->activeBatch = true;
        foreach ($b as $function => $args) {
            $results[] = call_user_func_array(array($this,$function), $args);
        }
    }
    public function __call($method,$params){
        // check
        if (!is_scalar($method)) {
            throw new Exception('Method name has no scalar value');
        }
        
        // check
        if (is_array($params)) {
            // no keys
            $params = array_values($params);
        } else {
            throw new Exception('Params must be given as array');
        }
        // sets notification or request task
        if ($this->notification) {
            $currentId = NULL;
        } else {
            $currentId = $this->id;
        }
        $request = array(
                'jsonrpc' => '2.0',
                'method' => $this->class . '.' . $method,
                'params' => $params,
                'id' => $this->id
            );
        //testing batch
        //$request = array($request,$request);
        $opts = array ('http' => array (
                            'method'  => 'POST',
                            'header'  => $this->constructHeaders(),
                            'content' => json_encode($request)
                            ));
        $context  = stream_context_create($opts);
        if ($fp = fopen($this->url, 'r', false, $context)) {
            $h = $this->parseHeaders($http_response_header);
            print_r($h);
            if (isset($h['x-RPC-Auth-Session'])){
                print("setting session id to " . $h['x-RPC-Auth-Session']);
                $this->auth['sessionId'] = $h['x-RPC-Auth-Session'];
            }
            $response = '';
            while($row = fgets($fp)) {
                $response.= trim($row)."\n";
            }
        echo "resp:".$response;
            $response = json_decode($response,true);
        } else {
            throw new Exception('Unable to connect to '.$this->url);
        }
        if (!$this->notification) {
            // check
        print_r($response);
            if ($response['id'] != $currentId) {
                throw new Exception('Incorrect response id (request id: '.$currentId.', response id: '.$response['id'].')');
            }
            if (!is_null($response['error'])) {
                throw new Exception('Request error: '.$response['error']['code'].'::'.$response['error']['message'].':'.$response['error']['code']);
            }
            return $response['result'];
            
        } else {
            return true;
        }

    }
}
class jrpc2c_batch {
    private $batchcalls = array();
    public function __call($method,$params){
        if (!is_scalar($method)) {
            throw new Exception('Method name has no scalar value');
        }
        
        // check
        if (is_array($params)) {
            // no keys
            $params = array_values($params);
        } else {
            throw new Exception('Params must be given as array');
        }
        $this->batchcalls[$method] = $params;

    }
    public get_batchCalls(){
        return $this->batchcalls;
    }
}
?>