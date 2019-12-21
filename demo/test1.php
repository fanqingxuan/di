<?php

require_once '../vendor/autoload.php';

use Json\Request;

$request = new Request;

print_r($request->getUploadedFiles()[0]->moveTo("./a.txt"));
?>

<form action="?" method="post" enctype="multipart/form-data">
<input type="file" name="a"/>
<input type="submit" value="upload"/>
</from>