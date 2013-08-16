/*
                    COPYRIGHT

Copyright 2013 Stijn Van Campenhout <stijn.vancampenhout@gmail.com>

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
// Compile: valac --pkg json-glib-1.0 --pkg libsoup-2.4 --library=JsonRPC2 -H JsonRPC2.h jsonrpc2.vala -X -fPIC -X -shared -o JsonRPC2.so
// Tested with ubuntu 13.04
// for debian/Ubuntu : sudo apt-get install libjson-glib-1.0 libjson-glib-1.0 libsoup-2.4 libsoup-2.4-dev #I think

using Soup;
using Json;
namespace JsonRPC2 {
	public class JsonRPC2client {
		public Message msg;
		public string host;
		private string remoteClass;
		private bool debug;
		private string[] auth;
		private string RpcSessionId;
		private int requestId;
		public SessionAsync Session;
		/**
		* JsonRPC2client
		*
		* Constructor
		* 
		* @param url	string containing the url of the endpoint (server)
		* @param rClass string containing the remote class name
		**/
		public JsonRPC2client (string url, string rClass) {
			Session = new SessionAsync();
			host = url;
			remoteClass = rClass;
			debug = false;
			auth = {"",""};
			requestId = 0;
		}
		/**
		* authenticate
		*
		* Sets the authentication parameters.
		* Authentication will only be executed when these parameters
		* are set.
		* 
		* @param username	string containing the username for auth
		* @param password	string containing the password for auth
		**/
		public void authenticate(string username,string password){
			auth = {username,password};
		}
		/**
		* setDebug
		* 
		* Enables/Disabled debug output to stdout
		*
		* @param dbg	bool, true = enables debug
		**/
		public void setDebug(bool dbg){
			debug = dbg;
		}
		/**
		* request
		*
		* Main function. this function makes the resquests to the
		* jsonrpc2php server & returns the result as a raw json string
		* 
		* @param module		the remoteClass function to call
		* @param parameters	an string array containing the parameters
		*					for the remote function
		* @return			a raw json string containing the result
		**/
		public string request (string module,string[] parameters){
			size_t lenght;
			string request;
			string returned;

			requestId = requestId + 1;
			var jsonRequest = new Json.Object();
			var jsonRoot = new Json.Node(NodeType.OBJECT);
			var jsonGen = new Generator();
			jsonRoot.set_object(jsonRequest);
			jsonGen.set_root(jsonRoot);
			var jsonParameters = new Json.Array();
			foreach (var p in parameters){
				jsonParameters.add_string_element(p);
			}

			jsonRequest.set_string_member("jsonrpc","2.0");
			jsonRequest.set_string_member("method",remoteClass + "." + module);
			jsonRequest.set_array_member("params",jsonParameters);
			jsonRequest.set_int_member("id",requestId);
			request = jsonGen.to_data(out lenght);
			if (debug)
				stdout.printf("<-- Sending: %s\n", request);
			try {
				msg = new Message("POST" , host);
				msg.request_headers.append("Content-Type", "application/json");
      			if (auth[0] != "" && auth[1] != "" && RpcSessionId == null ){
      				msg.request_headers.append("x-RPC-Auth-Username",auth[0]);
      				msg.request_headers.append("x-RPC-Auth-Password",auth[1]);
      			} else if (auth[0] != "" && auth[1] != "" && RpcSessionId != null ){
      				msg.request_headers.append("x-RPC-Auth-Session",RpcSessionId);
      			}

				msg.request_body.append(MemoryUse.COPY,request.data);
				Session.send_message(msg);
				if (auth[0] != "" && auth[1] != ""){
					RpcSessionId = msg.response_headers.get("x-RPC-Auth-Session");
				}

				returned = (string) msg.response_body.flatten().data;
				if (debug){
					stdout.printf("H-> x-RPC-Auth-Session: %s\n",RpcSessionId);
					stdout.printf("--> Receiving:%s\n",returned);
				}

			} catch (Error e){
				error("ERROR: %s\n",e.message);
			}

			return returned;
		}
	}
}