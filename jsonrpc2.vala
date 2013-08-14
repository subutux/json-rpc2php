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
		public string body;
		private bool debug;
		private string[] auth;
		private string RpcSessionId;
		private int requestId;
		public SessionAsync Session;

		public JsonRPC2client (string url, string rClass) {
			Session = new SessionAsync();
			host = url;
			remoteClass = rClass;
			debug = false;
			auth = {"",""};
			requestId = 0;
		}
		public void authenticate(string username,string password){
			auth = {username,password};
		}
		public void setDebug(bool dbg){
			debug = dbg;
		}

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
				return returned;

			} catch (Error e){
				error("ERROR: %s\n",e.message);
			}
		}
		public void printUrl (string str) {
			stdout.printf("the url: %s\n",str);
			msg = new Message("GET",str);
			Session.send_message(msg);
			body = (string) msg.response_body.flatten().data;
			stdout.printf("the body:\n=========\n%s\n",body);
		}
	}
}