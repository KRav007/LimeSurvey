<!doctype html>
<html>
    <head>
<?php 
$path = getTemplatePath($template);
echo templatereplace(file_get_contents($path . '/startpage.pstpl'), array());
echo templatereplace(file_get_contents($path . '/startgroup.pstpl'), array());
echo $contents;
echo templatereplace(file_get_contents($path . '/endgroup.pstpl'), array());
echo templatereplace(file_get_contents($path . '/endpage.pstpl'), array());
?>
</html>