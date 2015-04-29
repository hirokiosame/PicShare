<?php

require_once("class.PicShare.php");

if( isset($_GET['id']) ){

	$PicShare = new PicShare();

	$stmt = $PicShare->db->prepare('
		SELECT	"imgType", "data"
		FROM	Photos
		WHERE	"photoId" = :photoId
	');
	
	$stmt->execute([ "photoId" => $_GET['id'] ]);


	if( $stmt->rowCount() > 0 ){

		// Each row
		$stmt->bindColumn("imgType", $imgType);
		$stmt->bindColumn("data", $imgData);
		$row = $stmt->fetch(PDO::FETCH_BOUND);

		header("Content-type: " . $imgType);
		print($imgData);
	}
}
?>