<?php

function getConnPortalbd(){

        //$db = new mysqli('10.100.82.125', 'root', '123456', 'test');
	$Host="10.100.82.125";
	$User="root";
	$Pwd="123456";
	$Bd="portalbd";

        $db = new mysqli($Host, $User, $Pwd, $Bd);

        if($db->connect_errno > 0){
            die('Unable to connect to database [' . $db->connect_error . ']');
        }

	return $db;

}

?>
