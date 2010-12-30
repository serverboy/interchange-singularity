<?php

/*
Serverboy Interchange

Copyright 2010 Serverboy Software; Matt Basta

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

*/

require("./constants.php");

$location = dirname(__FILE__);
define('IXG_PATH_PREFIX', $location . (strlen($location) > 1 ? '/' : ''));

require("helpers/keyval.php");

$libraries = array();

require('procedures/local_files.php');

define('PATH_PREFIX', IXG_PATH_PREFIX . 'endpoint');

if(!defined("NOSESSION")) {
	require('sessionmanager.php');
	$session = new session_manager();
}
require('views.php');
require('procedures/libraries.php');
require('pipes.php'); // Must be loaded after libraries.


// This is taken from url_parser.php in the base distro
$protocol = ((int)$_SERVER['SERVER_PORT']==443)?"https":"http";
$domain = $_SERVER['HTTP_HOST'];
$path = $_SERVER['REQUEST_URI'];
$url = "$protocol://$domain$path";
if(strpos($path, '?') !== false)
	$path = substr($path, 0, strpos($path, '?'));
define("TRAILING_SLASH", substr($path, -1) == '/');
$path = explode('/', $path);
$new_path = array();
foreach($path as $p) {
	if($p == '..')
		die('Invalid path.');
	elseif(empty($p) || $p == '.')
		continue;
	$new_path[] = $p;
}
$path = $new_path;
unset($new_path);

if($path_len = count($path))
	$final_path = urldecode($path[$path_len - 1]);
else
	$final_path = '';
if(strpos($final_path, '.') !== false) {
	$expl = explode('.', $final_path);
	define('EXTENSION', strtolower($expl[count($expl)-1]));
} else
	define('EXTENSION', '');
define('PROTOCOL', $protocol);
define('DOMAIN', $domain);
define('FILENAME', $final_path);
define('URL', $url);


$method_level = 0;
$path_name = '';
// $path_len = count($path); // Defined above
$method_base = null;
$mathod_name = '';
$method_arguments = array();

function load_defaultmethodfile() {
	global $path_name;
	if(!is_file($path = PATH_PREFIX . "$path_name/__default.methods.php"))
		return false;
	return load_methodfile($path);
}

function load_methodfile($path) {
	global $method_base, $method_level;
	
	// Load in the stuff methodical endpoints use.
	require(IXG_PATH_PREFIX . "procedures/methodical_requirements.php");
	
	// If something gets fried, don't stop the world.
	try {
		// Load up the PHP file that contains the methods. Note that we
		// can just do this because the URL parser sanitizes everything
		// for us.
		require($path);
		
		// If the file implements full methodical functionality, proceed
		// with the methodical flow. Otherwise, just exit.
		if(class_exists('methods')) {
			// The methods file should implement the class "methods" based on
			// the abstract class "methods_base"
			$method_base = new methods();
			$method_level++;
		} else {
			// The PHP file was loaded, but there was no methodical
			// code, so we assume it ran successfully.
			exit;
		}
		
	} catch(Exception $e) {
		return false;
	}
	return true;
}

if($path_len) {
	// Loop through each of the path segments
	for($i=0;$i<$path_len;$i++) {
		$pathlet = $path[$i];
		
		// Let's assume that the user is referring to the file:
		// /endpoints/endpoint_name/path/so/far/whatever_this_segment_is
		$possible_match = PATH_PREFIX . "$path_name/$pathlet";
		
		switch($method_level) {
			case 0: // Seek Files
				
				// If it's a file (without a PHP extension), load it like a static endpoint
				if(is_file($possible_match) && substr($pathlet, -4) != ".php") {
					// If it can be loaded, we're done. Make sure we don't load it as a
					// directory.
					// This should match the code to load a static file in
					// /procedures/local_files.php
					load_local_file($possible_match, EXTENSION, false);
					exit; // We're done, so break
				
				// If it's a directory, we're looking for a class within it. Append the
				// search path with the current segment and continue searching.
				} elseif(is_dir($possible_match)) {
					$path_name .= "/$pathlet";
					break;
				
				// If it's a file (ending in .methods.php), start the proverbial car because
				// we're probably going to wind up with a methodical endpoint.
				} elseif(is_file($possible_match . ".methods.php") && $pathlet != "__default") {
					
					if(load_methodfile($possible_match . ".methods.php"))
						break;
					
					break 2;
					
				// Try to load the default method file
				} elseif(load_defaultmethodfile()) {
					
					// We didn't consume this pathlet, so look for it as a method.
					$i--;
					continue;
					
				}
				
				// Nothing was found that applies.
				break 2;
			case 1: // Seek Functions
				// Disallow access to magic functions
				if(substr($pathlet, 0, 2) == "__")
					break 2;
				
				if(method_exists($method_base, $pathlet) || method_exists($method_base, $pathlet = "_$pathlet")) {
					$method_name = $pathlet;
					$method_level++;
					continue;
				} elseif(method_exists($method_base, "__default")) {
					$method_name = "__default";
					$method_level++;
					$i--; // Pull this back in as an argument
					continue;
				} else {
					load_page("404", 404);
					exit;
				}
				break 2;
			case 2: // Seek Arguments
				// Any further arguments after a method has already been chosen
				// are used as arguments to the method.
				$method_arguments[] = $pathlet;
		}
	}
}

// Try one last-ditch attempt to load a default file.
if($method_level == 0)
	load_defaultmethodfile();

// If the methods class implements a default method, call that and don't fail.
if($method_level == 1 && method_exists($method_base, '__default')) {
	$method_name = '__default';
	$method_level++;
}

# TODO : This might not fire if the error occurs on the last item;
// Throw a 404 if the URL doesn't specify a class and method
if($method_level < 2)
	load_page("404", 404);
else {
	// Otherwise, call the method in the class
	$result = call_user_func_array(
		array(
			$method_base,
			$method_name
		), $method_arguments
	);
	if($result !== false) {
		// This should have Django-like output (HttpResponse, etc.)
		$result->output();
	}
}
