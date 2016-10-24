<?php
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
	
	include_once("/var/www/html/gestoroperaciones/connections.php");

	date_default_timezone_set('America/Bogota');
	//$o = new testi;
	//$o->doit();
                        $connf= getConnFenix();

                        //2. verifico el estado del pedido en fenix y actualizo estado en base de datos local si es necesario
                                /*$sqlfenix="SELECT ".
                                "  SOL.ESTADO_BLOQUEO".
                                ",  SOL.USUARIO_ID".
                                " FROM FNX_SOLICITUDES SOL".
                                "     WHERE ".
                                "     SOL.PEDIDO_ID='$pedido_id'".
                                "      AND SOL.SUBPEDIDO_ID='$subpedido_id'".
                                "      AND SOL.SOLICITUD_ID='$solicitud_id'".
                                "      AND ROWNUM=1";*/

                                $sqlfenix="     SELECT ".
                                "       SOL.ESTADO_BLOQUEO, SOL.USUARIO_ID ".
                                "       FROM FNX_SOLICITUDES SOL ".
                                "       WHERE ".
                                "       SOL.PEDIDO_ID='$pedido_id' ".
                                "       AND SOL.ESTADO_BLOQUEO='S' ";

                                //echo  $sqlfenix.", \n ";
                                $stid = oci_parse($connf, $sqlfenix);
                                oci_execute($stid);
                                if($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
                                        return $row;
                                        //echo $concepto_id." - ".$row['CONCEPTO_ID']."\n";
                                }


?>
