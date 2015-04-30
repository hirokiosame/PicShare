<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: image.php
// Description: File that fetches a photo given the ID and displays it


require_once("class.PicShare.php");

// Make sure ID parameter is set and is numeric - otherwise throw error
if( !isset($_GET['id']) || !is_numeric($_GET['id']) ){ die("You must provide a photo ID"); }

$PicShare = new PicShare();

// Select photo by ID
$stmt = $PicShare->db->prepare('
	SELECT	"imgType", "data"
	FROM	Photos
	WHERE	"photoId" = :photoId
	LIMIT 1
');

$stmt->execute([ "photoId" => $_GET['id'] ]);

// If the Photo exists
if( $stmt->rowCount() > 0 ){

	// Fetch image
	$stmt->bindColumn("imgType", $imgType);
	$stmt->bindColumn("data", $imgData);
	$row = $stmt->fetch(PDO::FETCH_BOUND);

	// Render image
	header("Content-type: " . $imgType);
	print($imgData);
}

?>