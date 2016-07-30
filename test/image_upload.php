<?php
?>
<!DOCTYPE html>

<html>

<head>
<title>e家e味</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device_width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="format-detection" content="telephone=no"/>

</head>

<body>
<form enctype="multipart/form-data" action="android/api.php?method=uploadImage" method="POST">
    <!-- MAX_FILE_SIZE must precede the file input field -->
    <input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
    <!-- Name of input element determines name in $_FILES array -->
    Send this file: <input name="image_file" type="file" />
    <input type="submit" value="Send File" />
</form>
</body>
</html>