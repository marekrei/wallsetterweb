<?php 
include_once("inc/config.php");
include_once("inc/db.php");
DB::init();
DB::install();
print "Finished install";
?>