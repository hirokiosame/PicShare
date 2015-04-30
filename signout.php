<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: signout.php
// Description: Sign-out page - kills the login session

require_once("class.PicShare.php");

// Must be logged in to access this page
$PicShare = new PicShare(1);
	
// Kill the session
session_destroy();

// Redirect to index page
header("Location: /");

?>