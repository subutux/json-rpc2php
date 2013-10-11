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
 * jsonrpc2php client for javascript
 * for use with http://github.com/subutux/json-rpc2php/
 * @author Stijn Van Campenhout <stijn.vancampenhout@gmail.com>
 * @version 1.2
 */
 function jsonrpcphp(host,mainCallback,options){
 	defaultOptions = {
 		"ignoreErrors" : [],
 		"username" : "",
 		"password" : ""
 	}
 	this.o = $.extend({},defaultOptions,options);
 	var that = this;
 	this.host = host;
 	this.currId = 0;
 	this.err = function (code,msg,fullmsg){
	if ($.inArray(code,this.o.ignoreErrors) < 0){
			alert(code + "::" + msg + "::" + fullmsg);
			//console.log(msg);
		}
 	}
 	/**
 	 * Main rpc function, wrapper for $.ajax();
 	 *
 	 * @param string method
 	 * @param string,array,object params
 	 * @param function callback
 	 */
 	this.__rpc__ = function(method,params,callback){
 		request = {};
 		request.jsonrpc = "2.0";
 		request.method = method;
 		if (typeof params == "string"){
 			request.params = new Array();
 			request.params[0] = params;
 		} else {
	 		request.params = params;
	 	}
 		if (typeof(callback) != "undefined"){
 			this.currId += 1;
 			request.id = this.currId;
 		}
 		function setHeaders(xhr){
 			if (typeof(that.o['sessionId']) != "undefined"){
 				xhr.setRequestHeader("x-RPC-Auth-Session",that.o['sessionId'])
 			} else if (that.o['username'] != "" && that.o['password'] != ""){
 				xhr.setRequestHeader("x-RPC-Auth-Username",that.o['username'])
 				xhr.setRequestHeader("x-RPC-Auth-Password",that.o['password'])
 			}
 		}
 		$.ajax({
		  url:host,
		  type:"POST",
		  data:JSON.stringify(request),
		  contentType:"application/json",
		  dataType:"json",
		  beforeSend: function(xhr){
		  	setHeaders(xhr);
		  },
		  error: function(jqXHR,textStatus){
		  	//Don't throw an error if we don't expect any results
		  	if (typeof(callback) != "undefined"){
		  		alert('error:' + textStatus);
		  		return false;
		  	}
		  },
		  success: function(r,textStatus,XMLHttpRequest){
		  	var sessionId = XMLHttpRequest.getResponseHeader("x-RPC-Auth-Session");
		  	if (typeof(sessionId) == "string"){
		  		that.o['sessionId'] = sessionId;
		  	}
 			if (r.error != null){
 				that.err(r.error.code,r.error.message,r.error.data.fullMessage)
 				/*alert(r.error.code + "::" + r.error.message + "::" + r.error.data.fullMessage);
 				console.log(r.error);*/
 				return false;
 			} else if (typeof r.id != "undefined"){
 				if (r.id == request.id){
 					callback(r);
 				} else {
 					//alert("jsonrpc2Error::NO_ID_MATCH::Given Id and recieved Id does not match");
 					that.err("jsonrpc2Error","NO_ID_MATCH","Given Id and recieved Id does not match");
 					return false;
 				}
 			} else {
 				return true;
 			}
 		 }
		});

 	}
 	/**
 	 * Build the function to execute a this.rpc call for the given object method
 	 *
 	 * @param string method
 	 * @return function
 	 */
 	this.buildFunction = function(method) {
 		return function (params,callback){
 			that.__rpc__(method,params,callback);
 		}
 	}
 	
 	/**
 	 * Build object for each method available like so:
 	 * rpc.[extension].[method](params,callback);
 	 *
 	 */
 		this.__rpc__('rpc.listMethods','',function(system){
 			console.log(system);
 			$.each(system.result,function(ext,methods){
 				that[ext] = {};
 				for (method in methods){
					m = system.result[ext][method];
 					that[ext][m] = that.buildFunction(ext + "." + m);
 				};
 			});
 			mainCallback();
 		});
}
