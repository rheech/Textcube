<?php
define('ROOT', '../../../../..');
$IV = array(  
	'POST' => array(
		'id' => array( 'id' ),
		'name' => array( 'string' , 'min' => 0 ,  'max' => 255),
		'rss' => array( 'string' , 'min' => 0 ,  'max' => 255 , 'mandatory' => false),
		'url' => array( 'string' , 'min' => 0 ,  'max' => 255)
	)
);
require ROOT . '/lib/includeForOwner.php';
respondResultPage(updateLink($owner, $_POST));
?>
