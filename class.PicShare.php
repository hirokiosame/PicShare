<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);


require_once("class.Template.php");

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


require_once("class.Database.php");

require_once("class.Account.php");

require_once("class.Photo.php");

require_once("class.Album.php");



class PicShare{

	public function __construct ($priviliges = 0){

		session_start();

		$this->db = Database::getInstance();


		// Checked if logged in
		$this->account = new Account( isset($_SESSION['userId']) ? $_SESSION['userId'] : null );


		// If Page Requires you to be Logged out, but you're logged in
		if( $priviliges === -1 && $this->account->signed ){
			header("Location: index.php");
		}
	}


	public function createPage($content){

		if( $this->account->signed ){

			$rUl = Template::ul([
				$this->account->firstName . " " . $this->account->lastName => "/profile.php?id=" . $this->account->userId,
				"Sign out" => "/signout.php"
			]);
		}else{
			$rUl = Template::ul([
				"Sign in" => "/signin.php",
				"Sign up" => "/signup.php",
			]);
		}


		$lUl = Template::ul([
			"Popular tags" => "popular.php",
			"User ranking" => "ranking.php",
			"You may like" => "mayLike.php"
		]);


		$mast = Template::view("mast", [$lUl, $rUl]);

		return Template::view("layout", [
			"PicShare",
			$mast . Template::view("page", [$content])
		]);
	}
}

?>