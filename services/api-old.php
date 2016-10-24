<?php
 	error_reporting(E_ALL);
	ini_set('display_errors', '1');
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


		private function insertPedido(){
                       if($this->get_request_method() != "POST"){
                                $this->response('',406);
                        }
			
			//actualizar pedidos con el log de la transaccion y la tabla de pendientes_petec			

                        $pedido = json_decode(file_get_contents("php://input"),true);
                        $column_names = array('pedido', 'fuente', 'actividad','estado', 'user','duracion','accion','fecha_inicio','fecha_fin','PEDIDO_ID','SUBPEDIDO_ID','SOLICITUD_ID','MUNICIPIO_ID');
                        $keys = array_keys($pedido);
                        $columns = '';
                        $values = '';
			$fecha_estado='';
			$fecha_estado=$pedido['pedido']['FECHA_ESTADO'];
			$iddd=$pedido['pedido']['ID'];
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
				$query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado,concepto_final,source) VALUES(".trim($values,',').",'$fecha_estado','$concepto_final','AUTO')";
			
                                $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
				//cierro el registro en la tabla de automatizacion asignaciones
				$sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='$today',CONCEPTO_ID='$concepto_final',STATUS='CERRADO_PETEC' WHERE ID=$iddd ";
				$rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);
				$this->response(json_encode(array("msg"=>"$concepto_final","data" => $today)),200);

                        }else{
                                $this->response('',204);        //"No Content" status
                                //$this->response("$query",200);        //"No Content" status
			}

		}

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
                                " TO_CHAR(SOL.FECHA_ESTADO,'RRRR-MM-DD hh24:mi:ss') as FECHA_FINAL".
                                " , SOL.CONCEPTO_ID".
                                " FROM FNX_SOLICITUDES SOL".
                                //" , FNX_PEDIDOS".
                                //" , FNX_SUBPEDIDOS".
                                //" , FNX_TRABAJOS_SOLICITUDES   ".
                                "     WHERE ".
                                "     SOL.PEDIDO_ID='$pedido_id'".
                                "      AND SOL.SUBPEDIDO_ID='$subpedido_id'".
                                "      AND SOL.SOLICITUD_ID='$solicitud_id'".
                                //"      AND FNX_TRABAJOS_SOLICITUDES.TIPO_TRABAJO IN ('NUEVO', 'CAMBI') ".
                                //"      AND SOL.ESTADO_BLOQUEO='N' ".
                                //"      AND SOL.SUBPEDIDO_ID=FNX_SUBPEDIDOS.SUBPEDIDO_ID(+) ".
                                //"      AND SOL.PEDIDO_ID=FNX_SUBPEDIDOS.PEDIDO_ID(+) ".
                                //"      AND SOL.PEDIDO_ID=FNX_PEDIDOS.PEDIDO_ID(+) ".
                                //"      AND SOL.PEDIDO_ID=FNX_TRABAJOS_SOLICITUDES.PEDIDO_ID(+) ".
                                //"      AND SOL.SUBPEDIDO_ID=FNX_TRABAJOS_SOLICITUDES.SUBPEDIDO_ID(+) ".
                                //"      AND SOL.SOLICITUD_ID=FNX_TRABAJOS_SOLICITUDES.SOLICITUD_ID(+) ".
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
						$sqlupdate="update informe_petec_pendientesm set ESTUDIOS=ESTUDIOS+1,ASESOR='',CONCEPTO_ID='".$row['CONCEPTO_ID']."' WHERE ID=$id ";
						$this->mysqli->query($sqlupdate);
						return $row['CONCEPTO_ID'];
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
                        $today = date("Y-m-d");
			
                        $query="SELECT a.ID,a.PEDIDO_ID,a.SUBPEDIDO_ID,a.SOLICITUD_ID,a.TIPO_ELEMENTO_ID,a.PRODUCTO,a.UEN_CALCULADA,a.ESTRATO,MUNICIPIO_ID,a.DIRECCION_SERVICIO,a.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(a.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,a.FUENTE,a.CONCEPTO_ID,a.FECHA_ESTADO from informe_petec_pendientesm a where a.FECHA_ESTADO between '$fechaini 00:00:00' and '$fechafin 23:59:59' limit 100";
			$query="SELECT id, pedido, fuente, actividad, fecha_fin, estado,duracion,accion,concepto_final,user from pedidos where fecha_fin between '$fechaini 00:00:00' and '$fechafin 23:59:59' order by fecha_fin desc limit 100";
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

                private function listadoPendientes(){//pendientes
                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }

                        $fechaini = $this->_request['fecha_inicio'];
                        $fechafin = $this->_request['fecha_fin'];
                        $today = date("Y-m-d");

                        //$query="SELECT a.ID,a.PEDIDO_ID,a.PEDIDO,a.SUBPEDIDO_ID,a.SOLICITUD_ID,a.TIPO_ELEMENTO_ID,a.PRODUCTO,a.UEN_CALCULADA,a.ESTRATO,MUNICIPIO_ID,a.DIRECCION_SERVICIO,a.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(a.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,a.FUENTE,a.CONCEPTO_ID,a.FECHA_ESTADO from informe_petec_pendientesm a where a.FECHA_ESTADO between '$fechaini 00:00:00' and '$fechafin 23:59:59' and a.STATUS='PENDI_PETEC' order by a.FECHA_ESTADO ASC limit 100";
                        $query="SELECT a.ID,a.PEDIDO_ID,a.PEDIDO,a.SUBPEDIDO_ID,a.SOLICITUD_ID,a.TIPO_ELEMENTO_ID,a.PRODUCTO,a.UEN_CALCULADA,a.ESTRATO,MUNICIPIO_ID,a.DIRECCION_SERVICIO,a.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(a.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,a.FUENTE,a.CONCEPTO_ID,a.FECHA_ESTADO from informe_petec_pendientesm a where a.STATUS='PENDI_PETEC' order by a.FECHA_ESTADO ASC limit 100";
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
                        //si el actual usuario tenia un pedido "agarrado, hay que liberarlo"
                        $pedido_actual = $this->_request['pedido_actual'];
                        if($pedido_actual!=''){//en este caso tenia pedido antes, estaba trabajando uno, debo actualizarlo para dejarlo libre
                                $sqlupdate="update informe_petec_pendientesm set ASESOR='' where ASESOR='$user' ";
                                $xxx = $this->mysqli->query($sqlupdate);
                        }

                        $user=strtoupper($user);
                        $today = date("Y-m-d");

                        $query1="SELECT a.ID,a.PEDIDO_ID,a.SUBPEDIDO_ID,a.SOLICITUD_ID,a.TIPO_ELEMENTO_ID,a.PRODUCTO,a.UEN_CALCULADA,a.ESTRATO,MUNICIPIO_ID,a.DIRECCION_SERVICIO,a.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,a.FUENTE,a.CONCEPTO_ID,a.FECHA_ESTADO,a.ASESOR,a.STATUS from informe_petec_pendientesm a JOIN (SELECT distinct(a.pedido) as pedido2,(select b.id from informe_petec_pendientesm b where b.pedido=a.pedido order by id desc limit 1 ) as id2 FROM `informe_petec_pendientesm` a WHERE a.PEDIDO_ID='$pedido' and (a.STATUS='PENDI_PETEC' or a.STATUS='BUSCADO_PETEC')) kai on a.id=kai.id2";

                         //$this->response($query1,200);
                        $r = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);
			$busy="";
                        if($r->num_rows > 0){
                                $result = array();
                                $ids="";
                                $sep="";
                                while($row = $r->fetch_assoc()){
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
                                " , TO_CHAR(SOL.FECHA_ESTADO,'RRRR-MM-DD hh24:mi:ss') as FECHA_ESTADO ".
                                " , TO_CHAR(SOL.FECHA_CITA,'RRRR-MM-DD') as FECHA_CITA ".
                                " , FN_NOMBRE_PRODUCTO(SOL.PRODUCTO_ID) AS PRODUCTO ".
                                " , TRIM(FN_UEN_CALCULADA(SOL.PEDIDO_ID,SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID)) AS UEN_CALCULADA ".
                                " , FN_VALOR_CARACT_SUBPEDIDO(SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID,37) ESTRATO ".
                                " , SOL.CONCEPTO_ID ".
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
                                "      and SOL.TIPO_ELEMENTO_ID IN ('BDID', 'TDID','BDIDE1', 'TDIDE1', 'BDODE1', 'TDODE1', 'TO', 'TOIP','INSHFC', 'INSIP', 'INSTIP', 'SEDEIP', 'P2MB', '3PLAY', 'CNTXIP', 'ACCESP', 'PLANT', 'PLP', 'PTLAN', 'PMULT', 'PPCM', 'PBRI', 'PPRI', 'INSTA', 'TP', 'PBRI','SLL', 'TC', 'SLLBRI', 'TCBRI', 'SLLPRI', 'TCPRI','SEDEIP','EQURED','EQACCP','STBOX')".
                                "      AND SOL.SUBPEDIDO_ID=FNX_SUBPEDIDOS.SUBPEDIDO_ID(+) ".
                                "      AND SOL.PEDIDO_ID=FNX_SUBPEDIDOS.PEDIDO_ID(+) ".
                                "      AND SOL.PEDIDO_ID=FNX_PEDIDOS.PEDIDO_ID(+) ".
                                "      AND SOL.PEDIDO_ID=FNX_TRABAJOS_SOLICITUDES.PEDIDO_ID(+) ".
                                "      AND SOL.SUBPEDIDO_ID=FNX_TRABAJOS_SOLICITUDES.SUBPEDIDO_ID(+) ".
                                "      AND SOL.SOLICITUD_ID=FNX_TRABAJOS_SOLICITUDES.SOLICITUD_ID(+) ";
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
                                $sqlfenix="SELECT ".
				"  SOL.ESTADO_BLOQUEO".
				",  SOL.USUARIO_ID".
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
					return $row;
                                        //echo $concepto_id." - ".$row['CONCEPTO_ID']."\n";
                                }
                                return "No rows!!!!";
                }


		//este demepedido valida contra fenix antes de suministrar el pedido...
                private function demePedido(){

                        if($this->get_request_method() != "GET"){
                                $this->response('',406);
                        }
                        $user = $this->_request['userID'];
                        $concepto = $this->_request['concepto'];
			$plaza = $this->_request['plaza'];
		
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
			if($hora<7){
				$uphold="1";
			}else{
				$uphold="2";
			}
			
			$query1="select PEDIDO_ID,SUBPEDIDO_ID,SOLICITUD_ID,FECHA_ESTADO,FECHA_CITA from informe_petec_pendientesm where STATUS='PENDI_PETEC'  and ASESOR ='' and FECHA_CITA= CURDATE() + INTERVAL $uphold DAY  and CONCEPTO_ID='$concepto' AND MUNICIPIO_ID IN (select a.MUNICIPIO_ID from tbl_plazas a where a.PLAZA='$plaza') order by VIEWS,FECHA_ESTADO ASC";
			
			//$query1="select PEDIDO_ID,SUBPEDIDO_ID,SOLICITUD_ID,FECHA_ESTADO,FECHA_CITA from informe_petec_pendientesm where STATUS='PENDI_PETEC'  and ASESOR ='' and CONCEPTO_ID='$concepto' AND MUNICIPIO_ID IN (select a.MUNICIPIO_ID from tbl_plazas a where a.PLAZA='$plaza') order by VIEWS,FECHA_ESTADO ASC";

			$query1="select PEDIDO_ID,SUBPEDIDO_ID,SOLICITUD_ID,FECHA_ESTADO,FECHA_CITA from informe_petec_pendientesm where STATUS='PENDI_PETEC'  and ASESOR ='' and CONCEPTO_ID='$concepto' AND MUNICIPIO_ID IN (select a.MUNICIPIO_ID from tbl_plazas a where a.PLAZA='$plaza') order by VIEWS,FECHA_ESTADO ASC";
			$rr = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);

                        $mypedido="";
                        $mypedidoresult=array();
			if($rr->num_rows > 0){//recorro los registros de la consulta para
                                while($row = $rr->fetch_assoc()){
                                        $result[] = $row;

                                        $rta=$this->pedidoOcupadoFenix($row);
                                        if($rta['ESTADO_BLOQUEO']=='N'){//me sirve, salgo del ciclo y busco este pedido...
                                                //echo "el pedido es: ".$row['PEDIDO_ID'];
                                                $mypedido=$row['PEDIDO_ID'];
                                                $mypedidoresult=$rta;
                                                break;
                                        }
                                }
			//2.traigo solo los pedidos mas viejos en la base de datos...	
                        }else {
				$query1="select PEDIDO_ID,SUBPEDIDO_ID,SOLICITUD_ID,FECHA_ESTADO,FECHA_CITA from informe_petec_pendientesm where STATUS='PENDI_PETEC'  and ASESOR ='' and CONCEPTO_ID='$concepto' AND MUNICIPIO_ID IN (select a.MUNICIPIO_ID from tbl_plazas a where a.PLAZA='$plaza') order by VIEWS,FECHA_ESTADO ASC";
			//echo $query1;
        	                $r = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);
				$mypedido="";
				$mypedidoresult=array();
				if($r->num_rows > 0){//recorro los registros de la consulta para 
        	                        while($row = $r->fetch_assoc()){
                	                        $result[] = $row;
						
						$rta=$this->pedidoOcupadoFenix($row);
						//var_dump($rta);
						if($rta['ESTADO_BLOQUEO']=='N'){//me sirve, salgo del ciclo y busco este pedido...
							//echo "el pedido es: ".$row['PEDIDO_ID'];
							$mypedido=$row['PEDIDO_ID'];
							$mypedidoresult=$rta;
							break;
						}
						//echo $row['PEDIDO_ID']." NO SIRVE!!!";
	                                }
	
				}
			
			}//end if
			//INSERTAR REGISTRO EN EL LOG DE EVENTOS VISTAS
			//$INSERTLOG="insert into vistas_pedidos(user,pedido_id) values ('','')";
			$query1="SELECT a.ID,a.PEDIDO_ID,a.SUBPEDIDO_ID,a.SOLICITUD_ID,a.TIPO_ELEMENTO_ID,a.PRODUCTO,a.UEN_CALCULADA,a.ESTRATO,a.MUNICIPIO_ID,a.DIRECCION_SERVICIO,a.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,a.FUENTE,a.CONCEPTO_ID,a.FECHA_ESTADO,a.USUARIO_BLOQUEO_FENIX,a.TIPO_TRABAJO from informe_petec_pendientesm a where a.PEDIDO_ID = '$mypedido' and a.STATUS='PENDI_PETEC' ";

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
                                $sqlupdate="update informe_petec_pendientesm set ASESOR='$user',VIEWS=VIEWS+1 where ID in ($ids)";
                                $x = $this->mysqli->query($sqlupdate);
				$INSERTLOG="insert into vistas_pedidos(user,pedido_id) values ('$user','$mypedido')";
				$x = $this->mysqli->query($INSERTLOG);
				//sleep(20);
				unlink($filename);
                                echo json_encode($result);
                                $this->response('', 200); // send user details
                        }else{//i have pretty heavy problems over here...
				
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
			//echo "login: $login - ".var_dump($params)."------".var_dump(file_get_contents('php://input'));
			//$this->response($this->json('login:'.$login), 201);
			//return;
			$today = date("Y-m-d");
			//echo "'login $login ".var_dump($params);
			//$this->response($this->json('login:'.$login), 201);

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
					$sqllogin="update registro_ingreso_usuarios set status='logged off',fecha_salida=now(),salidas=salidas+1 where id=$idd";
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

			if(!empty($login) and !empty($password)){
				$login=strtoupper($login);
				$query="SELECT ID as id, USUARIO_NOMBRE as name, USUARIO_ID as login, GRUPO FROM tbl_usuarios WHERE USUARIO_ID = '$login' AND password = MD5('$password') LIMIT 1";
				$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

				if($r->num_rows > 0) {
					$result = $r->fetch_assoc();	
					// If success everythig is good send header as "OK" and user details
					$login=$result['login'];
					//here i can control this session....

					//its a login, search if theres a login today
					$today = date("Y-m-d");
					$name=$result['name'];
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
						$sqllogin="insert into registro_ingreso_usuarios(usuario,status,ip) values('$login','logged in','$ip')";
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
		
		private function insertCustomer(){
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}

			$customer = json_decode(file_get_contents("php://input"),true);
			$column_names = array('customerName', 'login', 'city', 'address', 'country');
			$keys = array_keys($customer);
			$columns = '';
			$values = '';
			foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
			   if(!in_array($desired_key, $keys)) {
			   		$$desired_key = '';
				}else{
					$$desired_key = $customer[$desired_key];
				}
				$columns = $columns.$desired_key.',';
				$values = $values."'".$$desired_key."',";
			}
			$query = "INSERT INTO angularcode_customers(".trim($columns,',').") VALUES(".trim($values,',').")";
			if(!empty($customer)){
				$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
				$success = array('status' => "Success", "msg" => "Customer Created Successfully.", "data" => $customer);
				$this->response($this->json($success),200);
			}else
				$this->response('',204);	//"No Content" status
		}
		private function updateCustomer(){
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			$customer = json_decode(file_get_contents("php://input"),true);
			$id = (int)$customer['id'];
			$column_names = array('customerName', 'login', 'city', 'address', 'country');
			$keys = array_keys($customer['customer']);
			$columns = '';
			$values = '';
			foreach($column_names as $desired_key){ // Check the customer received. If key does not exist, insert blank into the array.
			   if(!in_array($desired_key, $keys)) {
			   		$$desired_key = '';
				}else{
					$$desired_key = $customer['customer'][$desired_key];
				}
				$columns = $columns.$desired_key."='".$$desired_key."',";
			}
			$query = "UPDATE angularcode_customers SET ".trim($columns,',')." WHERE customerNumber=$id";
			if(!empty($customer)){
				$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
				$success = array('status' => "Success", "msg" => "Customer ".$id." Updated Successfully.", "data" => $customer);
				$this->response($this->json($success),200);
			}else
				$this->response('',204);	// "No Content" status
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
