<?php
header('Content-disposition: attachment; filename="webkul_sitemap.xml"');
header('Content-type: "text/xml"; charset="utf8"');
readfile('webkul_sitemap.xml');
exit();
?>