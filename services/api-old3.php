<?php
 	//error_reporting(E_ALL);
	//ini_set('display_errors', '1');
	require_once("Rest.inc.php");
        include_once("/var/www/html/gestorasignaciones/conn_fenix.php");
        include_once("/var/www/html/gestorasignaciones/conn_portalbd.php");

        //1. Inicializar conexion fenix y local mysql
        //$connf=getConnFenix();
        //$connm=getConnPortalbd();

	date_default_timezone_set('America/Bogota');

	class API extends REST {
	
		public $data = "";
		
		const DB_SERVER = "10.100.82.125";
		const DB_USER = "root";
		const DB_PASSWORD = "123456";
		const DB = "portalbd";

		private $db = NULL;
		private $mysqli = NULL;
		private $connf = NULL;
		public static $doink=0;
	
		public function __construct(){
			parent::__construct();				// Init parent contructor
			$this->dbConnect();					// Initiate Database connection
		}

		/*
		 *  Connect to Database
		*/
		private function dbConnect(){
			//$this->mysqli = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);
			$this->mysqli = getConnPortalbd();
		}
		//if i need fenix i get it directly!!!!
		private function dbFenixConnect(){
                        //$this->mysqli = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);
                        $this->connf = getConnFenix();
                }	

	
		/*
		 * Dynmically call the method based on the query string
		 */
		public function processApi(){
			$func = strtolower(trim(str_replace("/","",$_REQUEST['x'])));
			
			//debuger code :)
			//$this->response($func,200);
			if((int)method_exists($this,$func) > 0)
				$this->$func();
			else
				$this->response("No, i dont know this service!!  ",404); // If the method not exist with in this class "Page not found".
		}



		private function csvHistoricos(){
                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $login = $this->_request['login'];
                        $fechaIni = $this->_request['fechaIni'];
                        $fechaFin = $this->_request['fechaFin'];

                        $today = date("Y-m-d h:i:s");
                        $filename="Fenix_NAL-$login-$today.csv";
                        $query=" SELECT ".
                        " pedido_id,subpedido_id,solicitud_id,municipio_id, fuente, actividad, fecha_fin, estado,duracion,accion,concepto_final,concepto_anterior,user,idllamada,motivo,nuevopedido,caracteristica ".
                        " from pedidos where ".
			" fecha_fin between '$fechaIni 00:00:00' and '$fechaFin 23:59:59' ";

                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                $fp = fopen("../tmp/$filename", 'w');
                                fputcsv($fp, array('pedido_id','subpedido_id','solicitud_id','municipio_id',' fuente',' actividad',' fecha_fin',' estado', 'duracion','accion','concepto_final','concepto_anterior','user','idllamada','motivo','nuevopedido','caracteristica'));
                                while($row = $r->fetch_assoc()){
                                        //$result[] = $row;
                                        fputcsv($fp, $row);
                                }
                                fclose($fp);

                                $this->response($this->json(array($filename,$login)), 200); // send user details
                        }

                        $this->response('',204);        // If no records "No Content" status

		}
	
		private function csvFenixNal(){
			if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
			$login = $this->_request['login'];

                        $today = date("Y-m-d h:i:s");
			$filename="Fenix_NAL-$login-$today.csv";
                        $query=" SELECT ".
			" b.pedido_id".
			" , b.subpedido_id".
			" , b.solicitud_id".
			" , b.concepto_id".
			" , b.tipo_elemento_id".
			" , b.tipo_trabajo".
			" , b.MUNICIPIO_ID".
			" , (SELECT A.PLAZA FROM tbl_plazas A WHERE A.MUNICIPIO_ID = b.MUNICIPIO_ID LIMIT 1 ) as plaza".
			" , b.UEN_CALCULADA".
			" , b.producto".
			" , b.fecha_cita".
			" ,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(b.fecha_estado)) AS CHAR(255)) as tiempo_cola".
			" FROM  informe_petec_pendientesm b ".
			" WHERE (b.STATUS='PENDI_PETEC' OR b.STATUS='MALO' ) and b.FUENTE='FENIX_NAL'".
			" order by TIMEDIFF(CURRENT_TIMESTAMP(),(b.fecha_estado)) desc";

                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
				$fp = fopen("../tmp/$filename", 'w');
				fputcsv($fp, array('pedido_id','subpedido_id','solicitud_id','concepto_id','tipo_elemento_id','tipo_trabajo','municipio_id','plaza','uen_calculada','producto','fecha_cita','tiempo_cola'));
                                while($row = $r->fetch_assoc()){
                                        $result[] = $row;
					fputcsv($fp, $row);
                                }
				fclose($fp);
				
                                $this->response($this->json(array($filename,$login)), 200); // send user details
                        }
			
                        $this->response('',204);        // If no records "No Content" status

		}

                private function csvFenixBog(){
                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $login = $this->_request['login'];

                        $today = date("Y-m-d h:i:s");
                        $filename="Fenix_BOG-$login-$today.csv";
                        $query=" SELECT ".
                        " b.pedido_id".
                        " , b.subpedido_id".
                        " , b.solicitud_id".
                        " , b.tipo_elemento_id".
                        " , b.tipo_trabajo".
                        " , b.MUNICIPIO_ID".
                        " , (SELECT A.PLAZA FROM tbl_plazas A WHERE A.MUNICIPIO_ID = b.MUNICIPIO_ID LIMIT 1 ) as plaza".
                        " , b.UEN_CALCULADA".
                        " , b.producto".
                        " , b.fecha_cita".
                        " ,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(b.fecha_estado)) AS CHAR(255)) as tiempo_cola".
                        " FROM  informe_petec_pendientesm b ".
                        " WHERE (b.STATUS='PENDI_PETEC' OR b.STATUS='MALO' ) and b.FUENTE='FENIX_BOG'".
                        " order by TIMEDIFF(CURRENT_TIMESTAMP(),(b.fecha_estado)) desc";

                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                $fp = fopen("../tmp/$filename", 'w');
                                fputcsv($fp, array('pedido_id','subpedido_id','solicitud_id','tipo_elemento_id','tipo_trabajo','municipio_id','plaza','uen_calculada','producto','fecha_cita','tiempo_cola'));
                                while($row = $r->fetch_assoc()){
                                        $result[] = $row;
                                        fputcsv($fp, $row);
                                }
                                fclose($fp);

                                $this->response($this->json(array($filename,$login)), 200); // send user details
                        }

                        $this->response('',204);        // If no records "No Content" status

                }

                private function csvActivacion(){
                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $login = $this->_request['login'];

                        $today = date("Y-m-d h:i:s");
                        $filename="Fenix_Activacion-$login-$today.csv";
                        $query=" SELECT ".
			"  REQUERIMIENTO_ID  ".
			" , PEDIDO_ID  ".
			" , SUBPEDIDO_ID  ".
			" , SOLICITUD_ID  ".
			" , TIPO_ELEMENTO_ID  ".
			" , TIPO_TRABAJO  ".
			" , FECHA_ESTADO  ".
			" , ETAPA_ID  ".
			" , ESTADO_ID  ".
			" , COLA_ID  ".
			" , ACTIVIDAD_ID  ".
			" , NOMBRE_ACTIVIDAD  ".
			" , CONCEPTO_ID  ".
                        " ,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_PENDIENTE ".
			" FROM  informe_activacion_pendientesm  WHERE  STATUS ='PENDI_ACTIVACION' ";

                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                $fp = fopen("../tmp/$filename", 'w');
                                fputcsv($fp, array('REQUERIMIENTO_ID','PEDIDO_ID','SUBPEDIDO_ID','SOLICITUD_ID','TIPO_ELEMENTO_ID','TIPO_TRABAJO','FECHA_ESTADO','ETAPA_ID','ESTADO_ID','COLA_ID','ACTIVIDAD_ID','NOMBRE_ACTIVIDAD','CONCEPTO_ID','TIEMPO_PENDIENTE'));
                                while($row = $r->fetch_assoc()){
                                        $result[] = $row;
                                        fputcsv($fp, $row);
                                }
                                fclose($fp);

                                $this->response($this->json(array($filename,$login)), 200); // send user details
                        }

                        $this->response('',204);        // If no records "No Content" status

                }




                private function insertMPedido(){
                       if($this->get_request_method() != "POST"){
                                $this->response('',406);
                        }

			$pedido = json_decode(file_get_contents("php://input"),true);
                        $column_names = array('pedido', 'fuente', 'actividad','estado', 'user','duracion','accion','fecha_inicio','fecha_fin','concepto_final');
                        $keys = array_keys($pedido);
                        $columns = '';
                        $values = '';
                        $fecha_estado='';
			//var_dump($pedido);
			 //$this->response('hola',200);
                        //$fecha_estado=$pedido['pedido']['FECHA_ESTADO'];
                        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
                           if(!in_array($desired_key, $keys)) {
                                        $$desired_key = '';
                                }else{
                                        $$desired_key = $pedido[$desired_key];
                                }
                                $columns = $columns.$desired_key.',';
                                $values = $values."'".$pedido[$desired_key]."',";
                        }
                        $today = date("Y-m-d H:i:s");
                        //$query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado) VALUES(".trim($values,',').",'$fecha_estado')";
                        if(!empty($pedido)){
                                //$concepto_final=$this->updateFenix($pedido);
                                $query = "INSERT INTO pedidos(".trim($columns,',').",source) VALUES(".trim($values,',').",'MANUAL')";
				//echo $query;
                                $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                                //hago la actualizacion en fenix
                                $this->response(json_encode(array("msg"=>"N/A","data" => $today)),200);

                        }else{
                                $this->response('',204);        //"No Content" status
                                //$this->response("$query",200);        //"No Content" status
                        }

		}

		 private function insertPedidoReconfiguracion(){
                       if($this->get_request_method() != "POST"){
                                $this->response('',406);
                        }

                        $pedido = json_decode(file_get_contents("php://input"),true);
                        $column_names = array('pedido', 'fuente', 'actividad','estado', 'user','duracion','fecha_inicio','fecha_fin','PEDIDO_ID','SUBPEDIDO_ID','SOLICITUD_ID','MUNICIPIO_ID','CONCEPTO_ANTERIOR','idllamada','nuevopedido');
                        $keys = array_keys($pedido);
                        $columns = '';
                        $values = '';
                        $fecha_estado='';
                        $fecha_estado=$pedido['pedido']['FECHA_ESTADO'];
                        $iddd=$pedido['pedido']['ID'];

                        $estadum=$pedido['pedido']['estado'];
                        $CONCEPT=$pedido['pedido']['CONCEPTO_ID'];
                        //echo "estado: $estado";

                        $useri=$pedido['pedido']['user'];
                        $username=$pedido['pedido']['username'];

                        $PEDIDO_IDi=$pedido['pedido']['PEDIDO_ID'];
                        $SUBPEDIDO_IDi=$pedido['pedido']['SUBPEDIDO_ID'];
                        $SOLICITUD_IDi=$pedido['pedido']['SOLICITUD_ID'];


                        $sourcee=$pedido['pedido']['source'];
                        if($sourcee==""){
                                $sourcee="AUTO";
                        }

                        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
                           if(!in_array($desired_key, $keys)) {
                                        $$desired_key = '';
                                }else{
                                        $$desired_key = $pedido[$desired_key];
                                }
                                $columns = $columns.$desired_key.',';
                                $values = $values."'".$pedido[$desired_key]."',";
                        }
                        $today = date("Y-m-d H:i:s");
                        $query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado) VALUES(".trim($values,',').",'$fecha_estado')";
                        if(!empty($pedido)){
                                $concepto_final=$this->updateFenix($pedido);
                                $estado=$pedido['pedido']['estado'];
                                //echo "estado: '$estadum'";
                                if($concepto_final=="NO CAMBIO CONCEPTO" && $estadum!="MALO"){
					$concepto_final=$CONCEPT;
                                        //$this->response(json_encode(array("msg"=>"El pedido NO ha cambiado de concepto en Fenix!!!")),200);
                                }

                                if($estadum=="MALO"){
                                        $concepto_final=$CONCEPT;
                                        $query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado,concepto_final,source) VALUES(".trim($values,',').",'$fecha_estado','$concepto_final','$sourcee')";
                                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                                        $sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='$today',STATUS='$estadum',ASESOR='' WHERE ID=$iddd ";
                                        $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);
					$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','RECONFIGURACION','$estadum','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi','ESTUDIO','$concepto_final') ";
					$rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);
                                        //hago la actualizacion en fenix
                                        $this->response(json_encode(array("msg"=>"$concepto_final","data" => $today)),200);


                                } else if($estadum=="VOLVER A LLAMAR" ||$estadum=="PENDIENTE"){//SE DEFINE LLAMADA EN RECONFIGURACION PARA VOLVER A LLAMAR
					
                                        //echo "HORA LLLAMAR: ".$pedido['horaLlamar'];
				
					$programacion="";
					
					if($pedido['horaLlamar']==""){
						$pedido['horaLlamar']="manana";
					}					

					if($pedido['horaLlamar']=="manana"){//este pedido se programo para ser entregado mañana
						$datetime = new DateTime('tomorrow');
						//echo "PROGRAMACION: ".$datetime->format('Y-m-d 08:00:00');
						 $programacion=$datetime->format('Y-m-d 08:00:00');
						//$tomorrow = date("Y-m-d H:i:s");
					}else{//pedido programado para entregarse el dia de hoy, mas tarde
						$today2 = date("Y-m-d ".$pedido['horaLlamar'].":00:00");
						//echo "PROGRAMACION: ".$today2;
						$programacion=$today2;
					}

					$pedido = json_decode(file_get_contents("php://input"),true);
					//$pedido['pedido']['estado']=$estadum." : ".$programacion;
					$pedido['pedido']['estado']=$estadum;
					$pedido['estado']=$estadum." : ".$programacion;
		                        $columns = '';
		                        $values = '';

					 $sqlupdate="update informe_petec_pendientesm set PROGRAMACION='$programacion' WHERE STATUS='PENDI_PETEC' and PEDIDO_ID='".$PEDIDO_ID."' ";

                                        $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);


					//ESTO ES PARA GENERAR NUEVAMENTE EL QUERY DE INSERT CON EL ESTADO CUSTOM... NICE
		                        foreach($column_names as $desired_key){
                				if(!in_array($desired_key, $keys)) {
                                        		$$desired_key = '';
                                		}else{
                                        		$$desired_key = $pedido[$desired_key];
                                		}
                                		$columns = $columns.$desired_key.',';
                                		$values = $values."'".$pedido[$desired_key]."',";
                        		}

					$concepto_final=$CONCEPT;
                                        $query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado,concepto_final,source) VALUES(".trim($values,',').",'$fecha_estado','$concepto_final','$sourcee')";
					//echo $query;
                                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                                        $sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='$today',STATUS='PENDI_PETEC',PROGRAMACION='$programacion',ASESOR='' WHERE ID=$iddd ";
					
                                        $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);
					
					//$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta) values ('$useri','$username','RECONFIGURACION','$concepto_final','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi') ";
					$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','RECONFIGURACION','$estadum','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi','ESTUDIO','$concepto_final') ";
                                        $rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

                                        //hago la actualizacion en fenix
                                        $this->response(json_encode(array("msg"=>"$concepto_final","data" => $today)),200);


                                } else {//CONCEPTO CAMBIO, PASA NORMALMENTE
                                        $query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado,concepto_final,source) VALUES(".trim($values,',').",'$fecha_estado','$concepto_final','$sourcee')";
                                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                                        //cierro el registro en la tabla de automatizacion asignaciones
                                        $sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='$today',CONCEPTO_ID='$concepto_final',STATUS='CERRADO_PETEC',ASESOR='' WHERE ID=$iddd ";
                                        $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);
					//$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta) values ('$useri','$username','RECONFIGURACION','$concepto_final','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi')";
					$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','RECONFIGURACION','$estadum','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi','ESTUDIO','$concepto_final') ";
                                        $rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

                                        //hago la actualizacion en fenix
                                        $this->response(json_encode(array("msg"=>"$concepto_final","data" => $today)),200);

                                }

                        }else{
                                $this->response('',204);        //"No Content" status
                                //$this->response("$query",200);        //"No Content" status
                        }

                }




		private function insertPedido(){
                       if($this->get_request_method() != "POST"){
                                $this->response('',406);
                        }
			
                        $pedido = json_decode(file_get_contents("php://input"),true);
			//2015-09-28: se retira seguimiento....
                        //$column_names = array('pedido', 'fuente', 'actividad','estado','motivo', 'user','duracion','fecha_inicio','fecha_fin','PEDIDO_ID','SUBPEDIDO_ID','SOLICITUD_ID','MUNICIPIO_ID','CONCEPTO_ANTERIOR','caracteristica','motivo_malo');
                        $column_names = array('pedido', 'fuente', 'actividad','estado', 'user','duracion','fecha_inicio','fecha_fin','PEDIDO_ID','SUBPEDIDO_ID','SOLICITUD_ID','MUNICIPIO_ID','CONCEPTO_ANTERIOR','motivo_malo');
                        $keys = array_keys($pedido);
                        $columns = '';
                        $values = '';
			$fecha_estado='';
			$fecha_estado=$pedido['pedido']['FECHA_ESTADO'];
			$iddd=$pedido['pedido']['ID'];

			$estadum=$pedido['pedido']['estado'];
			$useri=$pedido['pedido']['user'];
			$username=$pedido['pedido']['username'];

			$PEDIDO_IDi=$pedido['pedido']['PEDIDO_ID'];
			$SUBPEDIDO_IDi=$pedido['pedido']['SUBPEDIDO_ID'];
			$SOLICITUD_IDi=$pedido['pedido']['SOLICITUD_ID'];

			$CONCEPT=$pedido['pedido']['CONCEPTO_ID'];
			//echo "estado: $estado";
                        $sourcee=$pedido['pedido']['source'];
                        if($sourcee==""){
                                $sourcee="AUTO";
                        }

                        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
                           if(!in_array($desired_key, $keys)) {
                                        $$desired_key = '';
                                }else{
                                        $$desired_key = $pedido[$desired_key];
                                }
                                $columns = $columns.$desired_key.',';
                                $values = $values."'".$pedido[$desired_key]."',";
                        }
			$today = date("Y-m-d H:i:s"); 
                        $query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado) VALUES(".trim($values,',').",'$fecha_estado')";
                        if(!empty($pedido)){
				$concepto_final=$this->updateFenix($pedido);
				$estado=$pedido['pedido']['estado'];
				//echo "estado: '$estadum'";
				if($concepto_final=="NO CAMBIO CONCEPTO" && $estadum!="MALO"){
                                        $this->response(json_encode(array("msg"=>"El pedido NO ha cambiado de concepto en Fenix!!!")),200);
                                }

				if($estadum=="MALO"){
					$concepto_final=$CONCEPT;
					$query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado,concepto_final,source) VALUES(".trim($values,',').",'$fecha_estado','$concepto_final','$sourcee')";
                                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
					$sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='$today',STATUS='$estadum',ASESOR='' WHERE ID=$iddd ";
                                        $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);
                                        //hago la actualizacion en fenix
					//activity feed.
					//$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta) values ('$useri','$username','ASIGNACIONES','$concepto_final','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi') ";
					$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','ASIGNACIONES','$estadum','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi','ESTUDIO','$concepto_final') ";
					
					//echo $sqlfeed;	
					$rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

                                        $this->response(json_encode(array("msg"=>"$concepto_final","data" => $today)),200);

				}else{
					$query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado,concepto_final,source) VALUES(".trim($values,',').",'$fecha_estado','$concepto_final','$sourcee')";
	                                $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                                	//cierro el registro en la tabla de automatizacion asignaciones
        	                        $sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='$today',CONCEPTO_ID='$concepto_final',STATUS='CERRADO_PETEC' WHERE ID=$iddd ";

                	                $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);

					$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta) values ('$useri','$username','ASIGNACIONES','$concepto_final','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi') ";
					$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','ASIGNACIONES','$estadum','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi','ESTUDIO','$concepto_final') ";
					//echo $sqlfeed;	
					$rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);
                                	//hago la actualizacion en fenix
                        	        $this->response(json_encode(array("msg"=>"$concepto_final","data" => $today)),200);

				}
				
				

                        }else{
                                $this->response('',204);        //"No Content" status
                                //$this->response("$query",200);        //"No Content" status
			}

		}

        //Funcion para actualizar Concepto y Fecha estado en PEDIDOS en pendientesm
        //2015-09-17 - Se modifica query para que traiga Concepto y fecha de Novedades - CGONZGO
		private function updateFenix($obj){
			$id=$obj['ID'];
                        $pedido_id=$obj['PEDIDO_ID'];
                        $subpedido_id=$obj['SUBPEDIDO_ID'];
                        $solicitud_id=$obj['SOLICITUD_ID'];
                        $concepto_id=$obj['CONCEPTO_ID'];

			$this->dbFenixConnect(); 
			$connf=$this->connf;
                        //2. verifico el estado del pedido en fenix y actualizo estado en base de datos local si es necesario
                                $sqlfenix="SELECT ".
                                "  NVL((select TO_CHAR(NSO.fecha, 'YYYY-MM-DD HH24:MI:SS') ".
                                " from FENIX.FNX_NOVEDADES_SOLICITUDES NSO ".
                                " where SOL.PEDIDO_ID=NSO.PEDIDO_ID(+) ".
                                "       AND SOL.SUBPEDIDO_ID=NSO.SUBPEDIDO_ID(+) ".
                                "       AND SOL.SOLICITUD_ID=NSO.SOLICITUD_ID(+) ".
                                " and NSO.consecutivo=(select max(a.consecutivo) from FENIX.FNX_NOVEDADES_SOLICITUDES a  ".
                                "     where SOL.PEDIDO_ID=a.PEDIDO_ID(+) ".
                                "       AND SOL.SUBPEDIDO_ID=a.SUBPEDIDO_ID(+) ".
                                "       AND SOL.SOLICITUD_ID=a.SOLICITUD_ID(+))), TO_CHAR(SOL.FECHA_ESTADO,'RRRR-MM-DD hh24:mi:ss')) AS FECHA_ESTADO ".
                                ",  NVL((select NSO.CONCEPTO_ID_ACTUAL ".
                                " from FENIX.FNX_NOVEDADES_SOLICITUDES NSO ".
                                " where SOL.PEDIDO_ID=NSO.PEDIDO_ID(+) ".
                                "       AND SOL.SUBPEDIDO_ID=NSO.SUBPEDIDO_ID(+) ".
                                "       AND SOL.SOLICITUD_ID=NSO.SOLICITUD_ID(+) ".
                                " and NSO.consecutivo=(select max(a.consecutivo) from FENIX.FNX_NOVEDADES_SOLICITUDES a  ".
                                "     where SOL.PEDIDO_ID=a.PEDIDO_ID(+) ".
                                "       AND SOL.SUBPEDIDO_ID=a.SUBPEDIDO_ID(+) ".
                                "       AND SOL.SOLICITUD_ID=a.SOLICITUD_ID(+))), SOL.CONCEPTO_ID) AS CONCEPTO_ID ".
                                " FROM FNX_SOLICITUDES SOL".
                                "     WHERE ".
                                "     SOL.PEDIDO_ID='$pedido_id'".
                                "      AND SOL.SUBPEDIDO_ID='$subpedido_id'".
                                "      AND SOL.SOLICITUD_ID='$solicitud_id'".
                                "      AND ROWNUM=1";
                                //echo  $sqlfenix.", \n ";
                                $stid = oci_parse($connf, $sqlfenix);
                                oci_execute($stid);
                                if($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
                                        //echo $concepto_id." - ".$row['CONCEPTO_ID']."\n";
                                        if($concepto_id!=$row['CONCEPTO_ID']){
						$status="PENDI_PETEC";
						if($row['CONCEPTO_ID']!='PETEC' && $row['CONCEPTO_ID']!='92' && $row['CONCEPTO_ID']!='15' && $row['CONCEPTO_ID']!='OKRED'){//el concepto cambio, actualizo y quito el status de pendiente
							$status="CERRADO_PETEC";
						}
                                                $sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='".$row['FECHA_FINAL']."',CONCEPTO_ID='".$row['CONCEPTO_ID']."',STATUS='$status', ESTUDIOS=ESTUDIOS+1 WHERE ID=$id ";
						//echo $sqlupdate;
                                                //echo $sqlupdate."\n";
                                                $this->mysqli->query($sqlupdate);
						return $row['CONCEPTO_ID'];
                                        }else{//no cambio de concepto, controlar...
                                                //echo $sqlupdate."\n";
						//$sqlupdate="update informe_petec_pendientesm set ESTUDIOS=ESTUDIOS+1,ASESOR='',CONCEPTO_ID='".$row['CONCEPTO_ID']."' WHERE ID=$id ";
						//$this->mysqli->query($sqlupdate);
						//return $row['CONCEPTO_ID'];
						return "NO CAMBIO CONCEPTO";
					}
                                        //echo $row['FECHA_FINAL']."-".$row['CONCEPTO_ID']."\n";
                                }
				return "No rows!!!!";
		}


		private function pedidosPorUser(){
                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $id = $this->_request['userID'];
			$today = date("Y-m-d");
			$query="SELECT id, pedido, fuente, actividad, fecha_fin, estado,duracion,accion,concepto_final from pedidos where user='$id' and fecha_fin between '$today 00:00:00' and '$today 23:59:59'";

                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                while($row = $r->fetch_assoc()){
                                        $result[] = $row;
                                }
                                $this->response($this->json($result), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status
		}
                private function listadoPedidos(){//historico por 1 pedido
                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $fechaini = $this->_request['fecha_inicio'];
                        $fechafin = $this->_request['fecha_fin'];
                        $page = $this->_request['page'];
                        $today = date("Y-m-d");
		
			if($page=="undefined"){
				$page="0";
			}else{
				$page=$page-1;
			}
			$page=$page*100;
			//counter
			$query="SELECT count(*) as counter from pedidos where fecha_fin between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
			$rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
			$counter=0;
                        if($rr->num_rows > 0){
                                $result = array();
                                if($row = $rr->fetch_assoc()){
                                        $counter = $row['counter'];
                                }
                        }


			$query="SELECT id, pedido, fuente, actividad, fecha_fin, estado,duracion,accion,concepto_final,user from pedidos where fecha_fin between '$fechaini 00:00:00' and '$fechafin 23:59:59' order by fecha_fin desc limit 100 offset $page";
                        //echo $query;
                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                while($row = $r->fetch_assoc()){
                                        $result[] = $row;
                                }
                                $this->response($this->json(array($result,$counter)), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status
                }

                private function pendientesPorColaConceptoActivacion(){
                         if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
			
			$queryConcepto="  select  ".
			"  C1.CONCEPTO_ID  ".
			"  , count(*) as CANTIDAD  ".
			"  , sum(if(C1.RANGO_PENDIENTE='Entre 0-2', 1,0)) as 'Entre02',  ".
			"  sum(if(C1.RANGO_PENDIENTE='Entre 3-4', 1,0)) as 'Entre34',  ".
			"  sum(if(C1.RANGO_PENDIENTE='Entre 5-6', 1,0)) as 'Entre56',  ".
			"  sum(if(C1.RANGO_PENDIENTE='Mas de 6', 1,0)) as 'Masde6'  ".
			"  from ( ".
			" SELECT   ".
			"      PP.`PEDIDO_ID`,   ".
			"      PP.`SUBPEDIDO_ID`,   ".
			"      PP.`SOLICITUD_ID`,   ".
			"      PP.`TIPO_ELEMENTO_ID`,   ".
			"      PP.`FECHA_ESTADO`,   ".
			"      PP.`FECHA_FINAL`,   ".
			"      PP.`PRODUCTO`,   ".
			"      PP.`CONCEPTO_ID`,     ".
			"      PP.`RANGO_CARGA`,   ".
			"      PP.`FECHA_CARGA`,   ".
			"      DATE_FORMAT((PP.FECHA_CARGA),'%H') AS HORA_CARGA,   ".
			"      PP.`DIA_CARGA`,   ".
			"      PP.`SEMANA_CARGA`,   ".
			"      PP.`SEMANA_ANO_CARGA`,   ".
			"      PP.`FUENTE`,   ".
			"      PP.`STATUS`,   ".
			"      PP.`VIEWS`   ".
			"      , CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO)) AS CHAR(255)) AS TIEMPO_PENDIENTE_FULL   ".
			"      , CASE   ".
			"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 0 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 2 THEN 'Entre 0-2'   ".
			"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 3 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 4 THEN 'Entre 3-4'   ".
			"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 5 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 6 THEN 'Entre 5-6'   ".
			"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) > 6 THEN 'Mas de 6'   ".
			"      END AS RANGO_PENDIENTE, ".
			" 	PP.COLA_ID ".
			"  FROM informe_activacion_pendientesm PP   ".
			"   where (PP.STATUS= 'PENDI_ACTIVACION' )  ".
			"   ) C1  ".
			"  group by C1.CONCEPTO_ID order by count(*) DESC ";


                        $r = $this->mysqli->query($queryConcepto) or die($this->mysqli->error.__LINE__);

                        $resultConcepto = array();
                        if($r->num_rows > 0){

                                while($row = $r->fetch_assoc()){
                                        $resultConcepto[] = $row;
                                }
                        }

			$queryCola=" select  ".
			"  C1.COLA_ID  ".
			"  , count(*) as CANTIDAD  ".
			"  , sum(if(C1.RANGO_PENDIENTE='Entre 0-2', 1,0)) as 'Entre02',  ".
			"  sum(if(C1.RANGO_PENDIENTE='Entre 3-4', 1,0)) as 'Entre34',  ".
			"  sum(if(C1.RANGO_PENDIENTE='Entre 5-6', 1,0)) as 'Entre56',  ".
			"  sum(if(C1.RANGO_PENDIENTE='Mas de 6', 1,0)) as 'Masde6'  ".
			"  from ( ".
			" SELECT   ".
			"      PP.`PEDIDO_ID`,   ".
			"      PP.`SUBPEDIDO_ID`,   ".
			"      PP.`SOLICITUD_ID`,   ".
			"      PP.`TIPO_ELEMENTO_ID`,   ".
			"      PP.`FECHA_ESTADO`,   ".
			"      PP.`FECHA_FINAL`,   ".
			"      PP.`PRODUCTO`,   ".
			"      PP.`CONCEPTO_ID`,     ".
			"      PP.`RANGO_CARGA`,   ".
			"      PP.`FECHA_CARGA`,   ".
			"      DATE_FORMAT((PP.FECHA_CARGA),'%H') AS HORA_CARGA,   ".
			"      PP.`DIA_CARGA`,   ".
			"      PP.`SEMANA_CARGA`,   ".
			"      PP.`SEMANA_ANO_CARGA`,   ".
			"      PP.`FUENTE`,   ".
			"      PP.`STATUS`,   ".
			"      PP.`VIEWS`   ".
			"      , CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO)) AS CHAR(255)) AS TIEMPO_PENDIENTE_FULL   ".
			"      , CASE   ".
			"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 0 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 2 THEN 'Entre 0-2'   ".
			"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 3 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 4 THEN 'Entre 3-4'   ".
			"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 5 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 6 THEN 'Entre 5-6'   ".
			"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) > 6 THEN 'Mas de 6'   ".
			"      END AS RANGO_PENDIENTE, ".
			" 	PP.COLA_ID ".
			"  FROM informe_activacion_pendientesm PP   ".
			"   where (PP.STATUS= 'PENDI_ACTIVACION' )  ".
			"   ) C1  ".
			"  group by C1.COLA_ID order by count(*) DESC ";
			


                        $r = $this->mysqli->query($queryCola) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $resultCola = array();
                                while($row = $r->fetch_assoc()){
                                        //$row['label']="Concepto ".$row['label'];
                                        $resultCola[] = $row;
                                }
                                $this->response($this->json(array($resultCola,$resultConcepto)), 200); // send user details
                        }

                        $this->response('',204);        // If no records "No Content" status


		}

		private function pendientesPorPlaza(){
			 if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }

			 $queryBogota=" select".
                        " C1.PLAZA".
                        " , count(*) as CANTIDAD".
                        " , sum(if(C1.RANGO_PENDIENTE='Entre 0-2', 1,0)) as 'Entre02',".
                        "   sum(if(C1.RANGO_PENDIENTE='Entre 3-4', 1,0)) as 'Entre34', ".
                        "   sum(if(C1.RANGO_PENDIENTE='Entre 5-6', 1,0)) as 'Entre56', ".
                        "   sum(if(C1.RANGO_PENDIENTE='Entre 7-12', 1,0)) as 'Entre712', ".
                        "   sum(if(C1.RANGO_PENDIENTE='Entre 13-24', 1,0)) as 'Entre1324', ".
                        "   sum(if(C1.RANGO_PENDIENTE='Entre 25-48', 1,0)) as 'Entre2548', ".
                        "   sum(if(C1.RANGO_PENDIENTE='Mas de 48', 1,0)) as 'Masde48' ".
                        " from (SELECT ".
                        "     PP.`PEDIDO`, ".
                        "     PP.`PEDIDO_ID`, ".
                        "     PP.`SUBPEDIDO_ID`, ".
                        "     PP.`SOLICITUD_ID`, ".
                        "     PP.`TIPO_ELEMENTO_ID`, ".
                        "     PP.`FECHA_INGRESO`, ".
                        "     PP.`FECHA_ESTADO`, ".
                        "     PP.`FECHA_FINAL`, ".
                        "     PP.`PRODUCTO`, ".
                        "     PP.`UEN_CALCULADA`, ".
                        "     PP.`ESTRATO`, ".
                        "     PP.`CONCEPTO_ID`, ".
                        "     PP.`MUNICIPIO_ID`, ".
                        "     PP.`DIRECCION_SERVICIO`, ".
                        "     PP.`FECHAINGRESO_SOLA`, ".
                        "     PP.`HORAINGRESO`, ".
                        "     DATE((PP.`FECHAESTADO_SOLA`)) as FECHAESTADO_SOLA, ".
                        "     PP.`HORAESTADO`, ".
                        "     PP.`DIANUM_ESTADO`, ".
                        "     PP.`DIANOM_ESTADO`, ".
                        "     PP.`RANGO_CARGA`, ".
                        "     PP.`FECHA_CARGA`, ".
                        "     DATE_FORMAT((PP.FECHA_CARGA),'%H') AS HORA_CARGA, ".
                        "     PP.`DIA_CARGA`, ".
                        "     PP.`MESNOMBRE_CARGA`, ".
                        "     PP.`MESNUMERO_CARGA`, ".
                        "     PP.`SEMANA_CARGA`, ".
                        "     PP.`SEMANA_ANO_CARGA`, ".
                        "     PP.`ANO_CARGA`, ".
                        "     PP.`FUENTE`, ".
                        "     PP.`STATUS`, ".
                        "     PP.`VIEWS` ".
                        "     , CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO)) AS CHAR(255)) AS TIEMPO_PENDIENTE_FULL ".
                        "     , CASE ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 0 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 2 THEN 'Entre 0-2'  ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 3 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 4 THEN 'Entre 3-4'   ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 5 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 6 THEN 'Entre 5-6'   ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 7 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 12 THEN 'Entre 7-12'   ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 13 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 24 THEN 'Entre 13-24'  ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 25 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 48 THEN 'Entre 25-48' ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) > 48 THEN 'Mas de 48'  ".
                        "     END AS RANGO_PENDIENTE ".
                        "     , TP.PLAZA ".
                        " FROM `portalbd`.`informe_petec_pendientesm` PP ".
                        " left join portalbd.tbl_plazas TP ".
                        " on PP.MUNICIPIO_ID=TP.MUNICIPIO_ID ".
                        " where (PP.STATUS= 'PENDI_PETEC' or PP.STATUS= 'MALO' ) and PP.FUENTE='FENIX_BOG') C1".
                        " group by C1.PLAZA order by count(*) DESC";
                        $r = $this->mysqli->query($queryBogota) or die($this->mysqli->error.__LINE__);

			$resultBogota = array();
                        if($r->num_rows > 0){
                                
                                while($row = $r->fetch_assoc()){
                                        //$row['label']="Concepto ".$row['label'];
                                        $resultBogota[] = $row;
                                }
                        }

             $queryConceptos=" select".
                        " C1.CONCEPTO_ID".
                        " , count(*) as CANTIDAD".
                        " , sum(if(C1.RANGO_PENDIENTE='Entre 0-2', 1,0)) as 'Entre02',".
                        "   sum(if(C1.RANGO_PENDIENTE='Entre 3-4', 1,0)) as 'Entre34', ".
                        "   sum(if(C1.RANGO_PENDIENTE='Entre 5-6', 1,0)) as 'Entre56', ".
                        "   sum(if(C1.RANGO_PENDIENTE='Entre 7-12', 1,0)) as 'Entre712', ".
                        "   sum(if(C1.RANGO_PENDIENTE='Entre 13-24', 1,0)) as 'Entre1324', ".
                        "   sum(if(C1.RANGO_PENDIENTE='Entre 25-48', 1,0)) as 'Entre2548', ".
                        "   sum(if(C1.RANGO_PENDIENTE='Mas de 48', 1,0)) as 'Masde48' ".
                        " from (SELECT ".
                        "     PP.`PEDIDO`, ".
                        "     PP.`PEDIDO_ID`, ".
                        "     PP.`SUBPEDIDO_ID`, ".
                        "     PP.`SOLICITUD_ID`, ".
                        "     PP.`TIPO_ELEMENTO_ID`, ".
                        "     PP.`FECHA_INGRESO`, ".
                        "     PP.`FECHA_ESTADO`, ".
                        "     PP.`FECHA_FINAL`, ".
                        "     PP.`PRODUCTO`, ".
                        "     PP.`UEN_CALCULADA`, ".
                        "     PP.`ESTRATO`, ".
                        "     PP.`CONCEPTO_ID`, ".
                        "     PP.`MUNICIPIO_ID`, ".
                        "     PP.`DIRECCION_SERVICIO`, ".
                        "     PP.`FECHAINGRESO_SOLA`, ".
                        "     PP.`HORAINGRESO`, ".
                        "     DATE((PP.`FECHAESTADO_SOLA`)) as FECHAESTADO_SOLA, ".
                        "     PP.`HORAESTADO`, ".
                        "     PP.`DIANUM_ESTADO`, ".
                        "     PP.`DIANOM_ESTADO`, ".
                        "     PP.`RANGO_CARGA`, ".
                        "     PP.`FECHA_CARGA`, ".
                        "     DATE_FORMAT((PP.FECHA_CARGA),'%H') AS HORA_CARGA, ".
                        "     PP.`DIA_CARGA`, ".
                        "     PP.`MESNOMBRE_CARGA`, ".
                        "     PP.`MESNUMERO_CARGA`, ".
                        "     PP.`SEMANA_CARGA`, ".
                        "     PP.`SEMANA_ANO_CARGA`, ".
                        "     PP.`ANO_CARGA`, ".
                        "     PP.`FUENTE`, ".
                        "     PP.`STATUS`, ".
                        "     PP.`VIEWS` ".
                        "     , CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO)) AS CHAR(255)) AS TIEMPO_PENDIENTE_FULL ".
                        "     , CASE ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 0 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 2 THEN 'Entre 0-2'  ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 3 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 4 THEN 'Entre 3-4'   ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 5 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 6 THEN 'Entre 5-6'   ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 7 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 12 THEN 'Entre 7-12'   ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 13 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 24 THEN 'Entre 13-24'  ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 25 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 48 THEN 'Entre 25-48' ".
                        "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) > 48 THEN 'Mas de 48'  ".
                        "     END AS RANGO_PENDIENTE ".
                        "     , TP.PLAZA ".
                        " FROM `portalbd`.`informe_petec_pendientesm` PP ".
                        " left join portalbd.tbl_plazas TP ".
                        " on PP.MUNICIPIO_ID=TP.MUNICIPIO_ID ".
                        " where (PP.STATUS= 'PENDI_PETEC' or PP.STATUS= 'MALO' ) and PP.FUENTE='FENIX_NAL') C1".
                        " group by C1.CONCEPTO_ID order by count(*) DESC";
                        $rr = $this->mysqli->query($queryConceptos) or die($this->mysqli->error.__LINE__);

            $queryConceptos = array();
                        if($rr->num_rows > 0){
                                
                                while($row = $rr->fetch_assoc()){
                                        //$row['label']="Concepto ".$row['label'];
                                        $queryConceptos[] = $row;
                                }
                        }

                        $query=" select".
			" C1.PLAZA".
			" , count(*) as CANTIDAD".
			" , sum(if(C1.RANGO_PENDIENTE='Entre 0-2', 1,0)) as 'Entre02',".
			"   sum(if(C1.RANGO_PENDIENTE='Entre 3-4', 1,0)) as 'Entre34', ".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 5-6', 1,0)) as 'Entre56', ".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 7-12', 1,0)) as 'Entre712', ".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 13-24', 1,0)) as 'Entre1324', ".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 25-48', 1,0)) as 'Entre2548', ".
            "   sum(if(C1.RANGO_PENDIENTE='Mas de 48', 1,0)) as 'Masde48' ".
			" from (SELECT ".
			"     PP.`PEDIDO`, ".
			"     PP.`PEDIDO_ID`, ".
			"     PP.`SUBPEDIDO_ID`, ".
			"     PP.`SOLICITUD_ID`, ".
			"     PP.`TIPO_ELEMENTO_ID`, ".
			"     PP.`FECHA_INGRESO`, ".
			"     PP.`FECHA_ESTADO`, ".
			"     PP.`FECHA_FINAL`, ".
			"     PP.`PRODUCTO`, ".
			"     PP.`UEN_CALCULADA`, ".
			"     PP.`ESTRATO`, ".
			"     PP.`CONCEPTO_ID`, ".
			"     PP.`MUNICIPIO_ID`, ".
			"     PP.`DIRECCION_SERVICIO`, ".
			"     PP.`FECHAINGRESO_SOLA`, ".
			"     PP.`HORAINGRESO`, ".
			"     DATE((PP.`FECHAESTADO_SOLA`)) as FECHAESTADO_SOLA, ".
			"     PP.`HORAESTADO`, ".
			"     PP.`DIANUM_ESTADO`, ".
			"     PP.`DIANOM_ESTADO`, ".
			"     PP.`RANGO_CARGA`, ".
			"     PP.`FECHA_CARGA`, ".
			"     DATE_FORMAT((PP.FECHA_CARGA),'%H') AS HORA_CARGA, ".
			"     PP.`DIA_CARGA`, ".
			"     PP.`MESNOMBRE_CARGA`, ".
			"     PP.`MESNUMERO_CARGA`, ".
			"     PP.`SEMANA_CARGA`, ".
			"     PP.`SEMANA_ANO_CARGA`, ".
			"     PP.`ANO_CARGA`, ".
			"     PP.`FUENTE`, ".
			"     PP.`STATUS`, ".
			"     PP.`VIEWS` ".
			"     , CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO)) AS CHAR(255)) AS TIEMPO_PENDIENTE_FULL ".
			"     , CASE ".
		    "         WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 0 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 2 THEN 'Entre 0-2'  ".
            "         WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 3 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 4 THEN 'Entre 3-4'   ".
            "         WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 5 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 6 THEN 'Entre 5-6'   ".
            "         WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 7 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 12 THEN 'Entre 7-12'   ".
            "         WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 13 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 24 THEN 'Entre 13-24'  ".
            "         WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 25 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 48 THEN 'Entre 25-48' ".
            "         WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) > 48 THEN 'Mas de 48'  ".
			"     END AS RANGO_PENDIENTE ".
			"     , TP.PLAZA ".
			" FROM `portalbd`.`informe_petec_pendientesm` PP ".
			" left join portalbd.tbl_plazas TP ".
			" on PP.MUNICIPIO_ID=TP.MUNICIPIO_ID ".
			" where (PP.STATUS= 'PENDI_PETEC' or PP.STATUS= 'MALO' ) and PP.FUENTE='FENIX_NAL') C1".
			" group by C1.PLAZA order by count(*) DESC";
                        //echo $query;
                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                while($row = $r->fetch_assoc()){
                                        //$row['label']="Concepto ".$row['label'];
                                        $result[] = $row;
                                }
                                $this->response($this->json(array($result,$resultBogota,$queryConceptos)), 200); // send user details
                        }

                        $this->response('',204);        // If no records "No Content" status

		}


		private function pendientesGrafica(){
			if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
			
			$query= " SELECT concepto_id as label, COUNT(*) as value ".
				" FROM  informe_petec_pendientesm ".
				" WHERE (STATUS='PENDI_PETEC' or STATUS='MALO') ".
				" GROUP BY concepto_id ".
				" ORDER BY COUNT(*) ASC";
                        //echo $query;
                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                while($row = $r->fetch_assoc()){
					$row['label']="Concepto ".$row['label'];
                                        $result[] = $row;
                                }
                                $this->response($this->json($result), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status

		}


                private function pendientesGraficaAD(){
                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }

                        $query= " SELECT cola_id as label, COUNT(*) as value ".
                                " FROM  informe_activacion_pendientesm ".
                                " WHERE (STATUS='PENDI_ACTIVACION') ".
                                " GROUP BY cola_id ".
				" ORDER BY COUNT(*) ASC" ;
                        //echo $query;
                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
				$total=0;
                                while($row = $r->fetch_assoc()){
                                        $row['label']="Cola ".$row['label'];
					$total=$total + $row['value'];
                                        $result[] = $row;
                                }
                                $this->response($this->json(array($result,$total)), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status

                }



		private function productividadGrupo(){
                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $today = date("Y-m-d");

                        $fechaIni= $this->_request['fechaIni'];
                        $fechaFin=$this->_request['fechaFin'];

                        if($fechaIni==''||$fechaFin==''){
                                $fechaIni=  $today;
                                $fechaFin= $today;

                        }
                        $query= " select ".
			" c1.user,".
			" count(*) as servicios, ".
			" count(distinct c1.pedido_id) as Pedidos,".
			" sum(if(c1.concepto_final='15', 1,0)) as 'c15',".
			" sum(if(c1.concepto_final='99', 1,0)) as 'c99',".
			" sum(if(c1.concepto_final='14', 1,0)) as 'c14',".
			" sum(if(c1.concepto_final='92', 1,0)) as 'c92',".
			" sum(if(c1.concepto_final='PORDE', 1,0)) as PORDE,".
			" sum(if(c1.concepto_final='OTRO', 1,0)) as OTRO,".
			" round(AVG(((TIMESTAMPDIFF(SECOND,".
			"         (c1.`fecha_inicio`),".
			"         (c1.`fecha_fin`))) / 60)),2) AS AVG_ESTUDIO_MN".
			" from (SELECT PE.`id`,".
			"     PE.`pedido`,".
			"     PE.`fuente`,".
			"     PE.`actividad`,".
			"     PE.`fecha_fin`,".
			"   date_format( PE.`fecha_fin`,'%H') as Hora_fin,".
			"     PE.`user`,".
			"     PE.`estado`,".
			"     PE.`duracion`,".
			"     PE.`accion`,".
			"     PE.`fecha_estado`,".
			"     PE.`fecha_inicio`,".
			"     case".
			"     when PE.`concepto_final` not in ('15','99','14','92','PORDE') then 'OTRO'".
			"                                else PE.concepto_final".
			"                 end as concepto_final,".
			" PE.`concepto_final` as Conceptos,".
			"     PE.`source`,".
			"     PE.`pedido_id`,".
			"     PE.`subpedido_id`,".
			"     PE.`solicitud_id`,".
			"     PE.`municipio_id`".
			" FROM `portalbd`.`pedidos` PE".
			"         LEFT JOIN".
			"     portalbd.tbl_usuarios TU ON PE.user = TU.USUARIO_ID".
			"         LEFT JOIN".
			"     portalbd.tbl_cargos TC ON TU.CARGO_ID = TC.ID_CARGO".
			" where date_format(fecha_fin,'%Y-%m-%d') between '$fechaIni' and '$fechaFin' and PE.source='AUTO' ) c1".
			" group by c1.user order by  count(*) DESC ";

                        //echo $query;
                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){

                                while($row = $r->fetch_assoc()){
                                        $result[] = $row;
                                }
                                //$result=array($ingresos,$estudios,$pendientes);
                                $this->response($this->json($result), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status



		}
		
		//esto deberia llamarse calcularDetalleTMA
		private function calcularDetalleTME(){
			if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $today = date("Y-m-d");

                        $fechaIni= $this->_request['fechaIni'];
                        $fechaFin=$this->_request['fechaFin'];

                        if($fechaIni==''||$fechaFin==''){
                                $fechaIni=  date("Y-m")."-01";//el primer dia del mes
                                $fechaFin= $today;
                        }

                        $query= " SELECT ".
				"     DATE_FORMAT(PP.FECHA_ESTADO, '%Y') AS ANO,".
				"     DATE_FORMAT(PP.FECHA_ESTADO, '%M') AS MES,".
				"     DATE_FORMAT(PP.FECHA_ESTADO, '%Y-%m-%d') AS FECHA,".
				"     DATE_FORMAT(PP.FECHA_ESTADO, '%W') AS DIA,".
				"     COUNT(*) AS INGRESOS,".
				"     ES.ESTUDIOS AS ESTUDIOSM,".
				"     ROUND(ES.AVG_ESTUDIO_MN, 2) AS AVG_ESTUDIO_MN,".
				"     ROUND(ES.AVG_ESPERA_HR, 2) AS AVG_ESPERA_HR,".
				"     ROUND(ES.AVG_TMI_HR, 2) AS AVG_TME_HR,".
				"     6 as META_TME".
				" FROM".
				"     `portalbd`.`informe_petec_pendientesm` PP,".
				"     (SELECT ".
				"         DATE_FORMAT(PE.fecha_fin, '%Y-%m-%d') AS FECHA,".
				"             COUNT(*) AS ESTUDIOS,".
				"             (AVG(((TIMESTAMPDIFF(SECOND, (PE.`fecha_inicio`), (PE.`fecha_fin`))) / 60))) AS AVG_ESTUDIO_MN,".
				"             ((AVG(((TIMESTAMPDIFF(SECOND, (PE.`fecha_estado`), (PE.`fecha_inicio`))) / 60))) / 60) AS AVG_ESPERA_HR,".
				"             ((AVG(((TIMESTAMPDIFF(SECOND, (PE.`fecha_estado`), (PE.`fecha_fin`))) / 60))) / 60) AS AVG_TMI_HR".
				"     FROM".
				"         portalbd.pedidos PE".
				"     WHERE".
				//"         PE.fuente IN ('FENIX_NAL')".
				"         PE.source IN ('AUTO')".
				"     GROUP BY DATE_FORMAT(PE.fecha_fin, '%Y-%m-%d')) ES".
				" WHERE".
				"     DATE_FORMAT(PP.FECHA_ESTADO, '%Y-%m-%d') BETWEEN '$fechaIni' AND '$fechaFin'".
				"         AND DATE_FORMAT(PP.FECHA_ESTADO, '%Y-%m-%d') = ES.FECHA".
				" GROUP BY DATE_FORMAT(PP.FECHA_ESTADO, '%Y-%m-%d')";


			$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){

                                while($row = $r->fetch_assoc()){
                                        $result[] = $row;
                                }
                                $this->response($this->json($result), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" stat

		}


                private function ingresosEstudiosGrafica(){
                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
			$today = date("Y-m-d");

			$fechaIni= $this->_request['fechaIni'];
			$fechaFin=$this->_request['fechaFin'];

			if($fechaIni==''||$fechaFin==''){
				$fechaIni=  $today;
	                        $fechaFin= $today;

			}


                        $query= "SELECT a.HORA, group_concat(a.CONCEPTO,',',a.SERVICIOS order by a.CONCEPTO DESC separator ';' ) as val ".
				"from ".
				"( ".
				"SELECT HORA, CONCEPTO,sum(SERVICIOS) as SERVICIOS ".
				"FROM informe_petec_horam ".
				"WHERE FECHA between '$fechaIni' and '$fechaFin' ".
				"GROUP BY HORA,CONCEPTO order by HORA ASC ".
				") a GROUP BY a.HORA ";

                        //echo $query;
                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
				$ingresos = array();
				$estudios  = array();
				$pendientes = array();

                                while($row = $r->fetch_assoc()){
					$tmp=$row['val'];
					$conceptos=explode(";", $tmp);
					$tmp2=explode(",",$conceptos[0]);
					$pendientes[]= array("value"=>$tmp2[1]);
					
					$tmp2=explode(",",$conceptos[1]);
                                        $ingresos[]= array("value"=>$tmp2[1]);

					$tmp2=explode(",",$conceptos[2]);
                                        $estudios[]= array("value"=>$tmp2[1]);

                                        //$result[] = $row;
                                }
				$result=array($ingresos,$estudios,$pendientes);
                                $this->response($this->json($result), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status

                }
		
		private function csvPendientes(){
			 if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }

                        $concepto = $this->_request['concepto'];
                        $login = $this->_request['login'];
                        $today = date("Y-m-d h:i:s");
			
			if($concepto!="TODO"){
				$concepto=" AND a.CONCEPTO_ID='$concepto' ";
			}else{
				$concepto="";
			}

			$filename="Pendientes-$login-$today.csv";
			$query="SELECT a.PEDIDO_ID,a.PEDIDO,a.SUBPEDIDO_ID,a.SOLICITUD_ID,a.TIPO_ELEMENTO_ID,a.PRODUCTO,a.UEN_CALCULADA,a.ESTRATO,MUNICIPIO_ID,a.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(a.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,a.FUENTE,a.CONCEPTO_ID,a.FECHA_ESTADO,a.FECHA_INGRESO, a.FECHA_CITA,a.STATUS from informe_petec_pendientesm a where (a.STATUS='PENDI_PETEC' or a.STATUS='MALO') $concepto ";
                       
			$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
				$fp = fopen("../tmp/$filename", 'w');
				fputcsv($fp, array('PEDIDO_ID','PEDIDO','SUBPEDIDO_ID','SOLICITUD_ID','TIPO_ELEMENTO_ID','PRODUCTO','UEN_CALCULADA','ESTRATO','MUNICIPIO_ID','PAGINA_SERVICIO','TIEMPO_COLA','FUENTE','CONCEPTO_ID','FECHA_ESTADO','FECHA_INGRESO','FECHA_CITA','STATUS'));
                                while($row = $r->fetch_assoc()){
                                        $result[] = $row;
					fputcsv($fp, $row);
                                }
				fclose($fp);
                                $this->response($this->json(array($filename,$login)), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status
		}

                private function csvMalos(){
                         if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }

                        $concepto = $this->_request['concepto'];
                        $login = $this->_request['login'];
                        $today = date("Y-m-d h:i:s");

                        if($concepto!="TODO"){
                                $concepto=" AND a.CONCEPTO_ID='$concepto' ";
                        }else{
                                $concepto="";
                        }

                        $filename="Malos-$login-$today.csv";
                        //$query="SELECT a.PEDIDO_ID,a.PEDIDO,a.SUBPEDIDO_ID,a.SOLICITUD_ID,a.TIPO_ELEMENTO_ID,a.PRODUCTO,a.UEN_CALCULADA,a.ESTRATO,MUNICIPIO_ID,a.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(a.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,a.FUENTE,a.CONCEPTO_ID,a.FECHA_ESTADO,a.FECHA_INGRESO, a.FECHA_CITA,a.STATUS from informe_petec_pendientesm a where  a.STATUS='MALO' $concepto ";

			$query="Select ".
			" pm.PEDIDO_ID".
			", pm.FECHA_INGRESO".
			", pm.FECHA_ESTADO".
			", p.fecha_fin".
			", pm.FUENTE".
			", pm.STATUS".
			", p.motivo".
			", p.users".
			" from portalbd.informe_petec_pendientesm pm ".
			" inner join (Select ".
			" P.pedido_id ".
			", P.motivo ".
			", group_concat(P.user) as users ".
			", max(fecha_fin) as fecha_fin ".
			" from portalbd.pedidos P ".
			" where estado='MALO' ".
			" group by P.pedido_id, P.motivo) p ".
			" on pm.pedido_id=p.pedido_id ".
			" where  ".
			" status='MALO' ".
			" and CONCEPTO_ID in ('PETEC') ";


                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                $fp = fopen("../tmp/$filename", 'w');
                                fputcsv($fp, array('PEDIDO_ID','FECHA_INGRESO','FECHA_ESTADO','FECHA_FIN','FUENTE','STATUS','MOTIVO','USERS'));
                                while($row = $r->fetch_assoc()){
                                        $result[] = $row;
                                        fputcsv($fp, $row);
                                }
                                fclose($fp);
                                $this->response($this->json(array($filename,$login)), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status
                }


                private function listadoPendientes2(){//pendientes
                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }

                        $fechaini = $this->_request['fecha_inicio'];
                        $fechafin = $this->_request['fecha_fin'];
                        $concepto = $this->_request['concepto'];
			$page = $this->_request['page'];
                        $today = date("Y-m-d");


                        if($page=="undefined"){
                                $page="0";
                        }else{
                                $page=$page-1;
                        }
			
			$page=$page*100;
			
			if($concepto!="TODO"){
				if($concepto=="PETEC"){
					$concepto=" and a.CONCEPTO_ID IN ('PETEC','OKRED') ";
				}else{
					$concepto=" and a.CONCEPTO_ID='$concepto' ";
				}
			}else{
				$concepto="";
			}

			//calcular counter
			$query="SELECT count(*) as counter from informe_petec_pendientesm a where (a.STATUS='PENDI_PETEC' or a.STATUS='MALO') $concepto ";
			
                        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                        $counter=0;
                        if($rr->num_rows > 0){
                                $result = array();
                                if($row = $rr->fetch_assoc()){
                                        $counter = $row['counter'];
                                }
                        }
	

                        $query="SELECT a.ID,a.PEDIDO_ID,a.PEDIDO,a.SUBPEDIDO_ID,a.SOLICITUD_ID,a.TIPO_ELEMENTO_ID,a.PRODUCTO,a.UEN_CALCULADA,a.ESTRATO,MUNICIPIO_ID,a.DIRECCION_SERVICIO,a.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(a.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,a.FUENTE,a.CONCEPTO_ID,a.FECHA_ESTADO, a.FECHA_CITA,a.STATUS from informe_petec_pendientesm a where (a.STATUS='PENDI_PETEC' or a.STATUS='MALO') $concepto order by a.FECHA_ESTADO ASC limit 100 offset $page";
                        //echo $query;
                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                while($row = $r->fetch_assoc()){
                                        $result[] = $row;
                                }
                                $this->response($this->json(array($result,$counter)), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status
                }



                private function pedidosPorPedido(){//historico por 1 pedido
                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $pedido = $this->_request['pedido'];
                        $today = date("Y-m-d");
                        $query="SELECT id, pedido, fuente, actividad, fecha_estado,fecha_inicio,fecha_fin, estado,accion,duracion,user,concepto_final from pedidos where pedido like '$pedido%' order by fecha_fin desc limit 10";
                        //$query="SELECT id, pedido, fuente, actividad, fecha_estado,fecha_inicio,fecha_fin, estado,accion,duracion,user,concepto_final from pedidos order by fecha_fin desc limit 10";
			//echo $query;
                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                while($row = $r->fetch_assoc()){
                                        $result[] = $row;
                                }
                                $this->response($this->json($result), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status
                }

                private function buscarPedido(){

                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $pedido = $this->_request['pedidoID'];
			$user = $this->_request['userID'];
			$username = $this->_request['username'];
                        //si el actual usuario tenia un pedido "agarrado, hay que liberarlo"
                        $pedido_actual = $this->_request['pedido_actual'];
                        if($pedido_actual!=''){//en este caso tenia pedido antes, estaba trabajando uno, debo actualizarlo para dejarlo libre
                                $sqlupdate="update informe_petec_pendientesm set ASESOR='' where ASESOR='$user' ";
                                $xxx = $this->mysqli->query($sqlupdate);
                        }

                        $user=strtoupper($user);
                        $today = date("Y-m-d");

                        $query1="SELECT a.ID,a.PEDIDO_ID,a.SUBPEDIDO_ID,a.SOLICITUD_ID,a.TIPO_ELEMENTO_ID,a.PRODUCTO,a.UEN_CALCULADA,a.ESTRATO,MUNICIPIO_ID,a.DIRECCION_SERVICIO,a.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,a.FUENTE,a.CONCEPTO_ID,a.FECHA_ESTADO,a.ASESOR,a.STATUS,a.CONCEPTO_ANTERIOR from informe_petec_pendientesm a JOIN (SELECT distinct(a.pedido) as pedido2,(select b.id from informe_petec_pendientesm b where b.pedido=a.pedido order by id desc limit 1 ) as id2 FROM `informe_petec_pendientesm` a WHERE a.PEDIDO_ID='$pedido' and (a.STATUS='PENDI_PETEC' or a.STATUS='BUSCADO_PETEC' or a.STATUS='MALO')) kai on a.id=kai.id2";

                         //$this->response($query1,200);
                        $r = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);
			$busy="";
                        if($r->num_rows > 0){
                                $result = array();
                                $ids="";
                                $sep="";
                                while($row = $r->fetch_assoc()){
					$row['source']='BUSCADO';
                                        $result[] = $row;
                                        $ids=$ids.$sep.$row['ID'];
					$asess=$row['ASESOR'];
					if($asess!=''){//este pedido esta ocupado, no deberia hacer la actualizacion de abajo..
						$busy="YES";
					}
                                        $sep=",";
                                }
				$sqlupdate="";
				if($busy=="YES"){
					$sqlupdate="update informe_petec_pendientesm set VIEWS=VIEWS+1 where ID in ($ids)";
				}else{
					$sqlupdate="update informe_petec_pendientesm set VIEWS=VIEWS+1,ASESOR='$user' where ID in ($ids)";
				}

                                $x = $this->mysqli->query($sqlupdate);
 $sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$user','$username','','','PEDIDO: $pedido','BUSCARPEDIDO','') ";
				$xx = $this->mysqli->query($sqlfeed);
                                //echo json_encode($result);
                                $this->response(json_encode($result), 200); // send user details
                        }else {//si el pedido no esta en la base de datos buscar en fenix, esto implica insertar en la tabla core..
				$success=$this->buscarPedidoFenix($pedido);
				if($success=="OK"){//logro encontrar el pedido en fenix he hizo el insert local...
					//recursion?????
					$this->buscarPedido();
				}
                        }

                        $this->response('nothing',204);        // If no records "No Content" status
                }

                //Función para buscar pedidos directamente en Fenix - Boton BuscarPedido.
                // 2015-09-17 - Se modifica fecha estado, ahora vamos a novedades por ella - CGONZGO
		private function buscarPedidoFenix($pedido_id){

                        $this->dbFenixConnect();
                        $connf=$this->connf;
                        //2. verifico el estado del pedido en fenix y actualizo estado en base de datos local si es necesario
                        $sqlfenix="SELECT DISTINCT ".
                                " SOL.PEDIDO_ID||SOL.SUBPEDIDO_ID|| SOL.SOLICITUD_ID as PEDIDO".
                                " , SOL.PEDIDO_ID".
                                " , SOL.SUBPEDIDO_ID".
                                " , SOL.SOLICITUD_ID".
                                " , SOL.TIPO_ELEMENTO_ID".
                                " , SOL.ESTADO_BLOQUEO".
                                " , SOL.USUARIO_ID AS USUARIO_BLOQUEO_FENIX".
                                " , FNX_TRABAJOS_SOLICITUDES.TIPO_TRABAJO".
                                " , TO_CHAR(SOL.FECHA_INGRESO,'RRRR-MM-DD hh24:mi:ss') as FECHA_INGRESO ".
                                " , NVL((select TO_CHAR(NSO.fecha, 'YYYY-MM-DD HH24:MI:SS') ".
                                " from FENIX.FNX_NOVEDADES_SOLICITUDES NSO ".
                                " where SOL.PEDIDO_ID=NSO.PEDIDO_ID(+) ".
                                "       AND SOL.SUBPEDIDO_ID=NSO.SUBPEDIDO_ID(+) ".
                                "       AND SOL.SOLICITUD_ID=NSO.SOLICITUD_ID(+) ".
                                " and NSO.consecutivo=(select max(a.consecutivo) from FENIX.FNX_NOVEDADES_SOLICITUDES a  ".
                                "     where SOL.PEDIDO_ID=a.PEDIDO_ID(+) ".
                                "       AND SOL.SUBPEDIDO_ID=a.SUBPEDIDO_ID(+) ".
                                "       AND SOL.SOLICITUD_ID=a.SOLICITUD_ID(+))), TO_CHAR(SOL.FECHA_ESTADO,'RRRR-MM-DD hh24:mi:ss')) AS FECHA_ESTADO ".
                                " , TO_CHAR(SOL.FECHA_CITA,'RRRR-MM-DD') as FECHA_CITA ".
                                " , FN_NOMBRE_PRODUCTO(SOL.PRODUCTO_ID) AS PRODUCTO ".
                                " , TRIM(FN_UEN_CALCULADA(SOL.PEDIDO_ID,SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID)) AS UEN_CALCULADA ".
                                " , FN_VALOR_CARACT_SUBPEDIDO(SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID,37) ESTRATO ".
                                " , SOL.CONCEPTO_ID ".
                                " , SOL.CONCEPTO_ID as CONCEPTO_ANTERIOR ".
                                " , TRIM(FN_VALOR_CARACTERISTICA_SOL (SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID,'34')) AS MUNICIPIO_ID ".
                                " , TO_CHAR(TRIM(FN_VALOR_CARACTERISTICA_SOL (SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID,'35'))) AS DIRECCION_SERVICIO".
                                " , fn_valor_caracteristica_sol(SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID,'38') as PAGINA_SERVICIO".
                                " , TO_CHAR(SOL.FECHA_INGRESO,'RRRR-MM-DD') FECHAINGRESO_SOLA ".
                                " , (TO_CHAR(SOL.FECHA_INGRESO,'hh24')) HORAINGRESO".
                                " , (TO_CHAR(SOL.FECHA_ESTADO,'RRRR-MM-DD')) FECHAESTADO_SOLA ".
                                " , (TO_CHAR(SOL.FECHA_ESTADO,'hh24')) HORAESTADO".
                                " , TO_NUMBER (TO_CHAR (SOL.FECHA_ESTADO, 'DD')) AS DIANUM_ESTADO".
                                " , TO_CHAR (SOL.FECHA_ESTADO, 'DAY') AS DIANOM_ESTADO".
                                " , CASE ".
                                "     WHEN TO_NUMBER(TO_CHAR(SYSDATE,'hh24')) < 13 THEN 'AM'".
                                "     WHEN TO_NUMBER(TO_CHAR(SYSDATE,'hh24')) = 13  THEN 'MD'".
                                "     WHEN TO_NUMBER(TO_CHAR(SYSDATE,'hh24')) > 13  THEN 'PM'".
                                "   END AS RANGO_CARGA".
                                " , (TO_CHAR(SYSDATE,'RRRR-MM-DD hh24:mi:ss')) AS FECHA_CARGA".
                                " , TO_CHAR (SYSDATE, 'DAY') AS DIA_CARGA".
                                " , (TRIM ( TO_CHAR (SYSDATE, 'MONTH'))) AS MESNOMBRE_CARGA ".
                                " , TO_NUMBER ( TO_CHAR ( SYSDATE, 'MM')) AS MESNUMERO_CARGA".
                                " , CEIL (  TO_NUMBER ( TO_CHAR ( SYSDATE,  'W'))) AS SEMANA_CARGA".
                                " , TO_NUMBER ( TO_CHAR (SYSDATE, 'IW')) AS SEMANA_ANO_CARGA".
                                " , TO_NUMBER(TO_CHAR(SYSDATE,'RRRR')) AS ANO_CARGA".
                                " , 'FENIX_NAL' AS FUENTE".
                                " FROM FNX_SOLICITUDES SOL".
                                " , FNX_PEDIDOS".
                                " , FNX_SUBPEDIDOS".
                                " , FNX_TRABAJOS_SOLICITUDES".
                                "     WHERE".
                                "      SOL.PEDIDO_ID='$pedido_id'".
                                "      and SOL.TIPO_ELEMENTO_ID IN ('BDID', 'TDID','BDIDE1', 'TDIDE1', 'BDODE1', 'TDODE1', 'TO', 'TOIP','INSHFC', 'INSIP', 'INSTIP', 'SEDEIP', 'P2MB', '3PLAY', 'CNTXIP', 'ACCESP', 'PLANT', 'PLP', 'PTLAN', 'PMULT', 'PPCM', 'PBRI', 'PPRI', 'INSTA', 'TP', 'PBRI','SLL', 'TC', 'SLLBRI', 'TCBRI', 'SLLPRI', 'TCPRI','SEDEIP','EQURED','EQACCP','STBOX','ACCESO')".
                                "      AND SOL.SUBPEDIDO_ID=FNX_SUBPEDIDOS.SUBPEDIDO_ID ".
                                "      AND SOL.PEDIDO_ID=FNX_SUBPEDIDOS.PEDIDO_ID ".
                                "      AND SOL.PEDIDO_ID=FNX_PEDIDOS.PEDIDO_ID ".
                                "      AND SOL.PEDIDO_ID=FNX_TRABAJOS_SOLICITUDES.PEDIDO_ID ".
                                "      AND SOL.SUBPEDIDO_ID=FNX_TRABAJOS_SOLICITUDES.SUBPEDIDO_ID ".
                                "      AND SOL.SOLICITUD_ID=FNX_TRABAJOS_SOLICITUDES.SOLICITUD_ID ";
                                //echo  $sqlfenix.", \n ";
                                $stid = oci_parse($connf, $sqlfenix);
                                oci_execute($stid);
                                $ncols = oci_num_fields($stid);

                                $NUEVOS="";
			 	$sqlinsert="insert into informe_petec_pendientesm ";
                                $fields="";
                                $sep="";
                                for ($i = 1; $i <= $ncols; $i++) {
                                        $column_name  = oci_field_name($stid, $i);
                                        $fields=$fields.$sep.$column_name;
                                        $sep=",";
                                }

                                $sqlinsert = "$sqlinsert ($fields,status) values (";

                                $SEP="";
				$success="";
                                while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
                                        $subinsert=$sqlinsert;
                                        $sep="";
                                        foreach ($row as $item) {
                                                $item = str_replace("'", ".", $item);
                                                $subinsert="$subinsert $sep '$item'";
                                                $sep=",";
                                        }

                                        $SEP=",";
					$status="PENDI_PETEC";
	
					if($row['CONCEPTO_ID']!='PETEC' && $row['CONCEPTO_ID']!='OKRED' && $row['CONCEPTO_ID']!='15' && $row['CONCEPTO_ID']!='92' ){
						$status="BUSCADO_PETEC";
					}
					if($row['TIPO_ELEMENTO_ID']=='EQURED' || $row['TIPO_ELEMENTO_ID']=='EQACCP' || $row['TIPO_ELEMENTO_ID']=='STBOX'){
						$status="BUSCADO_PETEC";
					}
                                        $subinsert=$subinsert.",'$status')";
                                        if(!$result = $this->mysqli->query($subinsert)){
                                                die('There was an error running the query [' . $connm->error. ' --'.$subinsert.'** ]');
                                        }
					$success="OK";
                                }
				return $success;
                }

                private function pedidoOcupadoFenix($obj){
                        //$id=$obj['ID'];
                        $pedido_id=$obj['PEDIDO_ID'];
                        $subpedido_id=$obj['SUBPEDIDO_ID'];
                        $solicitud_id=$obj['SOLICITUD_ID'];
                        //$concepto_id=$obj['CONCEPTO_ID'];

                        $this->dbFenixConnect();
                        $connf=$this->connf;
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
				
				$sqlfenix="	SELECT ".
				"	SOL.ESTADO_BLOQUEO, SOL.USUARIO_ID ".
				"	FROM FNX_SOLICITUDES SOL ".
				"	WHERE ".
				"	SOL.PEDIDO_ID='$pedido_id' ".
				"	AND SOL.ESTADO_BLOQUEO='S' ";

                                //echo  $sqlfenix.", \n ";
                                $stid = oci_parse($connf, $sqlfenix);
                                oci_execute($stid);
                                if($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
					return $row;
                                        //echo $concepto_id." - ".$row['CONCEPTO_ID']."\n";
                                }
                                return "No rows!!!!";
                }
/*
		private function demePedidoReconfiguracion(){
                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
			
			//1. la idea es buscar primero si hay pedidos para atender en esta hora...
			$user = $this->_request['userID'];
                        $concepto = $this->_request['concepto'];
                        $plaza = $this->_request['plaza'];

			$sql="SELECT PEDIDO_ID ".
				" FROM  informe_petec_pendientesm ".
				" WHERE ".
				" TIMEDIFF( NOW( ) , PROGRAMACION ) /3600 >0 ".
				" AND ASESOR='' ".
				" AND CONCEPTO_ID = '$concepto' ".
				" AND STATUS='PENDI_PETEC'";
			$rr = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);
			
			
                        $mypedido="";
                        $mypedidoresult=array();
                        $pedidos_ignorados="";
                        if($rr->num_rows > 0){//recorro los registros de la consulta para
                                while($row = $rr->fetch_assoc()){
					
				}
			}else{
			
			//2. si no tengo nada llamo la funcion demePedido normal

				$this->demePedido(); 	
			}
		}

*/
		
		//este demepedido valida contra fenix antes de suministrar el pedido...
                private function demePedido(){

                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $user = $this->_request['userID'];
                        $concepto = $this->_request['concepto'];
			$plaza = $this->_request['plaza'];
			
			$username=$this->_request['username'];

			$filename = '../tmp/control-threads.txt';

			if(file_exists($filename)){
				sleep(1);
			}else{
				$file = fopen($filename, 'w') or die("can't create file");
	                	fclose($file);
			}
			

			$user=strtoupper($user);
                        //si el actual usuario tenia un pedido "agarrado, hay que liberarlo"
                        $pedido_actual = $this->_request['pedido_actual'];
                        //if($pedido_actual!=''){//en este caso tenia pedido antes, estaba trabajando uno, debo actualizarlo para dejarlo libre
			
			//NO SE PUEDE CONDICIONAR AL PEDIDO ACTUAL, SI LE DA F5 A LA PAGINA NO HAY PEDIDO ACTUAL.. ES MEJOR ASI!!!
                        $sqlupdate="update informe_petec_pendientesm set ASESOR='' where ASESOR='$user'";
			//echo $sqlupdate;
                        $xxx = $this->mysqli->query($sqlupdate);
                        //}

			//echo "WTF";
                        $user=strtoupper($user);
                        $today = date("Y-m-d");
			
			//1.consulto todo lo que tenga fecha cita de mañana
			$hora=date("G");
			$uphold="1";
			if($hora<11){
				$uphold="1";
			}else{
				$uphold="2";
			}
		
			//14B2B
			$llamadaReconfiguracion="0";	

                        $mypedido="";

			if($concepto=="PETEC"){
				if($plaza=="BOGOTA-COBRE"){
					$concepto=" and b.CONCEPTO_ID IN ('PETEC','OKRED') ";
				}else {
					$concepto=" and b.CONCEPTO_ID IN ('PETEC','OKRED') and b.TIPO_ELEMENTO_ID IN ('ACCESP','INSIP','INSHFC','TO','TOIP','INSTA')  ";
				}
			}
			else if($concepto=="COORP"){ 
				$concepto=" and b.CONCEPTO_ID in ('PETEC','15') and (b.TIPO_ELEMENTO_ID IN ('E2MB','P2MB','INSTIP','CNTXIP','SEDECX','PLANT','PLP','PTLAN','MTLAN', 'PMULT','EPCM','PPCM','PBRI','PPRI','INSTIP','TV','TP','PBRI','BDID','TDID','BDIDE1','TDIDE1','BDODE1','TDODE1','SLL','TC','SLLBRI','TCBRI','SLLE1','TCE1','SLLPRI','TCPRI','SEDEIP','CONECT','ACCESO') )";
			}else if($concepto=="14" || $concepto=="99"){
				//reviso si hay llamadas que se deben hacer y las entrego de primeras
				$sqlllamadas="SELECT PEDIDO_ID,SUBPEDIDO_ID,SOLICITUD_ID,FECHA_ESTADO,FECHA_CITA ".
                                " FROM  informe_petec_pendientesm ".
                                " WHERE ".
                                " TIMEDIFF( NOW() , PROGRAMACION ) /3600 >0 ".
                                " AND ASESOR='' ".
                                " AND CONCEPTO_ID = '$concepto' ".
                                " AND STATUS='PENDI_PETEC' ".
				" ORDER BY  TIMEDIFF( NOW() , PROGRAMACION ) /3600 ASC ";

				$rr = $this->mysqli->query($sqlllamadas) or die($this->mysqli->error.__LINE__);

                        	if($rr->num_rows > 0){//recorro los registros de la consulta para
                                	while($row = $rr->fetch_assoc()){//si encuentra un pedido ENTREGUELO COMO SEA NECESARIO!!!!!!!
                                        	$result[] = $row;
						$mypedido=$row['PEDIDO_ID'];
                                                $mypedidoresult=$rta;
						break;
					}
				}
				

                                $concepto=" and b.CONCEPTO_ID='$concepto' and b.TIPO_ELEMENTO_ID IN('ACCESP','INSIP','INSHFC','TO','TOIP') and b.UEN_CALCULADA ='HG' AND b.PROGRAMACION='' ";
                        }else if($concepto=="14B2B"){
                                $concepto=" and b.CONCEPTO_ID='$concepto' and ( b.UEN_CALCULADA !='HG' ) ";
                        }else{
				$concepto=" and b.CONCEPTO_ID='$concepto' and b.TIPO_ELEMENTO_ID IN('ACCESP','INSIP','INSHFC','TO','TOIP')";
				//$concepto=" and b.CONCEPTO_ID='$concepto' ";
			}

			if($plaza=="TODOS"){//para que sea posible obtener un registro de cualquier plaza
				$plaza="";
			}else{
				$plaza=" AND b.MUNICIPIO_ID IN (select a.MUNICIPIO_ID from tbl_plazas a where a.PLAZA='$plaza') ";
			}
	
			$query1="select b.PEDIDO_ID,b.SUBPEDIDO_ID,b.SOLICITUD_ID,b.FECHA_ESTADO,b.FECHA_CITA ".
			",(SELECT a.user FROM vistas_pedidos  a where a.user='$user' AND b.PEDIDO_ID=a.pedido_id ".
			" AND a.fecha BETWEEN  '$today 00:00:00' AND  '$today 23:59:59' limit 1) as BEENHERE ".
			" from informe_petec_pendientesm b ".
			" where b.STATUS='PENDI_PETEC'  ".
			" and b.ASESOR ='' ".
			$concepto.
			$plaza.
			//" and b.CONCEPTO_ID='$concepto' ".
			//" AND b.MUNICIPIO_ID IN (select a.MUNICIPIO_ID from tbl_plazas a where a.PLAZA='$plaza') ".
			" order by b.FECHA_CITA,b.FECHA_ESTADO ASC";
		
			if($mypedido==""){
	
			$rr = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);
                        $mypedidoresult=array();
			$pedidos_ignorados="";
			if($rr->num_rows > 0){//recorro los registros de la consulta para
                                while($row = $rr->fetch_assoc()){
                                        $result[] = $row;

                                        $rta=$this->pedidoOcupadoFenix($row);

                                        if($rta=="No rows!!!!"){//me sirve, salgo del ciclo y busco este pedido...
                                                //echo "el pedido es: ".$row['PEDIDO_ID'];

						if($row['BEENHERE']==$user){
							$pedidos_ignorados=$pedidos_ignorados.$row['PEDIDO_ID'].',';
							//este pedido ya lo vio el dia de hoy
							//busco otro pedido----
							continue;
                                                }

                                                $mypedido=$row['PEDIDO_ID'];
                                                $mypedidoresult=$rta;
                                                break;
                                        }

					/*					
                                        if($rta['ESTADO_BLOQUEO']=='N'){//me sirve, salgo del ciclo y busco este pedido...
                                                //echo "el pedido es: ".$row['PEDIDO_ID'];

                                                if($row['BEENHERE']==$user){
                                                        $pedidos_ignorados=$pedidos_ignorados.$row['PEDIDO_ID'].',';
                                                        //este pedido ya lo vio el dia de hoy
                                                        //busco otro pedido----
                                                        continue;
                                                }

                                                $mypedido=$row['PEDIDO_ID'];
                                                $mypedidoresult=$rta;
                                                break;
                                        }*/

                                }
			//2.traigo solo los pedidos mas viejos en la base de datos...	
                        } else {
				$query1="select b.PEDIDO_ID,b.SUBPEDIDO_ID,b.SOLICITUD_ID,b.FECHA_ESTADO,b.FECHA_CITA, b.TIPO_ELEMENTO_ID ".
				" from informe_petec_pendientesm b ".
				" where b.STATUS='PENDI_PETEC'  and b.ASESOR ='' ".
				"  $concepto ".
				$plaza.
				//" AND b.MUNICIPIO_ID IN (select a.MUNICIPIO_ID from tbl_plazas a where a.PLAZA='$plaza') ".
				" order by b.VIEWS,b.FECHA_ESTADO ASC";
			//echo $query1;
        	                $r = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);
				$mypedido="";
				$mypedidoresult=array();
				if($r->num_rows > 0){//recorro los registros de la consulta para 
        	                        while($row = $r->fetch_assoc()){
                	                        $result[] = $row;
						
						$rta=$this->pedidoOcupadoFenix($row);
						//var_dump($rta);

                                        if($rta=="No rows!!!!"){//me sirve, salgo del ciclo y busco este pedido...
                                                //echo "el pedido es: ".$row['PEDIDO_ID'];

                                                $mypedido=$row['PEDIDO_ID'];
                                                $mypedidoresult=$rta;
                                                break;
                                        }

					/*

						if($rta['ESTADO_BLOQUEO']=='N'){//me sirve, salgo del ciclo y busco este pedido...
							//echo "el pedido es: ".$row['PEDIDO_ID'];
							$mypedido=$row['PEDIDO_ID'];
							$mypedidoresult=$rta;
							break;
						}

					*/
						//echo $row['PEDIDO_ID']." NO SIRVE!!!";
	                                }
	
				}
			
			}//end if

			}//end mypedido if

			if($mypedido==''){
				$pedds=explode(",", $pedidos_ignorados);
				if(count($pedds)>0){
					$mypedido=$pedds[0];
				}
			}
			
			//de una lo ocupo cucho cucho!!!!
			$sqlupdate="update informe_petec_pendientesm set ASESOR='$user',PROGRAMACION='',VIEWS=VIEWS+1 where PEDIDO_ID = '$mypedido' and STATUS='PENDI_PETEC'";
                        $x = $this->mysqli->query($sqlupdate);

			$query1="SELECT b.ID,b.PEDIDO_ID,b.SUBPEDIDO_ID,b.SOLICITUD_ID,b.TIPO_ELEMENTO_ID,b.PRODUCTO,b.UEN_CALCULADA,b.ESTRATO,b.MUNICIPIO_ID,b.DIRECCION_SERVICIO,b.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(b.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,b.FUENTE,b.CONCEPTO_ID,b.FECHA_ESTADO,b.USUARIO_BLOQUEO_FENIX,b.TIPO_TRABAJO,b.CONCEPTO_ANTERIOR,b.FECHA_CITA from informe_petec_pendientesm b where b.PEDIDO_ID = '$mypedido' and b.STATUS='PENDI_PETEC' $concepto ";

			//echo $query1;
			$r = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                $ids="";
                                $sep="";
                                while($row = $r->fetch_assoc()){
                                        $result[] = $row;
                                        $ids=$ids.$sep.$row['ID'];
                                        $sep=",";
                                }
                                //$sqlupdate="update informe_petec_pendientesm set ASESOR='$user',VIEWS=VIEWS+1 where ID in ($ids)";
                                //$x = $this->mysqli->query($sqlupdate);
				$INSERTLOG="insert into vistas_pedidos(user,pedido_id) values ('$user','$mypedido')";
				$x = $this->mysqli->query($INSERTLOG);
				$sqlfeed="insert into portalbd.activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$user','$username','','','PEDIDO: $mypedido','DEMEPEDIDO')";
                                $rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

				//sleep(20);
				unlink($filename);
                                echo json_encode($result);
                                $this->response('', 200); // send user details
                        }else{//i have pretty heavy problems over here...
				$this->response('SYSTEM PANIC!',200);
			}
			unlink($filename);

                        $this->response('nothing',204);        // If no records "No Content" status
                }


                private function logout(){
                        if($this->get_request_method() != "POST"){
                                $this->response('',406);
                        }
                        $params = json_decode(file_get_contents('php://input'),true);
                        //$params = file_get_contents('php://input');

                        $login = $params['user'];
			            $fecha = $params['fecha'];
			            $today = date("Y-m-d");

                        $sqlfeed="insert into portalbd.activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$login','$login','LOGIN','logged off','','LOGIN') ";
                        $rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);
                        

			//le quito al asesor lo que sea que este ocupando en la tabla de operaciones!!!!
			$sqlupdate="update informe_petec_pendientesm set ASESOR='' where ASESOR='$login'";
                                //echo $sqlupdate;
                        $xxx = $this->mysqli->query($sqlupdate);

                        if(!empty($login)){
                                $query="SELECT id, usuario FROM registro_ingreso_usuarios WHERE usuario = '$login' AND fecha_ingreso between '$today 00:00:00' and '$today 23:59:59'  LIMIT 1";
                                $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
				//$this->response($this->json('info:'.$r->num_rows), 201);
                                if($r->num_rows > 0) {
                                        $result = $r->fetch_assoc();
                                        // If success everythig is good send header as "OK" and user details
					   $idd=$result['id'];
					   $sqllogin="update registro_ingreso_usuarios set status='logged off',fecha_salida='$fecha',salidas=salidas+1 where id=$idd";
                                        $rr = $this->mysqli->query($sqllogin);
                                        $this->response($this->json('logged out'), 201);

                                }//doesnt have sense, do nothing
                                $this->response($this->json('User do not exist!!!'), 400);      // If no records "No Content" status


                        }

                        $error = array('status' => "Failed", "msg" => "Invalid User Name or password ($login) - ($password)");
                        $this->response($this->json($error), 400);
                }


		private function login(){
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			$params = json_decode(file_get_contents('php://input'),true);

			$login = $params['username'];
			$password = $params['password'];
			$fecha = $params['fecha'];

			if(!empty($login) and !empty($password)){
				$login=strtoupper($login);
				$query="SELECT ID as id, USUARIO_NOMBRE as name, USUARIO_ID as login, GRUPO,CARGO_ID FROM tbl_usuarios WHERE USUARIO_ID = '$login' AND PASSWORD = MD5('$password') LIMIT 1";
				$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);


				if($r->num_rows > 0) {
					$result = $r->fetch_assoc();	
					// If success everythig is good send header as "OK" and user details
					$login=$result['login'];
					//here i can control this session....

					//its a login, search if theres a login today
					$today = date("Y-m-d");
					$name=$result['name'];
					 $sqlfeed="insert into portalbd.activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$login','$name','LOGIN','logged in','','LOGIN') ";
                        $rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

					//echo "nombre: $name";
					$sqllogin="SELECT id,fecha_ingreso FROM registro_ingreso_usuarios WHERE fecha_ingreso between '$today 00:00:00' and '$today 23:59:59' and usuario='$login' limit 1";
					//echo $sqllogin;
					
					$rr = $this->mysqli->query($sqllogin);
					if($rr->num_rows > 0){//update just the status, not dates cuz he already loged in early
						$result1 = $rr->fetch_assoc();
						$idd=$result1['id'];
						$sqllogin="update registro_ingreso_usuarios set status='logged in',ingresos=ingresos+1 where id=$idd";
						$rrr = $this->mysqli->query($sqllogin);
						$result['fecha_ingreso']=$result1['fecha_ingreso'];
						$name=$result['name'];
						//echo "kaiden!! ";
						//var_dump($result);
					}else{//make an insert, first time logged in today
						$ip=$_SERVER['REMOTE_ADDR'];
						$sqllogin="insert into registro_ingreso_usuarios(usuario,status,ip,fecha_ingreso) values('$login','logged in','$ip','$fecha')";
                                                $rrr = $this->mysqli->query($sqllogin);
						$idi=$this->mysqli->insert_id;
						$sqllogin="SELECT fecha_ingreso FROM registro_ingreso_usuarios WHERE fecha_ingreso between '$today 00:00:00' and '$today 23:59:59' and usuario='$login' limit 1";

                                        	$rs = $this->mysqli->query($sqllogin);
						if($rs->num_rows > 0){
							$result1 = $rs->fetch_assoc();
							$result['fecha_ingreso']=$result1['fecha_ingreso'];
						}else{
							$result['fecha_ingreso']='N/A';
						}
						//echo "kai!! ";
						//var_dump($result);
					}
					
					$result['name']=utf8_encode($result['name']);
					$this->response($this->json($result), 201);
				}
				$this->response($this->json('User do not exist!!!'), 400);	// If no records "No Content" status
				
			}
			
			$error = array('status' => "Failed", "msg" => "Invalid User Name or password ($login) - ($password)");
			$this->response($this->json($error), 400);
		}
	
                 private function insertTransaccionNCA(){
                       if($this->get_request_method() != "POST"){
                                $this->response('',406);
                        }

                        $transaccion = json_decode(file_get_contents("php://input"),true);

			$transaccion = $transaccion['transaccion'];
                        $column_names = array('OFERTA','MUNICIPIO_ID','TRANSACCION','ESTADO','FECHA','DURACION','INCIDENTE','FECHA_INICIO','FECHA_FIN','ESTADO_FINAL','OBSERVACION','USUARIO');
                        $keys = array_keys($transaccion);
                        $columns = '';
                        $values = '';
			
			$useri=$transaccion['USUARIO'];
			$username=$transaccion['USERNAME'];
			
			$oferta=$transaccion['OFERTA'];
			$estado_final=$transaccion['ESTADO_FINAL'];
                        //echo var_dump($transaccion);
			//echo var_dump($keys);
                        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
                           if(!in_array($desired_key, $keys)) {
                                        $$desired_key = '';
                                }else{
                                        $$desired_key = $transaccion[$desired_key];
                                }
                                $columns = $columns.$desired_key.',';
                                $values = $values."'".$transaccion[$desired_key]."',";
                        }
                        $today = date("Y-m-d H:i:s");
                        $query = "INSERT INTO  transacciones_nca (".trim($columns,',').") VALUES(".trim($values,',').")";
			//echo $query;
                        if(!empty($transaccion)){
				//echo $query;
                           	$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

				$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$useri','$username','NCA','$estado_final','OFERTA: $oferta','NCA') ";
				$rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);
				$this->response(json_encode(array("msg"=>"OK","transaccion" => $transaccion)),200);

                        }else{
                                $this->response('',200);        //"No Content" status
                                //$this->response("$query",200);        //"No Content" status
                        }

                }

		private function insertUsuario(){
                       if($this->get_request_method() != "POST"){
                                $this->response('',406);
                        }

                        $usuario = json_decode(file_get_contents("php://input"),true);
			//echo var_dump($usuario);

                        $usuario = $usuario['usuario'];
                        $column_names = array('USUARIO_ID','USUARIO_NOMBRE','CEDULA_ID','GRUPO','EQUIPO_ID','CORREO_USUARIO','FUNCION','TURNO','CARGO_ID','SUPERVISOR');
                        $keys = array_keys($usuario);
                        $columns = '';
                        $values = '';

                        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
                           if(!in_array($desired_key, $keys)) {
                                        $$desired_key = '';
                                }else{
                                        $$desired_key = $usuario[$desired_key];
                                }
                                $columns = $columns.$desired_key.',';
                                $values = $values."'".strtoupper($usuario[$desired_key])."',";
                        }
                        $today = date("Y-m-d H:i:s");
                        $query = "INSERT INTO  tbl_usuarios (".trim($columns,',').",PASSWORD) VALUES(".trim($values,',').",MD5('".$usuario['PASSWORD']."'))";
                        //echo $query;
                        if(!empty($usuario)){
                                //echo $query;
                                $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                                $this->response(json_encode(array("msg"=>"OK","transaccion" => $usuario)),200);
                        }else{
                                $this->response('',200);        //"No Content" status
                                //$this->response("$query",200);        //"No Content" status
                        }

                }

                private function editUsuario(){
                       if($this->get_request_method() != "POST"){
                                $this->response('',406);
                        }

                        $usuario = json_decode(file_get_contents("php://input"),true);
                        //echo var_dump($usuario);

                        $usuario = $usuario['usuario'];
                        $column_names = array('USUARIO_ID','USUARIO_NOMBRE','CEDULA_ID','GRUPO','EQUIPO_ID','CORREO_USUARIO','FUNCION','TURNO','CARGO_ID','SUPERVISOR');
                        $keys = array_keys($usuario);
                        $columns = '';
                        $values = '';
			
			$UPDATE="";
			$SEP="";
                        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
                           if(!in_array($desired_key, $keys)) {
                                        $$desired_key = '';
                                }else{
                                        $$desired_key = $usuario[$desired_key];
                                }
                                $columns = $columns.$desired_key.',';
                                $values = $values."'".$usuario[$desired_key]."',";
				$UPDATE=$UPDATE.$SEP.$desired_key." = '".strtoupper($usuario[$desired_key])."' ";
				$SEP=",";
                        }
                        $today = date("Y-m-d H:i:s");

			$passcode="";
			if($usuario['PASSWORD']!=""){
				$passcode=" , PASSWORD=MD5('".$usuario['PASSWORD']."')";
			}
                        $query = "UPDATE tbl_usuarios SET $UPDATE $passcode WHERE ID=".$usuario['ID'];
                        //echo $query;
			
                        if(!empty($usuario)){
                                //echo $query;
                                $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                                $this->response(json_encode(array("msg"=>"OK","transaccion" => $usuario)),200);
                        }else{
                                $this->response('',200);        //"No Content" status
                                //$this->response("$query",200);        //"No Content" status
                        }

                }


                private function getUsuario(){
                       if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
			$user = $this->_request['userID'];


			$query="select * from tbl_usuarios where ID=$user";

			$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                $ids="";
                                $sep="";
				$usuario='';
                                while($row = $r->fetch_assoc()){
                                        $usuario = $row;
                                }
				$usuario["PASSWORD"]="";
                                $this->response($this->json(array($usuario,"OK")), 200); // send user details
			}
			
			$this->response('',204);

                }



                private function csvNCA(){
                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $login = $this->_request['login'];
                        $fechaIni = $this->_request['fechaIni'];
                        $fechaFin = $this->_request['fechaFin'];

                        $today = date("Y-m-d h:i:s");
                        $filename="NCA-$login-$today.csv";
                        $query=" SELECT ".
                        " OFERTA,MUNICIPIO_ID,TRANSACCION,ESTADO,FECHA,DURACION,INCIDENTE,FECHA_INICIO,FECHA_FIN,ESTADO_FINAL,OBSERVACION,USUARIO ".
                        " from transacciones_nca where ".
                        " FECHA_FIN between '$fechaIni 00:00:00' and '$fechaFin 23:59:59' ";

                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                $fp = fopen("../tmp/$filename", 'w');
                                fputcsv($fp, array('OFERTA','MUNICIPIO_ID','TRANSACCION','ESTADO','FECHA','DURACION','INCIDENTE','FECHA_INICIO','FECHA_FIN','ESTADO_FINAL','OBSERVACION','USUARIO'));
                                while($row = $r->fetch_assoc()){
                                        //$result[] = $row;
                                        fputcsv($fp, $row);
                                }
                                fclose($fp);

                                $this->response($this->json(array($filename,$login)), 200); // send user details
                        }

                        $this->response('',204);        // If no records "No Content" status

                }
	


		private function listadoTransaccionesNCA(){
			 if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $fechaini = $this->_request['fechaInicio'];
                        $fechafin = $this->_request['fechaFin'];
                        $page = $this->_request['page'];
                        $today = date("Y-m-d");

                        if($page=="undefined"){
                                $page="0";
                        }else{
                                $page=$page-1;
                        }
                        $page=$page*100;
                        //counter
                        $query="SELECT count(*) as counter from transacciones_nca where FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
                        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                        $counter=0;
                        if($rr->num_rows > 0){
                                $result = array();
                                if($row = $rr->fetch_assoc()){
                                        $counter = $row['counter'];
                                }
                        }


                        $query="SELECT * FROM transacciones_nca where FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59' order by FECHA_FIN desc limit 100 offset $page";
                        //echo $query;
                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                while($row = $r->fetch_assoc()){
                                        $result[] = $row;
                                }
                                $this->response($this->json(array($result,$counter)), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status

		}

		
                private function listadoUsuarios(){
                         if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        //counter
                        
			$query="SELECT count(*) as counter from tbl_usuarios";
                        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                        $counter=0;
                        if($rr->num_rows > 0){
                                $result = array();
                                if($row = $rr->fetch_assoc()){
                                        $counter = $row['counter'];
                                }
                        }


                        $query="SELECT * FROM tbl_usuarios order by GRUPO, USUARIO_NOMBRE ASC";
                        //echo $query;
                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                while($row = $r->fetch_assoc()){
                                        //$result[] = $row;
					//echo "name: ".utf8_encode($row['USUARIO_NOMBRE'])."\n ";
					$row['USUARIO_NOMBRE']=utf8_encode($row['USUARIO_NOMBRE']);
					$result[] = $row;
                                }
                                $this->response($this->json(array($result,$counter)), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status

                }

                //Funcion para traer datos del Activity Feed.
                private function getFeed(){
                         if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        //counter
			             $today = date("Y-m-d");

                        $query="SELECT count(*) as counter from activity_feed where fecha between '$today 00:00:00' and '$today 23:59:59' ";
                        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                        $counter=0;
                        if($rr->num_rows > 0){
                                $result = array();
                                if($row = $rr->fetch_assoc()){
                                        $counter = $row['counter'];
                                }
                        }



                        $query="SELECT *, CAST(IF(TIMEDIFF(NOW(), fecha) > '00:01:00', ".
                        " CASE WHEN MINUTE(TIMEDIFF(NOW(), fecha)) = 1 ".
                        "   THEN CONCAT(MINUTE(TIMEDIFF(NOW(), fecha)),' Minuto') ".
                        "   ELSE CONCAT(MINUTE(TIMEDIFF(NOW(), fecha)),' Minutos') END, ".
                        " CASE WHEN SECOND(TIMEDIFF(NOW(), fecha)) = 1 ".
                        "   THEN CONCAT(SECOND(TIMEDIFF(NOW(), fecha)),' Segundo') ".
                        "   ELSE CONCAT(SECOND(TIMEDIFF(NOW(), fecha)),' Segundos')END) ".
                        "   AS CHAR) AS FECHA2 ".
                        " FROM activity_feed WHERE fecha BETWEEN '$today 00:00:00' AND '$today 23:59:59' ".
                        " ORDER BY id DESC ".
                        " LIMIT 10 ";
                        //echo $query;
                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                while($row = $r->fetch_assoc()){
                                        //$result[] = $row;
                                        //echo "name: ".utf8_encode($row['USUARIO_NOMBRE'])."\n ";
                                        //$row['USUARIO_NOMBRE']=utf8_encode($row['USUARIO_NOMBRE']);
                                        $result[] = $row;
                                }
                                $this->response($this->json(array($result,$counter)), 200); // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status

                }

            //Funcion para traer datos del Login Feed

             private function getLoginFeed(){
                         if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        //counter
                         $today = date("Y-m-d");

                        $query="SELECT count(*) as counter from portalbd.registro_ingreso_usuarios where fecha_ingreso between '$today 00:00:00' and '$today 23:59:59' ";
                        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                        $counter=0;
                        if($rr->num_rows > 0){
                                $result = array();
                                if($row = $rr->fetch_assoc()){
                                        $counter = $row['counter'];
                                }
                        }


                        $query="SELECT iu.id , iu.user as usuario , TU.grupo , iu.status, date_format(iu.fecha,'%a %h:%i %p') as fecha_ingreso FROM portalbd.activity_feed iu left JOIN portalbd.tbl_usuarios TU ON iu.user = TU.USUARIO_ID where iu.fecha between '$today 00:00:00' and '$today 23:59:59' and iu.grupo='LOGIN'order by iu.id desc limit 10";
                        //echo $query;
                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        if($r->num_rows > 0){
                                $result = array();
                                while($row = $r->fetch_assoc()){
                                        //$result[] = $row;
                                        //echo "name: ".utf8_encode($row['USUARIO_NOMBRE'])."\n ";
                                        //$row['USUARIO_NOMBRE']=utf8_encode($row['USUARIO_NOMBRE']);
                                        $result[] = $row;
                                }
                                $this->response($this->json(array($result,$counter)), 200);
                                //echo $this; // send user details
                        }
                        $this->response('',204);        // If no records "No Content" status

                }


		private function logVista(){
			 if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $user = $this->_request['userID'];
			$vista= $this->_request['vista'];

                        $query="insert into vistas_log(user,vista) values('$user','$vista')";

                        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

                        $this->response('',204);

		}
	
		private function customers(){	
			if($this->get_request_method() != "GET"){
				$this->response('',406);
			}
			$query="SELECT distinct c.customerNumber, c.customerName, c.email, c.address, c.city, c.state, c.postalCode, c.country FROM angularcode_customers c order by c.customerNumber desc";
			$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

			if($r->num_rows > 0){
				$result = array();
				while($row = $r->fetch_assoc()){
					$result[] = $row;
				}
				$this->response($this->json($result), 200); // send user details
			}
			$this->response('',204);	// If no records "No Content" status
		}
		private function customer(){	
			if($this->get_request_method() != "GET"){
				$this->response('',406);
			}
			$id = (int)$this->_request['id'];
			if($id > 0){	
				$query="SELECT distinct c.customerNumber, c.customerName, c.login, c.address, c.city, c.state, c.postalCode, c.country FROM angularcode_customers c where c.customerNumber=$id";
				$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
				if($r->num_rows > 0) {
					$result = $r->fetch_assoc();	
					$this->response($this->json($result), 200); // send user details
				}
			}
			$this->response('',204);	// If no records "No Content" status
		}
		
		private function deleteCustomer(){
			if($this->get_request_method() != "DELETE"){
				$this->response('',406);
			}
			$id = (int)$this->_request['id'];
			if($id > 0){				
				$query="DELETE FROM angularcode_customers WHERE customerNumber = $id";
				$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
				$success = array('status' => "Success", "msg" => "Successfully deleted one record.");
				$this->response($this->json($success),200);
			}else
				$this->response('',204);	// If no records "No Content" status
		}
		
		/*
		 *	Encode array into JSON
		*/
		private function json($data){
			if(is_array($data)){
				return json_encode($data);
			}
		}
	}
	
	// Initiiate Library
	
	$api = new API;
	$api->processApi();
?>
