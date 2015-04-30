<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: class.Account.php
// Description: Front-page of the website -- displays all of the albums

class Account{

	// Public variable indicating if actually fetched from the table or not
	public $signed = false;

	// Constructor - takes in an account ID
	public function __construct ($userId){

		// Get database connection
		$this->db = Database::getInstance();

		// if an account ID is passed in, fetch!
		if( isset($userId) && is_numeric($userId) ){

			// SELECT query to fetch the account by the ID
			$getUser = $this->db->prepare('SELECT * FROM Users WHERE "userId" = :userId;');

			$getUser->execute([ "userId" => $userId ]);

			$account = $getUser->fetch();

			// Add every attribute as a property of the class
			foreach( $account as $key => $val ){
				$this->{$key} = $val;
			}

			// Mark as signed in
			$this->signed = true;
		}
	}

	// Public login function - sets session to be logged in as the user
	public function login($email, $password){

		// Make sure the email and password are passed in
		if( isset($email) && isset($password) ){

			// Check if there are any users with that email and password
			$login = $this->db->prepare("
				SELECT	*
				FROM	Users
				WHERE	email = :email AND
						password = :password
				LIMIT	1;
			");

			$login->execute([
				"email" => $email,
				"password" => $password
			]);


			// If account successfully fetched
			if( $account = $login->fetch() ){

				// Add every attribute as a property to class instance
				foreach( $account as $key => $val ){
					$this->{$key} = $val;
				}

				// Mark as signed in
				$this->signed = true;
			}
		}

		// If the account successfully signed in
		if( $this->signed ){

			// Set to Session
			$_SESSION['userId'] = $this->userId;

			return true;
		}

		// If not successfully signed in, but has a session
		elseif( isset($_SESSION['userId']) ){

			// Delete session
			session_unset($_SESSION['userId']);
		}

		return false;
	}


	// Public function to get the formatted name of the user
	public function getName(){
		return $this->firstName ." ". $this->lastName;
	}


	// Public function to add the user as a friend
	public function addFriend($id){

		// Find which ID is smaller
		// We put the smaller id in the first column
		if( $this->userId < $id ){
			$userId1 = $this->userId;
			$userId2 = $id;
		}else{
			$userId1 = $id;
			$userId2 = $this->userId;
		}
		
		// Insert the friendship relationship
		$stmt = $this->db->prepare('
			INSERT INTO Friends ("userId1", "userId2")
			VALUES (:userId1, :userId2)
		');

		return $stmt->execute([ "userId1" => $userId1, "userId2" => $userId2 ]);
	}


	// Public function check if another account is a friend of this account
	public function checkFriends($id){

		// Find smaller Id
		if( $this->userId < $id ){
			$userId1 = $this->userId;
			$userId2 = $id;
		}else{
			$userId1 = $id;
			$userId2 = $this->userId;
		}

		// Check if the relationship exists
		$stmt = $this->db->prepare('
			SELECT *
			FROM Friends
			WHERE	"userId1" = :userId1 AND
					"userId2" = :userId2;
		');

		$stmt->execute([
			"userId1" => $userId1,
			"userId2" => $userId2
		]);

		return $stmt->rowCount();
	}

	// Public function to get all the friends of this account
	public function getFriends(){

		// Select query to get all friends
		$stmt = $this->db->prepare('
			SELECT *
			FROM Friends
			WHERE	"userId1" = :userId1 OR
					"userId2" = :userId2;
		');

		$stmt->execute([
			"userId1" => $this->userId,
			"userId2" => $this->userId
		]);

		// Aggregate array
		$friends = array();

		// For each friend
		while( $row = $stmt->fetch() ){

			// Determine which column is the friend
			$friend = $row['userId1'] === $this->userId ? $row['userId2'] : $row['userId1'];
			
			// Fetch information about friend
			$fStmt = $this->db->prepare('
				SELECT	"userId", "firstName", "lastName"
				FROM	Users
				WHERE	"userId" = :userId;
			');

			$fStmt->execute([ "userId" => $friend ]);

			$friend = $fStmt->fetch();

			// Add the friend as a link in the array
			$friends[] = '<a href="/profile.php?id='. $friend['userId'] .'">'. $friend['firstName'] .' '. $friend['lastName'] .'</a>';
		}

		// Return array
		return $friends;
	}


	// Public function to get the current unpublished album by the user
	public function getUnpublishedAlbum(){

		// Select query to check if the user has an unpublished album
		$getAlbum = $this->db->prepare('
			SELECT Albums.*
			FROM Users, Albums
			WHERE	Users."userId" = :userId AND
					Users."userId" = Albums."userId" AND
					Albums.published = false
		');

		$getAlbum->execute([ "userId" => $this->userId ]);

		// If an unpublished album exists
		if( $getAlbum->rowCount() === 1 ){

			// Fetch the album
			$album = $getAlbum->fetch();

			// Return it
			return new Album($album['albumId']);

		// If doesn't exist, create
		}else{

			// Insert the new album
			$stmt = $this->db->prepare('
				INSERT INTO Albums ("userId")
				VALUES (:userId)
			');

			$stmt->execute([ "userId" => $this->userId ]);

			// Return new album
			return new Album($this->db->lastInsertID('"albums_albumId_seq"'));
		}
	}


	// Public function to get all albums by a user
	public function getAlbums(){

		// Select all the albums owned by the user
		$getAlbums = $this->db->prepare('
			SELECT	Albums.*, MIN(Photos."photoId") AS "photoId"
			FROM	Albums, Photos
			WHERE	:userId = Albums."userId" AND
					Albums."albumId" = Photos."albumId"
			GROUP BY Albums."albumId"
		');

		$getAlbums->execute([ "userId" => $this->userId ]);

		// Aggregate albums in array
		$albums = array();

		// For every album
		while( $album = $getAlbums->fetch() ){

			// Add the HTML of the album
			$albums[] = Template::view("gridBox", [
				'/album.php?id='. $album['albumId'],
				'/image.php?id=' . $album['photoId'],
				$album['name']
			]);
		}

		// Return the albums
		return $albums;
	}

	// Public function to get all tags used by the user
	public function getTagAlbums(){

		// Select query to get all tags used by the user
		$getTags = $this->db->prepare('
			SELECT	Tags.*, MIN(Photos."photoId") AS "photoId"
			FROM	Albums, Photos, TaggedPhotos, Tags
			WHERE	:userId = Albums."userId" AND
					Albums."albumId" = Photos."albumId" AND
					Photos."photoId" = TaggedPhotos."photoId" AND
					TaggedPhotos."tagId" = Tags."tagId"
			GROUP BY Tags."tagId"
		');

		$getTags->execute([ "userId" => $this->userId ]);

		// Aggregate array
		$albums = array();

		// For each "album" by tags
		while( $album = $getTags->fetch() ){

			// Add the HTML of the album
			$albums[] = Template::view("gridBox", [
				"/search.php?type=tag&q=". $album['tag'] ."&user=" . $this->userId,
				'/image.php?id=' . $album['photoId'],
				$album['tag']
			]);
		}

		// Return array of albums
		return $albums;
	}
}

?>