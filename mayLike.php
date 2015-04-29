<?php

require_once("class.PicShare.php");

$PicShare = new PicShare(0);


$content = '';

$getPhotos = $PicShare->db->prepare('
	SELECT Photos."photoId", Photos.caption, COUNT(Photos."photoId") AS freq
	FROM	TaggedPhotos,
			(
				SELECT Photos."photoId", Photos.caption, COUNT(TaggedPhotos."photoId") AS tags
				FROM Photos, Albums, TaggedPhotos
				WHERE
					:userId != Albums."userId" AND
					Albums."albumId" = Photos."albumId"	AND
					Photos."photoId" = TaggedPhotos."photoId"
				GROUP BY	Photos."photoId"
			) AS Photos
	WHERE	Photos."photoId" = TaggedPhotos."photoId" AND
			TaggedPhotos."tagId" IN (
				SELECT TaggedPhotos."tagId"
				FROM TaggedPhotos, Photos, Albums
				WHERE 	TaggedPhotos."photoId" = Photos."photoId" AND
						Photos."albumId" = Albums."albumId" AND
						Albums."userId" = :userId
				GROUP BY TaggedPhotos."tagId"
				ORDER BY COUNT(TaggedPhotos."photoId") DESC
				LIMIT 5
			)
	GROUP BY Photos."photoId", Photos.caption, Photos.tags
	ORDER BY freq DESC, Photos.tags ASC
');


$getPhotos->execute([
	"userId" => $PicShare->account->userId
]);

while( $photo = $getPhotos->fetch() ){

	// print_r($row);
	$content .= Template::view("gridBox", [
		'/photo.php?id=' . $photo['photoId'],
		'/image.php?id=' . $photo['photoId'],
		$photo['caption']
	]);
}

ob_start();
?>
<div class="page white">
	<div class="title">Photos you make like</div>
	<?=$content?>
</div>
<?php
	print( $PicShare->createPage(ob_get_clean()) );
?>