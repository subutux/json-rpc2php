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
 	
 	this.o = this.extend({},defaultOptions,options);
 	var that = this;
 	this.host = host;
 	this.currId = 0;
 	/**
 	 * Quick and dirty $.extend() replacement
 	 * 
 	 * @param object destObj Destination object to change
 	 * @param object masterObj The master object to add
 	 * @param object slaveObj the slave object to add
 	 */
 	this.extend = function(destObj,masterObj,slaveObj) {
 		for (var i = masterObj.length - 1; i >= 0; i--) {
 			destObj[i] = JSON.parse(JSON.stringify(masterObj[i]));
 		};
 		for (var i = slaveObj.length - 1; i >= 0; i--) {
 			destObj[i] = JSON.parse(JSON.stringify(slaveObj[i]));
 		};
 		return destObj;
 	};
 	this.err = function (code,msg,fullmsg){
	if ($.inArray(code,this.o.ignoreErrors) < 0){
			alert(code + "::" + msg + "::" + fullmsg);
			//console.log(msg);
		}
 	}
 	this.ajax = function(options) {
 		defaultOptions = {
 			url : "",
 			type: "GET",
 			data: "",
 			contentType : "application/json",
 			dataType: "json",
 			error : function (HttpWebClient) { return true;},
 			success : function (data,HttpWebClient) { return true;},
 			beforeSend : function (HttpWebClient) { return true;},

 		};
 		o = that.extend({},defaultOptions,options);
 		//protocolFilter
 		protocolFilter = new Windows.Web.Http.Filters.HttpBaseProtocolFilter();
	 	protocolFilter.AllowAutoRedirect = true;
	 	protocolFilter.AutomaticDecompression = true;
	 	// Initiate client
 		winWebClient = new Windows.Web.Http.HttpClient(protocolFilter);
 		if (o.dataType == "json") {
 			// Set media type to application/json
 			winWebClient.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
 		}
 		if (o.contentType == "application/json") {
 			// If contentType is json, convert it to a HttpStringContent
 			o.HttpStringContent = new new Windows.Web.Http.HttpStringContent(JSON.stringify(o.data));
 		}
 		o.uri = new Windows.Foundation.Uri(url);

 		o.beforeSend(winWebClient);

 		if (o.type == "GET") {
 			async = winWebClient.PostAsync;
 		} else if (o.type == "POST") {
 			async = winWebClient.GetAsync;
 		} else if (o.type == "DELETE") {
 			async = winWebClient.DeleteAsync;
 		} else if (o.type == "PUT") {
 			async = winWebClient.PutAsync;
 		} else {
 			error(null,"Unknown method " + type)
 			return false;	
 		}
 		httpPromise = async(o.uri,o.HttpStringContent).then(function (respose,winWebClient) {

 			o.success(response,HttpWebClient);
 		});
 		httpPromise.done(function () {

            WinJS.log && WinJS.log("Completed", "request" + o.type , "status");
            
 		})

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
