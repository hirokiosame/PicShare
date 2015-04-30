<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: class.Template.php
// Description: File that contains the presentation layer functions


// Template class -- the presentation layer
class Template{

	// Get the HTML template file and pass inputs in
	public static function view($name, $args = []){

		// Get the template file
		$opened = file_get_contents("views/" . $name . ".html");

		// Format the template with the argument values passed in
		$filled = call_user_func_array("sprintf", array_merge([$opened], $args));

		// return formatted template
		return $filled;
	}

	// Function that creates an unordered list out of the array of list elements
	public static function ul($lis = []){

		// Initiate unordered list
		$list = '<ul>';

		// For every list element, wrap in li tag and link
		foreach( $lis as $key => $val ){
			$list .= '<li><a href="' . $val . '">' . $key . '</a></li>';
		}

		return $list . '</ul>';
	}
}
?>