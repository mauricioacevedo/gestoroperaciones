<?php

function getConnFenix(){
	$host="10.120.53.129";
	$user="ebarrien";
	$pwd="eb4rr1en";

	putenv("NLS_LANG=LATIN AMERICAN SPANISH_AMERICA.WE8ISO8859P9");
	putenv("NLS_DATE_FORMAT=DD/MM/RRRR");

	$db = "(DESCRIPTION =
	(ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = 1521))
	(CONNECT_DATA =
	(SERVER = DEDICATED)
	(SERVICE_NAME = FENIXUNE)
	)
	)";


	// Connects to the XE service (i.e. database) on the "localhost" machine
	$conn = oci_connect($user, $pwd, $db);

	if (!$conn) {
	    $e = oci_error();
	    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
	    echo "ERROR: $e";
	    return;
	}

	return $conn;

}

function getConnFenixBogota(){
        $host="10.120.53.129";
        $user="ebarrien";
        $pwd="ebarrien1";

        putenv("NLS_LANG=LATIN AMERICAN SPANISH_AMERICA.WE8ISO8859P9");
        //putenv("NLS_LANG=LATIN AMERICAN SPANISH.AMERICAN");

        $db = "(DESCRIPTION =
        (ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = 1521))
        (CONNECT_DATA =
        (SERVER = DEDICATED)
        (SERVICE_NAME = FENIXUNE)
        )
        )";


        // Connects to the XE service (i.e. database) on the "localhost" machine
        $conn = oci_connect($user, $pwd, $db);

        if (!$conn) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
            echo "ERROR: $e";
            return;
        }

        return $conn;

}



?>
