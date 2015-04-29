<?php

class Album{

	public $fetched = false;

	public function __construct ($albumId){

		$this->db = Database::getInstance();

		$getAlbum = $this->db->prepare('
			SELECT	*
			FROM	Albums
			WHERE	Albums."albumId" = :albumId
		');

		$getAlbum->execute([ "albumId" => $albumId ]);

		
		if( $album = $getAlbum->fetch() ){
			foreach( $album as $key => $val ){
				$this->{$key} = $val;
			}

			$this->owner = new Account($this->userId);
		}else{
			die("Invalid album Id");
		}
	}

	public function updateName($newName){

		// set new creationDate
		$stmt = $this->db->prepare('
			UPDATE	Albums
			SET		name = :name
			WHERE	"albumId" = :albumId
		');

		if( $stmt->execute([
			"name" => $newName,
			"albumId" => $this->albumId
		]) ){
			$this->name = $newName;
			return true;
		}
		return false;
	}

	public function publish(){

		// set new creationDate
		$stmt = $this->db->prepare('
			UPDATE	Albums
			SET		published = true
			WHERE	"albumId" = :albumId
		');

		if( $stmt->execute([
			"albumId" => $this->albumId
		]) ){
			$this->published = true;
			return true;
		}
		return false;
	}

	public function getPhotos(){

		$getPhotos = $this->db->prepare('
			SELECT	Photos."photoId", Photos."caption"
			FROM	Photos
			WHERE	Photos."albumId" = :albumId
		');

		$getPhotos->execute([
			"albumId" => $this->albumId
		]);

		return $getPhotos->fetchAll();
	}

	public function addPhoto($file, $caption){


		// Read image
		$img = fopen($file, 'rb') or die("cannot read image\n");

		$imgData = getimagesize($file);

		// Insertion query
		$addPhoto = $this->db->prepare('
			INSERT INTO Photos ("albumId", "imgType", "caption", "data")
			VALUES (:albumid, :imgType, :caption, :data)
		');

		$addPhoto->bindParam(":albumid", $this->albumId);
		$addPhoto->bindParam(":imgType", $imgData['mime']);
		$addPhoto->bindParam(":caption", $caption);
		$addPhoto->bindParam(":data", $img, PDO::PARAM_LOB);


		// Insert
		$this->db->beginTransaction();
		$inserted = $addPhoto->execute();
		$this->db->commit();

		if( $inserted ){
			// Fetch
			return $this->db->lastInsertID('"photos_photoId_seq"');
		}else{
			return false;
		}
	}

	public function delete(){

		$delAlbum = $this->db->prepare('
			DELETE FROM Albums
			WHERE Albums."albumId" = :albumId
		');

		return $delAlbum->execute([
			"albumId" => $this->albumId
		]);
	}
}

?>