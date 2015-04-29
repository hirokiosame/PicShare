<?php

require_once("class.PicShare.php");

$PicShare = new PicShare(0);

ob_start();
?>
<div class="page">
	<div class="title">Browse Albums</div>
<?php

	$offset = $PicShare->account->signed ? 1 : 0;

	if($PicShare->account->signed){

		// new Album button

		print( Template::view("dropBox", ["album.php"]) );

	}


	$stmt = $PicShare->db->prepare('
		SELECT		Albums."albumId",
					Albums."name",
					Albums."creationDate",
					MIN(Photos."photoId") AS "photoId"
		FROM		Albums, Photos
		WHERE		Albums."published" = true AND
					Albums."albumId" = Photos."albumId"
		GROUP BY	Albums."albumId"
		ORDER BY	Albums."creationDate" DESC
	');

	$stmt->execute();


	while( $album = $stmt->fetch() ){
		print( Template::view("gridBox", [
			'album.php?id=' . $album['albumId'],
			'image.php?id=' . $album['photoId'],
			$album['name']
		]) );
	}

	for($i = 0; $i < (4*3) - ($offset + $stmt->rowCount()); $i++){


		print( Template::view("gridBox", [
			'#',
			'',
			''
		]) );

	} ?>

</div>
<?php
	print( $PicShare->createPage(ob_get_clean()) );
?>
