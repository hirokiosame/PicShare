<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: popular.php
// Description: Displays top 3 popular photo tags

require_once("class.PicShare.php");

// Anyone can view the page
$PicShare = new PicShare(0);


// Get top 3 most popular tags
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


// Collect html output
$tags = '';

// For each tag
while( $tag = $getTags->fetch() ){

	$tags .= '<div class="title"><a href="/search.php?type=tag&q=' . $tag['tag'] . '">' . $tag['tag'] . '</a> ' . $tag['freq'] . ' photos</div>';

	// Get photos for each tag
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


	// List 4 photos per tag
	$i = 4;
	while( $i-- ){
		if( $photo = $getPhotos->fetch() ){
			$tags .= Template::view("gridBox", [
				'photo.php?id=' . $photo['photoId'],
				'/image.php?id=' . $photo['photoId'],
				$photo['caption']
			]);
		}else{
			$tags .= Template::view("gridBox", [
				'#',
				'',
				''
			]);
		}
	}

	$tags .= "<br>";
}


// Print HTML
ob_start();
?>
<div class="page white">
	<div class="title">Top 3 tags</div>
	<?=$tags?>
</div>
<?php
	print( $PicShare->createPage(ob_get_clean()) );
?>