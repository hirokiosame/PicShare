<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: class.PicShare.php
// Description: Main class for Picshare - mainly administers the user session

// Enable displaying errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);


// Global function
function validateInputs( $arr, $attrs ){
	$type = gettype($arr);
	foreach( $attrs as $attr ){

		if( $type === "object" ){
			if( !isset($arr->{$attr}) || strlen($arr->{$attr})===0 ){ return false; }
		}else{
			if( !isset($arr[$attr]) || strlen($arr[$attr])===0 ){ return false; }
		}
	}
	return true;
}


// Import other classes
require_once("class.Template.php");
require_once("class.Database.php");
require_once("class.Account.php");
require_once("class.Photo.php");
require_once("class.Album.php");



class PicShare{

	// Constructor function
	public function __construct ($priviliges = 0){

		// Start/enable session
		session_start();

		// Get database connection
		$this->db = Database::getInstance();

		// Checked if logged in
		$this->account = new Account( isset($_SESSION['userId']) ? $_SESSION['userId'] : null );

		// Only visible to logged out users
		if( $priviliges === -1 ){

			// If logged in - redirect
			if( $this->account->signed ){
				header("Location: index.php");
			}
		}

		// Can be viewed by anyone
		// if( $priviliges === 0 ){
		// Don't do anything
		// }

		// Only visible to logged in users
		if( $priviliges === 1 ){

			// If logged out - redirect
			if( !$this->account->signed ){
				header("Location: signin.php");
			}
		}
	}


	// Public function to display the webpage
	// Adds the header (logo, menu) and footer before and after the page content
	public function createPage($content){

		// Right-hand menu
		// Check if user is logged in
		if( $this->account->signed ){

			// If logged in, show profile link and signout button 
			$rUl = Template::ul([
				$this->account->firstName . " " . $this->account->lastName => "/profile.php?id=" . $this->account->userId,
				"Sign out" => "/signout.php"
			]);
		}else{

			// If not logged in, show sign in and sign up button
			$rUl = Template::ul([
				"Sign in" => "/signin.php",
				"Sign up" => "/signup.php",
			]);
		}

		// Left-hand menu
		$lUl = Template::ul([
			"Popular tags" => "popular.php",
			"User ranking" => "ranking.php",
			"You may like" => "mayLike.php"
		]);

		// The logo/mast with the left and righthand menu
		$mast = Template::view("mast", [$lUl, $rUl]);

		// Render everything in the layout
		return Template::view("layout", [
			// Title of the website
			"PicShare",

			// Content of the webpage
			$mast . Template::view("page", [$content])
		]);
	}
}

?>