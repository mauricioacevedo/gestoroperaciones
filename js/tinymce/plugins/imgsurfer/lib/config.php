<?php
	
	     error_reporting(E_ALL);
        ini_set('display_errors', '0');

	// FICHERO DE CONFIGURACION
	
	/*
	 * aqui se configuran las rutas del directorio de imagenes
	 * BASE_RUTA es una ruta física y 
	 * BASE_RUTA_HTTP es una ruta web que es la que es guardara en el
	 * editor y por tanto en la base de datos
	 * Ejemplo:
	 * define(BASE_RUTA, '/home/www/miweb.com/uploads/');
	 * define(BASE_RUTA_HTTP, 'http://www.miweb.com/uploads/');
	*/

	$rutabase = getcwd();
	$rutabase = substr($rutabase, 0 ,strrpos($rutabase,'plugins/imgsurfer')).'imagenesTips/';

	$rutahttp = $_SERVER['REQUEST_URI'];
	$rutahttp = 'http://10.100.82.125/gestoroperaciones/js/tinymce/imagenesTips/';
					//http://10.100.82.125/gestoroperaciones-dev/js/tinymce/imagenesTips/

	//echo $rutahttp;
		
	define(BASE_RUTA, $rutabase);
	define(BASE_RUTA_HTTP, $rutahttp);
	
	// si no tienes clara la ruta fisica, descomenta esto y
	// usa el plugin, veras el inicio de la ruta que necesitas
	//echo $_SERVER['DOCUMENT_ROOT'];

	//$ruta = $_SERVER['DOCUMENT_ROOT'];

	/*$ruta = getcwd();
	$ruta = substr($ruta, 0 ,strrpos($ruta,'plugins/imgsurfer')).'imagenesTips/';
	echo($ruta);*/
	//echo strrpos($ruta,'plugins/imgsurfer');
	//pruebas
	//echo getcwd() . "\n";
	//echo realpath()."\n";
  	//echo $_SERVER['REQUEST_URI'];
  	//echo $partes_ruta['DIRNAME'], "\n";

	//echo "1) ".dirname(getcwd(),1).PHP_EOL."\n";
	//echo dirname("/usr/local/lib", 2);
	//echo "1) ".basename(getcwd()).PHP_EOL."\n";
	//$page_directory = basename(__PATH__);
	//echo $page_directory;


?>
