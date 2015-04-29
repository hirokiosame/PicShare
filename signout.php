<?php
require_once("class.PicShare.php");
$PicShare = new PicShare(1);
	
session_destroy();

header("Location: index.php");
?>