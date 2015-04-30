<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: signin.php
// Description: Signin page for users

require_once("class.PicShare.php");

// Must be logged out to view
$PicShare = new PicShare(-1);

// Make sure the POST request contains email and password parameters
if( validateInputs($_POST, ["email", "password"]) ){

	// Check if login is successful and set session
	if( $PicShare->account->login( $_POST["email"], $_POST["password"] ) ){

		// Redirect if succesful
		header("Location: /");
	}else{

		// If login failed, display error 
		print("Failed to login!");
	}
}

// Print HTML
print( $PicShare->createPage( Template::view("signin") ) );

?>
