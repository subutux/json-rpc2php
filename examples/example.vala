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
// Compile with: valac --pkg json-glib-1.0 --pkg libsoup-2.4 ../JsonRPC2.vapi test.vala -X ../JsonRPC2.so -X -I ../ -o jsonRpc2clientTest
// Run with LD_LIBRARY_PATH=../ ./test
// or put the JsonRPC2.so in /usr/lib
using JsonRPC2;
void main(){
	// register a new class, same usage as the php client.
	var myClass = new JsonRPC2.JsonRPC2client("http://localhost/rpc/index.php","myClass");
	// debug is disabled by default.
	myClass.setDebug(true);
	// To use authentication, call this.
	//myClass.authenticate("user","password");
	//parameters are stored in a single dimentional array
	string[] parameters = {"testing the params"};
	//get the request.
	// for now, this is a raw json string.
	// you can convert this with the json-glib-1.0 library
	// wich is also used in the JsonRPC library. 
	var myRequest = myClass.request("ping",parameters);
}
