<?php

require_once("class.PicShare.php");

$PicShare = new PicShare(0);

ob_start();
?>
<div class="page white">
	<div class="title">Top 3 tags</div>
<?php

// , Photos, Albums
// 	WHERE	TaggedPhotos."photoId" = Photos."photoId" AND
// 			Photos."albumId" = Albums."albumId" AND
// 			Albums.published = true
// $getTags = $PicShare->db->prepare('
// 	SELECT		TaggedPhotos."tagId", COUNT(TaggedPhotos."tagId") AS freq, Tags.tag
// 	FROM		TaggedPhotos
// 	LEFT JOIN	Tags
// 	ON			TaggedPhotos."tagId" = Tags."tagId"
// 	GROUP BY	TaggedPhotos."tagId", Tags.tag
// 	ORDER BY	freq DESC
// 	LIMIT 3
// ');

	// WHERE		TaggedPhotos."photoId" = Photos."photoId" AND
	// 			Photos."albumId" = Albums."albumId" AND
	// 			Albums.published = true

$getTags = $PicShare->db->prepare('
	SELECT	TaggedPhotos."tagId", COUNT(TaggedPhotos."tagId") AS freq, Tags.tag
	FROM	(
				SELECT	TaggedPhotos."tagId"
				FROM	TaggedPhotos, Photos, Albums
				WHERE	TaggedPhotos."photoId" = Photos."photoId" AND
						Photos."albumId" = Albums."albumId" AND
						Albums.published = true
			) AS TaggedPhotos
			LEFT JOIN	Tags 
			ON			TaggedPhotos."tagId" = Tags."tagId"
	GROUP BY	TaggedPhotos."tagId", Tags.tag
	ORDER BY	freq DESC
	LIMIT 3
');

$getTags->execute();

while( $tag = $getTags->fetch() ){

	print('<div class="title"><a href="/search.php?type=tag&q=' . $tag['tag'] . '">' . $tag['tag'] . '</a> ' . $tag['freq'] . ' photos</div>');

	$getPhotos = $PicShare->db->prepare('
		SELECT	Photos.*
		FROM	TaggedPhotos, Photos, Albums
		WHERE	TaggedPhotos."tagId" = :tagId AND
				TaggedPhotos."photoId" = Photos."photoId" AND
				Photos."albumId" = Albums."albumId" AND
				Albums.published = true
		LIMIT	4
	');

	$getPhotos->execute([ "tagId" => $tag['tagId'] ]);

	$i = 4;
	while( $i-- ){
		if( $photo = $getPhotos->fetch() ){
			print( Template::view("gridBox", [
				'photo.php?id=' . $photo['photoId'],
				'/image.php?id=' . $photo['photoId'],
				$photo['caption']
			]) );
		}else{
			print( Template::view("gridBox", [
				'#',
				'',
				''
			]) );
		}
	}

	print("<br>");
}
?></div>
<?php
	print( $PicShare->createPage(ob_get_clean()) );
?>