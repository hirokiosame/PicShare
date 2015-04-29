<?php

class Account{

	public $signed = false;

	public function __construct ($userId){

		$this->db = Database::getInstance();

		if( isset($userId) && is_numeric($userId) ){

			$getUser = $this->db->prepare('SELECT * FROM Users WHERE "userId" = :userId;');

			$getUser->execute([ "userId" => $userId ]);

			$account = $getUser->fetch();

			foreach( $account as $key => $val ){
				$this->{$key} = $val;
			}

			$this->signed = true;
		}
	}

	public function login($email, $password){

		if( isset($email) && isset($password) ){

			$login = $this->db->prepare("
				SELECT *
				FROM Users
				WHERE	email = :email AND
						password = :password
				LIMIT 1;
			");

			$login->execute([
				"email" => $email,
				"password" => $password
			]);


			if( $account = $login->fetch() ){

				// Add to class instance
				foreach( $account as $key => $val ){
					$this->{$key} = $val;
				}

				$this->signed = true;
			}
		}

		if( $this->signed ){

			// Set to Session
			$_SESSION['userId'] = $this->userId;

			return true;
		}elseif( isset($_SESSION['userId']) ){

			session_unset($_SESSION['userId']);
		}

		return false;
	}

	public function getName(){
		return $this->firstName ." ". $this->lastName;
	}

	public function addFriend($id){


		if( $this->userId < $id ){
			$userId1 = $this->userId;
			$userId2 = $id;
		}else{
			$userId1 = $id;
			$userId2 = $this->userId;
		}
		
		$stmt = $this->db->prepare('
			INSERT INTO Friends ("userId1", "userId2")
			VALUES (:userId1, :userId2)
		');

		return $stmt->execute([ "userId1" => $userId1, "userId2" => $userId2 ]);
	}


	public function checkFriends($id){

		if( $this->userId < $id ){
			$userId1 = $this->userId;
			$userId2 = $id;
		}else{
			$userId1 = $id;
			$userId2 = $this->userId;
		}

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


	public function getFriends(){
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

		$friends = array();

		while( $row = $stmt->fetch() ){

			$friend = $row['userId1'] === $this->userId ? $row['userId2'] : $row['userId1'];
			
			$fStmt = $this->db->prepare('
				SELECT	"userId", "firstName", "lastName"
				FROM	Users
				WHERE	"userId" = :userId;
			');

			$fStmt->execute([ "userId" => $friend ]);

			$friend = $fStmt->fetch();
			
			$friends[] = '<a href="/profile.php?id='. $friend['userId'] .'">'. $friend['firstName'] .' '. $friend['lastName'] .'</a>';
		}

		return $friends;
	}

	public function getUnpublishedAlbum(){

		$getAlbum = $this->db->prepare('
			SELECT Albums.*
			FROM Users, Albums
			WHERE	Users."userId" = :userId AND
					Users."userId" = Albums."userId" AND
					Albums.published = false
		');

		$getAlbum->execute([ "userId" => $this->userId ]);


		if( $getAlbum->rowCount() === 1 ){
			$album = $getAlbum->fetch();

			return new Album($album['albumId']);

		// If doesn't exist, create
		}else{

			// Insert
			$stmt = $this->db->prepare('
				INSERT INTO Albums ("userId")
				VALUES (:userId)
			');

			$stmt->execute([ "userId" => $this->userId ]);

			return new Album($this->db->lastInsertID('"albums_albumId_seq"'));
		}
	}

	public function getAlbums(){

		$getAlbums = $this->db->prepare('
			SELECT	Albums.*, MIN(Photos."photoId") AS "photoId"
			FROM	Albums, Photos
			WHERE	:userId = Albums."userId" AND
					Albums."albumId" = Photos."albumId"
			GROUP BY Albums."albumId"
		');

		$getAlbums->execute([ "userId" => $this->userId ]);

		$albums = array();
		while( $album = $getAlbums->fetch() ){
			$albums[] = Template::view("gridBox", [
				'/album.php?id='. $album['albumId'],
				'/image.php?id=' . $album['photoId'],
				$album['name']
			]);
		}

		return $albums;
	}

	public function getTagAlbums(){

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

		$albums = array();
		while( $album = $getTags->fetch() ){

			$albums[] = Template::view("gridBox", [
				"/search.php?type=tag&q=". $album['tag'] ."&user=" . $this->userId,
				'/image.php?id=' . $album['photoId'],
				$album['tag']
			]);
		}
		return $albums;
	}
}

?>