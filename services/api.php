<?php

//error_reporting(E_ALL);
//ini_set('display_errors', '1');


require_once("Rest.inc.php");
//include_once("/var/www/html/gestorasignaciones/conn_fenix.php");
//include_once("/var/www/html/gestorasignaciones/conn_fenix_bogota.php");
// include_once("/var/www/html/gestorasignaciones/conn_portalbd.php");
//include_once("/var/www/html/gestoroperaciones/connections.php");
include_once("/var/www/html/gestoroperaciones/connections.php");

date_default_timezone_set('America/Bogota');

class API extends REST {

    public $data = "";

    const DB_SERVER = "10.100.82.125";
    const DB_USER = "root";
    const DB_PASSWORD = "123456";
    const DB = "portalbd";

    private $db = NULL;
    private $mysqli = NULL;
    private $mysqli03 = NULL;
    private $connf = NULL;
    private $connfstby = NULL;
    private $connfb =NULL;
    private $mysqliScheduling = NULL;
    public static $doink=0;

    public function __construct(){
        parent::__construct();				// Init parent contructor
        $this->dbConnect();					// Initiate Database connection
    }

    /*
		 *  Connect to Database
		*/
    private function dbConnectScheduling(){  //database object

        $this->mysqliScheduling = getConnScheduling();
    }

    private function dbSeguimientoConnect(){
        $this->connseguimiento = getConnSeguimientoPedidos();
    }

    private function dbConnect(){
        //$this->mysqli = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);
        $this->mysqli = getConnPortalbd();
    }

    private function dbConnect03(){
        //$this->mysqli = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);
        $this->mysqli03 = getConnPortalbd03();
    }


    //if i need fenix i get it directly!!!!
    private function dbFenixConnect(){
        //$this->mysqli = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);
        $this->connf = getConnFenix();
    }

    private function dbFenixSTBYConnect(){
        //$this->mysqli = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);
        $this->connfstby = getConnFenixSTBY();
    }

    private function dbFenixBogotaConnect(){
        //$this->mysqli = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);
        $this->connfb = getConnFenixBogota();
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

    /**
     *
     * @uses  demePedido()
     * @uses  buscarPedido()
     * @uses  guardarGestionAsignaciones()
     *
     */

//Inicia Mundo Asignaciones Y Reconfiguracion
    private function loginNombreIp()
    {
        if ($this->get_request_method () != "GET") {
            $this->response ('', 406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);

        if (!filter_var($usuarioIp, FILTER_VALIDATE_IP) === false){
            $sql =  " SELECT ".
                " SUBSTRING_INDEX(USER_NAME, ' ', 1) as NOMBRE ".
                " , USER as USUARIO_ID".
                " , FECHA ".
                " , date_format(FECHA,'%r') as HORA ".
                " FROM portalbd.activity_feed ".
                " where IP_HOST='$usuarioIp' ".
                " and GRUPO='LOGIN' ".
                " and ACCION='SE LOGUEO'   ".
                " order by FECHA desc limit 1 ";

            $rSql = $this->mysqli->query($sql);
            if($rSql->num_rows > 0){
                $result = array();
                while($row = $rSql->fetch_assoc()) {
                    $result[] = $row;
                }
                $this->response($this->json(array($usuarioIp,$usuarioPc,$result)), 200);
            }

        }else{
            $error = "Bienvenido";
            $this->response($this->json(array($error)), 403);
        }
    }
//------------------------------exportar historico asignaciones-------------------asignacion
    private function csvHistoricos(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $login = $this->_request['login'];
        $fechaIni = $this->_request['fechaIni'];
        $fechaFin = $this->_request['fechaFin'];
        $campo = $this->_request['campo'];
        $valorCampo = $this->_request['valorCampo'];

        if ($campo=="TODO" || $campo=="" || $campo=="undefined"){
            $filtro="";
        }
        else {
            $filtro= " and $campo = '$valorCampo'";
        }
        $today = date("Y-m-d h:i:s");
        $filename="Fenix_NAL-$login-$today.csv";
        $query=" SELECT ".
            " pedido_id,subpedido_id,solicitud_id,municipio_id, fuente, actividad, fecha_fin, estado,duracion,INCIDENTE,concepto_final,concepto_anterior,user,idllamada,motivo,nuevopedido,caracteristica,motivo_malo ".
            " from pedidos where ".
            " fecha_fin between '$fechaIni 00:00:00' and '$fechaFin 23:59:59' $filtro ";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO_ID','SUBPEDIDO_ID','SOLICITUD_ID','MUNICIPIO_ID',' FUENTE',' ACTIVIDAD',' FECHA_FIN',' ESTADO', 'DURACION','ACCION','CONCEPTO_FINAL','CONCEPTO_ANTERIOR','USER','IDLLAMADA','MOTIVO','NUEVOPEDIDO','CARACTERISTICA','MOTIVO MALO'));
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);

            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO HISTORICO' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }

    //---------------------------------fin exportes asignaciones

//---------------------------------exportar historicos reconfiguracion----asignacion

    private function csvHistoricosReconfiguracion(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $userID = $this->_request['userID'];
        $fechaIni = $this->_request['fechaIni'];
        $fechaFin = $this->_request['fechaFin'];
        $campo = $this->_request['campo'];
        $valorCampo = $this->_request['valorCampo'];

        if ($campo=="TODO" || $campo=="" || $campo=="undefined"){
            $filtro="";
        }
        else {
            $filtro= " and $campo = '$valorCampo' and user = '$userID'";
        }
        $today = date("Y-m-d h:i:s");
        $filename="Fenix_NAL-$userID-$today.csv";
        $query=" SELECT ".
            " pedido_id,subpedido_id,solicitud_id,municipio_id, fuente, actividad, fecha_fin, estado,duracion,INCIDENTE,concepto_final,concepto_anterior,user,idllamada,motivo,nuevopedido,caracteristica ".
            " from pedidos where ".
            " fecha_fin between '$fechaIni 00:00:00' and '$fechaFin 23:59:59' $filtro ";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('pedido_id','subpedido_id','solicitud_id','municipio_id',' fuente',' actividad',' fecha_fin',' estado', 'duracion','INCIDENTE','concepto_final','concepto_anterior','user','idllamada','motivo','nuevopedido','caracteristica'));
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO HISTORICO' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$userID)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }

//---------------------------------fin exportar historicos reconfiguracion----

//--------------------------exportar solo fenix nacional------------------asignacion

    private function csvFenixNal(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
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
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO PENDIENTES' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }
//--------------------------fin exportar solo fenix nacional------------------

//-------------------------exportar fenix bogota--------------------asignacion
    private function csvFenixBog(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
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
            " , b.status".
            " , b.fecha_cita".
            " ,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(b.fecha_estado)) AS CHAR(255)) as tiempo_cola".
            " FROM  informe_petec_pendientesm b ".
            " WHERE (b.STATUS='PENDI_PETEC' OR b.STATUS='MALO' ) and b.FUENTE='FENIX_BOG'".
            " order by TIMEDIFF(CURRENT_TIMESTAMP(),(b.fecha_estado)) desc";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('pedido_id','subpedido_id','solicitud_id','tipo_elemento_id','tipo_trabajo','municipio_id','plaza','uen_calculada','producto','status','fecha_cita','tiempo_cola'));
            while($row = $r->fetch_assoc()){
                $result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO PENDIENTES' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }


    //-------------------------fin exportar fenix bogota--------------------

//-----------------------exportar historico agendamiento-----------------------agendamiento

    private function csvHistoricosAgendamiento(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $login = $this->_request['login'];
        $fechaIni = $this->_request['fechaIni'];
        $fechaFin = $this->_request['fechaFin'];

        $today = date("Y-m-d h:i:s");
        $filename="Agendamiento-Fenix_NAL-$login-$today.csv";

        $query=" SELECT ".
            " a.PEDIDO_ID,a.CONCEPTOS,a.ACTIVIDADES,a.NOVEDAD,a.FECHA_CITA_FENIX ".
            " ,a.FECHA_CITA_REAGENDA,a.JORNADA_CITA ".
            " ,a.FECHA_INGRESO,a.FECHA_CARGA,a.ASESOR,a.FECHA_INICIO,a.FECHA_FIN ".
            " ,a.FECHA_ESTADO,a.DURACION,a.OBSERVACION_FENIX ".
            " ,a.OBSERVACION_GESTOR,a.FUENTE,a.ACTIVIDAD_GESTOR,a.ASESORNAME ".
            " ,a.CELULAR_AVISAR,a.CLIENTE_ID,a.CORREO_UNE ".
            " ,a.DIRECCION_ENVIO,a.E_MAIL_AVISAR,a.MICROZONA,a.NOMBRE_USUARIO ".
            " ,a.PARENT_ID,a.TELEFONO_AVISAR,a.TIEMPO_TOTAL ".
            " ,a.PROGRAMACION,a.SOURCE,a.DEPARTAMENTO,a.ACCESO,a.NUMERO_CR ".
            " ,a.IDLLAMADA,a.SUBZONA_ID,a.PROCESO".
            " , (SELECT hr.TODAY_TRIES FROM gestor_pendientes_reagendamiento hr WHERE hr.ID = (SELECT MAX( b.id )  ".
            "      FROM gestor_pendientes_reagendamiento b ".
            "   WHERE b.PEDIDO_ID =  a.PEDIDO_ID) )AS INTENTOS_CONTACTO ".
            " , (SELECT hr.TECNOLOGIA_ID FROM gestor_pendientes_reagendamiento hr WHERE hr.ID = (SELECT MAX( b.id )  ".
            "      FROM gestor_pendientes_reagendamiento b ".
            "   WHERE b.PEDIDO_ID =  a.PEDIDO_ID) )AS TECNOLOGIA_ID ".
            " from gestor_historicos_reagendamiento a ".
            "  where fecha_fin between '$fechaIni 00:00:00' and '$fechaFin 23:59:59' ";


        //$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        //Mauricio: CONSULTA REPODEROSA, unbuffered
        $this->mysqli->real_query($query) or die($this->mysqli->error.__LINE__);

        if($r = $this->mysqli->use_result()){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');

            fputcsv($fp, array( 'PEDIDO_ID','CONCEPTOS','ACTIVIDADES','NOVEDAD','FECHA_CITA_FENIX','FECHA_CITA_REAGENDA','JORNADA_CITA','FECHA_INGRESO','FECHA_CARGA','ASESOR','FECHA_INICIO','FECHA_FIN','FECHA_ESTADO','DURACION','OBSERVACION_FENIX','OBSERVACION_GESTOR','FUENTE','ACTIVIDAD_GESTOR','ASESORNAME','CELULAR_AVISAR','CLIENTE_ID','CORREO_UNE','DIRECCION_ENVIO','E_MAIL_AVISAR','MICROZONA','NOMBRE_USUARIO','PARENT_ID','TELEFONO_AVISAR','TIEMPO_TOTAL','PROGRAMACION','SOURCE','DEPARTAMENTO','ACCESO','NUMERO_CR','IDLLAMADA','SUBZONA_ID','PROCESO','INTENTOS DE CONTACTO','TECNOLOGIA'),chr (124));

            fclose($fp);

            $k=0;
            $kk=0;

            while($row = $r->fetch_assoc()){

                $row['OBSERVACION_FENIX']= trim(preg_replace('/\s+|', ' ',$row['OBSERVACION_FENIX']));
                $row['OBSERVACION_GESTOR'] = trim(preg_replace('/\s+|', ' ', $row['OBSERVACION_GESTOR']));
                //$row['NOVEDAD'] = trim(preg_replace('/\s+|,', ' ', $row['NOVEDAD']));
                $row['CONCEPTOS'] =  str_replace(',', ' ', $row['CONCEPTOS']);
                $row['ACTIVIDADES'] =  str_replace(',', ' ', $row['ACTIVIDADES']);

                $result[] = $row;

                //fputcsv($fp, $row);
                if($k>10000){//cerra y abrir
                    //echo "la k\n";
                    $fp = fopen("../tmp/$filename", 'a');

                    foreach ($result as $fields) {
                        //fwrite($fp, $fields.";");



                        fputcsv($fp, $fields,chr (124));
                    }
                    unset($result);
                    $result=NULL;
                    $result=array();

                    unset($rows);
                    //$r->free();
                    //fclose($fp);
                    //unset($fp);
                    //$fp=null;
                    $k=0;
                    //gc_collect_cycles();
                    //time_nanosleep(0, 10000000);
                    //mysql_free_result($r);
                    //ob_implicit_flush();
                    //echo  "Memoria Final real: ".(memory_get_peak_usage()/1024/1024)." MiB r:\n";
                    //$this->getMemoryUsage($r);
                }


                $k++;
                $kk++;
            }
            //para terminar de escribir las lineas que hacen falta.
            $fp = fopen("../tmp/$filename", 'a');

            foreach ($result as $fields) {
                fputcsv($fp, $fields,chr (124));
            }

            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO HISTORICO' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }

//----------------------- fin exportar historico agendamiento-----------------------

//----------------------------exportar historico agendamiento edatel-----------------agendamiento

    private function csvHistoricosAgendamientoEdatel(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $login = $this->_request['login'];
        $fechaIni = $this->_request['fechaIni'];
        $fechaFin = $this->_request['fechaFin'];

        $today = date("Y-m-d h:i:s");
        $filename="Agendamiento-Fenix_NAL-$login-$today.csv";
        $query=" SELECT ".
            " a.PEDIDO_ID,a.CONCEPTOS,a.ACTIVIDADES,a.NOVEDAD,a.FECHA_CITA_FENIX ".
            " ,a.FECHA_CITA_REAGENDA,a.JORNADA_CITA ".
            " ,a.FECHA_INGRESO,a.FECHA_CARGA,a.ASESOR,a.FECHA_INICIO,a.FECHA_FIN ".
            " ,a.FECHA_ESTADO,a.DURACION,a.OBSERVACION_FENIX ".
            " ,a.OBSERVACION_GESTOR,a.FUENTE,a.ACTIVIDAD_GESTOR,a.ASESORNAME ".
            " ,a.CELULAR_AVISAR,a.CLIENTE_ID,a.CORREO_UNE ".
            " ,a.DIRECCION_ENVIO,a.E_MAIL_AVISAR,a.MICROZONA,a.NOMBRE_USUARIO ".
            " ,a.PARENT_ID,a.TELEFONO_AVISAR,a.TIEMPO_TOTAL ".
            " ,a.PROGRAMACION,a.SOURCE,a.DEPARTAMENTO,a.ACCESO,a.NUMERO_CR ".
            " ,a.IDLLAMADA,a.SUBZONA_ID,a.PROCESO".
            " , (SELECT hr.TODAY_TRIES FROM gestor_pendientes_reagendamiento hr WHERE hr.ID = (SELECT MAX( b.id )  ".
            "      FROM gestor_pendientes_reagendamiento b ".
            "   WHERE b.PEDIDO_ID =  a.PEDIDO_ID) )AS INTENTOS_CONTACTO ".
            " from gestor_historicos_reagendamiento a ".
            "  where fecha_fin between '$fechaIni 00:00:00' and '$fechaFin 23:59:59' ".
            "	and fuente='EDATEL' ";



        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');

            fputcsv($fp, array( 'PEDIDO_ID','CONCEPTOS','ACTIVIDADES','NOVEDAD','FECHA_CITA_FENIX','FECHA_CITA_REAGENDA','JORNADA_CITA','FECHA_INGRESO','FECHA_CARGA','ASESOR','FECHA_INICIO','FECHA_FIN','FECHA_ESTADO','DURACION','OBSERVACION_FENIX','OBSERVACION_GESTOR','FUENTE','ACTIVIDAD_GESTOR','ASESORNAME','CELULAR_AVISAR','CLIENTE_ID','CORREO_UNE','DIRECCION_ENVIO','E_MAIL_AVISAR','MICROZONA','NOMBRE_USUARIO','PARENT_ID','TELEFONO_AVISAR','TIEMPO_TOTAL','PROGRAMACION','SOURCE','DEPARTAMENTO','ACCESO','NUMERO_CR','IDLLAMADA','SUBZONA_ID','PROCESO','INTENTOS_CONTACTO'));

            fclose($fp);

            $k=0;
            $kk=0;

            while($row = $r->fetch_assoc()){

                //$row['OBSERVACION_FENIX']= trim(preg_replace('/\s+/', ' ',$row['OBSERVACION_FENIX']));
                //$row['OBSERVACION_GESTOR'] = trim(preg_replace('/\s+/', ' ', $row['OBSERVACION_GESTOR']));

                $result[] = $row;

                //fputcsv($fp, $row);

                if($k>10000){//cerra y abrir
                    //echo "la k\n";
                    $fp = fopen("../tmp/$filename", 'a');

                    foreach ($result as $fields) {
                        //fwrite($fp, $fields.";");
                        fputcsv($fp, $fields);
                    }
                    unset($result);
                    $result=NULL;
                    $result=array();

                    unset($rows);
                    //$r->free();
                    //fclose($fp);
                    //unset($fp);
                    //$fp=null;
                    $k=0;
                    //gc_collect_cycles();
                    //time_nanosleep(0, 10000000);
                    //mysql_free_result($r);
                    //ob_implicit_flush();
                    //echo  "Memoria Final real: ".(memory_get_peak_usage()/1024/1024)." MiB r:\n";
                    //$this->getMemoryUsage($r);
                }

                $k++;
                $kk++;
            }
            //para terminar de escribir las lineas que hacen falta.
            $fp = fopen("../tmp/$filename", 'a');

            foreach ($result as $fields) {
                fputcsv($fp, $fields);
            }

            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO HISTORICO' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }

//----------------------------fin exportar historico agendamiento edatel-----------------


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

    private function csvActivacioncolas(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $login = $this->_request['login'];

        $today = date("Y-m-d h:i:s");
        $filename="Fenix_Activacion-$login-$today.csv";

        $query="SELECT REQUERIMIENTO_ID  , PEDIDO_ID  , SUBPEDIDO_ID  , SOLICITUD_ID ".
            "		 , TIPO_ELEMENTO_ID  , TIPO_TRABAJO  , FECHA_ESTADO  , ETAPA_ID  ".
            "		 , ESTADO_ID  , COLA_ID  , ACTIVIDAD_ID  , NOMBRE_ACTIVIDAD  , CONCEPTO_ID  ".
            "		 ,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_PENDIENTE ".
            "		  FROM  informe_activacion_pendientesm  ".
            "			WHERE  STATUS ='PENDI_ACTIVACION'  ".
            "			and cola_id in ('CBAPON','TRGPON')  ".
            "		and TIPO_TRABAJO IN ('RETIR') ".
            " UNION  ".
            " SELECT REQUERIMIENTO_ID  , PEDIDO_ID  , SUBPEDIDO_ID  , SOLICITUD_ID ".
            "	 , TIPO_ELEMENTO_ID  , TIPO_TRABAJO  , FECHA_ESTADO  , ETAPA_ID  ".
            " 		 , ESTADO_ID  , COLA_ID  , ACTIVIDAD_ID  , NOMBRE_ACTIVIDAD  , CONCEPTO_ID  ".
            "		 ,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_PENDIENTE ".
            "		  FROM  informe_activacion_pendientesm  ".
            "			WHERE  STATUS ='PENDI_ACTIVACION' ".
            "			and cola_id in ('GPONSR') ".
            " UNION ".
            " SELECT REQUERIMIENTO_ID  , PEDIDO_ID  , SUBPEDIDO_ID  , SOLICITUD_ID ".
            "		 , TIPO_ELEMENTO_ID  , TIPO_TRABAJO  , FECHA_ESTADO  , ETAPA_ID  ".
            "		 , ESTADO_ID  , COLA_ID  , ACTIVIDAD_ID  , NOMBRE_ACTIVIDAD  , CONCEPTO_ID  ".
            "		 ,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_PENDIENTE ".
            "		  FROM  informe_activacion_pendientesm  ".
            "			WHERE  STATUS ='PENDI_ACTIVACION' ".
            "			and cola_id in ('TOIPON') ".
            "			and ACTIVIDAD_ID IN ('ANOLT','DEOLT') ".
            " UNION ".
            " SELECT REQUERIMIENTO_ID  , PEDIDO_ID  , SUBPEDIDO_ID  , SOLICITUD_ID ".
            "		 , TIPO_ELEMENTO_ID  , TIPO_TRABAJO  , FECHA_ESTADO  , ETAPA_ID  ".
            "		 , ESTADO_ID  , COLA_ID  , ACTIVIDAD_ID  , NOMBRE_ACTIVIDAD  , CONCEPTO_ID  ".
            "		 ,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_PENDIENTE ".
            "		  FROM  informe_activacion_pendientesm  ".
            "			WHERE  STATUS ='PENDI_ACTIVACION' ".
            "			and cola_id in ('VRMAT') ".
            "			and CONCEPTO_ID NOT IN ('PROG') ".
            " UNION ".
            " SELECT REQUERIMIENTO_ID  , PEDIDO_ID  , SUBPEDIDO_ID  , SOLICITUD_ID ".
            "   , TIPO_ELEMENTO_ID  , TIPO_TRABAJO  , FECHA_ESTADO  , ETAPA_ID  ".
            "   , ESTADO_ID  , COLA_ID  , ACTIVIDAD_ID  , NOMBRE_ACTIVIDAD  , CONCEPTO_ID ".
            "   ,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_PENDIENTE ".
            "   FROM  informe_activacion_pendientesm  ".
            "   WHERE  STATUS ='PENDI_ACTIVACION'  ".
            "   and cola_id in ('CTVPONST')  ".
            "   and estado_id='PENDI' ".
            "   AND ACTIVIDAD_ID IN ('DEGTV') ";

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

    private function csvAmarillas(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $login = $this->_request['login'];

        $today = date("Y-m-d h:i:s");
        $filename="Fenix_Activacion-$login-$today.csv";
        $query=" select b.ORDER_SEQ_ID,b.ESTADO,b.PEDIDO,b.TRANSACCION,b.PRODUCTO ".
            " ,b.FECHA_EXCEPCION,b.TIPO_COMUNICACION,b.DEPARTAMENTO,b.STATUS ".
            " FROM pendientes_amarillas b ".
            " where b.STATUS in ('PENDI_ACTI','MALO')" ;

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('ORDER_SEQ_ID','ESTADO','PEDIDO','TRANSACCION','PRODUCTO','FECHA_EXCEPCION','TIPO_COMUNICACION','DEPARTAMENTO','STATUS','HISTORICO_TIPIFICACION'));
            while($row = $r->fetch_assoc()){
                $result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);

            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }




//-------------------------exportar pendientes activacion siebel--------------------------------activacion
    private function csvActivacionSiebel(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $login = $this->_request['login'];

        $today = date("Y-m-d h:i:s");
        $filename="Fenix_Activacion-$login-$today.csv";

        $query=  " SELECT ID,ORDER_SEQ_ID,PEDIDO,REFERENCE_NUMBER ".
            " ,ESTADO,FECHA_CREACION,FECHA_EXCEPCION ".
            " ,PRODUCTO,IDSERVICIORAIZ,TRANSACCION,CODIGO_CIUDAD ".
            " ,CODIGO_UNICO_DIRECCION,NOMBRE_CUIDAD,NOMBRE_DEPARTAMENTO ".
            " ,TAREA_EXCEPCION,CODIGOEXCEPCIONACT,FECHA_CARGA,STATUS ".
            " FROM gestor_activacion_pendientes_activador_suspecore ".
            " WHERE  ESTADO ='in_progress' ".
            " AND STATUS IN ('PENDI_ACTI','MALO')".
            " AND MOTIVOEXCEPCIONACT <>'La Cuenta NO existe.'";


        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('ID','ORDER_SEQ_ID','PEDIDO','REFERENCE_NUMBER','ESTADO','FECHA_CREACION','FECHA_EXCEPCION','PRODUCTO','IDSERVICIORAIZ','TRANSACCION','CODIGO_CIUDAD','CODIGO_UNICO_DIRECCION','NOMBRE_CUIDAD','NOMBRE_DEPARTAMENTO','TAREA_EXCEPCION','CODIGOEXCEPCIONACT','FECHA_CARGA','STATUS'));
            while($row = $r->fetch_assoc()){
              //  $row = str_replace(',','',$row); 
                //$row = str_replace("[\n|\r|\n\r|\t|\0|\x0B]", ' ', $row); 
               
                //$row['DESCRIPCIONEXCEPCIONACT']=str_replace(array(","," "," "), "\"", $row['DESCRIPCIONEXCEPCIONACT']);
                //$row['MOTIVOEXCEPCIONACT']=str_replace(array(","), "\"", $row['MOTIVOEXCEPCIONACT']);  
                $result[] = $row;
                fputcsv($fp, $row);

                  
                 // echo $row;    
            //      $row['DESCRIPCIONEXCEPCIONACT']=str_replace(array(","), "\"", $row['DESCRIPCIONEXCEPCIONACT']);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO PENDIENTES' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed

            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }
//------------------------- fin exportar pendientes activacion siebel--------------------------------


//-------------------------exportar pendientes activacion siebel dom--------------------------------activacion
    private function csvActivacionSiebeldom(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $login = $this->_request['login'];

        $today = date("Y-m-d h:i:s");
        $filename="Fenix_Activacion_dom-$login-$today.csv";
        $query=  " select ID,ORDER_SEQ_ID,PEDIDO,REFERENCE_NUMBER,ESTADO ".
            " ,FECHA_CREACION,CODIGO_ERROR ".
            " ,CODIGO_UNICO_DIRECCION,TAREA_EXCEPCION,FECHA_EXCEPCION ".
            " ,TIPO_COMUNICACION,PRODUCTO,IDSERVICIORAIZ ".
            " ,TRANSACCION,CODIGO_CIUDAD,NOMBRE_CIUDAD ".
            " ,DEPARTAMENTO,FECHA_CARGA,STATUS ".
            " from gestor_activacion_pendientes_activador_dom ".
            " WHERE STATUS='PENDI_ACTI'";


        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('ID','ORDER_SEQ_ID','PEDIDO','REFERENCE_NUMBER','ESTADO','FECHA_CREACION','CODIGO_ERROR','CODIGO_UNICO_DIRECCION','TAREA_EXCEPCION','FECHA_EXCEPCION','TIPO_COMUNICACION','PRODUCTO','IDSERVICIORAIZ','TRANSACCION','CODIGO_CIUDAD','NOMBRE_CIUDAD','DEPARTAMENTO','FECHA_CARGA','STATUS'));
            while($row = $r->fetch_assoc()){
                $result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO PENDIENTES' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed

            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }
//------------------------- fin exportar pendientes activacion siebel dom--------------------------------

//-----------------------------extortar activacion siebe invdom---------------------------------activacion

    private function csvActivacionSiebelinvdom(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $login = $this->_request['login'];

        $today = date("Y-m-d h:i:s");
        $filename="Fenix_Activacion-$login-$today.csv";
        $query=  " SELECT * ".
            " FROM gestor_activacion_pendientes_gtc_suspecore ";


        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('ID','ORDER_SEQ_ID','PEDIDO','REFERENCE_NUMBER','ESTADO','FECHA_CREACION','FECHA_EXCEPCION','PRODUCTO','IDSERVICIORAIZ','TRANSACCION','CODIGO_CIUDAD','NOMBRE_CUIDAD','CODIGO_UNICO_DIRECCION','NOMBRE_DEPARTAMENTO','TAREA_EXCEPCION','CODIGOEXCEPCIONGTC','DESCRIPCIONEXCEPCIONGTC','MOTIVOEXCEPCIONGTC','FECHA_CARGA'));
            while($row = $r->fetch_assoc()){
                $result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO PENDIENTES' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }

//-----------------------------fin extortar activacion siebe invdom---------------------------------

//-----------------------------extortar activacion GTC---------------------------------activacion

    private function csvActivacionGTC(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $login = $this->_request['login'];

        $today = date("Y-m-d h:i:s");
        $filename="Fenix_Activacion-$login-$today.csv";
        $query=  " SELECT * ".
            " FROM gestor_activacion_tbl_pendi_gtc ";


        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('ID','ORDER_SEQ_ID','PEDIDO','REFERENCE_NUMBER','ESTADO','FECHA_CREACION','TAREA_EXCEPCION','FECHA_EXCEPCION','PRODUCTO','IDSERVICIORAIZ','TRANSACCION','CODIGO_CIUDAD','CAMPO_ERROR','VALOR_CAMPO_ERROR','FECHA_CARGA'));
            while($row = $r->fetch_assoc()){
                $result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO PENDIENTES' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }

//-----------------------------fin extortar activacion GTC---------------------------------


    private function csvListadoActivacion(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $login = $this->_request['login'];
        $fechaIni = $this->_request['fechaIni'];
        $fechaFin = $this->_request['fechaFin'];

        $today = date("Y-m-d");


        $filename="Activacion-Fenix_NAL-$login-$today.csv";

        $query= "SELECT ORDER_SEQ_ID,PEDIDO, ESTADO, FECHA_CREACION, FECHA_EXCEPCION,TRANSACCION ".
            " , PRODUCTO,ASESOR,FECHA_GESTION,TIPIFICACION,FECHA_INICIO,FECHA_FIN,OBSERVACION,PSR,NUMERO_PSR,TABLA ".
            " ,my_sec_to_time(timestampdiff(second,fecha_inicio,fecha_fin)) as DURACION ".
            " from gestor_historico_activacion ".
            "where fecha_fin between '$fechaIni 00:00:00' and '$fechaFin 23:59:59' $filtro ";;


        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');

            fputcsv($fp, array( 'ORDER_SEQ_ID','PEDIDO','ESTADO','FECHA_CREACION','FECHA_EXCEPCION','TRANSACCION','PRODUCTO','ASESOR','FECHA_GESTION','TIPIFICACION','FECHA_INICIO','FECHA_FIN','OBSERVACION','PSR','NUMERO_PSR','TABLA','DURACION'));

            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                fputcsv($fp, $row);
            }

            fclose($fp);
            // SQL Feed---------------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO HISTORICO' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }

//--------------------------- fin exportar listado activacion seguimiento--------------------------

//-------------------------exportar datos de preinstalacion---------------------asignaciones




    private function csvPreInstalaciones(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $login = $this->_request['login'];

        $today = date("Y-m-d h:i:s");
        $filename="PRE-INSTALACION-$login-$today.csv";
        $query=" SELECT ".
            " a.PEDIDO_ID ".
            ",a.CONCEPTO_ID ".
            ",a.FECHA_ENTREGA ".
            ",a.HORA_ENTREGA ".
            ",a.JORNADA_ENTREGA ".
            ",a.TIPO_REVISION ".
            ",b.INTERNET ".
            ",b.TELEVISION ".
            ",b.TELEFONIA ".
            ",b.RUTA_TRABAJO_ID ".
            "FROM pre_agen a ".
            "left join (SELECT  ".
            "            p.PEDIDO_ID ".
            "            , MAX(CASE  ".
            "                WHEN p.TIPO_ELEMENTO_ID='ACCESP' THEN p.IDENTIFICADOR_ID ".
            "            END) AS INTERNET ".
            "            ,  MAX(CASE  ".
            "                WHEN p.TIPO_ELEMENTO_ID='INSHFC' THEN p.IDENTIFICADOR_ID ".
            "            END) AS TELEVISION ".
            "            ,  MAX(CASE  ".
            "                WHEN p.TIPO_ELEMENTO_ID='TOIP' THEN p.IDENTIFICADOR_ID ".
            "            END) AS TELEFONIA ".
            "            , MAX(p.RUTA_TRABAJO_ID) AS RUTA_TRABAJO_ID ".
            "            FROM gestor_informes.pre_agen_servicios p ".
            "            where 1=1 ".
            "            and (p.REVISION_DIGITAL='REVISAR' ".
            "            or p.REVISION_SINRED='REVISAR') ".
            "            group by p.PEDIDO_ID) b ".
            "on a.PEDIDO_ID=b.PEDIDO_ID ".
            " WHERE STATUS ='PENDI_PRE'";


        $this->dbConnect03();

        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO_ID','CONCEPTO_ID','FECHA_ENTREGA','HORA_ENTREGA','JORNADA_ENTREGA','TIPO_REVISION','INTERNET','TELEVISION','TELEFONIA','RUTA_TRABAJO_ID'));
            while($row = $r->fetch_assoc()){
                $result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO PRE INSTALACION' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }


//------------------------- fin exportar datos de preinstalacion---------------------

//-- ---------------------exportar agendamiento pedientes-------agendamiento------

    private function csvAgendamiento(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $login = $this->_request['login'];

        $today = date("Y-m-d h:i:s");
        $filename="Fenix_Agendamiento-$login-$today.csv";
        $query=" SELECT ".
            "  PEDIDO_ID ".
            " ,CONCEPTOS ".
            " ,ACTIVIDADES ".
            " ,FECHA_CITA_FENIX ".
            " ,MIGRACION ".
            " ,MICROZONA ".
            " ,SUBZONA_ID ".
            " ,CLIENTE_ID ".
            " ,CELULAR_AVISAR ".
            " ,CORREO_UNE ".
            " ,DIRECCION_ENVIO ".
            " ,E_MAIL_AVISAR ".
            " ,NOMBRE_USUARIO ".
            " ,FECHA_INGRESO ".
            " ,TELEFONO_AVISAR ".
            " ,RADICADO ".
            " ,MUNICIPIO ".
            " ,DEPARTAMENTO ".
            " ,FECHA_ESTADO ".
            " FROM  gestor_pendientes_reagendamiento  WHERE  STATUS in ('PENDI_AGEN','MALO') ";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO_ID','CONCEPTOS','ACTIVIDADES','FECHA_CITA_FENIX','MIGRACION','MICROZONA','SUBZONA_ID','CLIENTE_ID','CELULAR_AVISAR','CORREO_UNE','DIRECCION_ENVIO','E_MAIL_AVISAR','NOMBRE_USUARIO','FECHA_INGRESO','TELEFONO_AVISAR','RADICADO','MUNICIPIO','DEPARTAMENTO','FECHA_ESTADO'));
            while($row = $r->fetch_assoc()){
                $result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO PENDIENTES' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }
//-- --------------------- fin exportar agendamiento pedientes-------agendamiento------

//-------------------------------------insertar pedido ---------asignacion---------

    private function insertMPedido(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido = json_decode(file_get_contents("php://input"),true);

        $pedido1 = $pedido['pedido'];
        $pedidoid = $pedido1['pedido'];
        $observaciones = $pedido1['observacion'];

        //echo var_dump ($observaciones);

        $column_names = array('pedido', 'fuente', 'actividad','estado', 'user','duracion','INCIDENTE','fecha_inicio','fecha_fin','concepto_final');
        $keys = array_keys($pedido);

        //echo var_dump ($pedido);guardarPedidoGestionguardarPedidoGestion

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
            $query = "INSERT INTO pedidos(".trim($columns,',').",source,OBSERVACIONES_PROCESO, pedido_id) VALUES(".trim($values,',').",'MANUAL', '$observaciones', '$pedidoid')";
            //echo $query;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
            //hago la actualizacion en fenix
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'GUARDO PEDIDO' ".
                ",'MANUAL' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response(json_encode(array("msg"=>"N/A","data" => $today)),200);

        }else{
            $this->response('',204);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }
//-------------------------------------fin insertar pedido ---------asignacion------

//-------------insertar pedido reconfiguracion---------------asignacion------------

    private function insertPedidoReconfiguracion(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido = json_decode(file_get_contents("php://input"),true);
        $column_names = array('pedido', 'fuente', 'actividad', 'ESTADO_ID', 'OBSERVACIONES_PROCESO', 'estado', 'user','duracion','fecha_inicio','fecha_fin','PEDIDO_ID','SUBPEDIDO_ID','SOLICITUD_ID','MUNICIPIO_ID','CONCEPTO_ANTERIOR','idllamada','nuevopedido','motivo_malo');
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

        $concepto_anterior=$pedido['pedido']['CONCEPTO_ANTERIOR'];


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
            $concepto_final=$this-> updateFenixReconfiguracion($pedido);
            //var_dump($concepto_final);
            $estado=$pedido['pedido']['estado'];
            //echo "estado: '$estadum'";
            if($concepto_final=="NO CAMBIO CONCEPTO" && $estadum!="MALO"){
                $concepto_final=$CONCEPT;
                //$this->response(json_encode(array("msg"=>"El pedido NO ha cambiado de concepto en Fenix!!!")),200);
            }

            if($estadum=="MALO"){
                $concepto_final=$concepto_anterior;
                $query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado,concepto_final,source) VALUES(".trim($values,',').",'$fecha_estado','$concepto_final','$sourcee')";
                $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                $sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='$today',STATUS='$estadum',ASESOR='' WHERE ID=$iddd ";
                $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);


                // SQL Feed----------------------------------
                $sql_log=   "insert into portalbd.activity_feed ( ".
                    " USER ".
                    ", USER_NAME ".
                    ", GRUPO ".
                    ", STATUS ".
                    ", PEDIDO_OFERTA ".
                    ", ACCION ".
                    ", CONCEPTO_ID ".
                    ", IP_HOST ".
                    ", CP_HOST ".
                    ") values( ".
                    " UPPER('$useri')".
                    ", UPPER('$nombreGalleta')".
                    ", UPPER('$grupoGalleta')".
                    ",'$estadum' ".
                    ",'$PEDIDO_IDi' ".
                    ",'GUARDO PEDIDO MALO' ".
                    ",'$concepto_final' ".
                    ",'$usuarioIp' ".
                    ",'$usuarioPc')";

                $rlog = $this->mysqli->query($sql_log);
                // ---------------------------------- SQL Feed
                //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','RECONFIGURACION','$estadum','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi','ESTUDIO','$concepto_final') ";
                //$rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);
                //hago la actualizacion en fenix
                $this->response(json_encode(array("msg"=>"$concepto_final","data" => $today)),200);


            } else if($estadum=="VOLVER A LLAMAR" ||$estadum=="PENDIENTE"){//SE DEFINE LLAMADA EN RECONFIGURACION PARA VOLVER A LLAMAR

                //echo "HORA LLLAMAR: ".$pedido['horaLlamar'];

                $programacion=$pedido['horaLlamar'];

                if($pedido['horaLlamar']==""){
                    $pedido['horaLlamar']="manana";
                }

                /*if($pedido['horaLlamar']=="manana"){//este pedido se programo para ser entregado mañana
						$datetime = new DateTime('tomorrow');
						//echo "PROGRAMACION: ".$datetime->format('Y-m-d 08:00:00');
						 $programacion=$datetime->format('Y-m-d 08:00:00');
						//$tomorrow = date("Y-m-d H:i:s");
					}else{//pedido programado para entregarse el dia de hoy, mas tarde
						$today2 = date("Y-m-d ".$pedido['horaLlamar'].":00");
						//echo "PROGRAMACION: ".$today2;
						$programacion=$today2;
					}*/ // Programado Viejo, ya no es necesario

                if($pedido['horaLlamar']=="manana"){//Solo si programar viene vacio
                    $datetime = new DateTime('tomorrow');
                    $programacion=$datetime->format('Y-m-d 08:00:00');

                }else{//pedido programado para entregarse el dia de hoy, mas tarde

                    $programacion=$pedido['horaLlamar'];
                }


                $pedido = json_decode(file_get_contents("php://input"),true);
                //$pedido['pedido']['estado']=$estadum." : ".$programacion;
                $pedido['pedido']['estado']=$estadum;
                $pedido['estado']=$estadum." : ".$programacion;
                $columns = '';
                $values = '';

                $sqlupdate="update informe_petec_pendientesm set PROGRAMACION='$programacion', RADICADO_TEMPORAL='NO' WHERE STATUS='PENDI_PETEC' and PEDIDO_ID='".$PEDIDO_IDi."' ";

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

                $concepto_final=$concepto_anterior;
                $query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado,concepto_final,source) VALUES(".trim($values,',').",'$fecha_estado','$concepto_final','$sourcee')";
                //echo $query;
                $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                $sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='$today',STATUS='PENDI_PETEC',PROGRAMACION='$programacion',ASESOR='' , RADICADO_TEMPORAL='NO' WHERE ID=$iddd ";

                $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);
                // SQL Feed----------------------------------
                $sql_log=   "insert into portalbd.activity_feed ( ".
                    " USER ".
                    ", USER_NAME ".
                    ", GRUPO ".
                    ", STATUS ".
                    ", PEDIDO_OFERTA ".
                    ", ACCION ".
                    ", CONCEPTO_ID ".
                    ", IP_HOST ".
                    ", CP_HOST ".
                    ") values( ".
                    " UPPER('$usuarioGalleta')".
                    ", UPPER('$nombreGalleta')".
                    ", UPPER('$grupoGalleta')".
                    ",'$estadum' ".
                    ",'$PEDIDO_IDi' ".
                    ",'PROGRAMO PEDIDO' ".
                    ",'$concepto_final' ".
                    ",'$usuarioIp' ".
                    ",'$usuarioPc')";

                $rlog = $this->mysqli->query($sql_log);
                // ---------------------------------- SQL Feed
                //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta) values ('$useri','$username','RECONFIGURACION','$concepto_final','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi') ";
                //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','RECONFIGURACION','$estadum','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi','ESTUDIO','$concepto_final') ";
                //$rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

                //hago la actualizacion en fenix
                $this->response(json_encode(array("msg"=>"$concepto_final","data" => $today)),200);

                //COMODIN PARA DIVIDIR RECONFIGURACIONES EN CON Y SIN LLAMADA...
            }else if($estadum=="SOLO RENUMERAR"){

                $concepto_final=$concepto_anterior;
                $query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado,concepto_final,source) VALUES(".trim($values,',').",'$fecha_estado','$concepto_final','$sourcee')";
                $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                $sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='$today',STATUS='PENDI_RENUMS',ASESOR='' WHERE ID=$iddd ";
                $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);
                $sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','RECONFIGURACION','$estadum','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi','ESTUDIO','$concepto_final') ";
                $rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);
                //hago la actualizacion en fenix
                $this->response(json_encode(array("msg"=>"$concepto_final","data" => $today)),200);

            } else {//CONCEPTO CAMBIO, PASA NORMALMENTE

                if(is_array($concepto_final)){
                    $concepto_final=$concepto_final['CONCEPTO_ID'];
                }

                if($concepto_final==''){
                    $concepto_final=$concepto_anterior;
                }

                $query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado,concepto_final,source) VALUES(".trim($values,',').",'$fecha_estado','$concepto_final','$sourcee')";
                $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                //cierro el registro en la tabla de automatizacion asignaciones
                $sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='$today',CONCEPTO_ID='$concepto_final',STATUS='CERRADO_PETEC',ASESOR='' WHERE ID=$iddd ";
                $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);
                // SQL Feed----------------------------------
                $sql_log=   "insert into portalbd.activity_feed ( ".
                    " USER ".
                    ", USER_NAME ".
                    ", GRUPO ".
                    ", STATUS ".
                    ", PEDIDO_OFERTA ".
                    ", ACCION ".
                    ", CONCEPTO_ID ".
                    ", IP_HOST ".
                    ", CP_HOST ".
                    ") values( ".
                    " UPPER('$usuarioGalleta')".
                    ", UPPER('$nombreGalleta')".
                    ", UPPER('$grupoGalleta')".
                    ",'$estadum' ".
                    ",'$PEDIDO_IDi' ".
                    ",'RECONFIGURO PEDIDO' ".
                    ",'$concepto_final' ".
                    ",'$usuarioIp' ".
                    ",'$usuarioPc')";

                $rlog = $this->mysqli->query($sql_log);
                // ---------------------------------- SQL Feed
                //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta) values ('$useri','$username','RECONFIGURACION','$concepto_final','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi')";
                //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','RECONFIGURACION','$estadum','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi','ESTUDIO','$concepto_final') ";
                //$rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

                //hago la actualizacion en fenix
                $this->response(json_encode(array("msg"=>"$concepto_final","data" => $today)),200);

            }

        }else{
            $this->response('',204);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }

//-------------fin insertar pedido reconfiguracion---------------asignacion------------


//---------------------insert pedido reagendamiento--------------------agendamiento-------------

    private function insertPedidoReagendamiento(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido = json_decode(file_get_contents("php://input"),true);
        //var_dump($pedido);
        $column_names = array('PEDIDO_ID','CONCEPTOS','ACTIVIDADES','IDLLAMADA','NOVEDAD','FECHA_CITA_FENIX','FECHA_CITA_REAGENDA','FECHA_INGRESO','ASESOR','FECHA_INICIO','FECHA_FIN','DURACION','OBSERVACION_FENIX','OBSERVACION_GESTOR','FUENTE','ACTIVIDAD_GESTOR','ASESORNAME','CELULAR_AVISAR','CLIENTE_ID','CORREO_UNE','DIRECCION_ENVIO','E_MAIL_AVISAR','MICROZONA','NOMBRE_USUARIO','PARENT_ID','TELEFONO_AVISAR','TIEMPO_TOTAL','JORNADA_CITA','FECHA_ESTADO','DEPARTAMENTO','SUBZONA_ID','PROCESO');

        $f = fopen("consultas.txt", "w");
        //fwrite($f, var_dump($pedido)."\n\n");
        $pedido=$pedido['pedido'];

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
        $today2 = date("Y-m-d");

        $programacion=$pedido['PROGRAMACION'];
        $parent=$pedido['PARENT_ID'];
        $PEDIDO_ID=$pedido['PEDIDO_ID'];
        $sourcee=$pedido['source'];
        $SUBZONA_ID=$pedido['SUBZONA_ID'];


        if($sourcee=='')$sourcee="AUTO";

        $query = "INSERT INTO gestor_historicos_reagendamiento (".trim($columns,',').",PROGRAMACION,SOURCE ) VALUES(".trim($values,',').",'$programacion','$sourcee')";

        $fecha_cita_reagen=$pedido['FECHA_CITA_REAGENDA'];

        $jornada=$pedido['JORNADA_CITA'];
        //var_dump($pedido);
        //echo "parentid: $parent";

        $useri=$pedido['ASESOR'];
        $username=$pedido['ASESORNAME'];
        $cliente_id=$pedido['CLIENTE_ID'];
        $nombre_usuario=$pedido['NOMBRE_USUARIO'];
        $CODIGO_ESTADO='';
        $ACCESO='';
        $novedad=$pedido['NOVEDAD'];
        fwrite($f, "$query\n");

        fclose($f);

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        //cierro el registro en la tabla de automatizacion asignaciones

        if($novedad=='AGENDADO'||$novedad=='AGENDADO MANUAL'|| $novedad=='AGENDADO_FUTURO'|| $novedad=='CONFIRMA SOLUCION'
            || $novedad=='CONFIRMADA'|| $novedad=='CONFIRMADA-DATOS ERRADOS'|| $novedad=='NO CONOCE EL PEDIDO'
            || $novedad=='NO DESEA EL SERVICIO'|| $novedad=='LIBERACION DE CUPOS' || $novedad=='PENDIENTE EN OTRO CONCEPTO'
            || $novedad=='SIN CITA' || $novedad=='YA ESTA AGENDADO'){
            $sqlupdate="update gestor_pendientes_reagendamiento set FECHA_ACTUALIZACION='$today',STATUS='CERRADO_AGEN',FECHA_CITA_REAGENDA='$fecha_cita_reagen',TODAY_TRIES=(SELECT COUNT(*) FROM gestor_historicos_reagendamiento WHERE PEDIDO_ID='$PEDIDO_ID'  and NOVEDAD in ('AGENDADO','AGENDADO FUTURO','ANULADO 42','ANULADO AXGAR','ANULADO APRCT','LLAMADA SIN INFORMACION-MUDA','CLIENTE NO CONTACTADO','NO PUEDE ATENDER LLAMADA','LLAMAR FUTURO','INCUMPLIMIENTO FECHA CITA HOY','SE BRINDA INFORMACION','YA ESTA ANULADO-PENDIENTE','YA ESTA CUMPLIDO-PENDIENTE','PENDIENTE RECONFIGURAR PEDIDO','CONFIRMA SOLUCION','CLIENTE NO AUTORIZA','CLIENTE ILOCALIZADO','CONFIRMADA','CONFIRMADA-DATOS ERRADOS','CLIENTES NOS ESPERA')  ),PROGRAMACION='',ASESOR='' WHERE ID=$parent ";
        }else if($novedad=='ANULADO 42'||$novedad=='ANULADO APRCT' ||$novedad=='ANULADO AXGAR' ||$novedad=='CAMBIO_NUMERO/PLAN'
            ||$novedad=='ERROR SIEBEL 8.1' ||$novedad=='MIGRACION HFC' || $novedad=='NO DESEA EL SERVICIO' ||$novedad=='PEDIDO EN OTRO CONCEPTO'
            ||$novedad=='PENDIENTE RECONFIGUAR PEDIDO' ||$novedad=='PENDIENTE OTRO CONCEPTO' || $novedad=='YA ESTA CUMPLIDO'
            || $novedad=='YA ESTA ANULADO'||$novedad=='YA ESTA ANULADO-CERRADO'||$novedad=='YA ESTA ANULADO-PENDIENTE'
            ||$novedad=='YA ESTA CUMPLIDO–CERRADO'||$novedad=='YA ESTA CUMPLIDO–PENDIENTE' ||$novedad=='ANULADO POR ASESOR' || $novedad== 'ANULADO CERRADO Y/O PENDIENTE' || $novedad== 'CUMPLIDO CERRADO Y/O PEDIENTE' ||$novedad== 'ERROR SIEBEL' || $novedad=='YA ESTA CUMPLIDO' || $novedad=='NO SUBIO A CLICK
            ' ){
            $sqlupdate="update gestor_pendientes_reagendamiento set FECHA_ACTUALIZACION='$today',STATUS='MALO',FECHA_CITA_REAGENDA='$fecha_cita_reagen',TODAY_TRIES=(SELECT COUNT(*) FROM gestor_historicos_reagendamiento WHERE PEDIDO_ID='$PEDIDO_ID' and NOVEDAD in ('AGENDADO','AGENDADO FUTURO','ANULADO 42','ANULADO AXGAR','ANULADO APRCT','LLAMADA SIN INFORMACION-MUDA','NO PUEDE ATENDER LLAMADA','LLAMAR FUTURO','INCUMPLIMIENTO FECHA CITA HOY','SE BRINDA INFORMACION','YA ESTA ANULADO-PENDIENTE','YA ESTA CUMPLIDO-PENDIENTE','PENDIENTE RECONFIGURAR PEDIDO','CONFIRMA SOLUCION','CLIENTE NO AUTORIZA','CLIENTE ILOCALIZADO','CONFIRMADA','CONFIRMADA-DATOS ERRADOS','CLIENTES NOS ESPERA') ),PROGRAMACION='',ASESOR='' WHERE ID=$parent ";
        } else {
            $sqlupdate="update gestor_pendientes_reagendamiento set FECHA_ACTUALIZACION='$today',STATUS='PENDI_AGEN',FECHA_CITA_REAGENDA='$fecha_cita_reagen',TODAY_TRIES=(SELECT COUNT(*) FROM gestor_historicos_reagendamiento WHERE PEDIDO_ID='$PEDIDO_ID'  and NOVEDAD in ('AGENDADO','AGENDADO FUTURO','ANULADO 42','ANULADO AXGAR','ANULADO APRCT','LLAMADA SIN INFORMACION-MUDA','CLIENTE NO CONTACTADO','NO PUEDE ATENDER LLAMADA','LLAMAR FUTURO','INCUMPLIMIENTO FECHA CITA HOY','SE BRINDA INFORMACION','YA ESTA ANULADO-PENDIENTE','YA ESTA CUMPLIDO-PENDIENTE','PENDIENTE RECONFIGURAR PEDIDO','CONFIRMA SOLUCION','CLIENTE NO AUTORIZA','CLIENTE ILOCALIZADO','CONFIRMADA','CONFIRMADA-DATOS ERRADOS','CLIENTES NOS ESPERA','CLIENTE NO CONTACTADO','PENDIENTE AGENDA') ),PROGRAMACION='$programacion',ASESOR='' WHERE ID=$parent ";
        }
        //echo $sqlupdate;
        if($novedad=='AGENDADO'||$novedad=='AGENDADO MANUAL'||$novedad=='ANULADO 42'|| $novedad=='ANULADO AXGAR'
            || $novedad=='ANULADO POR SUSTITUCION DE PEDIDO' || $novedad=='CAMBIO_NUMERO' || $novedad=='NO CONOCE EL PEDIDO'
            || $novedad=='NO PUEDE ATENDER LLAMADA' || $novedad=='REQUIERE ANULACION'|| $novedad=='SE BRINDA INFORMACION'
            || $novedad=='NO HAY AGENDA'|| $novedad=='PENDIENTE RECONFIGUAR PEDIDO' || $novedad=='ACTIVACION_O_RETIRO_DE_CANALES'
            || $novedad=='AGENDADO_FUTURO'){
            $CODIGO_ESTADO="CONTACTADO";
        }else {
            $CODIGO_ESTADO="NO CONTACTADO";
        }

        $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);

        // SQL Feed----------------------------------
        $sql_log=   "insert into portalbd.activity_feed ( ".
            " USER ".
            ", USER_NAME ".
            ", GRUPO ".
            ", STATUS ".
            ", PEDIDO_OFERTA ".
            ", ACCION ".
            ", CONCEPTO_ID ".
            ", IP_HOST ".
            ", CP_HOST ".
            ") values( ".
            " UPPER('$useri')".
            ", UPPER('$nombreGalleta')".
            ", UPPER('AGENDAMIENTO')".
            ",'$novedad' ".
            ",'$PEDIDO_ID' ".
            ",'REAGENDO PEDIDO' ".
            ",'$CODIGO_ESTADO' ".
            ",'$usuarioIp' ".
            ",'$usuarioPc')";

        $rlog = $this->mysqli->query($sql_log);
        // ---------------------------------- SQL Feed
        //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','AGENDAMIENTO','CERRADO_AGEN','PEDIDO: $PEDIDO_ID','REAGENDAMIENTO','CERRADO_AGEN') ";                        //echo $sqlfeed;
        //$rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

        $sqlinteraccion="insert into gestor_interacciones_agendamiento (PEDIDO,CEDULA,NOMBRE,CANAL,NOVEDAD,CODIGO_ESTADO) values ('$PEDIDO_ID','$cliente_id','$nombre_usuario','CALL CENTER','$novedad','$CODIGO_ESTADO') ";

        $rrrr = $this->mysqli->query($sqlinteraccion) or die($this->mysqli->error.__LINE__);


        //hago la actualizacion en fenix
        $this->response(json_encode(array("msg"=>"OK","data" => $today)),200);
    }

//---------------------fin insert pedido reagendamiento--------------------agendamiento-------------

//---------------------insertar pedido auditoria-----------------agendamiento--------------
    private function insertPedidoAuditoria(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido = json_decode(file_get_contents("php://input"),true);
        //var_dump($pedido);
        $column_names = array('PEDIDO_ID','CONCEPTOS','ACTIVIDADES','IDLLAMADA','NOVEDAD','FECHA_CITA_FENIX','FECHA_CITA_REAGENDA','FECHA_INGRESO','ASESOR','FECHA_INICIO','FECHA_FIN','DURACION','OBSERVACION_FENIX','OBSERVACION_GESTOR','FUENTE','ACTIVIDAD_GESTOR','ASESORNAME','CELULAR_AVISAR','CLIENTE_ID','CORREO_UNE','DIRECCION_ENVIO','E_MAIL_AVISAR','MICROZONA','NOMBRE_USUARIO','PARENT_ID','TELEFONO_AVISAR','TIEMPO_TOTAL','JORNADA_CITA','FECHA_ESTADO','DEPARTAMENTO','NUMERO_CR');

        $f = fopen("consultas.txt", "w");
        //fwrite($f, var_dump($pedido)."\n\n");
        $pedido=$pedido['pedido'];

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
        $today2 = date("Y-m-d");

        $programacion=$pedido['PROGRAMACION'];
        $parent=$pedido['PARENT_ID'];
        $PEDIDO_ID=$pedido['PEDIDO_ID'];
        $sourcee=$pedido['source'];




        if($sourcee=='')$sourcee="AUTO";

        $query = "INSERT INTO gestor_historicos_reagendamiento (".trim($columns,',').",PROGRAMACION,SOURCE ) VALUES(".trim($values,',').",'$programacion','$sourcee')";

        $fecha_cita_reagen=$pedido['FECHA_CITA_REAGENDA'];

        $jornada=$pedido['JORNADA_CITA'];



        $useri=$pedido['ASESOR'];
        $username=$pedido['ASESORNAME'];
        $cliente_id=$pedido['CLIENTE_ID'];
        $nombre_usuario=$pedido['NOMBRE_USUARIO'];
        $CODIGO_ESTADO='';
        $ACCESO='';
        $fecha_fin='';

        $novedad=$pedido['NOVEDAD'];
        //var_dump($query);
        fwrite($f, "$query\n");

        fclose($f);



        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);


        //echo $xxx ;
        /*if($novedad=='ESCALADO CR'||$novedad=='RECUPERAR EQUIPOS'||$novedad=='RETIRAR ACCESO'|| $novedad=='ERROR SIEBEL'|| $novedad=='CUMPLIDO'|| $novedad=='ANULADO' || $novedad=='OTROS CONCEPTO ESCALADO' || $novedad=='NO APLICA GESTION' || $novedad=='DESINSTALAR' || $novedad=='YA ESTA AGENDADO'){
	                        $sqlupdate="update gestor_pendientes_reagendamiento set FECHA_ACTUALIZACION='$today',STATUS='MALO',FECHA_CITA_REAGENDA='$fecha_cita_reagen',TODAY_TRIES=(SELECT COUNT(*) FROM gestor_historicos_reagendamiento WHERE PEDIDO_ID='$PEDIDO_ID' ),PROGRAMACION='',ASESOR='' WHERE ID=$parent ";
			}else{ if($novedad=='PENDIENTE AGENDA'){
				$sqlupdate="update gestor_pendientes_reagendamiento set FECHA_ACTUALIZACION='$today',STATUS='PENDI_AGEN',FECHA_CITA_REAGENDA='$fecha_cita_reagen',TODAY_TRIES=(SELECT COUNT(*) FROM gestor_historicos_reagendamiento WHERE PEDIDO_ID='$PEDIDO_ID' ),PROGRAMACION='',ASESOR='' WHERE ID=$parent ";
			} else {
					$sqlupdate="update gestor_pendientes_reagendamiento set FECHA_ACTUALIZACION='$today',STATUS='CERRADO_AGEN',FECHA_CITA_REAGENDA='$fecha_cita_reagen',TODAY_TRIES=(SELECT COUNT(*) FROM gestor_historicos_reagendamiento WHERE PEDIDO_ID='$PEDIDO_ID' ),PROGRAMACION='',ASESOR='' WHERE ID=$parent ";
			}

			}*/

        if($novedad=='PENDIENTE AGENDA' || $novedad=='INFORMACION-PARA CONFIRMAR LLEGADA DE TECNICO' || $novedad=='INFORMACION GENERAL'){
            $sqlupdate="update gestor_pendientes_reagendamiento set FECHA_ACTUALIZACION='$today',STATUS='PENDI_AGEN',FECHA_CITA_REAGENDA='$fecha_cita_reagen',TODAY_TRIES=(SELECT COUNT(*) FROM gestor_historicos_reagendamiento WHERE PEDIDO_ID='$PEDIDO_ID' ),PROGRAMACION='',ASESOR='' WHERE ID=$parent ";
        }else {
            $sqlupdate="update gestor_pendientes_reagendamiento set FECHA_ACTUALIZACION='$today',STATUS='MALO',FECHA_CITA_REAGENDA='$fecha_cita_reagen',TODAY_TRIES=(SELECT COUNT(*) FROM gestor_historicos_reagendamiento WHERE PEDIDO_ID='$PEDIDO_ID' ),PROGRAMACION='',ASESOR='' WHERE ID=$parent ";
        }




        $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);

        // SQL Feed----------------------------------
        $sql_log=   "insert into portalbd.activity_feed ( ".
            " USER ".
            ", USER_NAME ".
            ", GRUPO ".
            ", STATUS ".
            ", PEDIDO_OFERTA ".
            ", ACCION ".
            ", CONCEPTO_ID ".
            ", IP_HOST ".
            ", CP_HOST ".
            ") values( ".
            " UPPER('$usuarioGalleta')".
            ", UPPER('$nombreGalleta')".
            ", UPPER('$grupoGalleta')".
            ",'$novedad' ".
            ",'$PEDIDO_ID' ".
            ",'AUDITO REAGENDAMIENTO' ".
            ",'CERRADO_AGEN' ".
            ",'$usuarioIp' ".
            ",'$usuarioPc')";

        $rlog = $this->mysqli->query($sql_log);
        // ---------------------------------- SQL Feed
        //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','AGENDAMIENTO','CERRADO_AGEN','PEDIDO: $PEDIDO_ID','REAGENDAMIENTO','CERRADO_AGEN') ";                        //echo $sqlfeed;
        //$rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

        $sqlinteraccion="insert into gestor_interacciones_agendamiento (PEDIDO,CEDULA,NOMBRE,CANAL,NOVEDAD,CODIGO_ESTADO) values ('$PEDIDO_ID','$cliente_id','$nombre_usuario','CALL CENTER','$novedad','$CODIGO_ESTADO') ";

        $rrrr = $this->mysqli->query($sqlinteraccion) or die($this->mysqli->error.__LINE__);


        //hago la actualizacion en fenix
        $this->response(json_encode(array("msg"=>"OK","data" => $today)),200);
    }

//---------------------fin insertar pedido auditoria-----------------agendamiento--------------

//--------------------inser pedidos malos --------------------agendamiento-----------------

    private function insertMPedidomalo(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        //echo "(1)";
        $pedido = json_decode(file_get_contents("php://input"),true);
        //var_dump($pedido);
        $column_names = array('PEDIDO_ID','CONCEPTOS','ACTIVIDADES','IDLLAMADA','NOVEDAD','FECHA_CITA_FENIX','FECHA_CITA_REAGENDA','FECHA_INGRESO','ASESOR','FECHA_INICIO','FECHA_FIN','DURACION','OBSERVACION_FENIX','OBSERVACION_GESTOR','FUENTE','ACTIVIDAD_GESTOR','ASESORNAME','CELULAR_AVISAR','CLIENTE_ID','CORREO_UNE','DIRECCION_ENVIO','E_MAIL_AVISAR','MICROZONA','NOMBRE_USUARIO','PARENT_ID','TELEFONO_AVISAR','TIEMPO_TOTAL','JORNADA_CITA','FECHA_ESTADO','DEPARTAMENTO','NUMERO_CR','PROCESO','PROGRAMACION');
        $pedido=$pedido['pedido'];
        $keys = array_keys($pedido);
        $today = date("Y-m-d H:i:s");
        $today2 = date("Y-m-d");
        $FECHA_ESTADO='';
        $OBSERVACION_GESTOR=$pedido['OBSERVACION_GESTOR'];;
        $FUENTE=$pedido['FUENTE'];
        $novedad=$pedido['NOVEDAD'];
        $useri=$pedido['ASESOR'];
        $username=$pedido['ASESORNAME'];
        $cliente_id=$pedido['CLIENTE_ID'];
        $nombre_usuario=$pedido['NOMBRE_USUARIO'];
        $PEDIDO_ID=$pedido['PEDIDO_ID'];
        $cliente_id=$pedido['CLIENTE_ID'];
        $DEPARTAMENTO=$pedido['DEPARTAMENTO'];
        $programacion=$pedido['PROGRAMACION'];
        $sourcee=$pedido['source'];
        $proceso=$pedido['proceso'];
        $FECHA_INICIO='';
        $FECHA_FIN='';

        $columns = '';
        $values = '';



        if($sourcee=='')$sourcee="MANUAL";

        $query = "INSERT INTO gestor_historicos_reagendamiento (".trim($columns,',').",PROGRAMACION,SOURCE ) VALUES(".trim($values,',').",'$programacion','$sourcee')";

        $FECHA_CITA_REAGENDA=$pedido['FECHA_CITA_REAGENDA'];

        $JORNADA_CITA=$pedido['JORNADA_CITA'];


        //echo($JORNADA_CITA);
        // echo($programacion);


        $today = date("Y-m-d H:i:s");

        //$query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado) VALUES(".trim($values,',').",'$fecha_estado')";
        if(!empty($pedido)){
            //$concepto_final=$this->updateFenix($pedido);
            // $query = "INSERT INTO gestor_historicos_reagendamiento (".trim($columns,',').",source) VALUES(".trim($values,',').",'MANUAL')";

            $query = "insert into gestor_historicos_reagendamiento (PEDIDO_ID,FUENTE,CLIENTE_ID,NOVEDAD,ASESOR,ASESORNAME,DEPARTAMENTO,OBSERVACION_GESTOR,PROCESO,ACCESO,SOURCE,FECHA_CITA_REAGENDA,JORNADA_CITA,FECHA_INICIO,FECHA_FIN,DURACION) values ('$PEDIDO_ID','$FUENTE','$cliente_id','$novedad','$useri','$username','$DEPARTAMENTO','$OBSERVACION_GESTOR','$proceso','BACKOFFICE','$sourcee','$FECHA_CITA_REAGENDA','$JORNADA_CITA','$today','$today','00:00:00') ";

            //echo $query;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
            //hago la actualizacion en fenix
            //  $query1 = "insert into gestor_pendientes_reagendamiento (PEDIDO_ID,CLIENTE_ID,ASESOR) values ('$PEDIDO_ID','$cliente_id','$useri') ";
            // $r = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);

            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'$proceso' ".
                ",'$pedido' ".
                ",'REAGENDO PEDIDO MALO' ".
                ",'$novedad' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response(json_encode(array("msg"=>"N/A","data" => $today)),200);

        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }
//-------------------- fin insert pedidos malos --------------------agendamiento-----------------


//------------------------insert pedidos adelantar agenda -------------agendamiento-------------

    private function insertPedidoAdelantarAgenda(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido = json_decode(file_get_contents("php://input"),true);
        //var_dump($pedido);
        $column_names = array('PEDIDO_ID','CONCEPTOS','ACTIVIDADES','NOVEDAD','FECHA_CITA_FENIX','FECHA_CITA_REAGENDA','FECHA_INGRESO','ASESOR','FECHA_INICIO','FECHA_FIN','DURACION','OBSERVACION_FENIX','OBSERVACION_GESTOR','FUENTE','ACTIVIDAD_GESTOR','ASESORNAME','CELULAR_AVISAR','CLIENTE_ID','CORREO_UNE','DIRECCION_ENVIO','E_MAIL_AVISAR','MICROZONA','NOMBRE_USUARIO','PARENT_ID','TELEFONO_AVISAR','TIEMPO_TOTAL','JORNADA_CITA','FECHA_ESTADO','DEPARTAMENTO');

        $f = fopen("consultas.txt", "w");
        //fwrite($f, var_dump($pedido)."\n\n");
        $pedido=$pedido['pedido'];

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
        $today2 = date("Y-m-d");

        $programacion=$pedido['PROGRAMACION'];
        $parent=$pedido['PARENT_ID'];
        $PEDIDO_ID=$pedido['PEDIDO_ID'];
        $sourcee=$pedido['source'];

        if($sourcee=='')$sourcee="AUTO";

        $query = "INSERT INTO gestor_historicos_reagendamiento (".trim($columns,',').",PROGRAMACION,SOURCE ) VALUES(".trim($values,',').",'$programacion','$sourcee')";

        $fecha_cita_reagen=$pedido['FECHA_CITA_REAGENDA'];

        $jornada=$pedido['JORNADA_CITA'];
        //echo "parentid: $parent";

        $useri=$pedido['ASESOR'];
        $username=$pedido['ASESORNAME'];

        $novedad=$pedido['NOVEDAD'];
        //echo $query;
        fwrite($f, "$query\n");
        fclose($f);
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        //cierro el registro en la tabla de automatizacion asignaciones

        if($novedad=='AGENDADO'||$novedad=='AGENDADO MANUAL'||$novedad=='AGENDADO_FUTURO'|| $novedad=='NO CONOCE EL PEDIDO'){
            $sqlupdate="update gestor_pendientes_reagendamiento set FECHA_ACTUALIZACION='$today',STATUS='CERRADO_AGEN',FECHA_CITA_REAGENDA='$fecha_cita_reagen',TODAY_TRIES=(SELECT COUNT(*) FROM gestor_historicos_reagendamiento WHERE PEDIDO_ID='$PEDIDO_ID' and FECHA_CARGA BETWEEN '$today2 00:00:00' AND '$today2 23:59:59'),PROGRAMACION='',ASESOR='' WHERE ID=$parent ";
        }else if($novedad=='YA ESTA ANULADO'||$novedad=='YA ESTA CUMPLIDO' ||$novedad=='YA ESTA AGENDADO'||$novedad=='YA ESTA ANULADO-CERRADO'|| $novedad=='ANULADO 42'||$novedad=='MIGRACION HFC'){
            $sqlupdate="update gestor_pendientes_reagendamiento set FECHA_ACTUALIZACION='$today',STATUS='MALO',FECHA_CITA_REAGENDA='$fecha_cita_reagen',TODAY_TRIES=(SELECT COUNT(*) FROM gestor_historicos_reagendamiento WHERE PEDIDO_ID='$PEDIDO_ID' and FECHA_CARGA BETWEEN '$today2 00:00:00' AND '$today2 23:59:59'),PROGRAMACION='',ASESOR='' WHERE ID=$parent ";
        } else {
            $sqlupdate="update gestor_pendientes_reagendamiento set FECHA_ACTUALIZACION='$today',STATUS='ADEN_AGEN',FECHA_CITA_REAGENDA='$fecha_cita_reagen',TODAY_TRIES=(SELECT COUNT(*) FROM gestor_historicos_reagendamiento WHERE PEDIDO_ID='$PEDIDO_ID' and FECHA_CARGA BETWEEN '$today2 00:00:00' AND '$today2 23:59:59'),PROGRAMACION='$programacion',ASESOR='' WHERE ID=$parent ";
        }
        //echo $sqlupdate;
        $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);

        // SQL Feed----------------------------------
        $sql_log=   "insert into portalbd.activity_feed ( ".
            " USER ".
            ", USER_NAME ".
            ", GRUPO ".
            ", STATUS ".
            ", PEDIDO_OFERTA ".
            ", ACCION ".
            ", CONCEPTO_ID ".
            ", IP_HOST ".
            ", CP_HOST ".
            ") values( ".
            " UPPER('$usuarioGalleta')".
            ", UPPER('$nombreGalleta')".
            ", UPPER('$grupoGalleta')".
            ",'$novedad' ".
            ",'$PEDIDO_ID' ".
            ",'REAGENDO PEDIDO' ".
            ",'CERRADO_AGEN' ".
            ",'$usuarioIp' ".
            ",'$usuarioPc')";

        $rlog = $this->mysqli->query($sql_log);
        // ---------------------------------- SQL Feed
        // $sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','AGENDAMIENTO','CERRADO_AGEN','PEDIDO: $PEDIDO_ID','REAGENDAMIENTO','CERRADO_AGEN') ";
        //echo $sqlfeed;
        //$rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);
        //hago la actualizacion en fenix
        $this->response(json_encode(array("msg"=>"OK","data" => $today)),200);
    }


//------------------------fin insert pedidos adelantar agenda -------------agendamiento-------------

//--------------------insert pedidos----------------------------------

    private function insertPedido(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido = json_decode(file_get_contents("php://input"),true);
        //2015-09-28: se retira seguimiento....
        //$column_names = array('pedido', 'fuente', 'actividad','estado','motivo', 'user','duracion','fecha_inicio','fecha_fin','PEDIDO_ID','SUBPEDIDO_ID','SOLICITUD_ID','MUNICIPIO_ID','CONCEPTO_ANTERIOR','caracteristica','motivo_malo');
        $column_names = array(
            'pedido'
        , 'fuente'
        , 'actividad'
        ,'ESTADO_ID'
        , 'OBSERVACIONES_PROCESO'
        , 'estado'
        , 'user'
        ,'duracion'
        ,'fecha_inicio'
        ,'fecha_fin'
        ,'PEDIDO_ID'
        ,'SUBPEDIDO_ID'
        ,'SOLICITUD_ID'
        ,'MUNICIPIO_ID'
        ,'CONCEPTO_ANTERIOR'
        ,'motivo_malo'
        ,'DEPARTAMENTO'
        ,'TIPO_TRABAJO'
        ,'TECNOLOGIA_ID');
        $keys = array_keys($pedido);
        $columns = '';
        $values = '';
        $fecha_estado='';
        $fecha_estado=$pedido['pedido']['FECHA_ESTADO'];
        $iddd=$pedido['pedido']['ID'];

        $estadum=$pedido['pedido']['estado'];
        $estadoid=$pedido['pedido']['estado'];
        $useri=$pedido['pedido']['user'];
        $username=$pedido['pedido']['username'];

        $fuente=$pedido['pedido']['fuente'];

        $PEDIDO_IDi=$pedido['pedido']['PEDIDO_ID'];
        $SUBPEDIDO_IDi=$pedido['pedido']['SUBPEDIDO_ID'];
        $SOLICITUD_IDi=$pedido['pedido']['SOLICITUD_ID'];

        $CONCEPT=$pedido['pedido']['CONCEPTO_ID'];
        $concepto_anterior=$pedido['pedido']['CONCEPTO_ANTERIOR'];
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

            //verifico que si el pedido existe en la tabla de bloqueados para no dejar guardar
            $queryBloqueo="SELECT PEDIDO_ID FROM gestor_pedidos_desbloqueados WHERE PEDIDO_ID='$PEDIDO_IDi' AND ASESOR='$useri' AND TIMEDIFF(NOW( ) ,FECHA_DESBLOQUEO) <  '01:00:00' ";
            //echo ($queryBloqueo);
            $blo = $this->mysqli->query($queryBloqueo) or die($this->mysqli->error.__LINE__);
            if($blo->num_rows > 0){
                $this->response(json_encode(array("msg"=>"El pedido bloqueado por Usuario por mas de una hora, ".
                    "fue liberado por el sistema, usuario no podra gestionarlo hasta despues de una hora!!!")),200);
            }

            $fuente=$pedido['fuente'];

            if($fuente=='FENIX_NAL' && $CONCEPT=='PETEC'){
                $concepto_final=$this->updateFenix($pedido);
                $estado=$pedido['pedido']['estado'];
                //echo "estado: '$estadum'";
                //var_dump($concepto_final);
                if($concepto_final=="NO CAMBIO CONCEPTO" && $estadum!="MALO"){
                    $this->response(json_encode(array("msg"=>"El pedido NO ha cambiado de concepto en Fenix!!!")),200);
                }

                if($concepto_final=="No rows!!!!" && $estadum!="MALO"){//INDICA QUE NO SE ENCONTRO INFORMACION EN FENIX CON ESTE USUARIO Y PEDIDO
                    //DO SOMETHING
                    $this->response(json_encode(array("msg"=>"ERROR!","text"=>"No rows!!!!")),200);
                }
            }

            if($fuente=='FENIX_BOG'){
                $concepto_final=$this->updateFenixBogota($pedido);
                //$estado=$pedido['pedido']['estado'];
                //echo "estado: '$estadum'";
                //var_dump($concepto_final);
                if($concepto_final=="NO CAMBIO CONCEPTO" && $estadum!="MALO"){
                    $this->response(json_encode(array("msg"=>"El pedido NO ha cambiado de concepto en Fenix!!!")),200);
                }

                if($concepto_final=="No rows!!!!" && $estadum!="MALO"){//INDICA QUE NO SE ENCONTRO INFORMACION EN FENIX CON ESTE USUARIO Y PEDIDO
                    //DO SOMETHING
                    $this->response(json_encode(array("msg"=>"ERROR!","text"=>"No rows!!!!")),200);
                }
            }

            if($estadum=="MALO"){
                $concepto_final=$concepto_anterior;
                $query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado,concepto_final,source) VALUES(".trim($values,',').",'$fecha_estado','$concepto_final','$sourcee')";
                $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                $sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='$today',STATUS='$estadum',ASESOR='' WHERE ID=$iddd "; $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);
                //hago la actualizacion en fenix
                //activity feed.
                // SQL Feed----------------------------------
                $sql_log=   "insert into portalbd.activity_feed ( ".
                    " USER ".
                    ", USER_NAME ".
                    ", GRUPO ".
                    ", STATUS ".
                    ", PEDIDO_OFERTA ".
                    ", ACCION ".
                    ", CONCEPTO_ID ".
                    ", IP_HOST ".
                    ", CP_HOST ".
                    ") values( ".
                    " UPPER('$useri')".
                    ", UPPER('$nombreGalleta')".
                    ", UPPER('$grupoGalleta')".
                    ",'$estadum' ".
                    ",'$PEDIDO_IDi' ".
                    ",'GUARDO PEDIDO MALO' ".
                    ",'$concepto_final' ".
                    ",'$usuarioIp' ".
                    ",'$usuarioPc')";

                $rlog = $this->mysqli->query($sql_log);
                // ---------------------------------- SQL Feed
                //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta) values ('$useri','$username','ASIGNACIONES','$concepto_final','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi') ";
                //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','ASIGNACIONES','$estadum','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi','ESTUDIO','$concepto_final') ";

                //echo $sqlfeed;
                //$rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

                $this->response(json_encode(array("msg"=>"$concepto_final","data" => $today)),200);


            }else{
                //var_dump($concepto_final);

                if($concepto_final['FECHA_FINAL']==''){
                    $concepto_final['FECHA_FINAL']=$today;
                }

                if($concepto_final['CONCEPTO_ID']==''){
                    $concepto_final['CONCEPTO_ID']=$CONCEPT;
                }

                $query = "INSERT INTO pedidos(".trim($columns,',').",fecha_estado,concepto_final,source,concepto_anterior_fenix,fecha_estado_fenix) VALUES(".trim($values,',').",'$fecha_estado','".$concepto_final['CONCEPTO_ID']."','$sourcee','".$concepto_final['CONCEPTO_ID_ANTERIOR_FENIX']."','".$concepto_final['FECHA_FINAL']."')";
                $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
                $concepto_fen=$concepto_final['CONCEPTO_ID'];
                //cierro el registro en la tabla de automatizacion asignaciones
                $sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='$today',CONCEPTO_ID='".$concepto_final['CONCEPTO_ID']."',STATUS='CERRADO_PETEC', ASESOR='' WHERE ID=$iddd ";

                $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);

                // SQL Feed----------------------------------
                $sql_log=   "insert into portalbd.activity_feed ( ".
                    " USER ".
                    ", USER_NAME ".
                    ", GRUPO ".
                    ", STATUS ".
                    ", PEDIDO_OFERTA ".
                    ", ACCION ".
                    ", CONCEPTO_ID ".
                    ", IP_HOST ".
                    ", CP_HOST ".
                    ") values( ".
                    " UPPER('$useri')".
                    ", UPPER('$nombreGalleta')".
                    ", UPPER('$grupoGalleta')".
                    ",'$estadum' ".
                    ",'$PEDIDO_IDi' ".
                    ",'ASIGNO PEDIDO' ".
                    ",'$estadum' ".
                    ",'$usuarioIp' ".
                    ",'$usuarioPc')";

                $rlog = $this->mysqli->query($sql_log);
                // ---------------------------------- SQL Feed
                //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta) values ('$useri','$username','ASIGNACIONES','$concepto_final','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi') ";
                //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$useri','$username','ASIGNACIONES','$estadum','PEDIDO: $PEDIDO_IDi-$SUBPEDIDO_IDi$SOLICITUD_IDi','ESTUDIO','$concepto_final') ";
                //echo $sqlfeed;
                //$rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);
                //hago la actualizacion en fenix
                $this->response(json_encode(array("msg"=>"$concepto_final","data" => $today,"con_fenix"=> $concepto_fen)),200);

            }



        }else{
            $this->response('',204);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }

    private function insertPedidoEdatel(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido = json_decode(file_get_contents("php://input"),true);

        $SOLICITUD=$pedido['pedido']['SOLICITUD'];
        $FUENTE = 'EDATEL';
        $ACTIVIDAD = 'TRANSACCION';
        $SOURCE = 'AUTO';
        $COD_LOCALIDAD=$pedido['pedido']['COD_LOCALIDAD'];
        $LOCALIDAD=$pedido['pedido']['LOCALIDAD'];
        $GEOREFERENCIA=$pedido['pedido']['GEOREFERENCIA'];
        $REDSUGERIDA=$pedido['pedido']['REDSUGERIDA'];
        $FECHA_CARGA=$pedido['pedido']['FECHA_CARGA'];
        $TIPO_TRANSACCION=$pedido['pedido']['TIPO_TRANSACCION'];
        $Duracion=$pedido['pedido']['duracion'];
        $ESTADO=$pedido['pedido']['motivo_malo'];
        $useri=$pedido['pedido']['user'];
        $username=$pedido['pedido']['username'];
        //echo var_dump($ESTADO);

        $today = date("Y-m-d H:i:s");
        $query = "INSERT INTO pedidos (pedido, fuente, actividad, user, estado, duracion, ".
                                    " fecha_estado, concepto_anterior, concepto_final,".
                                    " source, pedido_id, subpedido_id, solicitud_id ,municipio_id, ".
                                    " DEPARTAMENTO, ACCION ) ".
                                    " values ( ".
            " '$SOLICITUD','$FUENTE','$ACTIVIDAD','$useri','$ESTADO','$Duracion','$FECHA_CARGA','$TIPO_TRANSACCION','$TIPO_TRANSACCION','$SOURCE','$SOLICITUD','$SOLICITUD','$SOLICITUD','$COD_LOCALIDAD','$LOCALIDAD', ".
            " '$REDSUGERIDA') ";


        //echo var_dump($query);

        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

           if($rr == 'true'){
                //echo var_dump("ingreso");
                $queryupdate = "update pendientes_edatel set STATUS = '$ESTADO', ASESOR= '$useri' where SOLICITUD = '$SOLICITUD'";
                $update = $this->mysqli->query($queryupdate) or die($this->mysqli->error.__LINE__);
                //echo var_dump($update);
            }
        //echo var_dump($rr);

        $this->response(json_encode(array("msg"=>"OK","pedido" => $pedido['pedido']['SOLICITUD'])),200);

        //$this->response('',204);


    }

    //Funcion para actualizar Concepto y Fecha estado en PEDIDOS en pendientesm
    //2015-09-17 - Se modifica query para que traiga Concepto y fecha de Novedades - CGONZGO


//--------------------fin insert pedidos----------------------------------

//---------------------------------update fenix bogota------base de datos----

    private function updateFenixBogota($obj){
        $id=$obj['ID'];
        $pedido_id=$obj['PEDIDO_ID'];
        $subpedido_id=$obj['SUBPEDIDO_ID'];
        $solicitud_id=$obj['SOLICITUD_ID'];
        $concepto_id=$obj['CONCEPTO_ID'];
        $user=$obj['user'];

        $this->dbFenixBogotaConnect();
        $connfb=$this->connfb;

        $sqlfenix=" select ".
            " nsol.concepto_id_anterior AS CONCEPTO_ID_ANTERIOR_FENIX".
            " , nsol.concepto_id_actual as CONCEPTO_ID".
            " , to_char(nsol.fecha,'RRRR-MM-DD hh24:mi:ss') as FECHA_FINAL ".
            " , nsol.usuario_id as USUARIO_ID ".
            " from fnx_novedades_solicitudes nsol ".
            " where nsol.pedido_id='$pedido_id' ".
            " and nsol.subpedido_id='$subpedido_id' ".
            " and nsol.solicitud_id='$solicitud_id' ".
            //" and nsol.usuario_id='$user' ".
            " and nsol.consecutivo=(select max(a.consecutivo) from fenix.fnx_novedades_solicitudes a ".
            "   where nsol.pedido_id=a.pedido_id(+) ".
            "     and nsol.subpedido_id=a.subpedido_id(+) ".
            "     and nsol.solicitud_id=a.solicitud_id(+)) ";
        //"     and nsol.usuario_id=a.usuario_id(+)) ";


        //echo  $sqlfenix." \n ";
        $stid = oci_parse($connfb, $sqlfenix);
        oci_execute($stid);
        if($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
            //echo $concepto_id." - ".$row['CONCEPTO_ID']."\n";

            if($concepto_id!=$row['CONCEPTO_ID']){
                $status="CERRADO_PETEC";
                return $row;
            }
            else{

                $sqlfenix=" select  nsol.concepto_id_anterior AS CONCEPTO_ID_ANTERIOR_FENIX  ".
                    " , sol.concepto_id as CONCEPTO_ID  ".
                    " , to_char(nsol.fecha,'RRRR-MM-DD hh24:mi:ss') as FECHA_FINAL   ".
                    " , nsol.usuario_id as USUARIO_ID   ".
                    " from fnx_novedades_solicitudes nsol, fnx_solicitudes sol ".
                    " where nsol.pedido_id='06052334'   ".
                    " and nsol.subpedido_id='1'   ".
                    " and nsol.solicitud_id='1'   ".
                    " and nsol.pedido_id=sol.pedido_id ".
                    " and nsol.subpedido_id=sol.subpedido_id ".
                    " and nsol.solicitud_id=sol.solicitud_id ".
                    " and nsol.consecutivo=(select max(a.consecutivo)  ".
                    " from fenix.fnx_novedades_solicitudes a     ".
                    " where nsol.pedido_id=a.pedido_id(+)       ".
                    " and nsol.subpedido_id=a.subpedido_id(+)       ".
                    " and nsol.solicitud_id=a.solicitud_id(+)) ";

                //echo  $sqlfenix." \n ";
                $stid = oci_parse($connfb, $sqlfenix);
                oci_execute($stid);
                if($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
                    //echo $concepto_id." - ".$row['CONCEPTO_ID']."\n";

                    if($concepto_id!=$row['CONCEPTO_ID']){
                        $status="CERRADO_PETEC";
                        return $row;
                    }
                    else{

                        return "NO CAMBIO CONCEPTO";
                    }

                }
                return "No rows!!!!";
            }
        }
        return "No rows!!!!";

    }
//--------------------------------- fin update fenix bogota------base de datos----

//---------------------------------update fenix nacional------base de datos----

    private function updateFenix($obj){
        $id=$obj['ID'];
        $pedido_id=$obj['PEDIDO_ID'];
        $subpedido_id=$obj['SUBPEDIDO_ID'];
        $solicitud_id=$obj['SOLICITUD_ID'];
        $concepto_id=$obj['CONCEPTO_ID'];
        $user=$obj['user'];

        $this->dbFenixConnect();
        $connf=$this->connf;
        //2. verifico el estado del pedido en fenix y actualizo estado en base de datos local si es necesario

        $sqlfenix=" select ".
            " nsol.concepto_id_anterior AS CONCEPTO_ID_ANTERIOR_FENIX".
            " , nsol.concepto_id_actual as CONCEPTO_ID".
            " , to_char(nsol.fecha,'RRRR-MM-DD hh24:mi:ss') as FECHA_FINAL ".
            " , nsol.usuario_id as USUARIO_ID ".
            " from fnx_novedades_solicitudes nsol ".
            " where nsol.pedido_id='$pedido_id' ".
            " and nsol.subpedido_id='$subpedido_id' ".
            " and nsol.solicitud_id='$solicitud_id' ".
            //" and nsol.usuario_id='$user' ".
            " and nsol.consecutivo=(select max(a.consecutivo) from fenix.fnx_novedades_solicitudes a ".
            "   where nsol.pedido_id=a.pedido_id(+) ".
            "     and nsol.subpedido_id=a.subpedido_id(+) ".
            "     and nsol.solicitud_id=a.solicitud_id(+)) ";
        //"     and nsol.usuario_id=a.usuario_id(+)) ";


        //echo  $sqlfenix." \n ";
        $stid = oci_parse($connf, $sqlfenix);
        oci_execute($stid);
        if($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
            //echo $concepto_id." - ".$row['CONCEPTO_ID']."\n";
            if($concepto_id!=$row['CONCEPTO_ID']){
                $status="CERRADO_PETEC";
                if($row['CONCEPTO_ID']!='PETEC' && $row['CONCEPTO_ID']!='92' && $row['CONCEPTO_ID']!='15' && $row['CONCEPTO_ID']!='OKRED'){//el concepto cambio, actualizo y quito el status de pendiente
                    $status="CERRADO_PETEC";
                }
                return $row;
            }else{//no cambio de concepto, controlar...
                return "NO CAMBIO CONCEPTO";
            }
        }
        return "No rows!!!!";
    }

//---------------------------------fin update fenix nacional------base de datos----

//-----------------------update reconfiguracion ----------asignacion--------

    private function updateFenixReconfiguracion($obj){
        $id=$obj['ID'];
        $pedido_id=$obj['PEDIDO_ID'];
        $user=$obj['user'];
        $concepto_id=$obj['CONCEPTO_ID'];

        $this->dbFenixConnect();
        $connf=$this->connf;


        $sqlfenix=" select ".
            " regexp_replace(LISTAGG(nsol.concepto_id_anterior,',') WITHIN GROUP (ORDER BY NULL),'([^,]+)(,\\1)+', '\\1' ) AS CONCEPTO_ID_ANTERIOR_FENIX  ".
            ", regexp_replace(LISTAGG(nsol.concepto_id_actual,',') WITHIN GROUP (ORDER BY NULL),'([^,]+)(,\\1)+', '\\1' ) AS CONCEPTO_ID ".
            ",MIN( to_char(nsol.fecha,'RRRR-MM-DD hh24:mi:ss')) as FECHA_FINAL ".
            ", MIN(nsol.usuario_id) as USUARIO_ID ".
            " from fnx_novedades_solicitudes nsol ".
            " where nsol.pedido_id='$pedido_id' ".
            " and nsol.consecutivo=(select max(a.consecutivo) from fenix.fnx_novedades_solicitudes a ".
            " where nsol.pedido_id=a.pedido_id(+) ".
            " and nsol.subpedido_id=a.subpedido_id(+) ".
            " and nsol.solicitud_id=a.solicitud_id(+)) ";

        $stid = oci_parse($connf, $sqlfenix);
        oci_execute($stid);
        if($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
            //echo $concepto_id." - ".$row['CONCEPTO_ID']."\n";
            if($concepto_id!=$row['CONCEPTO_ID']){
                $status="CERRADO_PETEC";
                if($row['CONCEPTO_ID']!='PETEC' && $row['CONCEPTO_ID']!='92' && $row['CONCEPTO_ID']!='15' && $row['CONCEPTO_ID']!='OKRED'){//el concepto cambio, actualizo y quito el status de pendiente
                    $status="CERRADO_PETEC";
                }
                return $row;
            }else{//no cambio de concepto, controlar...

                return "NO CAMBIO CONCEPTO";
            }
        }
        return "No rows!!!!";
    }

//-----------------------fin update reconfiguracion ----------asignacion--------

//---------------------pedidos por usuario ----------asignacion------

    private function pedidosPorUser(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $id = $this->_request['userID'];
        $today = date("Y-m-d");


        $query = "	SELECT  ".
            " p.id,  ".
            " p.pedido_id as pedido,  ".
            " p.fuente,  ".
            " p.actividad,  ".
            " p.fecha_fin,  ".
            " p.estado,  ".
            " my_sec_to_time(timestampdiff(second,p.fecha_inicio,p.fecha_fin)) as duracion,  ".
            " p.INCIDENTE,  ".
            " SUBSTRING_INDEX(p.concepto_final, ',', 3) as concepto_final, ".
            " p.source ".
            " from pedidos p ".
            " where 1=1  ".
            " and p.user='$id'  ".
            " and p.fecha_fin between '$today 00:00:00' and '$today 23:59:59'  ".
            " UNION  ".
            " SELECT  ".
            " nn.id  ".
            " , nn.oferta as pedido ".
            " ,'SIEBEL' as fuente  ".
            " , 'ESTUDIO' as actividad  ".
            " , nn.fecha_fin  ".
            " , nn.observacion as estado  ".
            " , my_sec_to_time(timestampdiff(second,nn.fecha_inicio,nn.fecha_fin)) as duracion  ".
            " , nn.incidente  ".
            " , SUBSTRING_INDEX(nn.ESTADO_FINAL, ',', 3) as concepto_final  ".
            " , 'MANUAL' as source ".
            " FROM portalbd.transacciones_nca nn ".
            " where 1=1   ".
            " and nn.USUARIO='$id'  ".
            " and nn.fecha_fin between '$today 00:00:00' and '$today 23:59:59' ".
            " and nn.OFERTA not in (select a.pedido_id from pedidos a where a.user='$id' and  a.fecha_fin between '$today 00:00:00' and '$today 23:59:59' ) ";

        $queryPediUnico="	SELECT  ".
            " COUNT(distinct c1.PEDIDO_ID) as pedidos ".
            " from ( ".
            " SELECT  ".
            " p.id,  ".
            " p.pedido_id as PEDIDO_ID,  ".
            " p.fuente,  ".
            " p.actividad,  ".
            " p.fecha_fin,  ".
            " p.estado,  ".
            " my_sec_to_time(timestampdiff(second,p.fecha_inicio,p.fecha_fin)) as duracion,  ".
            " p.INCIDENTE,  ".
            " SUBSTRING_INDEX(p.concepto_final, ',', 3) as concepto_final, ".
            " p.source ".
            " from pedidos p ".
            " where 1=1  ".
            " and p.user='$id'  ".
            " and p.fecha_fin between '$today 00:00:00' and '$today 23:59:59'  ".
            " UNION  ".
            " SELECT  ".
            " nn.id  ".
            " , nn.oferta as PEDIDO_ID ".
            " ,'SIEBEL' as fuente  ".
            " , 'ESTUDIO' as actividad  ".
            " , nn.fecha_fin  ".
            " , nn.observacion as estado  ".
            " , my_sec_to_time(timestampdiff(second,nn.fecha_inicio,nn.fecha_fin)) as duracion  ".
            " , nn.incidente  ".
            " , SUBSTRING_INDEX(nn.ESTADO_FINAL, ',', 3) as concepto_final  ".
            " , 'MANUAL' as source ".
            " FROM portalbd.transacciones_nca nn ".
            " where 1=1   ".
            " and nn.USUARIO='$id'  ".
            " and nn.fecha_fin between '$today 00:00:00' and '$today 23:59:59' ".
            " and nn.OFERTA not in (select a.pedido_id from pedidos a where a.user='$id' and  a.fecha_fin between '$today 00:00:00' and '$today 23:59:59' ) ) c1 ";


        $r2 = $this->mysqli->query($queryPediUnico) or die($this->mysqli->error.__LINE__);

        //$counter="0";

        if($r2->num_rows > 0){
            $result = array();
            if($rowd = $r2->fetch_assoc()){
                $counter=$rowd['pedidos'];
            }


            //$this->response($this->json($result), 200); // send user details
        }

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            $this->response($this->json(array($result,$counter)), 200); // send user details
            //echo $result,$counter;
        }
        $this->response('',204);        // If no records "No Content" status
    }

//---------------------fin pedidos por usuario ----------asignacion------

//---------------------pedidos por usuario reagendamiento ----------asignacion------


    private function pedidosPorUserReagendamiento(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $id = $this->_request['userID'];
        $today = date("Y-m-d");
        $query="SELECT ID,PEDIDO_ID,FUENTE,NOVEDAD,FECHA_FIN,DURACION,SUBZONA_ID from gestor_historicos_reagendamiento where ASESOR='$id' and FECHA_FIN between '$today 00:00:00' and '$today 23:59:59'";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            $this->response($this->json(array($result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }

//---------------------fin pedidos por usuario reagendamiento ----------asignacion------

//--------------------- pedidos por usuario activacion ----------activacion------------


    private function pedidosPorUserActivacion(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $id = $this->_request['userID'];
        $today = date("Y-m-d");

        $query=" SELECT ID,PEDIDO,ESTADO,TIPIFICACION,FECHA_FIN,TRANSACCION,ASESOR,TABLA  ".
            ", my_sec_to_time(timestampdiff(second, fecha_inicio, fecha_fin)) as duracion ".
            " from gestor_historico_activacion ".
            " where ASESOR='$id' ".
            " and FECHA_FIN between '$today 00:00:00' and '$today 23:59:59' ";

        /*$queryunico="SELECT ".
            " count(distinct pedido) as pedidos ".
            " from gestor_historico_activacion  ".
            " where ASESOR='$id'  ".
            " and fecha_fin between '$today 00:00:00'  ".
            " and '$today 23:59:59' ".
            " group by date_format(fecha_fin,'%Y-%m-%d')";


        $r2 = $this->mysqli->query($queryunico) or die($this->mysqli->error.__LINE__);

        //$counter="0";

        if($r2->num_rows > 0){
            $result = array();
            if($rowd = $r2->fetch_assoc()){
                $counter=$rowd['pedidos'];
            }

        }
*/
        // echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            $this->response($this->json(array($result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }

//--------------------- fin pedidos por usuario activacion ----------activacion------------

//-----------------------actividades por usuario------------------------activacion---------

    private function actividadesUser(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $id = $this->_request['userID'];
        $today = date("Y-m-d");

        $query=" SELECT ID,DIA,FECHA,TIPO_TRABAJO,APLICACION_ACTIVIDADES,COLA,AMANECIERON,GESTIONADO_DIA,QUEDAN_PENDIENTES,OBSERVACIONES,USUARIO,FECHA_FIN from transacciones_actividades where USUARIO='$id' and FECHA_FIN between '$today 00:00:00' and '$today 23:59:59' ";

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
//-----------------------fin actividades por usuario------------------------activacion---------


//----------------------eliminar filas subir archivos -------------------activacion---------------

    private function eliminarfile(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $file = $this->_request['file'];
        $dir = "../documentacion/activacion/";
        if($dh = opendir($dir)){
            if(file_exists($dir.$file)) @unlink($dir.$file);
            closedir($dh);
            $respuesta = "Archivo eliminado";
        }
        //echo $respuesta;
        $this->response(json_encode("OK"), 200);
        // send user details

        $this->response('',204);        // If no records "No Content" status
    }
//----------------------fin eliminar filas subir archivos -------------------activacion---------------


//-----------------------------------eliminar filas subir archivos agendamiento-----------------------

    private function eliminarfile1(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $file = $this->_request['file'];
        $dir = "../uploads/";
        if($dh = opendir($dir)){
            if(file_exists($dir.$file)) @unlink($dir.$file);
            closedir($dh);
            $respuesta = "Archivo eliminado";
        }
        //echo $respuesta;
        $this->response(json_encode("OK"), 200);
        // send user details

        $this->response('',204);        // If no records "No Content" status
    }


//-----------------------------------fin eliminar filas subir archivos agendamiento-----------------------

//------------------------------------pedidos por usuario adelantar agenda---------------------agendamiento----

    private function pedidosPorUserAdelantarAgenda(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $id = $this->_request['userID'];
        $today = date("Y-m-d");
        $query="SELECT ID,PEDIDO_ID,FUENTE,NOVEDAD,FECHA_FIN,DURACION from gestor_historicos_reagendamiento where ASESOR='$id' and ACTIVIDAD_GESTOR='ADELANTAR_AGENDA' and FECHA_FIN between '$today 00:00:00' and '$today 23:59:59'";

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


//------------------------------------fin pedidos por usuario adelantar agenda---------------------agendamiento----

//--------------------------ingresos por zonas de reagendamiento ---------------------agendamiento----
    private function getZonasReagendamiento(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $dep = $this->_request['departamento'];
        $today = date("Y-m-d");

        $sql="";

        if($dep=="VACIOS"){
            $sql=" SELECT DISTINCT SUBZONA_ID ".
                " FROM gestor_pendientes_reagendamiento ".
                " WHERE STATUS =  'PENDI_AGEN' ".
                " AND DEPARTAMENTO =  '' ".
                " and ASESOR =''  and (FECHA_CITA_FENIX <=CURDATE() OR FECHA_CITA_FENIX='9999-00-00')  and (MIGRACION='NO' or MIGRACION='' or MIGRACION is null ) ".
                " GROUP BY 1 ORDER BY 1 ASC ";

        }else if($dep=="DTH"){
            $sql=" SELECT DISTINCT SUBZONA_ID ".
                " FROM gestor_pendientes_reagendamiento ".
                " WHERE STATUS =  'PENDI_AGEN' ".
                " AND TECNOLOGIA_ID = 'DTH' ".
                " and ASESOR =''  and (FECHA_CITA_FENIX <=CURDATE() OR FECHA_CITA_FENIX='9999-00-00')  and (MIGRACION='NO' or MIGRACION='' or MIGRACION is null ) ".
                " GROUP BY 1 ORDER BY 1 ASC ";

        }else if($dep=="EDATEL"){
            $sql=" SELECT DISTINCT SUBZONA_ID ".
                " FROM gestor_pendientes_reagendamiento ".
                " WHERE STATUS =  'PENDI_AGEN' ".
                " AND DEPARTAMENTO =  'EDATEL' ".
                " and ASESOR ='' ".
                //" and (FECHA_CITA_FENIX <=CURDATE() OR FECHA_CITA_FENIX='9999-00-00') ".
                " and (MIGRACION='NO' or MIGRACION='' or MIGRACION is null ) ".
                " and (CASE WHEN TECNOLOGIA_ID = 'DTH' THEN 'DTH' ELSE DEPARTAMENTO END) <> 'DTH' ".
                " GROUP BY 1 ORDER BY 1 ASC ";
        }

        else{
            $sql=" SELECT DISTINCT SUBZONA_ID ".
                " FROM gestor_pendientes_reagendamiento ".
                " WHERE STATUS =  'PENDI_AGEN' ".
                " AND DEPARTAMENTO =  '$dep' ".
                " and ASESOR =''  and (FECHA_CITA_FENIX <=CURDATE() OR FECHA_CITA_FENIX='9999-00-00')  and (MIGRACION='NO' or MIGRACION='' or MIGRACION is null ) ".
                " and (CASE WHEN TECNOLOGIA_ID = 'DTH' THEN 'DTH' ELSE DEPARTAMENTO END) <> 'DTH' ".
                " GROUP BY 1 ORDER BY 1 ASC ";
        }

        //echo $sql;

        $r = $this->mysqli->query($sql) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $row['SUBZONA_ID']=utf8_encode($row['SUBZONA_ID']);
                $result[] = $row;
            }

            $this->response($this->json($result), 200); // send user details
        }


        $this->response('',200);        // If no records "No Content" status

    }

//--------------------------fin ingresos por zonas de reagendamiento ---------------------agendamiento----

//--------------------------ingresos por microzonas de reagendamiento ---------------------agendamiento----

    private function getMicrozonasReagendamiento(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $dep = $this->_request['departamento'];
        $zona = $this->_request['zona'];
        $today = date("Y-m-d");

        $sql="";

        if($dep=="VACIOS"){
            $sql=" SELECT MICROZONA,COUNT(*) AS COUNTER ".
                " FROM gestor_pendientes_reagendamiento ".
                " WHERE STATUS = 'PENDI_AGEN' ".
                " AND DEPARTAMENTO =  '' ".
                " AND SUBZONA_ID='$zona' ".

                " and ASESOR =''  and (FECHA_CITA_FENIX <=CURDATE() OR FECHA_CITA_FENIX='9999-00-00')  and (MIGRACION='NO' or MIGRACION='' or MIGRACION is null ) ".
                " and CONCEPTOS NOT LIKE '%AGEN%' ".
                " GROUP BY MICROZONA ORDER BY 2 DESC ";

        }else{
            $sql=" SELECT MICROZONA,COUNT(*) as COUNTER ".
                " FROM gestor_pendientes_reagendamiento ".
                " WHERE STATUS = 'PENDI_AGEN' ".
                " AND DEPARTAMENTO = '$dep' ".
                " AND SUBZONA_ID='$zona' ".
                " and ASESOR =''  and (FECHA_CITA_FENIX < CURDATE() OR FECHA_CITA_FENIX='9999-00-00')  and (MIGRACION='NO' or MIGRACION='' or MIGRACION is null ) ".
                " and CONCEPTOS NOT LIKE '%AGEN%' ".
                " GROUP BY MICROZONA ORDER BY 2 DESC ";
        }

        $r = $this->mysqli->query($sql) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $row['MICROZONA']=utf8_encode($row['MICROZONA']);
                $result[] = $row;
            }

            $this->response($this->json($result), 200); // send user details
        }


        $this->response('',204);        // If no records "No Content" status

    }

//--------------------------fin ingresos por microzonas de reagendamiento ---------------------agendamiento----

//-----------------------------departamento pendientes de reagendamiento -----------agendamiento----


    private function getDepartamentosPendientesReagendamiento(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $today = date("Y-m-d");

        $proceso = $this->_request['proceso'];

        if($proceso==""||$proceso=="TODO"){
            $proceso="  ";
        }else{
            $PROCESO=" and PROCESO='$proceso' ";
        }

        $query=" SELECT DISTINCT ( CASE WHEN DEPARTAMENTO =  '' THEN  'VACIOS' ".
            "  WHEN DEPARTAMENTO = null THEN 'VACIOS' ".
            "  WHEN TECNOLOGIA_ID = 'DTH' THEN 'DTH' ".
            "  ELSE DEPARTAMENTO END) AS DEPARTAMENT ".
            " FROM gestor_pendientes_reagendamiento ".
            " WHERE STATUS =  'PENDI_AGEN' ".
            " and ASESOR ='' ".
            $PROCESO.
            " and (FECHA_CITA_FENIX <=CURDATE() OR FECHA_CITA_FENIX='9999-00-00') ".
            //" and (MIGRACION='NO' or MIGRACION='' or MIGRACION is null ) ".
            " ORDER BY 1 ASC ";

        //echo $query;

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $row['DEPARTAMENT']=utf8_encode($row['DEPARTAMENT']);
                $result[] = $row;
            }

            //var_dump($result);
            $this->response($this->json($result), 200); // send user details
        }




        $this->response('',204);        // If no records "No Content" status

    }


//-----------------------------fin departamento pendientes de reagendamiento -----------agendamiento----

//-----------------------departamento reagendamiento por proceso----------------agendamiento-------------

    private function getDepartamentosPendientesReagendamientoproceso(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $today = date("Y-m-d");

        $proceso = $this->_request['proceso'];

        if($proceso==""||$proceso=="TODO"){
            $proceso="  ";
        }else{
            $PROCESO=" and PROCESO='$proceso' ";
        }

        $query=" SELECT DISTINCT ( CASE WHEN NOVEDAD =  '' THEN  'VACIOS'".
            "WHEN NOVEDAD = null THEN 'VACIOS' ".
            " ELSE NOVEDAD END) AS NOVEDADES ".
            "FROM gestor_historicos_reagendamiento ".
            "WHERE ACTIVIDAD_GESTOR='REAGENDAMIENTO' ".
            " AND PROCESO='$proceso'";

        //echo $query;

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $row['NOVEDADES']=utf8_encode($row['NOVEDADES']);
                $result[] = $row;
            }

            //var_dump($result);
            $this->response($this->json($result), 200); // send user details
        }




        $this->response('',204);        // If no records "No Content" status

    }

//-----------------------fin departamento reagendamiento por proceso----------------agendamiento-------------

//-----------------------departamento adelantar agenda----------------agendamiento-------------

    private function getDepartamentosAdelantarAgenda(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $query= " SELECT (CASE WHEN DEPARTAMENTO =  '' THEN  'VACIOS' ".
            "    WHEN DEPARTAMENTO = null THEN 'VACIOS' ".
            "    ELSE DEPARTAMENTO END) AS DEPARTAMENT ".
            " FROM gestor_microzonas_agendamiento ".
            " GROUP BY DEPARTAMENTO ".
            " ORDER BY DEPARTAMENTO ASC ";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                // $row['DEPARTAMENT']=utf8_encode($row['DEPARTAMENT']);
                $result[] = $row;
            }

            //var_dump($result);
            $this->response($this->json($result), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status
    }
//-----------------------fin departamento adelantar agenda----------------agendamiento-------------

//-------------------------parametrizacion por zona ---------------------asignaciones------------

    private function getZonasParametrizacionSiebel(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $dep = $this->_request['departamento'];

        $this->dbConnect03();
        //echo "departamento es: $dep\n";
        $sql= " SELECT DISTINCT ZONA".
            " FROM alistamiento.parametrizacion_siebel ".
            " WHERE DEPARTAMENTO ='$dep' ".
            " GROUP BY ZONA ORDER BY ZONA ASC ";
        //echo "$sql";
        $r = $this->mysqli03->query($sql) or die($this->mysqli->error.__LINE__);
        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $row['ZONA']=utf8_encode($row['ZONA']);
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }

//-------------------------parametrizacion por zona ---------------------asignaciones------------

//------------------------parametrizacion por departamento siebel-------asignacion-------------


    private function getDepartamentosParametrizacionSiebel(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbConnect03();
        //echo "departamento es: $dep\n";
        $sql= " SELECT DISTINCT DEPARTAMENTO".
            " FROM alistamiento.parametrizacion_siebel ".
            " GROUP BY DEPARTAMENTO ORDER BY DEPARTAMENTO ASC ";
        //echo "$sql";
        $r = $this->mysqli03->query($sql) or die($this->mysqli->error.__LINE__);
        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $row['DEPARTAMENTO']=utf8_encode($row['DEPARTAMENTO']);
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }
//------------------------parametrizacion por departamento siebel-------asignacion-------------

//----------------------insertar datos parametrizacion ----------------------asignaciones------

    private function insertarDatoParametrizacion(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $departamento = $this->_request['depa'];
        $zona = $this->_request['zona'];
        $AM = $this->_request['AM'];
        $PM = $this->_request['PM'];
        $fechaformato = $this->_request['fechaformato'];

        $this->dbConnect03();

        $sql= " SELECT ID, FECHA, AM , PM ".
            "FROM alistamiento.parametrizacion_siebel ".
            "WHERE DEPARTAMENTO = '$departamento' ".
            "AND ZONA = '$zona' ".
            "AND FECHA = '$fechaformato'";
        $r = $this->mysqli03->query($sql) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $row= $r->fetch_assoc();
            $ID = $row['ID'];
            $sql= "update alistamiento.parametrizacion_siebel ".
                "set AM='$AM', PM='$PM' WHERE ID='$ID' ";
            $r = $this->mysqli03->query($sql) or die($this->mysqli->error.__LINE__);
        }
        else {
            $sql="INSERT INTO alistamiento.parametrizacion_siebel ".
                " (DEPARTAMENTO, ZONA, FECHA, AM, PM) ".
                " VALUES ('$departamento','$zona','$fechaformato','$AM','$PM')";
            $r = $this->mysqli03->query($sql) or die($this->mysqli->error.__LINE__);
        }
        // SQL Feed----------------------------------
        $sql_log=   "insert into portalbd.activity_feed ( ".
            " USER ".
            ", USER_NAME ".
            ", GRUPO ".
            ", STATUS ".
            ", PEDIDO_OFERTA ".
            ", ACCION ".
            ", CONCEPTO_ID ".
            ", IP_HOST ".
            ", CP_HOST ".
            ") values( ".
            " UPPER('$usuarioGalleta')".
            ", UPPER('$nombreGalleta')".
            ", UPPER('$grupoGalleta')".
            ",'OK' ".
            ",'SIN PEDIDO' ".
            ",'PARAMETRIZO REAGENDAMIENTO' ".
            ",'$zona' ".
            ",'$usuarioIp' ".
            ",'$usuarioPc')";

        $rlog = $this->mysqli->query($sql_log);
        // ---------------------------------- SQL Feed
        $this->response(json_encode("OK"), 200);
    }

//----------------------fin insertar datos parametrizacion ----------------------asignaciones------

//----------------------insertar datos parametrizacion 2 ----------------------asignaciones------

    private function insertarDatoParametrizacion2(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $departamento = $this->_request['depa'];
        $zona = $this->_request['zona'];
        $fechaini = $this->_request['fechaini'];
        $fechafin = $this->_request['fechafin'];
        $AM = $this->_request['AM'];
        $PM = $this->_request['PM'];
        //$fechaformato = $this->_request['fechaformato'];
        $this->dbConnect03();
        //echo "esto es lo que llego al API=  $departamento, $zona, $fechaini, $fechafin, $AM, $PM";
        while ($fechaini <= $fechafin) {

            $sql= " SELECT ID, FECHA, AM , PM ".
                "FROM alistamiento.parametrizacion_siebel ".
                "WHERE DEPARTAMENTO = '$departamento' ".
                "AND ZONA = '$zona' ".
                "AND FECHA = '$fechaini'";
            //echo "entro a select $fechaini";
            $r = $this->mysqli03->query($sql) or die($this->mysqli->error.__LINE__);

            if($r->num_rows > 0){
                $row= $r->fetch_assoc();
                $ID = $row['ID'];
                $sql= "update alistamiento.parametrizacion_siebel ".
                    "set AM='$AM', PM='$PM' WHERE ID='$ID' ";
                $r = $this->mysqli03->query($sql) or die($this->mysqli->error.__LINE__);
                //echo "entro a uptdate $fechaini";
            }
            else {
                $sql="INSERT INTO alistamiento.parametrizacion_siebel ".
                    " (DEPARTAMENTO, ZONA, FECHA, AM, PM) ".
                    " VALUES ('$departamento','$zona','$fechaini','$AM','$PM')";
                $r = $this->mysqli03->query($sql) or die($this->mysqli->error.__LINE__);
                //echo "entro a insert $fechaini";
            }

            $fechaini= date("Y/m/d", strtotime("$fechaini + 1 days"));
            //echo "sumo fecha $fechaini";
        }
        $this->response(json_encode("OK"), 200);

    }
//----------------------insertar datos parametrizacion 2 ----------------------asignaciones------



    //-------------------listado parametrizacion siebel ----------------------asignaciones----------

    private function listadoParametrizadosSiebel(){//parametrizados
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $departamento = $this->_request['depa'];
        $zona = $this->_request['zona'];

        //echo "esta es la zona: ".$zona;

        $this->dbConnect03();

        //$zona = $this->mysqli03->real_escape_string($zona);
        //$zona=	mysqli_real_escape_string($zona);
        $query= " SELECT FECHA, AM , PM ".
            "FROM alistamiento.parametrizacion_siebel ".
            "WHERE DEPARTAMENTO = '$departamento'".
            " AND ZONA = '$zona' ".
            //"AND FECHA >= CURDATE() ".
            "AND (AM <> '0' or PM <> '0')";
        //echo $query;

        /*$preparedQuery= " SELECT FECHA, AM , PM ".
                        "FROM alistamiento.parametrizacion_siebel ".
                        "WHERE DEPARTAMENTO =  ?  ".
                        " AND ZONA = ? ".
                        //"AND FECHA >= CURDATE() ".
                        "AND (AM <> '0' or PM <> '0')";

			$stmt= $this->mysqli03->prepare($preparedQuery);
			$stmt->bind_param("ss", $departamento, $zona);
			*/

        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('',204);         // If no records "No Content" status
    }

    //-------------------fin listado parametrizacion siebel ----------------------asignaciones----------

//----------------------------exportar parametrizacion siebel --------------------asignaciones-----

    private function csvParametrizacionSiebel(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $login = $this->_request['login'];
        $today = date("Y-m-d");
        $filename="Parametrizacion-Siebel-$login-$today.csv";
        $this->dbConnect03();
        $query=" SELECT ".
            " FECHA,DEPARTAMENTO,ZONA,AM,PM ".
            " from alistamiento.parametrizacion_siebel ".
            " WHERE (AM is not null or PM is not null) ".
            "AND (AM <> '0' OR PM <> '0') order by FECHA asc";
        //echo $query;
        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array( 'FECHA','DEPARTAMENTO','ZONA','AM','PM'));
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO PARAMETRIZACION' ".
                ",'ARCHIV EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }
        $this->response('',204);
    }


//---------------------------- fin exportar parametrizacion siebel --------------------asignaciones-----

//-----------------------exportar parametrizacion por microzonas---------------------asignaciones-----

    private function csvParametrizacionMicrozona(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $login = $this->_request['login'];
        $depa = $this->_request['depa'];
        $zona = $this->_request['zona'];
        $today = date("Y-m-d");
        $filename="Parametrizacion-Siebel-$login-$today-$depa-$zona.csv";
        $this->dbConnect03();
        $query=" SELECT ".
            " FECHA,DEPARTAMENTO,ZONA,AM,PM ".
            " from alistamiento.parametrizacion_siebel ".
            //" WHERE FECHA >= '$today' ".
            " where DEPARTAMENTO = '$depa' and ZONA = '$zona' ".
            " and(AM is not null or PM is not null) order by FECHA asc ";
        //echo $query;
        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array( 'FECHA','DEPARTAMENTO','ZONA','AM','PM'));
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO PARAMETRIZACION' ".
                ",'ARCHIV EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }else {
            $this->response($this->json(array("Null")), 200); // send user details

        }
        $this->response('',204);
    }


//-----------------------fin exportar parametrizacion por microzonas---------------------asignaciones-----

//-----------------------------por zonas adelantar agenda------------------------------agendamiento-------

    private function getZonasAdelantarAgenda(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $dep = $this->_request['departamento'];
        $today = date("Y-m-d");

        $sql="";

        if($dep=="VACIOS"){
            $sql= " SELECT DISTINCT SUBZONA_ID ".
                " FROM gestor_microzonas_agendamiento ".
                " WHERE DEPARTAMENTO ='' ".
                " GROUP BY SUBZONA_ID ORDER BY SUBZONA_ID ASC ";

        }else{

            $sql= " SELECT DISTINCT SUBZONA_ID ".
                " FROM gestor_microzonas_agendamiento ".
                " WHERE DEPARTAMENTO ='$dep' ".
                " GROUP BY SUBZONA_ID ORDER BY SUBZONA_ID ASC ";

        }
        $r = $this->mysqli->query($sql) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $row['SUBZONA_ID']=utf8_encode($row['SUBZONA_ID']);
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }

//-----------------------------por zonas adelantar agenda------------------------------agendamiento-------


//-----------------------------por microzona adelantar agenda------------------------------agendamiento-------

    private function getMicrozonasAdelantarAgenda(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $dep = $this->_request['departamento'];
        $zona = $this->_request['zona'];
        $today = date("Y-m-d");

        $sql="";

        if($dep=="VACIOS"){
            $sql=" SELECT MICROZONA ".
                " FROM gestor_microzonas_agendamiento ".
                " WHERE DEPARTAMENTO = '' ".
                " AND SUBZONA_ID='$zona' ".
                " ORDER BY MICROZONA ASC ";

        }else{
            $sql=" SELECT MICROZONA ".
                " FROM gestor_microzonas_agendamiento ".
                " WHERE DEPARTAMENTO = '$dep' ".
                " AND SUBZONA_ID='$zona' ".
                " ORDER BY MICROZONA ASC ";

        }

        $r = $this->mysqli->query($sql) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $row['MICROZONA']=utf8_encode($row['MICROZONA']);
                $result[] = $row;
            }

            $this->response($this->json($result), 200); // send user details
        }


        $this->response('',204);        // If no records "No Content" status

    }

//----------------------------- fin por microzona adelantar agenda------------------------------agendamiento-------

//------------------------pedidos que actualmente se encuentran agendados-----------------------agendamiento-------

    private function getPedidoActualmenteAgendado(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $dep = $this->_request['departamento'];
        $zona = $this->_request['zona'];
        $micro=$this->_request['microzona'];
        $fecha=$this->_request['fecha'];
        $asesor=$this->_request['asesor'];
        $pedido_actual = $this->_request['pedido_actual'];

        $conna=getConnAgendamiento();

        $this->dbFenixConnect();
        $connf=$this->connf;

        $sqlupdateCleanAsesor="";
        $sqlupdateCleanAsesor="update gestor_pendientes_reagendamiento ".
            " set ASESOR='' WHERE STATUS='ADEN_AGEN' AND ASESOR='$asesor' ";
        //echo "$sqlupdateCleanAsesor";
        //$this->response('',200);
        $xx = $this->mysqli->query($sqlupdateCleanAsesor);

        // CONSULTO LOS PEDIDOS QUE SE ENCUENTRAN AGENDADOS EN LA FECHA.
        $sql="";
        $sql=" select agm_pedido, agm_estadototal, agm_subestadototal, agm_fechacita, agm_microzona, agm_jornadacita ".
            " from agn_agendamientos ".
            " where agm_fechacita='$fecha' ";

        $r = $conna->query($sql) or die($this->conna->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){

                $pedido=$row['agm_pedido'];
                $fechacita=$row['agm_fechacita'];
                $jornadacita=$row['agm_jornadacita'];
                /*  $estadoTotal=$row['agm_estadototal'];
                                        $subEstadoTotal=$row['agm_subestadototal'];*/

                //echo "My pedido es: $pedido\n";
                $sqlfenix=    "  select * from (SELECT OT.PEDIDO_ID, OT.RUTA_TRABAJO_ID AS MICROZONA, OT.SUBZONA_ID, ".
                    "  PE.CLIENTE_ID, PE.CELULAR_AVISAR,PE.CORREO_UNE,PE.DIRECCION_ENVIO,PE.E_MAIL_AVISAR,PE.NOMBRE_USUARIO ".
                    "  ,TO_CHAR(PE.FECHA_INGRESO,'RRRR-MM-DD hh24:mi:ss') as FECHA_INGRESO ".

                    "  ,PE.TELEFONO_AVISAR ".
                    "   ,UPPER(FN_NOMBRE_MUNICIPIO(COALESCE(FN_MUNICIPIO_PEDIDO(OT.PEDIDO_ID,OT.SUBPEDIDO_ID, OT.SOLICITUD_ID), ".
                    "   FN_MUNICIPIO_PEDIDO(OT.PEDIDO_ID,1, OT.SOLICITUD_ID), ".
                    "   FN_MUNICIPIO_PEDIDO(OT.PEDIDO_ID,2, OT.SOLICITUD_ID), ".
                    "   FN_MUNICIPIO_PEDIDO(OT.PEDIDO_ID,3, OT.SOLICITUD_ID), ".
                    "   FN_MUNICIPIO_PEDIDO(OT.PEDIDO_ID,4, OT.SOLICITUD_ID) ))) AS MUNICIPIO ".
                    "   , COALESCE(UPPER(D.NOMBRE_DEPARTAMENTO),OT.AREA_OPERATIVA_ID)  AS DEPARTAMENTO ".
                    "   FROM FNX_ORDENES_TRABAJOS OT, FNX_PEDIDOS PE,  FNX_DEPARTAMENTOS D ".
                    "   WHERE OT.PEDIDO_ID='$pedido' ".
                    "   AND PE.PEDIDO_ID='$pedido' ".
                    "   AND OT.AREA_OPERATIVA_ID=D.DEPARTAMENTO_ID(+)) C1  ".
                    "   WHERE C1.MUNICIPIO is not null ".
                    "   and rownum =1 ";

                //echo $sqlfenix;
                $stid = oci_parse($connf, $sqlfenix);
                oci_execute($stid);

                while ($row2 = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS) ) {

                    $result2 = array();

                    $MICROZONA=$this->clean_chars($row2['MICROZONA']);
                    $SUBZONA_ID=$this->clean_chars($row2['SUBZONA_ID']);
                    $DEPARTAMENTO=$this->clean_chars($this->quitar_tildes(utf8_encode($row2['DEPARTAMENTO'])));

                    //ENCUENTRO EL PEDIDO CON EL DEPARTAMENTO ZONA SUBZONA Y MICROZONA ELEGIDA.
                    if($DEPARTAMENTO==$dep and $SUBZONA_ID==$zona and $MICROZONA==$micro ){
                        $mun= $this->clean_chars($this->quitar_tildes(utf8_encode($row2['MUNICIPIO'])));
                        $row2['MUNICIPIO'] = $mun;
                        $pedido=$row2['PEDIDO_ID'];
                        $fecha_agendamiento=$row['agm_fechacita'];
                        $today = date("Y-m-d h:i:s");
                        // echo "$pedido";
                        ///VERIFICO SI EL PEDIDO QUE ENCONTRE YA EXISTE EN LA TABLA DE PENDIENTES DE REAGENDAMIENTO
                        $EXISTE_EN_PENDIENTES=$this->existeEnPendientesAdelantar($pedido);
                        // echo "$EXISTE_EN_PENDIENTES";
                        if($EXISTE_EN_PENDIENTES=="YES"){
                            //echo "ingreso aca";
                            $sqlupdate="";
                            $sqlupdate="update gestor_pendientes_reagendamiento ".
                                " set STATUS='ADEN_AGEN', ASESOR='$asesor', FECHA_VISTO_ASESOR='$today', ".
                                " FECHA_ACTUALIZACION='$today' , FECHA_CITA_REAGENDA='$fecha_agendamiento' WHERE PEDIDO_ID='$pedido'  ".
                                " ORDER BY FECHA_ACTUALIZACION DESC LIMIT 1 " ;
                            //echo "$sqlupdate";
                            //$this->response('',200);
                            $xxx = $this->mysqli->query($sqlupdate);

                        }else{
                            $sqlfenix2=" SELECT OT.PEDIDO_ID ".
                                "   ,regexp_replace(LISTAGG(OT.CONCEPTO_ID,',') WITHIN GROUP (ORDER BY NULL),'([^,]+)(,\\1)+', '\\1' ) as  CONCEPTOS  ".
                                "  ,regexp_replace(LISTAGG(OT.ACTIVIDAD_ID,',') WITHIN GROUP (ORDER BY NULL),'([^,]+)(,\\1)+', '\\1' ) as ACTIVIDADES  ".
                                "  ,TO_CHAR(max(OT.FECHA_ENTREGA), 'RRRR-MM-DD') AS FECHA_CITA_FENIX  ".
                                "  , COALESCE(MAX(FN_VALOR_CARACTERISTICA_SOL (OT.PEDIDO_ID, OT.SUBPEDIDO_ID, OT.SOLICITUD_ID,'234')),MAX(FN_VALOR_CARACT_SUBPEDIDO(OT.PEDIDO_ID, OT.SUBPEDIDO_ID, '234')),'NO') AS MIGRACION  ".
                                "  , MAX(FNX_PEDIDOS.RADICADO_TEMPORAL) AS RADICADO  ".
                                "  , LISTAGG(OT.OBSERVACION,',') WITHIN GROUP (ORDER BY NULL) AS OBSERVACION_FENIX ".
                                "  , TO_CHAR(MIN(OT.FECHA_ESTADO),'RRRR-MM-DD hh24:mi:ss') AS FECHA_ESTADO  ".
                                "  , (select a.CONCEPTO_ID from fnx_solicitudes a where a.pedido_id=OT.PEDIDO_ID AND (a.CONCEPTO_ID ='FACTU' OR a.ESTADO_ID='FACTU') AND ROWNUM=1) AS ESTADO_FACTU  ".
                                "  FROM FNX_ORDENES_TRABAJOS OT, FNX_PEDIDOS  ".
                                "         WHERE OT.PEDIDO_ID='$pedido'  ".
                                "        AND OT.ETAPA_ID ='INSTA'  ".
                                "         AND OT.MARCA_ID='HG'  ".
                                "        AND OT.PEDIDO_ID=FNX_PEDIDOS.PEDIDO_ID  ".
                                "  GROUP BY OT.PEDIDO_ID ";

                            //echo "$sqlfenix2";
                            //$this->response('',200);
                            $stid = oci_parse($connf, $sqlfenix2);
                            oci_execute($stid);

                            while ($row3 = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
                                $result3 = array();

                                $row3['OBSERVACION_FENIX']=$this->clean_chars($row3['OBSERVACION_FENIX']);
                                $row3['OBSERVACION_FENIX']=str_replace(array("'"), "\"", $row3['OBSERVACION_FENIX']);

                                $PEDIDO_ID= $this->clean_chars($row3['PEDIDO_ID']);
                                $CONCEPTOS= $this->clean_chars($row3['CONCEPTOS']);
                                $ACTIVIDADES= $this->clean_chars($row3['ACTIVIDADES']);
                                $FECHA_CITA_FENIX = $this->clean_chars($row3['FECHA_CITA_FENIX']);
                                $MIGRACION= $this->clean_chars($row3['MIGRACION']);
                                $MICROZONA = $this->clean_chars($row2['MICROZONA']);
                                $SUBZONA_ID =$this->clean_chars($row2['SUBZONA_ID']);
                                $CLIENTE_ID =$this->clean_chars($row2['CLIENTE_ID']);
                                $CELULAR_AVISAR =$this->clean_chars($row2['CELULAR_AVISAR']);
                                $CORREO_UNE = $this->clean_chars($row2['CORREO_UNE']);
                                $DIRECCION_ENVIO =$this->clean_chars($row2['DIRECCION_ENVIO']);
                                $E_MAIL_AVISAR =$this->clean_chars($row2['E_MAIL_AVISAR']);
                                $NOMBRE_USUARIO= $this->clean_chars($row2['NOMBRE_USUARIO'] );
                                $FECHA_INGRESO= $this->clean_chars($row2['FECHA_INGRESO']);
                                $TELEFONO_AVISAR = $this->clean_chars($row2['TELEFONO_AVISAR']);
                                $RADICADO = $this->clean_chars($row3['RADICADO']);
                                $MUNICIPIO =$this->clean_chars($row2['MUNICIPIO']);
                                $DEPARTAMENTO =$this->clean_chars($row2['DEPARTAMENTO']);
                                $OBSERVACION_FENIX = $this->clean_chars($row3['OBSERVACION_FENIX']);
                                $FECHA_ESTADO= $this->clean_chars($row3['FECHA_ESTADO']);
                                $ESTADO_FACTU =$this->clean_chars($row3['ESTADO_FACTU']);

                                $sqlinsert="";
                                $sqlinsert="insert into gestor_pendientes_reagendamiento ".
                                    " (PEDIDO_ID,CONCEPTOS,ACTIVIDADES,FECHA_CITA_FENIX, MIGRACION,MICROZONA,SUBZONA_ID ".
                                    " ,CLIENTE_ID,CELULAR_AVISAR,CORREO_UNE,DIRECCION_ENVIO,E_MAIL_AVISAR,NOMBRE_USUARIO ".
                                    " ,FECHA_INGRESO,TELEFONO_AVISAR,RADICADO,MUNICIPIO,DEPARTAMENTO,OBSERVACION_FENIX ,FECHA_ESTADO,ESTADO_FACTU, STATUS ".
                                    " , ASESOR, FECHA_VISTO_ASESOR, FUENTE, FECHA_CITA_REAGENDA ) values ".
                                    "  ('$PEDIDO_ID','$CONCEPTOS','$ACTIVIDADES', '$FECHA_CITA_FENIX', '$MIGRACION', '$MICROZONA', '$SUBZONA_ID', '$CLIENTE_ID', '$CELULAR_AVISAR','$CORREO_UNE' ".
                                    "  , '$DIRECCION_ENVIO', '$E_MAIL_AVISAR', '$NOMBRE_USUARIO','$FECHA_INGRESO', '$TELEFONO_AVISAR', '$RADICADO', '$MUNICIPIO', '$DEPARTAMENTO', '$OBSERVACION_FENIX' ".
                                    "  , '$FECHA_ESTADO', '$ESTADO_FACTU','ADEN_AGEN' , '$asesor', '$today', 'ADELANTAR_AGENDA','$fecha_agendamiento' ) ";

                                //echo "$sqlinsert";
                                // $this->response('',200);
                                if(!$x = $this->mysqli->query($sqlinsert)){
                                    echo "ERROR EN SQL: $sqlinsert\n";
                                    //continue;
                                    //die('There was an error running the query [' . $connm->error. ' --'.$subinsert.'** ]');
                                }

                            } // termina while de pedido que va ser insertado.

                        }// termina el else donde se inserta pedido pendiente.

                        //$pedidoEncontrado= demePedidoAdelantarAgenda($pedido,$asesor,$pedido_actual);

                        $sql="";
                        $sql= " SELECT b.ID as PARENT_ID,b.PEDIDO_ID,b.FECHA_CITA_FENIX,b.FECHA_CITA_REAGENDA,b.CLIENTE_ID,b.CELULAR_AVISAR,b.CORREO_UNE, ' $fechacita' as FECHA_CITA, '$jornadacita' as JORNADA_CITA ".
                            " ,b.DIRECCION_ENVIO,b.E_MAIL_AVISAR,b.NOMBRE_USUARIO,b.FECHA_INGRESO,b.TELEFONO_AVISAR,b.CONCEPTOS ".
                            " ,b.ACTIVIDADES,cast(TIMESTAMPDIFF(HOUR,(b.FECHA_INGRESO),CURRENT_TIMESTAMP())/24 AS decimal(5,2)) as TIEMPO_TOTAL ".
                            " ,b.MICROZONA,b.OBSERVACION_FENIX,b.FUENTE,b.TODAY_TRIES,b.PROGRAMACION ,b.FECHA_ESTADO,b.DEPARTAMENTO ".
                            "  from gestor_pendientes_reagendamiento b where b.PEDIDO_ID = '$pedido' and b.ASESOR='$asesor' ".
                            "  and b.STATUS='ADEN_AGEN' and b.MIGRACION='NO' ";

                        //echo "$sql";
                        //$this->response('',200);
                        $rr = $this->mysqli->query($sql) or die($this->mysqli->error.__LINE__);
                        if($rr->num_rows > 0){
                            $result4 = array();

                            while($row4 = $rr->fetch_assoc()){
                                $result4[] = $row4;
                                $pedido= $row4['PEDIDO_ID'];

                                $this->response($this->json(array($result4,$pedido)), 200);
                                //break;
                            }
                        }

                        // $result[]=$row;
                        // $result2[] = $row2;
                        // $result3[]=$row3;

                        //echo "encontro pedido ";

                        //break;


                    }//cierro if que comprueba si es la misma microzona.

                } //cierra while de consulta de microzona.

            } //cierra while de pedidos encontrados en el modulo agendamiento.
            //echo var_dump($row2);
            //$this->response($this->json($result), 200); // send user details

        } //Cierra if si hay pedidos consultados en el modulo agendamiento.
        $this->response('',204);
    } // cierra Metodo-


    function quitar_tildes($cadena) {
        //echo 'recibi cadena'.$cadena;
        $no_permitidas= array ("á","é","í","ó","ú","Á","É","Í","Ó","Ú","ñ","À","Ã","Ì","Ò","Ù","Ã™","Ã ","Ã¨","Ã¬","Ã²","Ã¹","ç","Ç","Ã¢","ê","Ã®","Ã´","Ã»","Ã‚","ÃŠ","ÃŽ","Ã”","Ã›","ü","Ã¶","Ã–","Ã¯","Ã¤","«","Ò","Ã","Ã„","Ã‹");
        $permitidas= array ("a","e","i","o","u","A","E","I","O","U","n","N","A","E","I","O","U","a","e","i","o","u","c","C","a","e","i","o","u","A","E","I","O","U","u","o","O","i","a","e","U","I","A","E");
        $texto = str_replace($no_permitidas, $permitidas ,$cadena);
        //echo "\nsalida".$texto;
        return $texto;
    }



    function clean_chars($string){
        return str_replace('\'','',$string);
    }

    function existeEnPendientesAdelantar($pedido){

        $sqlVerifica="";
        $sqlVerifica=" SELECT * FROM gestor_pendientes_reagendamiento ".
            " WHERE PEDIDO_ID='$pedido' ".
            " ORDER BY FECHA_ACTUALIZACION DESC ".
            " LIMIT 1 ";

        // echo "$sqlVerifica";
        if ($result2 = $this->mysqli->query($sqlVerifica)) {
            if($obj = $result2->fetch_object()){
                //ESTE PEDIDO EXISTE EN LA TABLA DE PENDIENTES.
                return "YES";
            }else{

                return "NO";
            }
        }
    }

//------------------------pedidos que actualmente se encuentran agendados-----------------------agendamiento-------

//---------------------------listado pedidos reconfiguracion----------------------------------asignacion---------

    //funcionReconfiguracion__Julian
    private function listadoPedidosReconfiguracion(){//historico por 1 pedido
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $fechaini = $this->_request['fecha_inicio'];
        $fechafin = $this->_request['fecha_fin'];
        $page = $this->_request['page'];
        $campo = $this->_request['campo'];
        $valorCampo = $this->_request['valorCampo'];
        $userID = $this->_request['userID'];
        $today = date("Y-m-d");

        if($page=="undefined"){
            $page="0";
        }else{
            $page=$page-1;
        }
        if ($campo=="TODO" || $campo=="" || $campo=="undefined"){
            $filtro="";
        }
        else {
            $filtro= " and $campo = '$valorCampo' and user = '$userID'";
            //$filtro= " and $campo = '$valorCampo'";
        }
        $page=$page*100;

        //echo $filtro;
        //counter
        $query="SELECT count(*) as counter from pedidos where fecha_fin between '$fechaini 00:00:00' and '$fechafin 23:59:59' $filtro";
        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter = $row['counter'];
            }
        }

        $query="SELECT id, pedido, fuente, actividad, fecha_fin, estado,duracion,INCIDENTE,concepto_final,user from pedidos where fecha_fin between '$fechaini 00:00:00' and '$fechafin 23:59:59' $filtro order by fecha_fin desc limit 100 offset $page";

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
//---------------------------listado pedidos reconfiguracion----------------------------------asignacion---------

//- -------------------Funcion para listar los registros historicos---------------------

    private function listadoPedidos(){//historico por 1 pedido
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }


        $fechaini = $this->_request['fecha_inicio'];
        $fechafin = $this->_request['fecha_fin'];
        $page = $this->_request['page'];
        $campo = $this->_request['campo'];
        $valorCampo = $this->_request['valorCampo'];
        $today = date("Y-m-d");

        if($page=="undefined"){
            $page="0";
        }else{
            $page=$page-1;
        }
        if ($campo=="TODO" || $campo=="" || $campo=="undefined"){
            $filtro="";
        }
        else {
            $in_stmt = "'".str_replace(",", "','", $valorCampo)."'";
            //$paramlst = " and PEDIDO_ID in (".$in_stmt.") ";
            $filtro= " and $campo in (".$in_stmt.")";
        }
        $page=$page*100;
        //counter
        $query="SELECT count(*) as counter from pedidos where fecha_fin between '$fechaini 00:00:00' and '$fechafin 23:59:59' $filtro";
        $rr = $this->mysqli->query($query);
        $counter=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter = $row['counter'];
            }
        }

        $query="SELECT id, pedido_id, fuente, actividad ".
            ", fecha_fin, estado ".
            ", my_sec_to_time(timestampdiff(second, fecha_inicio, fecha_fin)) as duracion ".
            ", INCIDENTE ".
            ", SUBSTRING_INDEX(SUBSTRING_INDEX(concepto_final, ',', 3), ' ', -1) as concepto_final ".
            ",user,motivo_malo ".
            " from pedidos ".
            " where fecha_fin between '$fechaini 00:00:00' ".
            " and '$fechafin 23:59:59' $filtro order by fecha_fin desc limit 100 offset $page";

        $r = $this->mysqli->query($query);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            $this->response($this->json(array($result,$counter)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }

// -----------------------------fin  Funcion para listar los registros historicos---------------------

//----------------------KPIS maestro---------------------------------------------asignaciones------

    private function lightKPISMaestro(){//listado light de kpis
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbConnect03();

        $query=" select NEGOCIO, CANTIDAD FROM kpi_pendientes_alistamiento ".
            " order by ID DESC limit 4";

        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        $asigna='0';
        $recon='0';
        $agenda='0';
        $activa='0';

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                if($row['NEGOCIO']=='ASIGNACIONES'){
                    $asigna=$row['CANTIDAD'];
                    continue;
                }
                if($row['NEGOCIO']=='AGENDAMIENTO'){
                    $agenda=$row['CANTIDAD'];
                    continue;
                }
                if($row['NEGOCIO']=='ACTIVACION'){
                    $activa=$row['CANTIDAD'];
                    continue;
                }
                if($row['NEGOCIO']=='RECONFIGURACION'){
                    $recon=$row['CANTIDAD'];
                    continue;
                }

                $result[] = $row;
            }
            $this->response($this->json(array($asigna,$recon,$agenda,$activa)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }
//----------------------fin KPIS maestro---------------------------------------------asignaciones------

//----------------------KPIS maestro tabla informe_petec_pendientesm ------------------------asignaciones----

    private function lightKPIS(){//listado light de kpis
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $query=" select ".
            "     count(*) AS COUNTER, c1.CONCEPTO_ID ".
            "     from( ".
            "     select distinct pedido_id ".
            "     , case  ".
            "          when FUENTE='FENIX_BOG' and CONCEPTO_ID='PETEC' then 'PETEC-BOG'  ".
            "          when CONCEPTO_ID='14' AND STATUS='PENDI_RENUMS' then '14-RENUMS'  ".
            "          when CONCEPTO_ID='PETEC' and RADICADO_TEMPORAL='EQURED' then 'EQURED' ".
            "          when STATUS='MALO' then 'MALO' ".
            "         else CONCEPTO_ID  ".
            "     end as CONCEPTO_ID  ".
            "     from informe_petec_pendientesm ".
            "     where status in ('PENDI_PETEC','MALO','PENDI_RENUMS') ) c1 ".
            "     group by c1.CONCEPTO_ID ";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            $this->response($this->json(array($result,'')), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }
//----------------------KPIS maestro tabla informe_petec_pendientesm ------------------------asignaciones----

//----------------------KPIS maestro tabla pendientes_reagendamiento ------------------------agendamiento----


    private function lightKPISAgendamiento(){//listado light de kpis
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $query=    " SELECT COUNT(SUBZONA_ID) AS COUNTER, SUBZONA_ID, DEPARTAMENTO ".
            " FROM gestor_pendientes_reagendamiento ".
            "  WHERE STATUS ='PENDI_AGEN' ".
            "  AND (MIGRACION is null or MIGRACION='' OR MIGRACION!='SI') ".
            "  GROUP BY DEPARTAMENTO, SUBZONA_ID ";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                //$dep=$row['DEPARTAMENTO'];
                $row['DEPARTAMENTO']=utf8_encode($row['DEPARTAMENTO']);
                $result[] = $row;
            }
            $this->response($this->json(array($result,'')), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }
//---------------------- fin KPIS maestro tabla pendientes_reagendamiento ------------------------agendamiento----

//------------------pendientes por colas activacion----------------------------activacion-----------------

    private function pendientesPorColaConceptoActivacion(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $queryConcepto="  select  ".
            "  C1.CONCEPTO_ID  ".
            "  , count(*) as CANTIDAD  ".
            " , sum(if(C1.RANGO_PENDIENTE='Entre 0-2', 1,0)) as 'Entre02',".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 3-4', 1,0)) as 'Entre34', ".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 5-6', 1,0)) as 'Entre56', ".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 7-12', 1,0)) as 'Entre712', ".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 13-24', 1,0)) as 'Entre1324', ".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 25-48', 1,0)) as 'Entre2548', ".
            "   sum(if(C1.RANGO_PENDIENTE='Mas de 48', 1,0)) as 'Masde48' ".
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
            //"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 0 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 2 THEN 'Entre 0-2'   ".
            //"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 3 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 4 THEN 'Entre 3-4'   ".
            //"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 5 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 6 THEN 'Entre 5-6'   ".
            //"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) > 6 THEN 'Mas de 6'   ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 0 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 2 THEN 'Entre 0-2'  ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 3 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 4 THEN 'Entre 3-4'   ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 5 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 6 THEN 'Entre 5-6'   ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 7 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 12 THEN 'Entre 7-12'   ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 13 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 24 THEN 'Entre 13-24'  ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 25 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 48 THEN 'Entre 25-48' ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) > 48 THEN 'Mas de 48'  ".
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
            " , sum(if(C1.RANGO_PENDIENTE='Entre 0-2', 1,0)) as 'Entre02',".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 3-4', 1,0)) as 'Entre34', ".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 5-6', 1,0)) as 'Entre56', ".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 7-12', 1,0)) as 'Entre712', ".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 13-24', 1,0)) as 'Entre1324', ".
            "   sum(if(C1.RANGO_PENDIENTE='Entre 25-48', 1,0)) as 'Entre2548', ".
            "   sum(if(C1.RANGO_PENDIENTE='Mas de 48', 1,0)) as 'Masde48' ".
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
            //"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 0 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 2 THEN 'Entre 0-2'   ".
            //"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 3 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 4 THEN 'Entre 3-4'   ".
            //"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 5 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 6 THEN 'Entre 5-6'   ".
            //"          WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) > 6 THEN 'Mas de 6'   ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 0 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 2 THEN 'Entre 0-2'  ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 3 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 4 THEN 'Entre 3-4'   ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 5 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 6 THEN 'Entre 5-6'   ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 7 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 12 THEN 'Entre 7-12'   ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 13 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 24 THEN 'Entre 13-24'  ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 25 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 48 THEN 'Entre 25-48' ".
            "        WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) > 48 THEN 'Mas de 48'  ".
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
//------------------pendientes por colas activacion----------------------------activacion----------------

//-------------------pendientes por plaza  informe_petec_pendientesm-------------asignaciones------------


    private function pendientesPorPlaza(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        /*
         * Query viejo, cuenta por servicios.*

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
            "     PP.`FECHA_INGRESO`, ".
            "     PP.`FECHA_ESTADO`, ".
            "     case  ".
            "       when PP.FUENTE='FENIX_NAL' and PP.CONCEPTO_ID='PETEC' then 'PETEC-NAL' ".
            "       when PP.FUENTE='FENIX_BOG' and PP.CONCEPTO_ID='PETEC' then 'PETEC-BOG' ".
            "       else PP.CONCEPTO_ID ".
            "     end as CONCEPTO_ID,  ".
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
            " FROM `portalbd`.`informe_petec_pendientesm` PP ".
            " where (PP.STATUS= 'PENDI_PETEC' or PP.STATUS= 'MALO' ) and PP.FUENTE in ('FENIX_NAL','FENIX_BOG','EDATEL','SIEBEL')) C1".
            " group by C1.CONCEPTO_ID order by count(*) DESC";
        */

        $queryConceptos="SELECT ".
            " C2.CONCEPTO_ID ".
            " , COUNT(*) AS CANTIDAD ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) >= 0 AND (C2.RANGO_PENDIENTE) <= 2 THEN 1 ELSE 0 END) as 'Entre02' ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) >= 3 AND (C2.RANGO_PENDIENTE) <= 4 THEN 1 ELSE 0 END) as 'Entre34' ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) >= 5 AND (C2.RANGO_PENDIENTE) <= 6 THEN 1 ELSE 0 END) as 'Entre56' ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) >= 7 AND (C2.RANGO_PENDIENTE) <= 12 THEN 1 ELSE 0 END) as 'Entre712' ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) >= 13 AND (C2.RANGO_PENDIENTE) <= 24 THEN 1 ELSE 0 END) as 'Entre1324' ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) >= 25 AND (C2.RANGO_PENDIENTE) <= 48 THEN 1 ELSE 0 END) as 'Entre2548' ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) > 48 THEN 1 ELSE 0 END) as 'Masde48' ".
            " FROM(SELECT ".
            " C1.PEDIDO_ID ".
            " , MAX(C1.CONCEPTO_ID) AS CONCEPTO_ID ".
            " , MAX(C1.RANGO_PENDIENTE) AS RANGO_PENDIENTE ".
            " FROM(select ".
            " PP.PEDIDO_ID ".
            " , case   ".
            "        when PP.FUENTE='FENIX_NAL' and PP.CONCEPTO_ID='PETEC' AND PP.STATUS!='MALO' then 'PETEC-NAL'  ".
            "        when PP.FUENTE='FENIX_BOG' and PP.CONCEPTO_ID='PETEC' AND PP.STATUS!='MALO' then 'PETEC-BOG'  ".
            "        WHEN PP.STATUS='MALO' THEN 'MALO' ".
            "        else PP.CONCEPTO_ID  ".
            "      end as CONCEPTO_ID ".
            " , HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) AS RANGO_PENDIENTE ".
            " FROM portalbd.informe_petec_pendientesm PP ".
            " WHERE PP.STATUS IN ('PENDI_PETEC','MALO') ) C1  ".
            " GROUP BY C1.PEDIDO_ID ) C2 ".
            " GROUP BY C2.CONCEPTO_ID ".
            " order by count(*) DESC ";
        $rr = $this->mysqli->query($queryConceptos) or die($this->mysqli->error.__LINE__);

        $queryConceptos = array();
        if($rr->num_rows > 0){

            while($row = $rr->fetch_assoc()){
                //$row['label']="Concepto ".$row['label'];
                $queryConceptos[] = $row;
            }
        }

        $queryConceptosNUEVO="     SELECT  ".
            "    C2.CONCEPTO_ID  ".
            "    , COUNT(*) AS CANTIDAD  ".
            "    , sum( CASE WHEN (C2.RANGO_PENDIENTE) >= 0 AND (C2.RANGO_PENDIENTE) <= 2 THEN 1 ELSE 0 END) as 'Entre02'  ".
            "    , sum( CASE WHEN (C2.RANGO_PENDIENTE) >= 3 AND (C2.RANGO_PENDIENTE) <= 4 THEN 1 ELSE 0 END) as 'Entre34'  ".
            "    , sum( CASE WHEN (C2.RANGO_PENDIENTE) >= 5 AND (C2.RANGO_PENDIENTE) <= 6 THEN 1 ELSE 0 END) as 'Entre56'  ".
            "    , sum( CASE WHEN (C2.RANGO_PENDIENTE) >= 7 AND (C2.RANGO_PENDIENTE) <= 12 THEN 1 ELSE 0 END) as 'Entre712'  ".
            "    , sum( CASE WHEN (C2.RANGO_PENDIENTE) >= 13 AND (C2.RANGO_PENDIENTE) <= 24 THEN 1 ELSE 0 END) as 'Entre1324'  ".
            "    , sum( CASE WHEN (C2.RANGO_PENDIENTE) >= 25 AND (C2.RANGO_PENDIENTE) <= 48 THEN 1 ELSE 0 END) as 'Entre2548'  ".
            "    , sum( CASE WHEN (C2.RANGO_PENDIENTE) > 48 THEN 1 ELSE 0 END) as 'Masde48'  ".
            "    FROM( ".
            "    SELECT ".
            "    C1.PEDIDO_ID ".
            "    , C1.CONCEPTO_ID ".
            "    , group_concat(DISTINCT C1.TIPO_TRABAJO order by 1 asc) AS TIPO_TRABAJO ".
            "    , group_concat(DISTINCT C1.FUENTE) AS FUENTE ".
            "    , group_concat(DISTINCT C1.RADICADO_TEMPORAL) AS RADICADO_TEMPORAL ".
            "    , MAX(C1.RANGO_PENDIENTE) as RANGO_PENDIENTE ".
            "    FROM ( ".
            "    SELECT  ".
            "    PEDIDO_ID ".
            "    , SUBPEDIDO_ID ".
            "    , SOLICITUD_ID ".
            "    , CASE ".
            "    	when FUENTE='FENIX_BOG' and CONCEPTO_ID='PETEC' and STATUS!='MALO' then 'PETEC-BOG'  ".
            "        when FUENTE='FENIX_NAL' and CONCEPTO_ID='PETEC' and STATUS!='MALO' then 'PETEC-NAL'  ".
            "        when STATUS='MALO' then 'MALO' ".
            "        ELSE CONCEPTO_ID ".
            "     END AS CONCEPTO_ID ".
            "    , CASE ".
            "    	when DESC_TIPO_TRABAJO='NA NUEVO' then 'NUEVO' ".
            "        when DESC_TIPO_TRABAJO='MODIFICACION,NA NUEVO' then 'CAMBI,NUEVO' ".
            "        when TIPO_TRABAJO='CAMBIO' then 'CAMBI' ".
            "        when TIPO_TRABAJO='NUEVO,RETIR' then 'NUEVO' ".
            "        when TIPO_TRABAJO='8' then 'NUEVO' ".
            "        when TIPO_TRABAJO='CAMBI,NUEVO,RETIR' then 'CAMBI,NUEVO' ".
            "        when TIPO_TRABAJO='CAMBIO,VENTA' then 'CAMBI,NUEVO' ".
            "        else TIPO_TRABAJO ".
            "        end as TIPO_TRABAJO ".
            "    , FUENTE ".
            "    , RADICADO_TEMPORAL ".
            "    , HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO))) AS RANGO_PENDIENTE  ".
            "    FROM portalbd.informe_petec_pendientesm ".
            "    where 1=1 ".
            "    and STATUS in ('PENDI_PETEC','MALO') ".
            "    and fuente in ('FENIX_NAL','FENIX_BOG','SIEBEL','EDATEL') ".
            "    and CONCEPTO_ID NOT IN ('OT-C11','OT-C08','OT-T01','OT-T04','OT-T05') )C1 ".
            "    GROUP BY C1.PEDIDO_ID, C1.CONCEPTO_ID ) C2 ".
            "    WHERE C2.TIPO_TRABAJO='NUEVO' ".
            "    GROUP BY C2.CONCEPTO_ID  ".
            "    order by count(*) DESC";
        $rr = $this->mysqli->query($queryConceptosNUEVO) or die($this->mysqli->error.__LINE__);

        $queryConceptosNUEVO = array();
        if($rr->num_rows > 0){

            while($row = $rr->fetch_assoc()){
                //$row['label']="Concepto ".$row['label'];
                $queryConceptosNUEVO[] = $row;
            }
        }

        /*
         * *Query viejo para sacar los pendientes segun su fecha cita por servicios
        $query=    " select  ".
            "       C1.CONCEPTO_ID  ".
            "     , count(*) as CANTIDAD  ".
            "     , sum(if(C1.RANGO_PENDIENTE='Ayer', 1,0)) as 'Ayer',  ".
            "       sum(if(C1.RANGO_PENDIENTE='Hoy', 1,0)) as 'Hoy',   ".
            "       sum(if(C1.RANGO_PENDIENTE='Manana', 1,0)) as 'Manana',  ".
            "       sum(if(C1.RANGO_PENDIENTE='Pasado Manana', 1,0)) as 'Pasado_Manana',  ".
            "       sum(if(C1.RANGO_PENDIENTE='Mas de 3 dias', 1,0)) as 'Mas_de_3_dias',   ".
            "       sum(if(C1.RANGO_PENDIENTE='Sin Fecha Cita', 1,0)) as 'Sin_Fecha_Cita',  ".
            "      sum(if(C1.RANGO_PENDIENTE='Viejos', 1,0)) as 'Viejos'  ".
            "       from (SELECT  ".
            "        PP.PEDIDO,   ".
            "        PP.PEDIDO_ID,  ".
            "        PP.FECHA_INGRESO,  ".
            "        PP.FECHA_ESTADO,  ".
            "        PP.FECHA_CITA,  ".
            "        case   ".
            "          when PP.FUENTE='FENIX_NAL' and PP.CONCEPTO_ID='PETEC' then 'PETEC-NAL'  ".
            "          when PP.FUENTE='FENIX_BOG' and PP.CONCEPTO_ID='PETEC' then 'PETEC-BOG'  ".
            "          else PP.CONCEPTO_ID end as CONCEPTO_ID,  ".
            "        PP.MUNICIPIO_ID,  ".
            "        DATE((PP.FECHAESTADO_SOLA)) as FECHAESTADO_SOLA,  ".
            "        DATE_FORMAT((PP.FECHA_CARGA),'%H') AS HORA_CARGA,  ".
            "        PP.FUENTE,  ".
            "        PP.STATUS ".
            "        , cast((CASE  ".
            "           WHEN  PP.FECHA_CITA= DATE_SUB(CURDATE() , INTERVAL 1 DAY) THEN 'Ayer'  ".
            "           WHEN  PP.FECHA_CITA=current_date() THEN 'Hoy'   ".
            "           WHEN  PP.FECHA_CITA=DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'Manana'    ".
            "           WHEN  PP.FECHA_CITA=DATE_ADD(CURDATE(), INTERVAL 2 DAY) THEN 'Pasado Manana'    ".
            "           WHEN  PP.FECHA_CITA='9999-00-00' OR PP.FECHA_CITA='0000-00-00' THEN 'Sin Fecha Cita'  ".
            "           WHEN  PP.FECHA_CITA>=DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'Mas de 3 dias'  ".
            "           WHEN  PP.FECHA_CITA<= DATE_SUB(CURDATE() , INTERVAL 1 DAY) THEN 'Viejos'  ".
            "           else PP.FECHA_CITA  ".
            "        END ) as char )AS RANGO_PENDIENTE    ".
            "            FROM portalbd.informe_petec_pendientesm PP   ".
            "    where (PP.STATUS= 'PENDI_PETEC' or PP.STATUS= 'MALO' ) and PP.FUENTE in ('FENIX_NAL','FENIX_BOG','EDATEL','SIEBEL')) C1  ".
            "    group by C1.CONCEPTO_ID order by count(*) DESC ";
        */
        //echo $query;
        $query="SELECT ".
            " C2.CONCEPTO_ID ".
            " , COUNT(*) AS CANTIDAD ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) ='Ayer' THEN 1 ELSE 0 END) as 'Ayer' ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) ='Hoy' THEN 1 ELSE 0 END) as 'Hoy' ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) ='Manana' THEN 1 ELSE 0 END) as 'Manana' ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) ='Pasado_Manana' THEN 1 ELSE 0 END) as 'Pasado_Manana' ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) ='Mas_3dias' THEN 1 ELSE 0 END) as 'Mas_de_3_dias' ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) ='Sin_Agenda' THEN 1 ELSE 0 END) as 'Sin_Fecha_Cita' ".
            " , sum( CASE WHEN (C2.RANGO_PENDIENTE) ='Viejos' THEN 1 ELSE 0 END) as 'Viejos' ".
            " FROM(SELECT ".
            " C1.PEDIDO_ID ".
            " , MAX(C1.CONCEPTO_ID) AS CONCEPTO_ID ".
            " , MAX(C1.RANGO_PENDIENTE) AS RANGO_PENDIENTE ".
            " FROM(select ".
            " PP.PEDIDO_ID ".
            " , case    ".
            "     when PP.FUENTE='FENIX_NAL' and PP.CONCEPTO_ID='PETEC' AND PP.STATUS!='MALO' then 'PETEC-NAL' ".
            "     when PP.FUENTE='FENIX_BOG' and PP.CONCEPTO_ID='PETEC' AND PP.STATUS!='MALO' then 'PETEC-BOG'   ".
            "     WHEN PP.STATUS='MALO' THEN 'MALO' ".
            "  else PP.CONCEPTO_ID  end as CONCEPTO_ID ".
            " , cast((CASE ".
            "        WHEN  PP.FECHA_CITA='9999-00-00' OR PP.FECHA_CITA='0000-00-00' THEN 'Sin_Agenda'   ".
            "        WHEN  PP.FECHA_CITA= DATE_SUB(CURDATE() , INTERVAL 1 DAY) THEN 'Ayer'   ".
            "        WHEN  PP.FECHA_CITA=current_date() THEN 'Hoy'    ".
            "        WHEN  PP.FECHA_CITA=DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'Manana'     ".
            "        WHEN  PP.FECHA_CITA=DATE_ADD(CURDATE(), INTERVAL 2 DAY) THEN 'Pasado_Manana'    ".
            "        WHEN  PP.FECHA_CITA>=DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'Mas_3dias'   ".
            "        WHEN  PP.FECHA_CITA<= DATE_SUB(CURDATE() , INTERVAL 1 DAY) THEN 'Viejos'   ".
            "        else PP.FECHA_CITA  ".
            "     END ) as char )AS RANGO_PENDIENTE ".
            " FROM portalbd.informe_petec_pendientesm PP ".
            " WHERE PP.STATUS IN ('PENDI_PETEC','MALO') ) C1  ".
            " GROUP BY C1.PEDIDO_ID ) C2 ".
            " GROUP BY C2.CONCEPTO_ID ".
            " order by count(*) DESC ";
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $queryConceptosFcita = array();
            while($row = $r->fetch_assoc()){
                //$row['label']="Concepto ".$row['label'];
                $queryConceptosFcita[] = $row;
            }
            $this->response($this->json(array('','',$queryConceptos,'',$queryConceptosFcita,$queryConceptosNUEVO)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }
//-------------------fin pendientes por plaza  informe_petec_pendientesm-------------asignaciones------------

//----------------Dashboard asignaciones gestor_informes.kpi_seguimiento_automatico-----asignaciones-----
    private function getDashboardAsignaciones(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $today = date("Y-m-d");

        $this->dbConnect03();

        $query= " SELECT B.FECHA as label, B.TOTAL as value, B.AUTOMATICO FROM (SELECT FECHA ".
            ",SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN HFC ELSE 0 END) AS AUTOMATICO ".
            ",SUM(TOTAL_TIPO_USUARIO-REDCO-GPON-OTRA-SIN) AS TOTAL ".
            " FROM gestor_informes.kpi_seguimiento_automatico ".
            " where year(FECHA)= YEAR(NOW()) ".
            " group by FECHA order by fecha desc limit 10) B order by B.FECHA asc";

        //$query= " SELECT B.FECHA as label, B.TOTAL as value, B.AUTOMATICO FROM (SELECT FECHA ".
        //",SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN TOTAL_TIPO_USUARIO ELSE 0 END) AS AUTOMATICO ".
        //",SUM(TOTAL_TIPO_USUARIO) AS TOTAL ".
        //" FROM gestor_informes.kpi_seguimiento_automatico ".
        //" group by FECHA order by fecha desc limit 10) B order by B.FECHA asc";


        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $categorias=array();
            $totales=array();
            $automatico=array();
            $i=0;
            while($row = $r->fetch_assoc()){
                $label=$row['label'];
                $total=$row['value'];
                $auto=$row['AUTOMATICO'];
                $pauto=$auto/$total*100;
                $pauto=number_format($pauto, 2);
                $categorias[]=array("label"=>"$label");
                $totales[]=array("value"=>"$total");
                $automatico[]=array("toolText"=>"AUTO: $pauto %","value"=>"$auto", "label"=>"$pauto");
                $result[] = $row;
                $i++;
            }
            $this->response($this->json(array($categorias,$totales,$automatico,$result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }
//----------------fin Dashboard asignaciones gestor_informes.kpi_seguimiento_automatico-----asignaciones-----

//------------Dashboard agendamiento ------------------------------agendamiento-----------------
    private function getDashboardAgendamiento(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $today = date("Y-m-d");

        $this->dbConnect03();

        $query= "SELECT B.FECHA, B.ORDENES_TERRENO, B.ORDENES_REAGENDADAS FROM ( SELECT DATE_FORMAT(FECHA,'%M') as FECHA ".
            ",SUM(ORDENES_TERRENO) AS ORDENES_TERRENO ".
            ",SUM(ORDENES_REAGENDADAS) AS ORDENES_REAGENDADAS ".
            " FROM gestor_informes.kpi_agendamiento_operativo ".
            " group by DATE_FORMAT(FECHA,'%M') order by DATE_FORMAT(FECHA,'%m') asc limit 12) B ";

        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $categorias=array();
            $o_terreno=array();
            $o_reagendadas=array();
            $i=0;
            while($row = $r->fetch_assoc()){
                $label=$row['FECHA'];
                $o_t=$row['ORDENES_TERRENO'];
                $o_r=$row['ORDENES_REAGENDADAS'];
                //$pauto=$auto/$total*100;
                //$pauto=number_format($pauto, 2);
                $categorias[]=array("label"=>"$label");
                $o_terreno[]=array("value"=>"$o_t");
                $o_reagendadas[]=array("value"=>"$o_r");
                $result[] = $row;
                $i++;
            }
            $this->response($this->json(array($categorias,$o_terreno,$o_reagendadas,$result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }
//------------fin Dashboard agendamiento ------------------------------agendamiento-----------------

//-----------------------Dashboard agendamiento presupuestal-----------agendamiento-----------------
    private function getDashboardAgendamientoPresupuestal(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $today = date("Y-m-d");

        $this->dbConnect03();

        $query= "SELECT B.FECHA, B.EJECUTADO, B.META FROM ( SELECT DATE_FORMAT(FECHA,'%M') as FECHA ".
            ",SUM(EJECUTADO) AS EJECUTADO ".
            ",SUM(META) AS META ".
            " FROM gestor_informes.kpi_agendamiento_presupuestal ".
            " group by DATE_FORMAT(FECHA,'%M') order by DATE_FORMAT(FECHA,'%m') asc limit 12) B ";

        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $categorias=array();
            $ejecutado=array();
            $meta=array();
            $i=0;
            while($row = $r->fetch_assoc()){
                $label=$row['FECHA'];
                $eje=$row['EJECUTADO'];
                $met=$row['META'];
                //$pauto=$auto/$total*100;
                //$pauto=number_format($pauto, 2);
                $categorias[]=array("label"=>"$label");
                $ejecutado[]=array("value"=>"$eje");
                $meta[]=array("value"=>"$met");
                $result[] = $row;
                $i++;
            }
            $this->response($this->json(array($categorias,$ejecutado,$meta,$result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

//-----------------------fin Dashboard agendamiento presupuestal-----------agendamiento-----------------

//------------------ Dashboard reconfiguracion gestor_informes.kpi_seguimiento_reconfigura------asignaciones--------

    private function getDashboardReconfiguracion(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $today = date("Y-m-d");

        $this->dbConnect03();

        $query= "SELECT B.FECHA as label, B.ESTUDIOS_MANUALES, B.PEN14,B.PEN99  ".
            " FROM gestor_informes.kpi_seguimiento_reconfigura B ".
            " order by B.FECHA desc limit 10";

        $query="SELECT B.FECHA, B.ESTUDIOS_MANUALES, B.PEN14,B.PEN99 FROM ( SELECT DATE_FORMAT(FECHA,'%M') as FECHA ".
            ",SUM(ESTUDIOS_MANUALES) AS ESTUDIOS_MANUALES ".
            ",SUM(PEN14) AS PEN14 ".
            ",SUM( PEN99) AS PEN99 ".
            " FROM gestor_informes.kpi_seguimiento_reconfigura ".
            " group by DATE_FORMAT(FECHA,'%M') order by DATE_FORMAT(FECHA,'%m') asc limit 12) B ";



        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $categorias=array();
            $estudios_manuales=array();
            $pen14=array();
            $pen99=array();
            $i=0;
            while($row = $r->fetch_assoc()){
                $label=$row['label'];
                $em=$row['ESTUDIOS_MANUALES'];
                $p14=$row['PEN14'];
                $p99=$row['PEN99'];
                //$pauto=$auto/$total*100;
                //$pauto=number_format($pauto, 2);
                $categorias[]=array("label"=>"$label");
                $estudios_manuales[]=array("value"=>"$em");
                $pen14[]=array("value"=>"$p14");
                $pen99[]=array("value"=>"$p99");

                //$automatico[]=array("toolText"=>"AUTO: $pauto %","value"=>"$auto", "label"=>"$pauto");
                $result[] = $row;
                $i++;
            }
            $this->response($this->json(array($categorias,$estudios_manuales,$pen14,$pen99)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }
//------------------fin  Dashboard reconfiguracion gestor_informes.kpi_seguimiento_reconfigura------asignaciones--------

//---------------Dashboard pendientes kpi_pendientes_alistamiento ----------asignaciones------------------

    private function getDashboardPendientes(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $today = date("Y-m-d");

        $this->dbConnect03();

        $query="select fecha, sum(case when NEGOCIO='ASIGNACIONES' THEN CANTIDAD ELSE 0 END) AS ASIGNACIONES".
            ",sum(case when NEGOCIO='RECONFIGURACION' THEN CANTIDAD ELSE 0 END) AS RECONFIGURACION".
            ",sum(case when NEGOCIO='AGENDAMIENTO' THEN CANTIDAD ELSE 0 END) AS AGENDAMIENTO".
            ",sum(case when NEGOCIO='ACTIVACION' THEN CANTIDAD ELSE 0 END) AS ACTIVACION ".
            " from kpi_pendientes_alistamiento ".
            " where fecha between '$today 00:00:00' and '$today 23:59:59' ".
            " group by fecha order by fecha asc";


        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $asignaciones=array();
            $reconfiguracion=array();
            $agendamiento=array();
            $activacion=array();
            $categorias=array();
            $i=1;
            while($row = $r->fetch_assoc()){


                $label=$row['fecha'];
                $asigna=$row['ASIGNACIONES'];
                $recon=$row['RECONFIGURACION'];
                $agenda=$row['AGENDAMIENTO'];
                $activa=$row['ACTIVACION'];

                $categorias[]=array("label"=>"$i");

                $asignaciones[]=array("value"=>"$asigna");
                $reconfiguracion[]=array("value"=>"$recon");
                $agendamiento[]=array("value"=>"$agenda");
                $activacion[]=array("value"=>"$activa");

                //$pauto=$auto/$total*100;
                //$pauto=number_format($pauto, 2);
                //$categorias[]=array("label"=>"$label");
                //$totales[]=array("value"=>"$total");
                //$automatico[]=array("toolText"=>"AUTO: $pauto %","value"=>"$auto", "label"=>"$pauto");
                //$result[] = $row;
                $i++;
            }
            $this->response($this->json(array($categorias,$asignaciones,$reconfiguracion,$agendamiento,$activacion)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }
//---------------fin Dashboard pendientes kpi_pendientes_alistamiento ----------asignaciones------------------

//-------------actualizar grafica de cambio nuevo kpi_seguimiento_automatico HFC-------asignacion-----------
    private function actualizarGraficaCambioNuevoHFC(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbConnect03();

        $query= " SELECT B.FECHA, B.AUTOMATICO, B.MANUAL, B.AUTOMATICO_NUEVO, B.AUTOMATICO_CAMBIO, B.MANUAL_NUEVO, B.MANUAL_CAMBIO ".
            "FROM ( SELECT DATE_FORMAT(FECHA,'%M') as FECHA, ".
            "SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN HFC ELSE 0 END) AS AUTOMATICO, ".
            "SUM(TOTAL_TIPO_USUARIO-REDCO-GPON-OTRA-SIN) AS TOTAL, ".
            "SUM( CASE WHEN  TIPO_USUARIO='MANUAL' THEN HFC ELSE 0 END) AS MANUAL,  ".
            "SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN NUEVO_HFC ELSE 0 END) AS AUTOMATICO_NUEVO, ".
            "SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN CAMBIO_HFC ELSE 0 END) AS AUTOMATICO_CAMBIO,  ".
            "SUM( CASE WHEN  TIPO_USUARIO='MANUAL' THEN NUEVO_HFC ELSE 0 END) AS MANUAL_NUEVO,  ".
            "SUM( CASE WHEN  TIPO_USUARIO='MANUAL' THEN CAMBIO_HFC ELSE 0 END) AS MANUAL_CAMBIO ".
            "FROM gestor_informes.kpi_seguimiento_automatico ".
            " where year(FECHA)= YEAR(NOW()) ".
            "group by DATE_FORMAT(FECHA,'%M') order by DATE_FORMAT(FECHA,'%m') asc limit 12) B";

        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $categorias=array();
            $totales_manual=array();
            $totales_auto=array();
            $manu_cambio=array();
            $manu_nuevo=array();
            $auto_cambio=array();
            $auto_nuevo=array();
            $i=0;
            while($row = $r->fetch_assoc()){
                $label=$row['FECHA'];
                $total_manual=$row['MANUAL'];
                $total_auto=$row['AUTOMATICO'];
                $cambio_manual=$row['MANUAL_CAMBIO'];
                $nuevo_manual=$row['MANUAL_NUEVO'];
                $cambio_auto=$row['AUTOMATICO_CAMBIO'];
                $nuevo_auto=$row['AUTOMATICO_NUEVO'];

                /* $manual_cambio=number_format($cambio_manual/$total_manual, 2);
										$manual_nuevo=number_format($nuevo_manual/$total_manual, 2);
										$automa_nuevo=number_format($nuevo_auto/$total_auto, 2);
										$automa_cambio=number_format($cambio_auto/$total_auto, 2);*/

                $categorias[]=array("label"=>"$label");
                $totales_manual[]=array("value"=>"$total_manual");
                $totales_auto[]=array("value"=>"$total_auto");
                $manu_cambio[]=array("value"=>"$cambio_manual");
                $manu_nuevo[]=array("value"=>"$nuevo_manual");
                $auto_nuevo[]=array("value"=>"$cambio_auto");
                $auto_cambio[]=array("value"=>"$nuevo_auto");
                $result[] = $row;
                $i++;
            }
            $this->response($this->json(array($categorias,$totales_manual,$totales_auto,$manu_cambio,$manu_nuevo,$auto_nuevo,$auto_cambio,$result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

//-------------fin actualizar grafica de cambio nuevo kpi_seguimiento_automatico HFC-------asignacion-----------

//------------------actualizar grafica cambio nuebo REDCO kpi_seguimiento_automatico----asignaciones-------

    private function actualizarGraficaCambioNuevoREDCO(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbConnect03();

        $query= " SELECT B.FECHA, B.AUTOMATICO, B.MANUAL, B.AUTOMATICO_NUEVO, B.AUTOMATICO_CAMBIO, MANUAL_NUEVO, B. MANUAL_CAMBIO ".
            "FROM ( SELECT DATE_FORMAT(FECHA,'%M') as FECHA, ".
            "SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN REDCO ELSE 0 END) AS AUTOMATICO, ".
            "SUM(TOTAL_TIPO_USUARIO-HFC-GPON-OTRA-SIN) AS TOTAL, ".
            "SUM( CASE WHEN  TIPO_USUARIO='MANUAL' THEN REDCO ELSE 0 END) AS MANUAL,  ".
            "SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN NUEVO_REDCO ELSE 0 END) AS AUTOMATICO_NUEVO, ".
            "SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN CAMBIO_REDCO ELSE 0 END) AS AUTOMATICO_CAMBIO,  ".
            "SUM( CASE WHEN  TIPO_USUARIO='MANUAL' THEN NUEVO_REDCO ELSE 0 END) AS MANUAL_NUEVO,  ".
            "SUM( CASE WHEN  TIPO_USUARIO='MANUAL' THEN CAMBIO_REDCO ELSE 0 END) AS MANUAL_CAMBIO ".
            "FROM gestor_informes.kpi_seguimiento_automatico ".
            " where year(FECHA)= YEAR(NOW()) ".
            "group by DATE_FORMAT(FECHA,'%M') order by DATE_FORMAT(FECHA,'%m') asc limit 12) B";

        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $categorias=array();
            $totales_manual=array();
            $totales_auto=array();
            $manu_cambio=array();
            $manu_nuevo=array();
            $auto_cambio=array();
            $auto_nuevo=array();
            $i=0;
            while($row = $r->fetch_assoc()){
                $label=$row['FECHA'];
                $total_manual=$row['MANUAL'];
                $total_auto=$row['AUTOMATICO'];
                $cambio_manual=$row['MANUAL_CAMBIO'];
                $nuevo_manual=$row['MANUAL_NUEVO'];
                $cambio_auto=$row['AUTOMATICO_CAMBIO'];
                $nuevo_auto=$row['AUTOMATICO_NUEVO'];

                /* $manual_cambio=number_format($cambio_manual/$total_manual, 2);
										$manual_nuevo=number_format($nuevo_manual/$total_manual, 2);
										$automa_nuevo=number_format($nuevo_auto/$total_auto, 2);
										$automa_cambio=number_format($cambio_auto/$total_auto, 2);*/
                $meses[]=array("$label");
                $categorias[]=array("label"=>"$label");
                $totales_manual[]=array("value"=>"$total_manual");
                $totales_auto[]=array("value"=>"$total_auto");
                $manu_cambio[]=array("value"=>"$cambio_manual");
                $manu_nuevo[]=array("value"=>"$nuevo_manual");
                $auto_nuevo[]=array("value"=>"$cambio_auto");
                $auto_cambio[]=array("value"=>"$nuevo_auto");
                $result[] = $row;
                $i++;
            }
            $this->response($this->json(array($categorias,$totales_manual,$totales_auto,$manu_cambio,$manu_nuevo,$auto_nuevo,$auto_cambio,$result,$meses)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

//------------------fin actualizar grafica cambio nuebo REDCO kpi_seguimiento_automatico----asignaciones-------

//-------------  Dashboard asignaciones por mes--------------------------asignaciones------------------

    private function getDashboardAsignacionesMes(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbConnect03();

        $query= " SELECT B.FECHA, B.AUTOMATICO, B.TOTAL,B.MANUAL FROM ( SELECT DATE_FORMAT(FECHA,'%M') as FECHA ".
            ",SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN HFC ELSE 0 END) AS AUTOMATICO ".
            ",SUM(TOTAL_TIPO_USUARIO-REDCO-GPON-OTRA-SIN) AS TOTAL ".
            ",SUM( CASE WHEN  TIPO_USUARIO='MANUAL' THEN HFC ELSE 0 END) AS MANUAL ".
            " FROM gestor_informes.kpi_seguimiento_automatico ".
            " where year(FECHA)= YEAR(NOW()) ".
            " group by DATE_FORMAT(FECHA,'%M') order by DATE_FORMAT(FECHA,'%m') asc limit 12) B ";

        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $categorias=array();
            $totales=array();
            $automatico=array();
            $manual=array();
            $i=0;
            while($row = $r->fetch_assoc()){
                $label=$row['FECHA'];
                $total=$row['TOTAL'];
                $auto=$row['AUTOMATICO'];
                $manu=$row['MANUAL'];

                $auto=$auto/$total;
                $auto=number_format($auto, 2);
                $manu=number_format($manu/$total, 2);

                $categorias[]=array("label"=>"$label");
                $totales[]=array("value"=>"$total");
                $manual[]=array("value"=>"$manu");
                $automatico[]=array("value"=>"$auto", "label"=>"pauto");
                $result[] = $row;
                $i++;
            }
            $this->response($this->json(array($categorias,$manual,$automatico,$totales,$result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }
//-------------  fin Dashboard asignaciones por mes--------------------------asignaciones------------------

//-----------------------------Dashboard asignaciones mes COBRE ------------------asignaciones-------------
    private function getDashboardAsignacionesMesCobre(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbConnect03();

        $query= " SELECT B.FECHA, B.AUTOMATICO, B.TOTAL,B.MANUAL FROM ( SELECT DATE_FORMAT(FECHA,'%M') as FECHA ".
            ",SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN REDCO ELSE 0 END) AS AUTOMATICO ".
            ",SUM(TOTAL_TIPO_USUARIO-HFC-GPON-OTRA-SIN) AS TOTAL ".
            ",SUM( CASE WHEN  TIPO_USUARIO='MANUAL' THEN REDCO ELSE 0 END) AS MANUAL ".
            " FROM gestor_informes.kpi_seguimiento_automatico ".
            " where year(FECHA)= YEAR(NOW()) ".
            " group by DATE_FORMAT(FECHA,'%M') order by DATE_FORMAT(FECHA,'%m') asc limit 12) B ";

        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $categorias=array();
            $totales=array();
            $automatico=array();
            $manual=array();
            $i=0;
            while($row = $r->fetch_assoc()){
                $label=$row['FECHA'];
                $total=$row['TOTAL'];
                $auto=$row['AUTOMATICO'];
                $manu=$row['MANUAL'];

                $auto=$auto/$total;
                $auto=number_format($auto, 2);
                $manu=number_format($manu/$total, 2);

                $categorias[]=array("label"=>"$label");
                $totales[]=array("value"=>"$total");
                $manual[]=array("value"=>"$manu");
                $automatico[]=array("value"=>"$auto", "label"=>"$auto");
                $result[] = $row;
                $i++;
            }
            $this->response($this->json(array($categorias,$manual,$automatico,$totales,$result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

//----------------------------- fin Dashboard asignaciones mes COBRE ------------------asignaciones-------------

//------------------------Dashboard asignaciones Tecnologia ------------------------asignaciones------
    private function getDashboardAsignacionesTecnologia(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbConnect03();

        $query= "SELECT B.FECHA, B.REDCO, B.HFC,B.GPON,B.OTRA,B.SIN,B.TOTAL FROM ( SELECT DATE_FORMAT(FECHA,'%M') as FECHA ".
            ",SUM(TOTAL_TIPO_USUARIO) AS TOTAL".
            ",SUM(REDCO) AS REDCO ".
            ",SUM(HFC) AS HFC ".
            ",SUM( GPON) AS GPON ".
            ",SUM(OTRA) AS OTRA ".
            ",SUM(SIN) AS SIN ".
            " FROM gestor_informes.kpi_seguimiento_automatico ".
            " where year(FECHA)= YEAR(NOW()) ".
            " group by DATE_FORMAT(FECHA,'%M') order by DATE_FORMAT(FECHA,'%m') asc limit 12) B ";
        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $categorias=array();
            $redco=array();
            $hfc=array();
            $gpon=array();
            $otra=array();
            $sin=array();
            $total_tipo_usuario=array();

            $i=0;
            while($row = $r->fetch_assoc()){
                $label=$row['FECHA'];
                $re=$row['REDCO'];
                $hf=$row['HFC'];
                $gp=$row['GPON'];
                $ot=$row['OTRA'];
                $si=$row['SIN'];
                $total=$row['TOTAL'];

                //NOTA: ESTO SE HACE PARA QUE RE SEA EL COMPLEMENTO DE HFC
                //$total=$re-$hf-$gp-$ot-$si;


                /*
                                        $re=number_format($re/$total,2);
                                        $hf=number_format($hf/$total,2);
                                        $gp=number_format($gp/$total,2);
                                        $ot=number_format($ot/$total,2);
                                        $si=number_format($si/$total,2);


*/

                //$p14=number_format($p14/$em, 2)*100;
                //$p99=number_format($p99/$em, 2)*100;

                $categorias[]=array("label"=>"$label");
                $redco[]=array("value"=>"$re");
                $hfc[]=array("value"=>"$hf");
                $gpon[]=array("value"=>"$gp");
                $otra[]=array("value"=>"$ot");
                $sin[]=array("value"=>"$si");
                $total_tipo_usuario[]=array("value"=>"$total");


                $i++;
            }
            $this->response($this->json(array($categorias,$total_tipo_usuario,$redco,$hfc,$gpon,$otra,$sin)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

//------------------------fin Dashboard asignaciones Tecnologia ------------------------asignaciones------

//-------------------- Dashboard reconfiguracion mes ------------------------asignaciones-----------


    private function getDashboardReconfiguracionMes(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbConnect03();

        $query= " SELECT B.FECHA, B.ESTUDIOS_MANUALES, B.PEN14,B.PEN99,B.TIEMPO_PROMEDIO14,B.TIEMPO_PROMEDIO99 FROM ( SELECT DATE_FORMAT(FECHA,'%M') as FECHA ".
            ",SUM(ESTUDIOS_MANUALES) AS ESTUDIOS_MANUALES ".
            ",SUM(PEN14) AS PEN14 ".
            ",SUM( PEN99) AS PEN99 ".
            ",avg(PROM_HORAS14) AS TIEMPO_PROMEDIO14 ".
            ",avg(PROM_HORAS99) AS TIEMPO_PROMEDIO99 ".
            " FROM gestor_informes.kpi_seguimiento_reconfigura ".
            " where year(FECHA)= YEAR(NOW()) ".
            " group by DATE_FORMAT(FECHA,'%M') order by DATE_FORMAT(FECHA,'%m') asc limit 12) B ";

        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $categorias=array();
            $estudios_manuales=array();
            $pen14=array();
            $pen99=array();
            $tprom14=array();
            $tprom99=array();

            $i=0;
            while($row = $r->fetch_assoc()){
                $label=$row['FECHA'];
                $em=$row['ESTUDIOS_MANUALES'];
                $p14=$row['PEN14'];
                $p99=$row['PEN99'];
                $tp14=$row['TIEMPO_PROMEDIO14'];
                $tp99=$row['TIEMPO_PROMEDIO99'];

                //NOTA: ESTO SE HACE PARA QUE EM SEA EL COMPLEMENTO DE P14 Y P99
                $em=$em-$p14-$p99;


                //$auto=$auto/$total;
                //$auto=number_format($auto, 2);
                //$manu=number_format($manu/$total, 2);

                //$p14=number_format($p14/$em, 2)*100;
                //$p99=number_format($p99/$em, 2)*100;

                $categorias[]=array("label"=>"$label");
                $estudios_manuales[]=array("value"=>"$em");
                $pen14[]=array("value"=>"$p14");
                $pen99[]=array("value"=>"$p99");
                $tprom14[]=array("value"=>"$tp14");
                $tprom99[]=array("value"=>"$tp99");

                $result[] = $row;
                $i++;
            }
            $this->response($this->json(array($categorias,$estudios_manuales,$pen14,$pen99,$result,$tprom14,$tprom99)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }
//-------------------- fin Dashboard reconfiguracion mes ------------------------asignaciones-----------

//--------------------- Dashboard activacion TMA mes -------------------activacion------------------
    //DashBoard Activacion TMA
    private function getDashboardActivacionMes(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbConnect03();

        $query= "SELECT ".
            "	a.NOMBREMES as label".
            "	, a.TMA as value".
            "	FROM gestor_informes.kpi_activacion_resumentma a ".
            "	order by a.MES asc LIMIT 12 ";

        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                //$row['label']="Mes ".$row['label'];
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('',204);

    }

//----------------------fin  Dashboard activacion TMA mes -------------------activacion------------------

//--------------------grafica con pendientes informe_petec_pendientesm---------asignaciones--------------

    private function pendientesGrafica(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $query= " SELECT concepto_id as label, COUNT(*) as value ".
            "   FROM(SELECT ".
            "    DISTINCT ".
            "    PEDIDO_ID ".
            "    , CASE  ".
            "        WHEN FUENTE='FENIX_NAL' and CONCEPTO_ID='PETEC' AND STATUS!='MALO' then 'PETEC-NAL' ".
            "        WHEN FUENTE='FENIX_BOG' and CONCEPTO_ID='PETEC' AND STATUS!='MALO' then 'PETEC-BOG' ".
            "        WHEN STATUS='MALO' THEN 'MALO'   ".
            "        ELSE CONCEPTO_ID END AS CONCEPTO_ID ".
            "    , STATUS ".
            "    , FUENTE ".
            "    FROM informe_petec_pendientesm ".
            "    WHERE (STATUS='PENDI_PETEC' or STATUS='MALO') ) C1 ".
            "    GROUP BY concepto_id ".
            "    ORDER BY COUNT(*) DESC ";
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $row['label']=" ".$row['label'];
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }


//--------------------fin grafica con pendientes informe_petec_pendientesm---------asignaciones--------------

//----------------------grafica pendientes informe_activacion_pendientesm -------activacion------------


    private function pendientesGraficaAD(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $query= " SELECT cola_id as label, COUNT(*) as value ".
            " FROM  informe_activacion_pendientesm ".
            " WHERE (STATUS='PENDI_ACTIVACION') ".
            " GROUP BY cola_id ".
            " ORDER BY COUNT(*) DESC" ;
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
//----------------------grafica pendientes informe_activacion_pendientesm -------activacion------------
//----------------------grafica GESTION -------activacion------------


    private function activacionGraficaseguimiento(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $today = date("Y-m-d");

        $query= " SELECT ASESOR as label, COUNT(*) as value  ".
                " FROM  gestor_historico_activacion  ".
                "  WHERE  FECHA_FIN between '$today 00:00:00' and '$today 23:59:59' ".
                "  GROUP BY ASESOR ".
                " ORDER BY COUNT(*) DESC ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $total=0;
            while($row = $r->fetch_assoc()){
                $row['label']="Asesor ".$row['label'];
                $total=$total + $row['value'];
                $result[] = $row;
            }
            $this->response($this->json(array($result,$total)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }
//----------------------grafica GESTION -------activacion------------

//--------------------------pendientes siebel grafica gestor_pendientes_activacion_siebel---activacion---

    private function PendientesSiebelGraficaAD(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }


        $query= " SELECT PRODUCTO as label, COUNT(*) as value ".
            " FROM  gestor_pendientes_activacion_siebel ".
            " WHERE (ESTADO='in_progress') ".
            " GROUP BY PRODUCTO ".
            " ORDER BY COUNT(*) DESC ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $total=0;
            while($row = $r->fetch_assoc()){
                $row['label']="Producto".$row['label'];
                $total=$total + $row['value'];
                $result[] = $row;
            }
            $this->response($this->json(array($result,$total)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }
//--------------------------fin pendientes siebel grafica gestor_pendientes_activacion_siebel---activacion---


//------------------------seguimiento activacion gestor_seguimiento_activacion---activacion-----------------


    private function seguimientoactivacionGraficaAD(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $today = date("Y-m-d");

        $fechaini=$today;
        $fechafin=$today;



        $query=   " SELECT cola_id as label, COUNT(*) as value,fecha_entrada_gestor,fecha_ultima_gestor ".
            " FROM  gestor_seguimiento_activacion ".
            " where fecha_ultima_gestor between '$fechaini 00:00:00' and '$fechafin 23:59:59'  ".
            " GROUP BY cola_id ".
            " ORDER BY COUNT(*) DESC";


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
//------------------------fin seguimiento activacion gestor_seguimiento_activacion---activacion-----------------


//---------------------pendientes grafica reagendamiento---------------------agendamiento-----------------

    private function pendientesGraficaAgendamiento(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
         $today = date("Y-m-d");                      
        $query= "SELECT (CASE WHEN CONCEPTOS LIKE  '%,%' THEN  'VARIOS_CONCEPTOS' ELSE CONCEPTOS END) AS label, COUNT( * ) as value ".
            " FROM  gestor_pendientes_reagendamiento ".
            " WHERE  STATUS in  ('PENDI_AGEN') ".
            " AND (MIGRACION is null or MIGRACION='' OR MIGRACION!='SI') ".
            " GROUP BY label DESC ";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $total=0;
            while($row = $r->fetch_assoc()){
                //2016-08-17: Mauricio - para que no muestre el string Concepto en la grafica
                $row['label']=" ".utf8_encode($row['label']);
                $total=$total + $row['value'];
                $result[] = $row;
            }

            $this->response($this->json(array($result,$total)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

//---------------------fin pendientes grafica reagendamiento---------------------agendamiento-----------------

//-------------------------listado agendamiento auditoria gestor_historicos_reagendamiento---agendamiento-----

    private function listadoAgendamientoAuditoria(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);

        }

        $fechaini = $this->_request['fecha_inicio'];
        $fechafin = $this->_request['fecha_fin'];
        $today = date("Y-m-d");

        if ($fechaini=="undefined" || $fechafin=="undefined"){

            $fechaini=$today;
            $fechafin=$today;
        }



        //var_dump($fechaini) ;


        if ($fechaini!="" || $fechafin!=""){


            $param = "AND DATE_FORMAT(r.FECHA_FIN,'%Y-%m-%d') BETWEEN  '$fechaini' AND '$fechafin' ) C1";

        }else {
            $param="AND DATE_FORMAT(r.FECHA_FIN,'%Y-%m-%d') BETWEEN  '$today' and '$today' ) C1";
        }
        $query= " SELECT ".
            " C1.NOVEDAD ".
            ", COUNT(*) AS REGISTROS ".
            ", COUNT(DISTINCT C1.PEDIDO_ID) AS PEDIDOS ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='ANTIOQUIA' THEN 1 ELSE 0 END) AS ANTIOQUIA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='ATLANTICO' THEN 1 ELSE 0 END) AS ATLANTICO ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='BOGOTA-COBRE' THEN 1 ELSE 0 END) AS 'BOGOTACOBRE' ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='BOGOTA D.C.s' THEN 1 ELSE 0 END) AS BOGOTA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='BOLIVAR' THEN 1 ELSE 0 END) AS BOLIVAR ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='BOYACA' THEN 1 ELSE 0 END) AS BOYACA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='CALDAS' THEN 1 ELSE 0 END) AS CALDAS ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='CAQUETA' THEN 1 ELSE 0 END) AS CAQUETA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='CESAR' THEN 1 ELSE 0 END) AS CESAR ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='CUNDINAMARCA' THEN 1 ELSE 0 END) AS CUNDINAMARCA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='DTH' THEN 1 ELSE 0 END) AS DTH ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='HUILA' THEN 1 ELSE 0 END) AS HUILA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='MAGDALENA' THEN 1 ELSE 0 END) AS MAGDALENA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='META' THEN 1 ELSE 0 END) AS META ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='NORTE DE SANTANDER' THEN 1 ELSE 0 END) AS NORTE_DE_SANTANDER ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='QUINDIO' THEN 1 ELSE 0 END) AS QUINDIO ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='SANTANDER' THEN 1 ELSE 0 END) AS SANTANDER ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='SUCRE' THEN 1 ELSE 0 END) AS SUCRE ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='TOLIMA' THEN 1 ELSE 0 END) AS TOLIMA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='VALLE' THEN 1 ELSE 0 END) AS VALLE ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='VALLE DEL CAUCA' THEN 1 ELSE 0 END) AS VALLE_DEL_CAUCA ".
            ",SUM(CASE WHEN C1.DEPARTAMENTO='' THEN 1 ELSE 0 END) AS VACIOS ".
            "FROM(SELECT ".
            "				r.PEDIDO_ID ".
            "				,r.NOVEDAD ".
            "				,r.DEPARTAMENTO ".
            "				, r.FECHA_FIN ".
            "				FROM portalbd.gestor_historicos_reagendamiento r ".
            "				where  1=1 ".
            "				$param	 ".
            "GROUP BY C1.NOVEDAD ";
        //echo $query;
        //echo $param  ;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $total=0;
            while($row = $r->fetch_assoc()){

                $result[] = $row;

                // var_dump($result);
            }

            $this->response($this->json(array($result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

    //-------------------------listado agendamiento auditoria gestor_historicos_reagendamiento---agendamiento-----

    //----------------------exportar tabla gestor_historicos_reagendamiento------agendamiento-------------------
    private function csvTabla(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);

        }

        $login = $this->_request['login'];
        $fechaini = $this->_request['fecha_inicio'];
        $fechafin = $this->_request['fecha_fin'];
        $today = date("Y-m-d");
        $filename="Tabla_Agendamiento-$login-$today.csv";

        //echo $fechaini.$fechafin;

        if ($fechaini=="undefined" || $fechafin=="undefined"){

            $fechaini=$today;
            $fechafin=$today;
        }

        //var_dump($fechaini) ;


        if ($fechaini!="" || $fechafin!=""){


            $param = "AND DATE_FORMAT(r.FECHA_FIN,'%Y-%m-%d') BETWEEN  '$fechaini' AND '$fechafin' ) C1";

        }else {
            $param="AND DATE_FORMAT(r.FECHA_FIN,'%Y-%m-%d') BETWEEN  '$today' and '$today' ) C1";
        }

        $query= " SELECT ".
            " C1.NOVEDAD ".
            ", COUNT(*) AS REGISTROS ".
            ", COUNT(DISTINCT C1.PEDIDO_ID) AS PEDIDOS ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='ANTIOQUIA' THEN 1 ELSE 0 END) AS ANTIOQUIA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='ATLANTICO' THEN 1 ELSE 0 END) AS ATLANTICO ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='BOGOTA-COBRE' THEN 1 ELSE 0 END) AS 'BOGOTA-COBRE' ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='BOGOTA D.C.' THEN 1 ELSE 0 END) AS BOGOTA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='BOLIVAR' THEN 1 ELSE 0 END) AS BOLIVAR ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='BOYACA' THEN 1 ELSE 0 END) AS BOYACA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='CALDAS' THEN 1 ELSE 0 END) AS CALDAS ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='CAQUETA' THEN 1 ELSE 0 END) AS CAQUETA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='CESAR' THEN 1 ELSE 0 END) AS CESAR ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='CUNDINAMARCA' THEN 1 ELSE 0 END) AS CUNDINAMARCA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='DTH' THEN 1 ELSE 0 END) AS DTH ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='HUILA' THEN 1 ELSE 0 END) AS HUILA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='MAGDALENA' THEN 1 ELSE 0 END) AS MAGDALENA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='META' THEN 1 ELSE 0 END) AS META ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='NORTE DE SANTANDER' THEN 1 ELSE 0 END) AS NORTE_DE_SANTANDER ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='QUINDIO' THEN 1 ELSE 0 END) AS QUINDIO ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='SANTANDER' THEN 1 ELSE 0 END) AS SANTANDER ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='SUCRE' THEN 1 ELSE 0 END) AS SUCRE ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='TOLIMA' THEN 1 ELSE 0 END) AS TOLIMA ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='VALLE' THEN 1 ELSE 0 END) AS VALLE ".
            ", SUM(CASE WHEN C1.DEPARTAMENTO='VALLE DEL CAUCA' THEN 1 ELSE 0 END) AS VALLE_DEL_CAUCA ".
            ",SUM(CASE WHEN C1.DEPARTAMENTO='' THEN 1 ELSE 0 END) AS VACIOS ".
            "FROM(SELECT ".
            "				r.PEDIDO_ID ".
            "				,r.NOVEDAD ".
            "				,r.DEPARTAMENTO ".
            "				, r.FECHA_FIN ".
            "				FROM portalbd.gestor_historicos_reagendamiento r ".
            "				where  1=1 ".
            "				$param	 ".
            "GROUP BY C1.NOVEDAD ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('Novedad','Registro','Pedidos','Antioquia','Atlantico','BogotaCobre','Bogota','Bolivar','Boyaca','Caldas','Caqueta','Cesar','Cundinamarca','Dth','Huila','Magdalena','Meta','Nortesantander','Quindio','Santander','Sucre','Tolima','Valle','ValleCauca','Vacios'));
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);

            $this->response($this->json(array($filename,$login,$fechaini,$fechafin)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }
//----------------------fin exportar tabla gestor_historicos_reagendamiento------agendamiento-------------------

//----------------pendientes por concepto reagendamiento-----------------agendamiento---------------------

    private function pendientesPorConceptoReagendamiento(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $queryFechaCita="select ".
            " c1.CONCEPTOS ".
            ", count(*) as CANTIDAD ".
            ", sum(if(c1.GESTIONABLES='NO', 1,0)) as 'Migracion' ".
            ", sum(if(c1.GESTIONABLES='SI', 1,0)) as 'Gestionables' ".
            ", sum(if(c1.RANGO_PENDIENTE='Cita Vencida' and c1.GESTIONABLES='SI', 1,0)) as 'Cita_Vencida' ".
            ", sum(if(c1.RANGO_PENDIENTE='Sin Agenda' and c1.GESTIONABLES='SI', 1,0)) as 'Sin_Agenda' ".
            ", sum(if(c1.RANGO_PENDIENTE='Agendados' and c1.GESTIONABLES='SI', 1,0)) as 'Agendados' ".
            " from (SELECT r.ID, ".
            "   r.PEDIDO_ID, ".
            "   case ".
            "        when r.CONCEPTOS like '%,%' then 'VARIOS' ".
            "        else r.CONCEPTOS ".
            "    end as CONCEPTOS, ".
            "    r.ACTIVIDADES, ".
            "    r.FECHA_CITA_FENIX, ".
            "    r.MIGRACION, ".
            "    cast((CASE  ".
            "        WHEN  r.FECHA_CITA_FENIX = '9999-00-00' THEN 'Sin Agenda' ".
            "        WHEN  r.FECHA_CITA_FENIX >= current_date() THEN 'Agendados' ".
            "        WHEN  r.FECHA_CITA_FENIX < current_date() THEN 'Cita Vencida' ".
            "    END ) as char )AS RANGO_PENDIENTE, ".
            "    case ".
            "        when r.MIGRACION='NO' or r.MIGRACION='' or r.MIGRACION is null then 'SI' ".
            "        else 'NO' ".
            "    end as GESTIONABLES ".
            " FROM portalbd.gestor_pendientes_reagendamiento r ".
            " where r.STATUS in ('PENDI_AGEN')) c1 ".
            " group by c1.CONCEPTOS order by count(*) DESC ";

        $r = $this->mysqli->query($queryFechaCita) or die($this->mysqli->error.__LINE__);

        $resultFechaCita = array();
        if($r->num_rows > 0){

            while($row = $r->fetch_assoc()){
                //$row['label']="Concepto ".$row['label'];
                $row['CONCEPTOS']=utf8_encode($row['CONCEPTOS']);
                $resultFechaCita[] = $row;
            }

            $this->response($this->json(array($resultFechaCita)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }
//----------------fin pendientes por concepto reagendamiento-----------------agendamiento---------------------


//----------------pendientes por transaccion activacio-----------------activacion---------------------

    private function listaPendientesSiebel(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $querytransaccion=" select  ".
            " c1.TRANSACCION ".
            " , count(*) as CANTIDAD ".
            " from (SELECT r.ID, ".
            " r.PEDIDO, ".
            " case ".
            "	when r.TRANSACCION like '%,%' then 'VARIOS' ".
            "	else r.TRANSACCION ".
            " end as TRANSACCION ".
            " FROM gestor_activacion_pendientes_activador_dom r  ".
            " where r.STATUS in ('PENDI_ACTI')) c1  ".
            " group by c1.TRANSACCION order by count(*) DESC ";

        $r = $this->mysqli->query($querytransaccion) or die($this->mysqli->error.__LINE__);

        $resultFechaCita = array();
        if($r->num_rows > 0){

            while($row = $r->fetch_assoc()){
                $resultFechaCita[] = $row;
            }

            $this->response($this->json(array($resultFechaCita)), 200);
        }

        $this->response('',204);

    }
//----------------fin pendientes por transaccion activacio-----------------activacion---------------------


//-----------------pendientes con agenda gestor_pendientes_reagendamiento----agendamiento-----------------

    private function pedidosConAgenda(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $queryPedidosAgen="select".
            " C1.DEPARTAMENTO ".
            " ,C1.CONCEPTOS".
            " , COUNT(*) AS Cantidad".
            " , sum(if(C1.RANGO_FECHAS='Hoy', 1,0)) as 'Hoy'".
            " , sum(if(C1.RANGO_FECHAS='Manana', 1,0)) as 'Manana'".
            " , sum(if(C1.RANGO_FECHAS='Pasado Manana', 1,0)) as 'Pasado_Manana'".
            " , sum(if(C1.RANGO_FECHAS='De 3 dias en adelante', 1,0)) as 'Mas_de_3_dias'".
            " from (SELECT ".
            " r.PEDIDO_ID ".
            " , r.CONCEPTOS ".
            " , r.ACTIVIDADES ".
            " , r.DEPARTAMENTO ".
            " , r.FECHA_CITA_FENIX".
            " , CAST((CASE".
            "     WHEN r.FECHA_CITA_FENIX = curdate() THEN 'Hoy'".
            "     WHEN r.FECHA_CITA_FENIX = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'Manana'".
            "     WHEN r.FECHA_CITA_FENIX = DATE_ADD(CURDATE(), INTERVAL 2 DAY) THEN 'Pasado Manana' ".
            "     WHEN r.FECHA_CITA_FENIX >=DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'De 3 dias en adelante' ".
            " END ) AS CHAR) AS RANGO_FECHAS".
            " , r.MIGRACION".
            " , r.STATUS".
            " FROM portalbd.gestor_pendientes_reagendamiento r".
            " where r.STATUS in ('PENDI_AGEN')".
            " and r.FECHA_CITA_FENIX >= curdate()".
            " and r.FECHA_CITA_FENIX !='9999-00-00'".
            " and r.MIGRACION='NO'".
            //" and (r.CONCEPTOS like '%AGEN%'".
            //" or r.CONCEPTOS like '%PROG%')".
            ") C1".
            " GROUP BY C1.DEPARTAMENTO,C1.CONCEPTOS".
            " order by C1.DEPARTAMENTO,count(*) DESC ";

        $r = $this->mysqli->query($queryPedidosAgen) or die($this->mysqli->error.__LINE__);

        $resultPedAgen = array();
        if($r->num_rows > 0){
            $total=0;
            while($row = $r->fetch_assoc()){
                //$row['label']="Concepto ".$row['label'];
                $total=$total + $row['Cantidad'];
                $resultPedAgen[] = $row;
            }

            $this->response($this->json(array($resultPedAgen,$total)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }
//-----------------fin pendientes con agenda gestor_pendientes_reagendamiento----agendamiento-----------------

//----------------productividaded del grupo------------------------------asignaciones--------------------------

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
        $query=  " Select  ".
            "   c1.user,  ".
            "   count(*) as servicios,   ".
            "   count(distinct c1.pedido_id) as Pedidos,  ".
            "   sum(if(c1.source='BUSCADO', 1,0)) as 'BUSCADO', ".
            "   sum(if(c1.source='AUTO', 1,0)) as 'DEMEPEDIDO', ".
            "   sum(if(c1.concepto_final='15', 1,0)) as 'c15',  ".
            "   sum(if(c1.concepto_final='99', 1,0)) as 'c99',  ".
            "   sum(if(c1.concepto_final='14', 1,0)) as 'c14',  ".
            "   sum(if(c1.concepto_final='2', 1,0)) as 'c2',  ".
            "   sum(if(c1.concepto_final='PORDE', 1,0)) as PORDE,  ".
            "   sum(if(c1.concepto_final='OTRO', 1,0)) as OTRO,  ".
            "   SEC_TO_TIME(AVG(TIMESTAMPDIFF(SECOND, c1.`fecha_inicio`,c1.`fecha_fin`))) AS AVG_ESTUDIO_MN  ".
            "   from (SELECT PE.`id`,  ".
            "       PE.`pedido`,  ".
            "       PE.`fuente`,  ".
            "       PE.`actividad`,  ".
            "       PE.`fecha_fin`,  ".
            "     date_format( PE.`fecha_fin`,'%H') as Hora_fin,  ".
            "       PE.`user`,  ".
            "       PE.`estado`,  ".
            "       PE.`duracion`,  ".
            "       PE.`INCIDENTE`,  ".
            "       PE.`fecha_estado`,  ".
            "       PE.`fecha_inicio`,  ".
            "       case  ".
            "       when PE.`concepto_final` not in ('15','99','14','2','PORDE') then 'OTRO'  ".
            "                  else PE.concepto_final  ".
            "                  end as concepto_final,  ".
            "   PE.`concepto_final` as Conceptos,  ".
            "       PE.`source`,  ".
            "       PE.`pedido_id`,  ".
            "       PE.`subpedido_id`,  ".
            "       PE.`solicitud_id`,  ".
            "       PE.`municipio_id`  ".
            "   FROM `portalbd`.`pedidos` PE  ".
            "           LEFT JOIN  ".
            "       portalbd.tbl_usuarios TU ON PE.user = TU.USUARIO_ID  ".
            "           LEFT JOIN  ".
            "       portalbd.tbl_cargos TC ON TU.CARGO_ID = TC.ID_CARGO  ".
            "   where date_format(fecha_fin,'%Y-%m-%d') between '$fechaIni' and '$fechaFin' and PE.source IN ('AUTO','BUSCADO') ) c1  ".
            "   group by c1.user order by  count(*) DESC ";

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
//----------------fin productividaded del grupo------------------------------asignaciones--------------------------

//-------------------calcular detalle TMA----------------------------------asignaciones-----------------
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

        $query=  " SELECT  ".
            "      pi.FECHA, ".
            "      pi.INGRESOS, ".
            "      es.ESTUDIOS, ".
            "      es.AUTO, ".
            "      es.BUSCADO, ".
            "      es.AVG_ESTUDIO_MN, ".
            "      es.AVG_ESPERA_HR, ".
            "      es.AVG_TMA_HR, ".
            "       TIMEDIFF(es.AVG_TMA_HR,'06:00:00') AS META_TMA ".
            "  FROM ".
            "      (SELECT  ".
            "          DATE_FORMAT(p.fecha_fin, '%Y-%m-%d') AS FECHA, ".
            "              COUNT(*) AS ESTUDIOS, ".
            "              SUM(CASE ".
            "                  WHEN p.SOURCE = 'AUTO' THEN 1 ".
            "                  ELSE 0 ".
            "              END) AS AUTO, ".
            "              SUM(CASE ".
            "                  WHEN p.SOURCE = 'BUSCADO' THEN 1 ".
            "                  ELSE 0 ".
            "              END) AS BUSCADO, ".
            "              SEC_TO_TIME(AVG(TIMESTAMPDIFF(SECOND, p.fecha_inicio, p.fecha_fin))) AS AVG_ESTUDIO_MN, ".
            "              SEC_TO_TIME(AVG(TIMESTAMPDIFF(SECOND, p.fecha_estado, p.fecha_inicio))) AS AVG_ESPERA_HR, ".
            "              SEC_TO_TIME(AVG(TIMESTAMPDIFF(SECOND, p.fecha_estado, p.fecha_fin))) AS AVG_TMA_HR ".
            "      FROM ".
            "          pedidos p ".
            "      LEFT JOIN portalbd.tbl_usuarios TU ON p.user = TU.USUARIO_ID ".
            "      WHERE ".
            "          p.source IN ('AUTO' , 'BUSCADO') ".
            "              AND TU.GRUPO IN ('ASIGNACIONES' , 'NCA')".
            "              AND (p.CONCEPTO_ANTERIOR IN ('PETEC','OKRED')".
            "                OR p.CONCEPTO_ANTERIOR IS NULL) ".
            "      GROUP BY DATE_FORMAT(p.fecha_fin, '%Y-%m-%d')) es, ".
            "      (SELECT  ".
            "          DATE_FORMAT(PP.FECHA_ESTADO, '%Y-%m-%d') AS FECHA, ".
            "              COUNT(*) AS INGRESOS ".
            "      FROM ".
            "          `portalbd`.`informe_petec_pendientesm` PP ".
            "      WHERE ".
            "          1 = 1 ".
            "              AND (PP.CONCEPTO_ANTERIOR IN ('PETEC','OKRED')".
            "                OR PP.CONCEPTO_ANTERIOR IS NULL)".
            "      GROUP BY DATE_FORMAT(PP.FECHA_ESTADO, '%Y-%m-%d')) pi ".
            "  WHERE ".
            "      es.FECHA = pi.FECHA ".
            "         AND pi.fecha BETWEEN CAST(DATE_FORMAT('$fechaIni', '%Y-%m-%d') AS DATE) AND ('$fechaFin') ";


        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){

            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" stat

    }
//-------------------calcular detalle TMA----------------------------------asignaciones-----------------

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
        $query="SELECT a.PEDIDO_ID,".
            " a.PEDIDO, ".
            " a.SUBPEDIDO_ID, ".
            " a.SOLICITUD_ID, ".
            " a.PROGRAMACION, ".
            " a.TIPO_TRABAJO, ".
            " a.TIPO_ELEMENTO_ID, ".
            " a.PRODUCTO, ".
            " a.UEN_CALCULADA, ".
            " a.ESTRATO, ".
            " a.MUNICIPIO_ID, ".
            " a.PAGINA_SERVICIO, ".
            " a.DIRECCION_SERVICIO, ".
            " CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(a.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA, ".
            " CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(a.FECHA_INGRESO)) AS CHAR(255)) as TIEMPO_INGRESO, ".
            //" hour(TIMEDIFF(CURRENT_TIMESTAMP(),(a.FECHA_INGRESO))) as TIEMPO_HORAS, ".
            " a.FUENTE, ".
            " a.CONCEPTO_ID, ".
            " a.FECHA_ESTADO, ".
            " a.FECHA_INGRESO, ".
            " a.FECHA_CITA, ".
            " a.STATUS, ".
            " ifnull((Select  p.OBSERVACIONES_PROCESO from portalbd.pedidos p  where 1=1  and estado_id='MALO'  and p.pedido_id=a.pedido_id  order by p.id desc   limit 1 ),'Sin') as OBS, ".
            " ifnull((Select  p.INCIDENTE from portalbd.pedidos p  where 1=1  and estado_id='MALO'  and p.pedido_id=a.pedido_id  order by p.id desc   limit 1 ),'Sin') as INCIDENTE, ".
            " a.RADICADO_TEMPORAL ".
            " from informe_petec_pendientesm a ".
            " where (a.STATUS='PENDI_PETEC' or a.STATUS='MALO') $concepto ";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO_ID','PEDIDO','SUBPEDIDO_ID','SOLICITUD_ID','PROGRAMACION','TIPO_TRABAJO','TIPO_ELEMENTO_ID','PRODUCTO','UEN_CALCULADA','ESTRATO','MUNICIPIO_ID','PAGINA_SERVICIO','DIRECCION_SERVICIO','TIEMPO_COLA','TIEMPO_INGRESO','FUENTE','CONCEPTO_ID','FECHA_ESTADO','FECHA_INGRESO','FECHA_CITA','STATUS','MOTIVO_MALO','INCIDENTE','RADICADO_TEMPORAL','PEDIDOFNX', 'CONCEPTO_CRM'));
            while($row = $r->fetch_assoc()){
                $pedidoCrm = $row['PEDIDO_ID'];
                $objRtaFenix = $this->conceptoPedidoSiebelFenix ($pedidoCrm);
                $row['PEDIDOFNX'] = $objRtaFenix['PEDIDOFNX'];
                $row['CONCEPTO_CRM'] = $objRtaFenix['CONCEPTOS'];

                $result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }


    private function Pendientespetec(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $concepto = $this->_request['concepto'];
        $login = $this->_request['login'];
        $today = date("Y-m-d h:i:s");

        if($concepto!="PETEC"){
            $concepto=" AND a.CONCEPTO_ID='$concepto' ";
        }else{
            $concepto="";
        }

        $checho="600.000";
        $this->response($this->json(array("este soy yo","otra vez yo",$checho)), 200); // send user details
        //$this->response('',204);        // If no records "No Content" status


    }

    private function csvPendientesAgendamientoInsta(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $concepto = $this->_request['concepto'];
        $login = $this->_request['login'];
        $today = date("Y-m-d h:i:s");
        $filename="PendiAgendamiento-$login-$today.csv";

        $query="SELECT pm.PEDIDO_ID ".
            "  , pm.FECHA_INGRESO ".
            "  , pm.FECHA_ESTADO ".
            "  , pm.FUENTE ".
            "  , pm.STATUS ".
            "   ,pm.NUMERO_CR ".
            "  , (SELECT hr.NOVEDAD FROM gestor_historicos_reagendamiento hr WHERE hr.ID = (SELECT MAX( a.id )  ".
            "       FROM gestor_historicos_reagendamiento a ".
            "        WHERE a.PEDIDO_ID =  pm.PEDIDO_ID) )AS ULTIMA_NOVEDAD ".
            "    , (SELECT hr.TIEMPO_TOTAL FROM gestor_historicos_reagendamiento hr WHERE hr.ID = (SELECT MAX( a.id ) ".
            "       FROM gestor_historicos_reagendamiento a ".
            "        WHERE a.PEDIDO_ID =  pm.PEDIDO_ID) )AS TIEMPO_TOTAL ".
            " ,pm.CONCEPTOS ".
            " ,pm.ACTIVIDADES ".
            " ,pm.FECHA_CITA_FENIX ".
            " ,pm.MIGRACION ".
            " ,pm.MICROZONA ".
            " ,pm.SUBZONA_ID ".
            " ,pm.CLIENTE_ID ".
            " ,pm.CELULAR_AVISAR ".
            " ,pm.CORREO_UNE ".
            " ,pm.DIRECCION_ENVIO ".
            " ,pm.E_MAIL_AVISAR ".
            " ,pm.NOMBRE_USUARIO ".
            " ,pm.TELEFONO_AVISAR ".
            " ,pm.RADICADO ".
            " ,pm.MUNICIPIO ".
            " ,pm.DEPARTAMENTO ".
            "  , pm.OBSERVACION_FENIX ".
            "  , pm.PROGRAMACION ".
            "  , pm.PROCESO ".
            "  , pm.TODAY_TRIES ".
            "  , pm.TIEMPO_SISTEMA ".
            "  , pm.FECHA_CITA_REAGENDA ".
            " FROM portalbd.gestor_pendientes_reagendamiento pm ".
            " WHERE pm.STATUS IN ('PENDI_AGEN',  'MALO') ".
            " AND pm.PROCESO = 'INSTALACION'";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO_ID','FECHA_INGRESO','FECHA_ESTADO','FUENTE','STATUS','NUMERO_CR','ULTIMA_NOVEDAD','TIEMPO_SISTEMA','CONCEPTOS','ACTIVIDADES','FECHA_CITA_FENIX','MIGRACION','MICROZONA','SUBZONA_ID','CLIENTE_ID','CELULAR_AVISAR','CORREO_UNE','DIRECCION_ENVIO','E_MAIL_AVISAR','NOMBRE_USUARIO','TELEFONO_AVISAR','RADICADO','MUNICIPIO','DEPARTAMENTO','OBSERVACION_FENIX','PROGRAMACION','PROCESO','INTENTOS DE CONTACTO','FECHA_CITA_REAGENDA'));
            while($row = $r->fetch_assoc()){

                $row['ULTIMA_NOVEDAD']=utf8_decode($row['ULTIMA_NOVEDAD']);
                $row['OBSERVACION_FENIX']=str_replace(array("\n","\r"), '/', $row['OBSERVACION_FENIX']);
                $result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO PENDIENTES' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }



    private function csvPendientesAgendamientoPredictiva(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $concepto = $this->_request['concepto'];
        $login = $this->_request['login'];
        $today = date("Y-m-d h:i:s");
        $filename="PendiAgendamientoPredictiva-$login-$today.csv";


        $query="SELECT pm.PEDIDO_ID ".
            "  , pm.CLIENTE_ID  ".
            ", pm.CELULAR_AVISAR ".
            "  , pm.TELEFONO_AVISAR ".
            " , pm.FUENTE ".
            " ,pm.NOMBRE_USUARIO ".
            " ,pm.ASESOR ".
            " ,pm.SUBZONA_ID ".
            " ,pm.DEPARTAMENTO ".
            " ,pm.PROCESO ".
            " ,pm.TECNOLOGIA_ID ".
            " , (SELECT hr.TIEMPO_TOTAL  FROM gestor_historicos_reagendamiento hr  WHERE hr.ID = (SELECT    MAX( a.id )".
            " FROM gestor_historicos_reagendamiento a ".
            " WHERE a.PEDIDO_ID =  pm.PEDIDO_ID) )AS TIEMPO_SISTEMA ".
            " FROM portalbd.gestor_pendientes_reagendamiento pm ".
            " WHERE pm.STATUS IN ('PENDI_AGEN') ";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO','CEDULA','PHONE1','PHONE2','APLICATIVO','NOMBRE','IDENTIFICADOR','CIUDAD','DEPTO','PROCESO','TECNOLOGIA','TIEMPO_SISTEMA','OBS1','OBS2','ID_CAMP','FILTRO_BD','IDSCRIPT'),chr (124));
            while($row = $r->fetch_assoc()){

                $result[] = $row;
                fputcsv($fp, $row,chr (124));
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO PREDICTIVA' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }



    private function csvPendientesAgenRepa(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $concepto = $this->_request['concepto'];
        $login = $this->_request['login'];
        $today = date("Y-m-d h:i:s");
        $filename="PendiAgendamiento-$login-$today.csv";

        $query="SELECT pm.PEDIDO_ID ".
            "  , pm.FECHA_INGRESO ".
            "  , pm.FECHA_ESTADO ".
            "  , pm.FUENTE ".
            "  , pm.STATUS ".
            "   ,pm.NUMERO_CR ".
            "  , (SELECT hr.NOVEDAD FROM gestor_historicos_reagendamiento hr WHERE hr.ID = (SELECT MAX( a.id )  ".
            "       FROM gestor_historicos_reagendamiento a ".
            "        WHERE a.PEDIDO_ID =  pm.PEDIDO_ID) )AS ULTIMA_NOVEDAD ".
            "    , (SELECT hr.TIEMPO_TOTAL FROM gestor_historicos_reagendamiento hr WHERE hr.ID = (SELECT MAX( a.id ) ".
            "       FROM gestor_historicos_reagendamiento a ".
            "        WHERE a.PEDIDO_ID =  pm.PEDIDO_ID) )AS TIEMPO_TOTAL ".
            " ,pm.CONCEPTOS ".
            " ,pm.ACTIVIDADES ".
            " ,pm.FECHA_CITA_FENIX ".
            " ,pm.MIGRACION ".
            " ,pm.MICROZONA ".
            " ,pm.SUBZONA_ID ".
            " ,pm.CLIENTE_ID ".
            " ,pm.CELULAR_AVISAR ".
            " ,pm.CORREO_UNE ".
            " ,pm.DIRECCION_ENVIO ".
            " ,pm.E_MAIL_AVISAR ".
            " ,pm.NOMBRE_USUARIO ".
            " ,pm.TELEFONO_AVISAR ".
            " ,pm.RADICADO ".
            " ,pm.MUNICIPIO ".
            " ,pm.DEPARTAMENTO ".
            "  , pm.OBSERVACION_FENIX ".
            "  , pm.PROGRAMACION ".
            "  , pm.PROCESO ".
            "  , pm.TODAY_TRIES ".
            "  , pm.TIEMPO_SISTEMA ".
            "  , pm.FECHA_CITA_REAGENDA ".
            " FROM portalbd.gestor_pendientes_reagendamiento pm ".
            " WHERE pm.STATUS IN ('PENDI_AGEN',  'MALO') ".
            " AND pm.PROCESO = 'REPARACION'";

        //" and CONCEPTO_ID = '' ";
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO_ID','FECHA_INGRESO','FECHA_ESTADO','FUENTE','STATUS','NUMERO_CR','ULTIMA_NOVEDAD','TIEMPO_SISTEMA','CONCEPTOS','ACTIVIDADES','FECHA_CITA_FENIX','MIGRACION','MICROZONA','SUBZONA_ID','CLIENTE_ID','CELULAR_AVISAR','CORREO_UNE','DIRECCION_ENVIO','E_MAIL_AVISAR','NOMBRE_USUARIO','TELEFONO_AVISAR','RADICADO','MUNICIPIO','DEPARTAMENTO','OBSERVACION_FENIX','PROGRAMACION','PROCESO','INTENTOS DE CONTACTO','FECHA_CITA_REAGENDA'));
            while($row = $r->fetch_assoc()){

                $row['ULTIMA_NOVEDAD']=utf8_decode($row['ULTIMA_NOVEDAD']);
                $row['OBSERVACION_FENIX']=str_replace(array("\n","\r"), '/', $row['OBSERVACION_FENIX']);
                $result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }



    private function csvMalosAgendamiento(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $login = $this->_request['login'];
        $today = date("Y-m-d h:i:s");


        $filename="Malos-$login-$today.csv";

        $query="Select ".
            " pm.PEDIDO_ID".
            ", pm.FECHA_INGRESO".
            ", pm.FECHA_ESTADO".
            ", pm.FUENTE".
            ", pm.STATUS".
            ", pm.CONCEPTOS ".
            ", pm.FECHA_CITA_FENIX ".
            "  , (SELECT hr.NOVEDAD FROM gestor_historicos_reagendamiento hr WHERE hr.ID = (SELECT MAX( a.id )  ".
            "     FROM gestor_historicos_reagendamiento a ".
            "     WHERE a.PEDIDO_ID =  pm.PEDIDO_ID) )AS ULTIMA_NOVEDAD ".
            ", pm.MICROZONA".
            ", pm.SUBZONA_ID".
            ", pm.PROCESO".
            ", replace(pm.OBSERVACION_FENIX,'|',' ') as OBSERVACION_FENIX ".
            " from portalbd.gestor_pendientes_reagendamiento pm ".
            " where pm.STATUS='MALO' ".
            " and pm.PROCESO = 'INSTALACION' ";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO_ID','FECHA_INGRESO','FECHA_ESTADO','FUENTE','STATUS','CONCEPTOS','FECHA_CITA','ULTIMA_NOVEDAD','MICROZONA','ZONA','PROCESO','OBSERVACION'));
            while($row = $r->fetch_assoc()){

                //$row['OBSERVACION_FENIX']=str_replace(array("\n","\r"), '/', $row['OBSERVACION_FENIX']);


                if($row['ULTIMA_NOVEDAD']=='AGENDADO_FUTURO'||$row['ULTIMA_NOVEDAD']=='AGENDADO'){
                    //echo "detecte agendado futuro!!!\n";
                    $sqlhis="select CONCAT(date_format(FECHA_CITA_REAGENDA,'%Y-%m-%d'),' - ',JORNADA_CITA) AS FECHA_CC from gestor_historicos_reagendamiento WHERE PEDIDO_ID='".$row['PEDIDO_ID']."' order by id desc limit 1";
                    //echo "consulta: $sqlhis\n";
                    $r2 = $this->mysqli->query($sqlhis);

                    if($r2->num_rows > 0){
                        //echo "si hay registros\n";
                        if($row2 = $r2->fetch_assoc()){
                            $row['FECHA_CITA_FENIX']=$row2['FECHA_CC'];
                        }
                    }

                }


                $row['ULTIMA_NOVEDAD']=utf8_decode($row['ULTIMA_NOVEDAD']);
                $row['OBSERVACION_FENIX']= trim(preg_replace('/\s+|,', ' ',$row['OBSERVACION_FENIX']));


                $result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO MALOS' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }
    private function csvMalosAgendamientoReparaciones(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $login = $this->_request['login'];
        $today = date("Y-m-d h:i:s");


        $filename="Malos-$login-$today.csv";

        $query="Select ".
            " pm.PEDIDO_ID".
            ", pm.FECHA_INGRESO".
            ", pm.FECHA_ESTADO".
            ", pm.FUENTE".
            ", pm.STATUS".
            ", pm.CONCEPTOS ".
            ", pm.FECHA_CITA_FENIX ".
            "  , (SELECT hr.NOVEDAD FROM gestor_historicos_reagendamiento hr WHERE hr.ID = (SELECT MAX( a.id )  ".
            "     FROM gestor_historicos_reagendamiento a ".
            "     WHERE a.PEDIDO_ID =  pm.PEDIDO_ID) )AS ULTIMA_NOVEDAD ".
            ", pm.MICROZONA".
            ", pm.SUBZONA_ID".
            ", pm.OBSERVACION_FENIX".
            ", pm.PROCESO".
            " from portalbd.gestor_pendientes_reagendamiento pm ".
            " where pm.STATUS='MALO' ".
            " and pm.PROCESO = 'REPARACION' ";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO_ID','FECHA_INGRESO','FECHA_ESTADO','FUENTE','STATUS','CONCEPTOS','FECHA_CITA','ULTIMA_NOVEDAD','MICROZONA','ZONA','OBSERVACION','PROCESO'));
            while($row = $r->fetch_assoc()){

                $row['OBSERVACION_FENIX']=str_replace(array("\n","\r"), '/', $row['OBSERVACION_FENIX']);


                if($row['ULTIMA_NOVEDAD']=='AGENDADO_FUTURO'||$row['ULTIMA_NOVEDAD']=='AGENDADO'){
                    //echo "detecte agendado futuro!!!\n";
                    $sqlhis="select CONCAT(date_format(FECHA_CITA_REAGENDA,'%Y-%m-%d'),' - ',JORNADA_CITA) AS FECHA_CC from gestor_historicos_reagendamiento WHERE PEDIDO_ID='".$row['PEDIDO_ID']."' order by id desc limit 1";
                    //echo "consulta: $sqlhis\n";
                    $r2 = $this->mysqli->query($sqlhis);

                    if($r2->num_rows > 0){
                        //echo "si hay registros\n";
                        if($row2 = $r2->fetch_assoc()){
                            $row['FECHA_CITA_FENIX']=$row2['FECHA_CC'];
                        }
                    }

                }


                $row['ULTIMA_NOVEDAD']=utf8_decode($row['ULTIMA_NOVEDAD']);
                //                    $row['OBSERVACION_FENIX']= trim(preg_replace('/\s+|', ' ',$row['OBSERVACION_FENIX']));


                $result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO MALOS' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }



    private function csvAGENToday(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $login = $this->_request['login'];
        $today = date("Y-m-d");


        $filename="PENDI-AGEN-$login-$today.csv";

        $this->dbFenixSTBYConnect();
        $connfstby=$this->connfstby;


        $sqlfenix=" SELECT DISTINCT OT.PEDIDO_ID  ".
            "  ,TO_CHAR(OT.FECHA_ENTREGA, 'RRRR-MM-DD') AS FECHA_CITA_FENIX  ".
            "  FROM FNX_ORDENES_TRABAJOS OT ".
            "         WHERE 1=1  ".
            "         and OT.ESTADO_ID ='PENDI'  ".
            "         AND OT.ETAPA_ID ='INSTA'  ".
            "         AND OT.CONCEPTO_ID = 'AGEN' ".
            "         AND OT.ACTIVIDAD_ID IN ('INSPA','MIRED','INSTA','INCAM','IADSL','EXTEN','ITOIP','IGPON')  ".
            "         AND OT.MARCA_ID='HG'  ".
            "         AND TO_CHAR(OT.FECHA_ENTREGA, 'RRRR-MM-DD')='$today' ";

        $stid = oci_parse($connfstby, $sqlfenix);
        oci_execute($stid);
        $fp = fopen("../tmp/$filename", 'w');
        fputcsv($fp, array('PEDIDO_ID','FECHA_CITA_FENIX'));

        while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
            fputcsv($fp, $row);
        }

        fclose($fp);
        $this->response($this->json(array($filename,$login)), 200);

    }



    private function csvMalos(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $concepto = $this->_request['concepto'];
        $login = $this->_request['login'];
        $today = date("Y-m-d h:i:s");

        if($concepto!="TODO"){
            $concepto=" AND pm.CONCEPTO_ID='$concepto' ";
        }else{
            $concepto="";
        }

        $filename="Malos-$login-$today.csv";

        $query= " Select ".
            "     pm.PEDIDO_ID  ".
            "    , pm.FECHA_CITA ".
            "    , min(pm.FECHA_INGRESO) as FECHA_INGRESO ".
            "    , max(pm.FECHA_ESTADO) as FECHA_ESTADO ".
            "    , group_concat(distinct pm.CONCEPTO_ID) as CONCEPTO_ID ".
            "    , pm.FUENTE ".
            "    , pm.STATUS ".
            "    , (Select  p.motivo_malo as motivo  ".
            "    from portalbd.pedidos p   ".
            "    where p.id = (select max(d.id) from portalbd.pedidos d where d.estado='MALO'  and d.pedido_id=pm.pedido_id group by d.pedido_id)) as MOTIVO_MALO ".
            "    , (Select  p.INCIDENTE ".
            "    from portalbd.pedidos p   ".
            "    where p.id = (select max(d.id) from portalbd.pedidos d where d.estado='MALO'  and d.pedido_id=pm.pedido_id group by d.pedido_id)) as INCIDENTE ".
            "    , (Select  p.user as motivo  ".
            "    from portalbd.pedidos p  ".
            "    where p.id = (select max(d.id) from portalbd.pedidos d where d.estado='MALO'  and d.pedido_id=pm.pedido_id group by d.pedido_id)) as USUARIO ".
            "    , (Select  p.fecha_fin as fecha  ".
            "    from portalbd.pedidos p  ".
            "    where p.id = (select max(d.id) from portalbd.pedidos d where d.estado='MALO'  and d.pedido_id=pm.pedido_id group by d.pedido_id)) as FECHAMALO ".
            "    from portalbd.informe_petec_pendientesm pm   ".
            "    where   ".
            "    pm.status='MALO'  ".
            "    group by pm.PEDIDO_ID  ";
        // $concepto;
        //" and CONCEPTO_ID = '' ";
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO_ID','FECHA_CITA','FECHA_INGRESO','FECHA_ESTADO','CONCEPTO_ID', 'FUENTE','STATUS','MOTIVO_MALO','INCIDENTE','USUARIO','FECHAMALO'));
            while($row = $r->fetch_assoc()){
                $result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO MALOS' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }

    private function buscarPedidoRegistro(){//pendientes
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $bpedido = $this->_request['bpedido'];
        $concepto = $this->_request['concepto'];


        if($concepto!="TODO"){
            if($concepto=="PETEC"){
                $concepto=" and a.CONCEPTO_ID IN ('PETEC','OKRED') ";
            }else{
                $concepto=" and a.CONCEPTO_ID='$concepto' ";
            }
        }else{
            $concepto="";
        }

        //$in_stmt = "'".str_replace(" ", "','", $bpedido)."'";

        $query="SELECT a.ID,a.PEDIDO_ID,a.PEDIDO,a.SUBPEDIDO_ID,a.SOLICITUD_ID ".
            ", a.TIPO_ELEMENTO_ID, a.PRODUCTO, a.UEN_CALCULADA ".
            ", a.ESTRATO, a.MUNICIPIO_ID, a.DIRECCION_SERVICIO, a.PAGINA_SERVICIO ".
            ", CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(a.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA ".
            ", a.FUENTE, a.CONCEPTO_ID, a.FECHA_ESTADO, a.FECHA_CITA, a.STATUS, a.PROGRAMACION ".
            ", RADICADO_TEMPORAL ".
            ", ifnull((Select  p.OBSERVACIONES_PROCESO from portalbd.pedidos p  where 1=1  and estado_id='MALO'  and p.pedido_id=a.pedido_id  order by p.id desc   limit 1 ),'Sin Observaciones') as OBS ".
            " from informe_petec_pendientesm a ".
            " where (a.STATUS='PENDI_PETEC' or a.STATUS='MALO') $concepto ".
            " AND a.PEDIDO_ID LIKE '$bpedido%' ".
            " order by a.FECHA_ESTADO ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$bpedido' ".
                ",'BUSCO PEDIDO REGISTRADO' ".
                ",'PEDIDO BUSCADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }



    private function buscarPedidoAgendamientoRegistro(){//pendientes
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $bpedido = $this->_request['bpedido'];

        //echo $query;
        $query="SELECT a.ID,a.PEDIDO_ID,a.CONCEPTOS,a.ACTIVIDADES,a.MICROZONA,a.SUBZONA_ID,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(a.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,a.FUENTE,a.FECHA_ESTADO, a.FECHA_CITA_FENIX,a.STATUS,a.PROGRAMACION,a.DEPARTAMENTO from gestor_pendientes_reagendamiento a where (a.STATUS='PENDI_AGEN' or a.STATUS='MALO') AND a.PEDIDO_ID LIKE '$bpedido%' order by a.FECHA_ESTADO ASC ";


        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$bpedido' ".
                ",'BUSCO PEDIDO' ".
                ",'PEDIDO BUSCADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }



    private function buscarPedidoAgendamientoRegistro1(){//pendientes
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $page = $this->_request['page'];
        $bpedido = $this->_request['bpedido'];


        if($page=="undefined"){
            $page="0";
        }else{
            $page=$page-1;
        }

        $page=$page*100;

        $query=" SELECT id, pedido_id, fuente, actividad_gestor, fecha_fin, duracion, novedad, asesor ".
            " ,fecha_cita_reagenda,conceptos,actividades,proceso ".
            " from gestor_historicos_reagendamiento ".
            " where PEDIDO_ID LIKE '$bpedido%' ".
            " order by fecha_fin desc limit 100 offset $page ";
        //echo $query;

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            $this->response($this->json(array($result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status
    }


    private function BuscarDatos(){//pendientes
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $usuario = $this->_request['usuario'];




        $query="  SELECT * ".
            "from transacciones_actividades ".
            " where USUARIO LIKE '$usuario%' ".
            " order by fecha_fin desc ";
        //echo $query;

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            $this->response($this->json(array($result)), 200); // send user details
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

        $query= "SELECT a.ID ".
            ", a.PEDIDO_ID, a.PEDIDO, a.SUBPEDIDO_ID, a.SOLICITUD_ID ".
            ", a.TIPO_ELEMENTO_ID, a.PRODUCTO, a.UEN_CALCULADA ".
            ", a.ESTRATO, a.MUNICIPIO_ID, a.DIRECCION_SERVICIO, a.PAGINA_SERVICIO ".
            ", cast(my_sec_to_time(timestampdiff(second,FECHA_INGRESO,current_timestamp()))AS CHAR(255)) as TIEMPO_COLA ".
            ", a.FUENTE, a.CONCEPTO_ID, a.FECHA_ESTADO, a.FECHA_CITA, a.STATUS, a.PROGRAMACION ".
            ", case when a.RADICADO_TEMPORAL in ('ARBOL','INMEDIAT') then 'ARBOL' else a.RADICADO_TEMPORAL end as RADICADO_TEMPORAL ".
            ", if(a.RADICADO_TEMPORAL='ARBOL','true','false') as PRIORIDAD ".
            ", ifnull((Select  p.OBSERVACIONES_PROCESO from portalbd.pedidos p  where 1=1  and estado_id='MALO'  and p.pedido_id=a.pedido_id  order by p.id desc   limit 1 ),'Sin') as OBS ".
            " from informe_petec_pendientesm a ".
            " where (a.STATUS='PENDI_PETEC' or a.STATUS='MALO') $concepto ".
            " order by a.FECHA_ESTADO ASC limit 100 offset $page";
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



    private function listadoPendientesAgendamiento(){//pendientes
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $fechaini = $this->_request['fecha_inicio'];
        $fechafin = $this->_request['fecha_fin'];
        //$concepto = $this->_request['concepto'];
        $page = $this->_request['page'];
        $today = date("Y-m-d");

        if($page=="undefined"){
            $page="0";
        }else{
            $page=$page-1;
        }

        $page=$page*100;


        //calcular counter
        $query="SELECT count(*) as counter from gestor_pendientes_reagendamiento a where (a.STATUS='PENDI_AGEN' or a.STATUS='MALO') ";

        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter = $row['counter'];
            }
        }

        $query="SELECT count(*) as counter from gestor_pendientes_reagendamiento a where (a.STATUS='MALO') and (a.PROCESO='REPARACION') ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $malo=0;
        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $malo = $row['counter'];
                //echo $malo;
            }
        }

        $query="SELECT count(*) as counter from gestor_pendientes_reagendamiento a where (a.STATUS='MALO') and (a.PROCESO='INSTALACION') ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $malo1=0;
        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $malo1 = $row['counter'];
                //echo $malo;
            }
        }

        $query="SELECT count(*) as counter from gestor_pendientes_reagendamiento a where (a.STATUS in ('PENDI_AGEN','MALO')) and (a.PROCESO='INSTALACION') ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter1=0;
        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $counter1 = $row['counter'];
                //echo $malo;
            }
        }

        $query="SELECT count(*) as counter from gestor_pendientes_reagendamiento a where (a.STATUS in ('PENDI_AGEN','MALO')) and (a.PROCESO='REPARACION') ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter2=0;
        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $counter2 = $row['counter'];
                //echo $malo;
            }
        }

        $query= "SELECT ".
            " a.ID, ".
            " a.PEDIDO_ID, ".
            " a.CONCEPTOS, ".
            " a.ACTIVIDADES, ".
            " a.MICROZONA, ".
            " a.SUBZONA_ID, ".
            " cast(my_sec_to_time(timestampdiff(second,if(a.FECHA_ESTADO='0000-00-00 00:00:00',FECHA_CARGA,a.FECHA_INGRESO),current_timestamp()))AS CHAR(255)) as TIEMPO_COLA, ".
            " a.FUENTE, ".
            " if(a.FECHA_ESTADO='0000-00-00 00:00:00',FECHA_CARGA,a.FECHA_ESTADO) as FECHA_ESTADO,  ".
            " a.FECHA_CITA_FENIX, ".
            " a.STATUS, ".
            " a.PROGRAMACION, ".
            " a.DEPARTAMENTO, ".
            " a.PROCESO, ".
            " a.TIPO_TRABAJO, ".
            " a.RADICADO ".
            " from gestor_pendientes_reagendamiento a ".
            " where (a.STATUS='PENDI_AGEN' or a.STATUS='MALO') ".
            " order by a.FECHA_ESTADO ASC limit 100 offset $page";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            $this->response($this->json(array($result,$counter,$malo,$malo1,$counter1,$counter2)), 200); // send user details
        }
        $error = "Sin registros ";
        $this->response($this->json(array($error)),403); // send user details
    }

//------------------------listado activacion-----------------

    private function listadoactivacion(){

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
         $query="SELECT count(*) as counter ".
            " from gestor_historico_activacion ".
            " where fecha_fin between '$fechaini 00:00:00' ".
            " and '$fechafin 23:59:59'  order by fecha_fin desc limit 100 offset $page";

        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter = $row['counter'];
            }
        }
         $query="SELECT  COUNT(*) as counter from gestor_activacion_pendientes_activador_suspecore where status in ('PENDI_ACTI','MALO')";


        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter1=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter1 = $row['counter'];
            }
        }
        $query="SELECT  COUNT(*) as counter from gestor_activacion_pendientes_activador_dom where status in ('PENDI_ACTI','MALO')";


        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter2=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter2 = $row['counter'];
            }
        }
         $query=" SELECT COUNT(*) as counter FROM  informe_activacion_pendientesm  WHERE  STATUS ='PENDI_ACTIVACION' ";


        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter3=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter3 = $row['counter'];
            }
        }
        $query=" select COUNT(*) as counter FROM pendientes_amarillas b where b.STATUS in ('PENDI_ACTI','MALO') ";


        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter4=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter4 = $row['counter'];
            }
        }

        $query= "SELECT ORDER_SEQ_ID,PEDIDO, ESTADO, FECHA_CREACION, FECHA_EXCEPCION,TRANSACCION ".
            " , PRODUCTO,ASESOR,FECHA_GESTION,TIPIFICACION,FECHA_INICIO,FECHA_FIN,TABLA ".
            " ,my_sec_to_time(timestampdiff(second,fecha_inicio,fecha_fin)) as DURACION ".
            " from gestor_historico_activacion ".
            " where fecha_fin between '$fechaini 00:00:00' ".
            " and '$fechafin 23:59:59'  order by fecha_fin desc limit 100 offset $page";


        //echo $query;

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $total=0;
            while($row = $r->fetch_assoc()){

                $result[] = $row;

                // var_dump($result);
            }

            $this->response($this->json(array($result,$counter,$counter1,$counter2,$counter3,$counter4)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

//-----------------------fin listado activacion
    private function listadoactivaciontabla(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);

        }

        $fechaini = $this->_request['fecha_inicio'];
        $fechafin = $this->_request['fecha_fin'];
        $today = date("Y-m-d");


        $query= " SELECT cola_id as label, COUNT(*) as value,FECHA_ENTRADA_GESTOR,FECHA_ULTIMA_GESTOR ".
            " from gestor_seguimiento_activacion ".
            " where FECHA_ULTIMA_GESTOR between '$fechaini 00:00:00' and '$fechafin 23:59:59' ".
            " GROUP BY cola_id ".
            " ORDER BY COUNT(*) ASC ";


        //echo $query;
        //echo $param  ;
        // var_dump($query);
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $totales=array();
            $total=0;
            $i=0;
            while($row = $r->fetch_assoc()){
                $label=$row['label'];
                $total=$row['value'];
                $totales[]=array("value"=>"$total");
                $result[] = $row;
                $i++;

                // var_dump($result);
            }

            $this->response($this->json(array($totales,$result,$i)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }


    private function pedidosPorPedido(){//historico por 1 pedido
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $pedido = $this->_request['pedido'];
        $today = date("Y-m-d");
        $query="SELECT id, pedido, fuente, actividad, fecha_estado,fecha_inicio,fecha_fin, estado,INCIDENTE,duracion,user,concepto_final from pedidos where pedido like '$pedido%' order by fecha_fin desc limit 10";
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


    //-------------------------------------inicion pedido por pedido activacion

    private function pedidosPorPedidoActivacion(){//historico por 1 pedido
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $pedido = $this->_request['pedido'];
        $today = date("Y-m-d");
        $tabla= $this->_request['tabla'];



        if($tabla=='ACTIVADOR_SUSPECORE'){

            $tabla = " from gestor_activacion_pendientes_activador_suspecore b " ;

        } else {

            $tabla = " from gestor_activacion_pendientes_activador_dom b " ;

        }


        $query=" SELECT id, order_seq_id,pedido,reference_number ".
            ",estado,fecha_creacion,tarea_excepcion ".
            ",fecha_excepcion,producto,idservicioraiz,transaccion ".
            $tabla.
            " where pedido like '$pedido%' order by fecha_excepcion desc   10 ";

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

    //-------------------------------------fin pedido por pedido activacion

    //-------------------------------------inicion pedido por pedido activacion

    private function pedidosPorPedidoAmarillas(){//historico por 1 pedido
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $pedido = $this->_request['pedido'];
        $today = date("Y-m-d");




        $query=" SELECT id, IDGRUPOAGENDA,PEDIDO,IDESTADO ".
            ",NOMBRE,FECHADIAGENDA,FECHACARGA ".
            ",OFERTAPEDIDO,DEPARTAMENTO ".
            " from getpedidosPorPedidoAmarillas  ".
            " where pedido like '$pedido%' order by fecha_excepcion desc limit 10 ";

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

    //-------------------------------------fin pedido por pedido activacion

    private function vecinosPagina(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $pagina_inicial = $this->_request['pagina_inicial'];
        $pagina_final = $this->_request['pagina_final'];

        $this->dbFenixSTBYConnect();
        $connfstby=$this->connfstby;


        $query="SELECT ".
            "	 FN_VALOR_CARACT_IDENTIF(I.IDENTIFICADOR_ID, '35') AS DIRECCION  ".
            "	, I.TIPO_ELEMENTO_ID AS ELEM   ".
            "	, I.IDENTIFICADOR_ID AS IDENTIF   ".
            "	, SUBSTR(FN_VALOR_CARACT_IDENTIF(I.IDENTIFICADOR_ID, '34'),1,6) AS MUNI_ID   ".
            "	, I.TECNOLOGIA_ID as TECNO   ".
            "  	, NVL(TO_CHAR(RA.NODO_CONMUTADOR_ID),'--') AS NOE_RED ".
            "	, NVL(TO_CHAR(RA.ARMARIO_ID),'--') AS ARM_R ".
            "	, NVL(TO_CHAR(RA.STRIP_ID),'--')  AS STRIP  ".
            "	, NVL(TO_CHAR(RA.PAR_PRIMARIO_ID),'--')  AS PRIM  ".
            "	, NVL(TO_CHAR(RA.CAJA_ID),'--')  AS CAJA  ".
            "	, NVL(DECODE(N.DIRECCION,'U','UNID','B','BIDI'),'--')  AS TEC   ".
            "	, NVL(TO_CHAR(TA.CENTRO_DISTRIBUCION_INTERMEDIA),'--')  AS CDI   ".
            "   , NVL(TO_CHAR(TA.NODO_OPTICO_ELECTRICO_ID),'--')  AS NOE  ".
            "	, NVL(TO_CHAR(TA.AMPLIFICADOR_ID),'--')  AS AMP  ".
            "	, NVL(TO_CHAR(TA.TIPO_DERIVADOR_ID),'--') AS  TIPO_DER  ".
            "	, NVL(TO_CHAR(TA.DERIVADOR_ID),'--') AS DER   ".
            "  	, NVL(TO_CHAR(TA.TERMINAL_DERIVADOR_ID),'--')  AS TAP ".
            "	, NVL(TO_CHAR(GA.OLT_ID),'--') AS OLT   ".
            "	, NVL(TO_CHAR(GA.ARMARIO_ID),'--') AS ARM_G  ".
            "	, NVL(TO_CHAR(GA.SPLITTER_ID),'--') AS SPLTT   ".
            "	, NVL(TO_CHAR(GA.NAP_ID),'--') AS NAP    ".
            "	, NVL(TO_CHAR(GA.HILO_ID),'--')  AS HILO  ".
            "   FROM FNX_IDENTIFICADORES I ".
            "	, FNX_INF_REDES_ACTIVAS RA  ".
            "	, FNX_CONFIGURACIONES_IDENTIF CI  ".
            "	, FNX_INF_TV_ACTIVAS TA    ".
            "  	, FENIX.FNX_INF_GPON_ACTIVAS GA ".
            "  	, FENIX.FNX_NODOS_OPTICOS_ELECTRICO N ".
            "	WHERE (CI.VALOR>='$pagina_inicial'  ".
            "	AND CI.VALOR<='$pagina_final'".
            "	AND I.TECNOLOGIA_ID<>'LOGIC'  ".
            "	AND I.ESTADO NOT IN ('LIB','RET') ".
            "	AND I.TIPO_ELEMENTO_ID NOT IN ('TELEV')  ".
            "	AND CI.CARACTERISTICA_ID='38') ".
            "	AND  ((I.IDENTIFICADOR_ID=CI.IDENTIFICADOR_ID)  ".
            "	AND (I.IDENTIFICADOR_ID=RA.IDENTIFICADOR_ID(+)) ".
            "	AND (I.IDENTIFICADOR_ID=TA.IDENTIFICADOR_ID(+))  ".
            "	AND (I.IDENTIFICADOR_ID=GA.IDENTIFICADOR_ID(+))  ".
            "	AND (TA.CENTRO_DISTRIBUCION_INTERMEDIA=N.CENTRO_DISTRIBUCION_INTERMEDIA(+))  ".
            "	AND (TA.NODO_OPTICO_ELECTRICO_ID=N.NODO_OPTICO_ELECTRICO_ID(+))) ".
            "   order by FN_VALOR_CARACT_IDENTIF(I.IDENTIFICADOR_ID, '38') asc";
        //echo $query;
        //var_dump($query);

        $stid = oci_parse($connfstby, $query);
        oci_execute($stid);
        //$numero_filas = oci_num_rows($stid);
        $returnn="No rows!!!!";
        //$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $result = array();
        $numero_filas = 0;
        while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
            //echo $concepto_id." - ".$row['CONCEPTO_ID']."\n";
            $result[] = $row;
            $numero_filas ++;
        }
        //echo $numero_filas;
        $this->response($this->json(array($numero_filas, $result) ), 200);

    }


    //BuscarPedido Funcion para buscar pedidos en la forma de DemePedido
    private function buscarPedido(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido = $this->_request['pedidoID'];
        $plaza = $this->_request ['plaza'];
        $user = $this->_request['userID'];
        $username = $this->_request['username'];
        //si el actual usuario tenia un pedido "agarrado, hay que liberarlo"
        $pedido_actual = $this->_request['pedido_actual'];

        //if($pedido_actual!=''){//en este caso tenia pedido antes, estaba trabajando uno, debo actualizarlo para dejarlo libre
        $sqlupdate="update informe_petec_pendientesm set ASESOR='' where ASESOR='$user' ";
        $xxx = $this->mysqli->query($sqlupdate);
        //}


        //1. CIERRO  LO QUE ESTE ABIERTO DE ESTE PEDIDO EN EL GESTOR..
        //
        //
        $sqlOcupado="select  PEDIDO_ID, ASESOR from informe_petec_pendientesm where PEDIDO_ID='$pedido' and ASESOR!='' group by PEDIDO_ID, ASESOR";
        $rOcu = $this->mysqli->query($sqlOcupado) or die($this->mysqli->error.__LINE__);

        if($rOcu->num_rows > 0){

            $row = $rOcu->fetch_assoc();

            $asesorito=$row['ASESOR'];


            //json_encode($result)
            $this->response(json_encode(array("PEDIDO_OCUPADO",$asesorito,$pedido)),200);


        }else{

            $sql="update informe_petec_pendientesm set status='CERRADO_PETEC' where PEDIDO_ID='$pedido' AND CONCEPTO_ID NOT IN ('14','99') and ASESOR = '' ";

            $r = $this->mysqli->query($sql) or die($this->mysqli->error.__LINE__);

            if ($plaza=="BOG-COBRE"){//pregunta si se debe buscar en fenix Bogotá o se debe buscar en fenix nacional por medio de la plaza.
                //echo "Esta Entrando por aca para llamar a fenix Bogotá";
                $success=$this->buscarPedidoFenixBogota($pedido);

            } else{
                $success=$this->buscarPedidoFenix($pedido);
            }
            if($success=="OK"){//logro encontrar el pedido en fenix he hizo el insert local...
                //recursion?????
                //$this->buscarPedido();
                $query1="SELECT ".
                    " a.ID, ".
                    " a.PEDIDO_ID, ".
                    " a.SUBPEDIDO_ID, ".
                    " a.SOLICITUD_ID, ".
                    " a.TIPO_TRABAJO, ".
                    " a.DESC_TIPO_TRABAJO, ".
                    " a.VEL_IDEN,  ".
                    " a.VEL_SOLI, ".
                    " a.IDENTIFICADOR_ID, ".
                    " a.TIPO_ELEMENTO_ID, ".
                    " a.PRODUCTO , ".
                    " a.PRODUCTO_ID, ".
                    " a.UEN_CALCULADA, ".
                    " a.ESTRATO, ".
                    "  CASE ".
                    "	 WHEN a.DESC_TIPO_TRABAJO='NUEVO-Identificador' AND a.TIPO_ELEMENTO_ID!='EQURED' AND a.UEN_CALCULADA IN ('HG','E3') AND a.ESTRATO='0' THEN TRUE ".
                    "    WHEN a.DESC_TIPO_TRABAJO='NUEVO-Identificador' AND a.TIPO_ELEMENTO_ID!='EQURED' AND a.UEN_CALCULADA IN ('HG','E3') AND a.ESTRATO='' THEN TRUE ".
                    "    WHEN a.DESC_TIPO_TRABAJO='NUEVO-Identificador' AND a.TIPO_ELEMENTO_ID!='EQURED' AND a.UEN_CALCULADA IN ('HG','E3') AND a.PAGINA_SERVICIO='' THEN TRUE ".
                    "    ELSE FALSE ".
                    "    END AS ESTRATOMALO, ".
                    " a.MUNICIPIO_ID, ".
                    " a.DIRECCION_SERVICIO, ".
                    " a.PAGINA_SERVICIO, ".
                    " CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA, ".
                    " a.FUENTE, ".
                    " a.GRUPO, ".
                    " a.ACTIVIDAD, ".
                    " a.CONCEPTO_ID, ".
                    " a.FECHA_ESTADO, ".
                    " a.ASESOR, ".
                    " a.STATUS, ".
                    " a.CONCEPTO_ANTERIOR, ".
                    " a.FECHA_CITA, ".
                    " a.CANTIDAD_EQU, ".
                    " a.EQUIPOS, ".
                    " a.CONCEPTOS_EQU, ".
                    " a.TIPO_EQUIPOS, ".
                    " a.EXTENSIONES, ".
                    " a.OBSERVACIONES, ".
                    " a.EJECUTIVO_ID, ".
                    " a.CANAL_ID, ".
                    " a.CELULAR_AVISAR, ".
                    " a.PROGRAMACION, ".
                    " a.PEDIDO_CRM, ".
                    " a.TELEFONO_AVISAR from informe_petec_pendientesm a ".
                    " JOIN (SELECT distinct(a.pedido) as pedido2,(select b.id from informe_petec_pendientesm b ".
                    " where b.pedido=a.pedido order by id asc limit 1 ) as id2 ".
                    " FROM `informe_petec_pendientesm` a ".
                    " WHERE a.PEDIDO_ID='$pedido' and a.CONCEPTO_ID NOT IN ('14','99') ".
                    " and (a.STATUS='PENDI_PETEC' or a.STATUS='BUSCADO_PETEC' or a.STATUS='MALO')) kai on a.id=kai.id2 ";

                $r = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);
                $busy="";

                if($r->num_rows > 0){
                    $result = array();
                    $ids="";
                    $sep="";
                    while($row = $r->fetch_assoc()){
                        $row['source']='BUSCADO';
                        $direccion = $this->quitar_tildes(utf8_encode($row['DIRECCION_SERVICIO']));
                        $observaciones = $this->quitar_tildes(utf8_encode($row['OBSERVACIONES']));
                        $row['OBSERVACIONES'] = $observaciones;
                        $row['DIRECCION_SERVICIO'] = $direccion;
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
                        $fecha_visto=date("Y-m-d H:i:s");
                        $sqlupdate="update informe_petec_pendientesm set VIEWS=VIEWS+1,ASESOR='$user',FECHA_VISTO_ASESOR='$fecha_visto' where ID in ($ids)";
                    }

                    $x = $this->mysqli->query($sqlupdate);
                    // SQL Feed----------------------------------
                    $sql_log=   "insert into portalbd.activity_feed ( ".
                        " USER ".
                        ", USER_NAME ".
                        ", GRUPO ".
                        ", STATUS ".
                        ", PEDIDO_OFERTA ".
                        ", ACCION ".
                        ", CONCEPTO_ID ".
                        ", IP_HOST ".
                        ", CP_HOST ".
                        ") values( ".
                        " UPPER('$user')".
                        ", UPPER('$username')".
                        ", UPPER('$grupoGalleta')".
                        ",'OK' ".
                        ",'$pedido' ".
                        ",'BUSCO PEDIDO' ".
                        ",'PEDIDO BUSCADO' ".
                        ",'$usuarioIp' ".
                        ",'$usuarioPc')";

                    $rlog = $this->mysqli->query($sql_log);
                    // ---------------------------------- SQL Feed
                    //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$user','$username','','','PEDIDO: $pedido','BUSCARPEDIDO','') ";
                    //$xx = $this->mysqli->query($sqlfeed);
                    $this->response(json_encode($result), 200); // send user details
                }



            }



            /*


                        if($pedido_actual!=''){//en este caso tenia pedido antes, estaba trabajando uno, debo actualizarlo para dejarlo libre
                                $sqlupdate="update informe_petec_pendientesm set ASESOR='' where ASESOR='$user' ";
                                $xxx = $this->mysqli->query($sqlupdate);
                        }

                        $user=strtoupper($user);
                        $today = date("Y-m-d");

                        $query1="SELECT a.ID,a.PEDIDO_ID,a.SUBPEDIDO_ID,a.SOLICITUD_ID, a.TIPO_TRABAJO, a.DESC_TIPO_TRABAJO, a.VEL_IDEN, a.VEL_SOLI, a.IDENTIFICADOR_ID, a.TIPO_ELEMENTO_ID, a.PRODUCTO_ID, a.PRODUCTO,a.UEN_CALCULADA,a.ESTRATO,MUNICIPIO_ID,a.DIRECCION_SERVICIO,a.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,a.FUENTE,a.CONCEPTO_ID,a.FECHA_ESTADO,a.ASESOR,a.STATUS,a.CONCEPTO_ANTERIOR,a.FECHA_CITA,a.CANTIDAD_EQU,a.EQUIPOS,a.CONCEPTOS_EQU,a.TIPO_EQUIPOS,a.EXTENSIONES,a.OBSERVACIONES, a.EJECUTIVO_ID, a.CANAL_ID, a.CELULAR_AVISAR, a.TELEFONO_AVISAR from informe_petec_pendientesm a JOIN (SELECT distinct(a.pedido) as pedido2,(select b.id from informe_petec_pendientesm b where b.pedido=a.pedido order by id desc limit 1 ) as id2 FROM `informe_petec_pendientesm` a WHERE a.PEDIDO_ID='$pedido' and (a.STATUS='PENDI_PETEC' or a.STATUS='BUSCADO_PETEC' or a.STATUS='MALO')) kai on a.id=kai.id2";

                        $r = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);
			$busy="";
			//2016-06-28: CARLOS PIDE QUE SE EJECUTE FENIX PARA TODAS LAS BUSQUEDAS DE PEDIDO.
			$check="CARLOS";

                        if($r->num_rows > 0&&$check==""){
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
					$fecha_visto=date("Y-m-d H:i:s");
					$sqlupdate="update informe_petec_pendientesm set VIEWS=VIEWS+1,ASESOR='$user',FECHA_VISTO_ASESOR='$fecha_visto' where ID in ($ids)";
				}

                                $x = $this->mysqli->query($sqlupdate);
                                    $sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$user','$username','','','PEDIDO: $pedido','BUSCARPEDIDO','') ";
				$xx = $this->mysqli->query($sqlfeed);
				$this->response(json_encode($result), 200); // send user details
                        }else {
                        //si el pedido no esta en la base de datos buscar en los dos fenix, esto implica insertar en la tabla core..


                      if ($plaza=="BOGOTA-COBRE"){//pregunta si se debe buscar en fenix Bogotá o se debe buscar en fenix nacional por medio de la plaza.
                        //echo "Esta Entrando por aca para llamar a fenix Bogotá";
                         $success=$this->buscarPedidoFenixBogota($pedido);

                      } else{
                        $success=$this->buscarPedidoFenix($pedido);
                      }
				if($success=="OK"){//logro encontrar el pedido en fenix he hizo el insert local...
					//recursion?????
					//$this->buscarPedido();
					 $query1="SELECT a.ID,a.PEDIDO_ID,a.SUBPEDIDO_ID,a.SOLICITUD_ID, a.TIPO_TRABAJO, a.DESC_TIPO_TRABAJO, a.VEL_IDEN, a.VEL_SOLI, a.IDENTIFICADOR_ID, a.TIPO_ELEMENTO_ID,a.PRODUCTO ,a.PRODUCTO_ID,a.UEN_CALCULADA,a.ESTRATO,MUNICIPIO_ID,a.DIRECCION_SERVICIO,a.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,a.FUENTE,a.CONCEPTO_ID,a.FECHA_ESTADO,a.ASESOR,a.STATUS,a.CONCEPTO_ANTERIOR,a.FECHA_CITA,a.CANTIDAD_EQU,a.EQUIPOS,a.CONCEPTOS_EQU,a.TIPO_EQUIPOS,a.EXTENSIONES,a.OBSERVACIONES, a.EJECUTIVO_ID, a.CANAL_ID, a.CELULAR_AVISAR, a.TELEFONO_AVISAR from informe_petec_pendientesm a JOIN (SELECT distinct(a.pedido) as pedido2,(select b.id from informe_petec_pendientesm b where b.pedido=a.pedido order by id desc limit 1 ) as id2 FROM `informe_petec_pendientesm` a WHERE a.PEDIDO_ID='$pedido' and (a.STATUS='PENDI_PETEC' or a.STATUS='BUSCADO_PETEC' or a.STATUS='MALO')) kai on a.id=kai.id2";

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
                                        		$fecha_visto=date("Y-m-d H:i:s");
                                        		$sqlupdate="update informe_petec_pendientesm set VIEWS=VIEWS+1,ASESOR='$user',FECHA_VISTO_ASESOR='$fecha_visto' where ID in ($ids)";
                                		}

                                		$x = $this->mysqli->query($sqlupdate);
                                    		$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$user','$username','','','PEDIDO: $pedido','BUSCARPEDIDO','') ";
                                		$xx = $this->mysqli->query($sqlfeed);
                                		$this->response(json_encode($result), 200); // send user details
                        		}



				}
                        }
			*/
        }

        $this->response('nothing',204);        // If no records "No Content" status
    }





    private function buscarPedidoReconfiguracion(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido = $this->_request['pedidoID'];
        $plaza = $this->_request ['plaza'];
        $user = $this->_request['userID'];
        $username = $this->_request['username'];
        //si el actual usuario tenia un pedido "agarrado, hay que liberarlo"
        $pedido_actual = $this->_request['pedido_actual'];

        /* if($pedido_actual!=''){//en este caso tenia pedido antes, estaba trabajando uno, debo actualizarlo para dejarlo libre
            $sqlupdate="update informe_petec_pendientesm set ASESOR='' where ASESOR='$user' ";
            $xxx = $this->mysqli->query($sqlupdate);
        } // Nose por que esta asi*/

        $sqlupdate="update informe_petec_pendientesm set ASESOR='' where ASESOR='$user' ";
        $xxx = $this->mysqli->query($sqlupdate);

        $user=strtoupper($user);
        $today = date("Y-m-d");

        $query1=" SELECT ".
            " a.ID ".
            " , a.PEDIDO_ID ".
            " , a.SUBPEDIDO_ID ".
            " , a.SOLICITUD_ID ".
            " , a.TIPO_ELEMENTO_ID ".
            " , a.TIPO_TRABAJO ".
            " , a.DESC_TIPO_TRABAJO ".
            " , a.CELULAR_AVISAR ".
            " , a.TELEFONO_AVISAR ".
            " , a.PRODUCTO ".
            " , a.TECNOLOGIA_ID ".
            " , a.UEN_CALCULADA ".
            " , a.ESTRATO ".
            " , MUNICIPIO_ID ".
            " , a.DIRECCION_SERVICIO ".
            " , a.PAGINA_SERVICIO ".
            " , CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA ".
            " , a.FUENTE ".
            " , a.CONCEPTO_ID ".
            " , a.FECHA_ESTADO ".
            " , a.ASESOR ".
            " , a.STATUS ".
            " , a.CONCEPTO_ANTERIOR ".
            " , a.FECHA_CITA ".
            " , a.CANTIDAD_EQU ".
            " , a.EQUIPOS ".
            " , a.CONCEPTOS_EQU ".
            " , a.TIPO_EQUIPOS ".
            " , a.EXTENSIONES ".
            " , a.OBSERVACIONES ".
            " , a.EJECUTIVO_ID ".
            " , a.CANAL_ID ".
            " , a.PROGRAMACION ".
            " , cast(ifnull(c.Total_Contactos,'SIN LLAMADAS') AS CHAR(255)) as LLAMADAS ".
            " , c.ULTIMO_CONTACTO	".
            " from informe_petec_pendientesm a ".
            " left join (SELECT a.pedido_id, count(a.pedido_id) as Total_Contactos, ".
            " max(a.fecha_fin) as Ultimo_Contacto	".
            " FROM portalbd.pedidos a	".
            " WHERE a.ESTADO = 'VOLVER A LLAMAR'	".
            " group by a.PEDIDO_ID) c	on c.PEDIDO_id = a.pedido_id ".
            " WHERE a.PEDIDO_ID='$pedido' and (a.STATUS='PENDI_PETEC' or a.STATUS='BUSCADO_PETEC' or a.STATUS='MALO') ";

        //$this->response($query1,200);
        $r = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);
        $busy="";
        $cReconfiguracion=array('14', '99', 'O-101');
        $cEdatel=array('12-EDATEL');
        $cAsignaciones=array('PETEC','OKRED','PUMED','O-106','O-13','O-15','PEOPP','19');

        if($r->num_rows > 0){
            $result = array();
            $ids="";
            $sep="";
            while($row = $r->fetch_assoc()){
                $row['source']='BUSCADO';
                $observaciones = $this->quitar_tildes(utf8_encode($row['OBSERVACIONES']));
                $row['OBSERVACIONES'] = $observaciones;
                if (in_array($row['CONCEPTO_ID'], $cReconfiguracion, true)) {
                    $row['ACTIVIDAD']   = 'RECONFIGURACION';
                    $row['GRUPO']       = 'RECONFIGURACION';
                }
                if (in_array($row['CONCEPTO_ID'], $cAsignaciones, true)) {
                    $row['ACTIVIDAD']   = 'ESTUDIO';
                    $row['GRUPO']       = 'ASIGNACIONES';
                }


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
                $fecha_visto=date("Y-m-d H:i:s");
                $sqlupdate="update informe_petec_pendientesm set VIEWS=VIEWS+1,ASESOR='$user',FECHA_VISTO_ASESOR='$fecha_visto' where ID in ($ids)";
            }

            $x = $this->mysqli->query($sqlupdate);

            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$pedido' ".
                ",'BUSCO PEDIDO' ".
                ",'PEDIDO BUSCADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$user','$username','','','PEDIDO: $pedido','BUSCARPEDIDO','') ";
            //$xx = $this->mysqli->query($sqlfeed);
            //echo json_encode($result);
            //$this->response(json_encode($result), 200); // send user details
            $this->response(json_encode($result), 200);
            //$this->response('test', 200); // send user details
        }else {

            if ($plaza=="BOGOTA-COBRE"){//pregunta si se debe buscar en fenix Bogotá o se debe buscar en fenix nacional por medio de la plaza.
                //echo "Esta Entrando por aca para llamar a fenix Bogotá";
                $success=$this->buscarPedidoFenixBogota($pedido);

            } else{
                $success=$this->buscarPedidoFenixReconfiguracion($pedido);
            }
            if($success=="OK"){//logro encontrar el pedido en fenix he hizo el insert local...
                //recursion?????
                $this->buscarPedidoReconfiguracion();
            }
        }
        $error="Pedido: $pedido, no encontrado.";

        $this->response(json_encode(array($error)),204);        // If no records "No Content" status
    }


    //Función para buscar pedidos directamente en Fenix - Boton BuscarPedido.
    // 2016-02-02 - Se modifica  este negocio solo por capricho de Carlos.
    private function buscarPedidoFenixReconfiguracion($pedido_id){

        $this->dbFenixConnect();
        $connf=$this->connf;
        //2. verifico el estado del pedido en fenix y actualizo estado en base de datos local si es necesario
        $sqlfenix="SELECT DISTINCT ".
            "  SOL.PEDIDO_ID as PEDIDO".
            "  , SOL.PEDIDO_ID ".
            "  , max(SOL.SUBPEDIDO_ID) as SUBPEDIDO_ID ".
            "  , max(SOL.SOLICITUD_ID) as SOLICITUD_ID ".
            "  , regexp_replace(LISTAGG(SOL.TIPO_ELEMENTO_ID,',') WITHIN GROUP (ORDER BY NULL),'([^,]+)(,\\1)+', '\\1' ) as TIPO_ELEMENTO_ID    ".
            "  , regexp_replace(LISTAGG(FNX_TRABAJOS_SOLICITUDES.TIPO_TRABAJO,',') WITHIN GROUP (ORDER BY NULL),'([^,]+)(,\\1)+', '\\1' ) as TIPO_TRABAJO  ".
            "  , convert((MIN(FNX_TRABAJOS_SOLICITUDES.TIPO_TRABAJO) || '-' || FN_NOMBRE_CARACTERISTICA(MIN(FNX_TRABAJOS_SOLICITUDES.CARACTERISTICA_ID))),'US7ASCII') as DESC_TIPO_TRABAJO   ".
            "  , min(  NVL((select TO_CHAR(NSO.fecha, 'YYYY-MM-DD HH24:MI:SS')   ".
            "   from FENIX.FNX_NOVEDADES_SOLICITUDES NSO   ".
            "   where SOL.PEDIDO_ID=NSO.PEDIDO_ID(+)   ".
            "        AND SOL.SUBPEDIDO_ID=NSO.SUBPEDIDO_ID(+)   ".
            "        AND SOL.SOLICITUD_ID=NSO.SOLICITUD_ID(+)   ".
            "   and NSO.consecutivo=(select max(a.consecutivo) from FENIX.FNX_NOVEDADES_SOLICITUDES a    ".
            "      where SOL.PEDIDO_ID=a.PEDIDO_ID(+)   ".
            "        AND SOL.SUBPEDIDO_ID=a.SUBPEDIDO_ID(+)   ".
            "        AND SOL.SOLICITUD_ID=a.SOLICITUD_ID(+))), TO_CHAR(SOL.FECHA_ESTADO,'RRRR-MM-DD hh24:mi:ss'))) AS FECHA_ESTADO   ".
            "  , min(TO_CHAR(SOL.FECHA_CITA,'RRRR-MM-DD')) as FECHA_CITA   ".
            "  , regexp_replace(LISTAGG(FN_NOMBRE_PRODUCTO(SOL.PRODUCTO_ID),',') WITHIN GROUP (ORDER BY NULL),'([^,]+)(,\\1)+', '\\1' ) AS PRODUCTO   ".
            "  , min(TRIM(FN_UEN_CALCULADA(SOL.PEDIDO_ID,SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID))) AS UEN_CALCULADA   ".
            "  , min(FN_VALOR_CARACT_SUBPEDIDO(SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID,37)) ESTRATO   ".
            "  , MIN(SOL.CONCEPTO_ID) AS CONCEPTO_ID ".
            "  , MIN(SOL.CONCEPTO_ID) AS CONCEPTO_ANTERIOR   ".
            "  , min(TRIM(FN_VALOR_CARACT_SUBPEDIDO(SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID,'34'))) AS MUNICIPIO_ID    ".
            "  , min(TO_CHAR(TRIM(FN_VALOR_CARACTERISTICA_SOL (SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID,'35')))) AS DIRECCION_SERVICIO   ".
            "  , min(fn_valor_caracteristica_sol(SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID,'38')) as PAGINA_SERVICIO   ".
            "  , min(TO_CHAR(SOL.FECHA_INGRESO,'RRRR-MM-DD hh24:mi:ss')) as FECHA_INGRESO ".
            "  , min(TO_CHAR(SOL.FECHA_INGRESO,'RRRR-MM-DD')) as FECHAINGRESO_SOLA    ".
            "  , min(TO_CHAR(SYSDATE,'RRRR-MM-DD hh24:mi:ss')) AS FECHA_CARGA   ".
            "  , 'FENIX_NAL' AS FUENTE   ".
            //"  , 'PENDI_PETEC' AS STATUS   ".
            "  ,min(COALESCE(TRIM(FN_VALOR_CARACTERISTICA_SOL(SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID,'33')), SOL.TECNOLOGIA_ID )) AS TECNOLOGIA_ID    ".
            "  ,max(FNX_PEDIDOS.CELULAR_AVISAR) as CELULAR_AVISAR   ".
            "  ,min(FNX_PEDIDOS.TELEFONO_AVISAR) as  TELEFONO_AVISAR ".
            "   FROM FNX_SOLICITUDES SOL   ".
            "  , FNX_PEDIDOS   ".
            "  , FNX_SUBPEDIDOS   ".
            "  , FNX_TRABAJOS_SOLICITUDES      ".
            "      WHERE   ".
            "      SOL.PEDIDO_ID='$pedido_id' ".
            //"  and (SOL.CONCEPTO_ID in ('14','99'))    ".
            //"   and TRIM(FN_UEN_CALCULADA(SOL.PEDIDO_ID,SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID)) in ('HG','AU')    ".
            "       AND SOL.SUBPEDIDO_ID=FNX_SUBPEDIDOS.SUBPEDIDO_ID(+)    ".
            "       AND SOL.PEDIDO_ID=FNX_SUBPEDIDOS.PEDIDO_ID(+)    ".
            "       AND SOL.PEDIDO_ID=FNX_PEDIDOS.PEDIDO_ID(+)    ".
            "       AND SOL.PEDIDO_ID=FNX_TRABAJOS_SOLICITUDES.PEDIDO_ID(+)    ".
            "       AND SOL.SUBPEDIDO_ID=FNX_TRABAJOS_SOLICITUDES.SUBPEDIDO_ID(+)    ".
            "       AND SOL.SOLICITUD_ID=FNX_TRABAJOS_SOLICITUDES.SOLICITUD_ID(+) ".
            "       GROUP BY SOL.PEDIDO_ID ";


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
        if ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
            $subinsert=$sqlinsert;
            $sep="";
            foreach ($row as $item) {
                $item = str_replace("'", ".", $item);
                $subinsert="$subinsert $sep '$item'";
                $sep=",";
            }

            $SEP=",";
            //$status="PENDI_PETEC";

            $status="BUSCADO_PETEC";

            $subinsert=$subinsert.",'$status')";
            if(!$result = $this->mysqli->query($subinsert)){
                die('There was an error running the query [' . $this->mysqli->error. ' --'.$subinsert.'** ]');
            }
            $success="OK";
        }
        return $success;
    }
    //Función para buscar pedidos directamente en Fenix - Boton BuscarPedido.
    // 2015-09-17 - Se modifica fecha estado, ahora vamos a novedades por ella - CGONZGO
    private function buscarPedidoFenix($pedido_id){

        $this->dbFenixConnect();
        $connf=$this->connf;
        //2. verifico el estado del pedido en fenix y actualizo estado en base de datos local si es necesario
        $sqlfenix="SELECT  ".
            "  SOL.PEDIDO_ID||SOL.SUBPEDIDO_ID|| SOL.SOLICITUD_ID as PEDIDO ".
            "  , SOL.PEDIDO_ID ".
            "  , SOL.SUBPEDIDO_ID ".
            "  , SOL.SOLICITUD_ID ".
            "  , SOL.TIPO_ELEMENTO_ID ".
            "  , SOL.ESTADO_BLOQUEO ".
            "  , SOL.USUARIO_ID AS USUARIO_BLOQUEO_FENIX ".
            "  , (SELECT MIN(T.TIPO_TRABAJO) AS TIPO_TRABAJO ".
            "       FROM FNX_TRABAJOS_SOLICITUDES T ".
            "       WHERE 1=1 ".
            "       AND T.PEDIDO_ID=SOL.PEDIDO_ID ".
            "       AND T.SUBPEDIDO_ID=SOL.SUBPEDIDO_ID ".
            "       AND T.SOLICITUD_ID=SOL.SOLICITUD_ID ".
            "       GROUP BY T.PEDIDO_ID, T.SUBPEDIDO_ID, T.SOLICITUD_ID ) AS  TIPO_TRABAJO ".
            "  , ( SELECT convert((MIN(T.TIPO_TRABAJO) || '-' || FN_NOMBRE_CARACTERISTICA(MAX(T.CARACTERISTICA_ID))),'US7ASCII') as DESC_TIPO_TRABAJO ".
            "         FROM FNX_TRABAJOS_SOLICITUDES T ".
            "         WHERE 1=1 ".
            "         AND T.PEDIDO_ID=SOL.PEDIDO_ID ".
            "         AND T.SUBPEDIDO_ID=SOL.SUBPEDIDO_ID ".
            "         AND T.SOLICITUD_ID=SOL.SOLICITUD_ID ".
            "         GROUP BY T.PEDIDO_ID, T.SUBPEDIDO_ID, T.SOLICITUD_ID ) AS DESC_TIPO_TRABAJO ".
            "  , TO_CHAR(SOL.FECHA_INGRESO,'RRRR-MM-DD hh24:mi:ss') as FECHA_INGRESO  ".
            "  , NVL((select TO_CHAR(NSO.fecha, 'YYYY-MM-DD HH24:MI:SS')  ".
            "  from FENIX.FNX_NOVEDADES_SOLICITUDES NSO  ".
            "  where SOL.PEDIDO_ID=NSO.PEDIDO_ID(+)  ".
            "        AND SOL.SUBPEDIDO_ID=NSO.SUBPEDIDO_ID(+)  ".
            "        AND SOL.SOLICITUD_ID=NSO.SOLICITUD_ID(+)  ".
            "  and NSO.consecutivo=(select max(a.consecutivo) from FENIX.FNX_NOVEDADES_SOLICITUDES a   ".
            "      where SOL.PEDIDO_ID=a.PEDIDO_ID(+)  ".
            "        AND SOL.SUBPEDIDO_ID=a.SUBPEDIDO_ID(+)  ".
            "        AND SOL.SOLICITUD_ID=a.SOLICITUD_ID(+))), TO_CHAR(SOL.FECHA_ESTADO,'RRRR-MM-DD hh24:mi:ss')) AS FECHA_ESTADO  ".
            "  , TO_CHAR(SOL.FECHA_CITA,'RRRR-MM-DD') as FECHA_CITA  ".
            "  , SOL.PRODUCTO_ID  ".
            "  , FN_NOMBRE_PRODUCTO(SOL.PRODUCTO_ID) AS PRODUCTO  ".
            "  , TRIM(FN_UEN_CALCULADA(SOL.PEDIDO_ID,SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID)) AS UEN_CALCULADA  ".
            "  , FN_VALOR_CARACT_SUBPEDIDO(SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID,37) as  ESTRATO  ".
            "  , SOL.CONCEPTO_ID  ".
            "  , SOL.CONCEPTO_ID as CONCEPTO_ANTERIOR ".
            "  , upper(fn_nombre_departamento(FN_VALOR_CARACT_SUBPEDIDO(SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID,'34'))) as DEPARTAMENTO ".
            "  , TRIM(FN_VALOR_CARACTERISTICA_SOL (SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID,'34')) AS MUNICIPIO_ID  ".
            "  , TO_CHAR(TRIM(FN_VALOR_CARACTERISTICA_SOL (SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID,'35'))) AS DIRECCION_SERVICIO ".
            "  , fn_valor_caracteristica_sol(SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID,'38') as PAGINA_SERVICIO ".
            "  , TO_CHAR(SOL.FECHA_INGRESO,'RRRR-MM-DD') FECHAINGRESO_SOLA  ".
            "  , (TO_CHAR(SOL.FECHA_INGRESO,'hh24')) HORAINGRESO ".
            "  , (TO_CHAR(SOL.FECHA_ESTADO,'RRRR-MM-DD')) FECHAESTADO_SOLA  ".
            "  , (TO_CHAR(SOL.FECHA_ESTADO,'hh24')) HORAESTADO ".
            "  , TO_NUMBER (TO_CHAR (SOL.FECHA_ESTADO, 'DD')) AS DIANUM_ESTADO ".
            "  , TO_CHAR (SOL.FECHA_ESTADO, 'DAY') AS DIANOM_ESTADO ".
            "  , CASE  ".
            "      WHEN TO_NUMBER(TO_CHAR(SYSDATE,'hh24')) < 13 THEN 'AM' ".
            "      WHEN TO_NUMBER(TO_CHAR(SYSDATE,'hh24')) = 13  THEN 'MD' ".
            "      WHEN TO_NUMBER(TO_CHAR(SYSDATE,'hh24')) > 13  THEN 'PM' ".
            "    END AS RANGO_CARGA ".
            "  , (TO_CHAR(SYSDATE,'RRRR-MM-DD hh24:mi:ss')) AS FECHA_CARGA ".
            "  , (TO_CHAR(SYSDATE,'RRRR-MM-DD hh24:mi:ss')) AS FECHA_VISTO_ASESOR ".
            "  , TO_CHAR (SYSDATE, 'DAY') AS DIA_CARGA ".
            "  , (TRIM ( TO_CHAR (SYSDATE, 'MONTH'))) AS MESNOMBRE_CARGA  ".
            "  , TO_NUMBER ( TO_CHAR ( SYSDATE, 'MM')) AS MESNUMERO_CARGA ".
            "  , CEIL (  TO_NUMBER ( TO_CHAR ( SYSDATE,  'W'))) AS SEMANA_CARGA ".
            "  , TO_NUMBER ( TO_CHAR (SYSDATE, 'IW')) AS SEMANA_ANO_CARGA ".
            "  , TO_NUMBER(TO_CHAR(SYSDATE,'RRRR')) AS ANO_CARGA ".
            "  , 'FENIX_NAL' AS FUENTE ".
            "  , FNX_PEDIDOS.RADICADO_TEMPORAL ".
            "  ,COALESCE(TRIM(FN_VALOR_CARACTERISTICA_SOL(SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID,'33')), SOL.TECNOLOGIA_ID,FN_VALOR_CARACT_SUBPEDIDO(SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID,'33') ) AS TECNOLOGIA_ID  ".
            "  , NVL(SOL.IDENTIFICADOR_ID, SOL.IDENTIFICADOR_ID_NUEVO) as IDENTIFICADOR_ID ".
            "  , IDTV.CANTIDAD_EQU ".
            "  , IDTV.EQUIPOS ".
            "  , IDTV.CONCEPTOS_EQU ".
            "  , IDTV.TIPO_EQUIPOS ".
            "  , IDTV.EXTENSIONES ".
            "  , NVL(FN_VALOR_CARACT_IDENTIF(NVL(SOL.IDENTIFICADOR_ID, SOL.IDENTIFICADOR_ID_NUEVO),'124'),'-') as VEL_IDEN  ".
            "  , NVL(FN_VALOR_CARACTERISTICA_SOL(SOL.PEDIDO_ID, SOL.SUBPEDIDO_ID, SOL.SOLICITUD_ID,'124'),'-') as VEL_SOLI  ".
            "  , (select N.CONCEPTO_ID_ANTERIOR from FNX_NOVEDADES_SOLICITUDES N  ".
            "          where N.PEDIDO_ID=SOL.PEDIDO_ID   ".
            "            and N.SUBPEDIDO_ID=SOL.SUBPEDIDO_ID  ".
            "           and N.SOLICITUD_ID=SOL.SOLICITUD_ID   ".
            "            and N.consecutivo=(select max(NN.consecutivo) from FNX_NOVEDADES_SOLICITUDES NN  ".
            "              where NN.PEDIDO_ID=N.PEDIDO_ID   ".
            "              and NN.SUBPEDIDO_ID=N.SUBPEDIDO_ID  ".
            "              and NN.SOLICITUD_ID=N.SOLICITUD_ID) ) AS CONCEPTO_ID_ANTERIOR_NOV  ".
            "  , FNX_PEDIDOS.CELULAR_AVISAR ".
            "  , FNX_PEDIDOS.TELEFONO_AVISAR ".
            "  FROM FNX_SOLICITUDES SOL ".
            "  , FNX_PEDIDOS ".
            "  , FNX_SUBPEDIDOS ".
            "  , (SELECT C1.PEDIDO_ID, ".
            "   CASE ".
            "     WHEN LISTAGG (C1.TIPO_ELEMENTO_ID, '-') WITHIN GROUP ( ".
            "     ORDER BY 1 ASC) LIKE '%INSHFC%' ".
            "     THEN (COUNT (*) - 1) ".
            "     WHEN LISTAGG (C1.TIPO_ELEMENTO_ID, '-') WITHIN GROUP ( ".
            "     ORDER BY 1 ASC) LIKE '%INSTA%' ".
            "     THEN (COUNT (*) - 1) ".
            "     ELSE COUNT ( *) ".
            "   END AS CANTIDAD_EQU, ".
            "   RTRIM ( REGEXP_REPLACE ( (LISTAGG (C1.EQUIPO, '-') WITHIN GROUP ( ".
            " ORDER BY 1 ASC)), '([^-]*)(-\\1)+($|-)', '\\1\\3'), '-') AS EQUIPOS, ".
            "   RTRIM ( REGEXP_REPLACE ( (LISTAGG (C1.CONCEPTO_EQU, '-') WITHIN GROUP ( ".
            " ORDER BY 1 ASC)), '([^-]*)(-\\1)+($|-)', '\\1\\3'), '-') AS CONCEPTOS_EQU, ".
            "   RTRIM ( REGEXP_REPLACE ( (LISTAGG (C1.TIPO_EQUIPO, '-') WITHIN GROUP ( ".
            " ORDER BY 1 ASC)), '([^-]*)(-\\1)+($|-)', '\\1\\3'), '-') AS TIPO_EQUIPOS, ".
            "   MAX (C1.EXTENSIONES)                                   AS EXTENSIONES ".
            " FROM ".
            "   (SELECT S.PEDIDO_ID, ".
            "     NVL ( FN_VALOR_CARACT_SUBPEDIDO (S.PEDIDO_ID, S.SUBPEDIDO_ID, '1067'), 'ANALOGO') AS EQUIPO, ".
            "     S.TIPO_ELEMENTO_ID, ".
            "     S.CONCEPTO_ID AS CONCEPTO_EQU, ".
            "     CASE ".
            "       WHEN TRIM ( FN_VALOR_CARACT_SUBPEDIDO (S.PEDIDO_ID, S.SUBPEDIDO_ID, '1067')) IN ('STBAS','STBAV') ".
            "       THEN 'HD' ".
            "       ELSE 'SD' ".
            "     END    AS TIPO_EQUIPO, ".
            "     NVL ( FN_VALOR_CARACT_SUBPEDIDO (S.PEDIDO_ID, S.SUBPEDIDO_ID, '2909'), 0) AS EXTENSIONES ".
            "   FROM FNX_SOLICITUDES S ".
            "   WHERE 1                 = 1 ".
            "   AND S.TIPO_ELEMENTO_ID IN ('EQURED', 'STBOX', 'INSTA', 'INSHFC') ".
            "   AND S.PRODUCTO_ID      IN ('TELEV', 'TV') ".
            "   AND S.PEDIDO_ID         = '$pedido_id' ".
            "   ) C1 ".
            " GROUP BY C1.PEDIDO_ID ) IDTV ".
            " WHERE ".
            "       SOL.PEDIDO_ID='$pedido_id' ".
            "       and SOL.TIPO_ELEMENTO_ID IN ('BDID', 'TDID','BDIDE1', 'TDIDE1', 'BDODE1', 'TDODE1', 'TO', 'TOIP','INSHFC', 'INSIP', 'INSTIP', 'SEDEIP', 'P2MB', '3PLAY', 'CNTXIP', 'ACCESP', 'PLANT', 'PLP', 'PTLAN', 'PMULT', 'PPCM', 'PBRI', 'PPRI', 'INSTA', 'TP', 'PBRI','SLL', 'TC', 'SLLBRI', 'TCBRI', 'SLLPRI', 'TCPRI','SEDEIP','SEDECX','EQURED','STBOX','ACCESO','DECO') ".
            "       	AND SOL.CONCEPTO_ID NOT IN ('14','99') ".
            "       AND SOL.SUBPEDIDO_ID=FNX_SUBPEDIDOS.SUBPEDIDO_ID  ".
            "       AND SOL.PEDIDO_ID=FNX_SUBPEDIDOS.PEDIDO_ID  ".
            "       AND SOL.PEDIDO_ID=FNX_PEDIDOS.PEDIDO_ID  ".
            "       AND SOL.PEDIDO_ID=IDTV.PEDIDO_ID(+) ";
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
            if($row['TIPO_ELEMENTO_ID']=='EQURED' || $row['TIPO_ELEMENTO_ID']=='EQACCP' || $row['TIPO_ELEMENTO_ID']=='STBOX'|| $row['TIPO_ELEMENTO_ID']=='INTCON'|| $row['TIPO_ELEMENTO_ID']=='TELEV'){
                $status="BUSCADO_PETEC";
            }
            $subinsert=$subinsert.",'$status')";
            if(!$result = $this->mysqli->query($subinsert)){
                die('There was an error running the query [' . $this->mysqli->error. ' --'.$subinsert.'** ]');
            }
            $success="OK";
        }
        return $success;
    }

//función para buscar pedidos en fenix Bogotá que no estan en la base de datos.
    private function buscarPedidoFenixBogota($pedido_id){

        $this->dbFenixBogotaConnect();
        $connfb=$this->connfb;
        //2. verifico el estado del pedido en fenix y actualizo estado en base de datos local si es necesario
        $sqlfenix="SELECT DISTINCT ".
            "  SOL.PEDIDO_ID||SOL.SUBPEDIDO_ID|| SOL.SOLICITUD_ID as PEDIDO  ".
            "  , SOL.PEDIDO_ID  ".
            "  ,  SOL.SUBPEDIDO_ID  ".
            "  ,  SOL.SOLICITUD_ID   ".
            "  ,  SOL.TIPO_ELEMENTO_ID   ".
            "  , SOL.ESTADO_BLOQUEO   ".
            "  , SOL.USUARIO_ID AS USUARIO_BLOQUEO_FENIX   ".
            "  , FENIX.FNX_TRABAJOS_SOLICITUDES.TIPO_TRABAJO  ".
            "  , TO_CHAR(SOL.FECHA_INGRESO,'RRRR-MM-DD hh24:mi:ss') as FECHA_INGRESO   ".
            "  ,NVL((select TO_CHAR(NSO.fecha, 'YYYY-MM-DD HH24:MI:SS')  ".
            "  from FENIX.FNX_NOVEDADES_SOLICITUDES NSO  ".
            "  where SOL.PEDIDO_ID=NSO.PEDIDO_ID(+)  ".
            "        AND SOL.SUBPEDIDO_ID=NSO.SUBPEDIDO_ID(+)  ".
            "        AND SOL.SOLICITUD_ID=NSO.SOLICITUD_ID(+)  ".
            "  and NSO.consecutivo=(select max(a.consecutivo) from FENIX.FNX_NOVEDADES_SOLICITUDES a   ".
            "      where SOL.PEDIDO_ID=a.PEDIDO_ID(+)  ".
            "        AND SOL.SUBPEDIDO_ID=a.SUBPEDIDO_ID(+)  ".
            "        AND SOL.SOLICITUD_ID=a.SOLICITUD_ID(+))),TO_CHAR(SOL.FECHA_ESTADO,'RRRR-MM-DD hh24:mi:ss')) AS FECHA_ESTADO  ".
            "  , FN_NOMBRE_PRODUCTO( SOL.PRODUCTO_ID) AS PRODUCTO  ".
            "  , 'HG' AS UEN_CALCULADA  ".
            "  , '2' as ESTRATO  ".
            "  ,  SOL.CONCEPTO_ID   ".
            "  ,  SOL.FECHA_CITA   ".
            "  , SOL.CONCEPTO_ID as CONCEPTO_ANTERIOR   ".
            "  , 'BOG-COBRE' AS MUNICIPIO_ID  ".
            "  , TO_CHAR(TRIM(FN_VALOR_CARACTERISTICA_SOL ( SOL.PEDIDO_ID,  SOL.SUBPEDIDO_ID,  SOL.SOLICITUD_ID,'35'))) AS DIRECCION_SERVICIO   ".
            "  , (TO_CHAR( SOL.FECHA_INGRESO,'RRRR-MM-DD')) FECHAINGRESO_SOLA  ".
            "  , (TO_CHAR( SOL.FECHA_INGRESO,'hh24')) HORAINGRESO   ".
            "  , (TO_CHAR( SOL.FECHA_ESTADO,'RRRR-MM-DD')) FECHAESTADO_SOLA   ".
            "  , (TO_CHAR( SOL.FECHA_ESTADO,'hh24')) HORAESTADO  ".
            "  , TO_NUMBER (TO_CHAR ( SOL.FECHA_ESTADO, 'DD')) AS DIANUM_ESTADO  ".
            "  , TO_CHAR ( SOL.FECHA_ESTADO, 'DAY') AS DIANOM_ESTADO  ".
            "  , CASE  ".
            "      WHEN TO_NUMBER(TO_CHAR(SYSDATE,'hh24')) < 13 THEN 'AM'  ".
            "      WHEN TO_NUMBER(TO_CHAR(SYSDATE,'hh24')) = 13  THEN 'MD'  ".
            "      WHEN TO_NUMBER(TO_CHAR(SYSDATE,'hh24')) > 13  THEN 'PM'  ".
            "    END AS RANGO_CARGA  ".
            "  , (TO_CHAR(SYSDATE,'RRRR-MM-DD hh24:mi:ss')) AS FECHA_CARGA   ".
            "  , (TO_CHAR(SYSDATE,'RRRR-MM-DD hh24:mi:ss')) AS FECHA_VISTO_ASESOR   ".
            "  , TO_CHAR (SYSDATE, 'DAY') AS DIA_CARGA  ".
            "  , (TRIM ( TO_CHAR (SYSDATE, 'MONTH'))) AS MESNOMBRE_CARGA    ".
            "  , TO_NUMBER ( TO_CHAR ( SYSDATE, 'MM')) AS MESNUMERO_CARGA  ".
            "  , CEIL (  TO_NUMBER ( TO_CHAR ( SYSDATE,  'W'))) AS SEMANA_CARGA   ".
            "  , TO_NUMBER ( TO_CHAR (SYSDATE, 'IW')) AS SEMANA_ANO_CARGA  ".
            "  , TO_NUMBER(TO_CHAR(SYSDATE,'RRRR')) AS ANO_CARGA  ".
            "  , 'FENIX_BOG' AS FUENTE  ".
            "  FROM   ".
            "   FENIX.FNX_SOLICITUDES SOL  ".
            "  , FENIX.FNX_CLIENTES  ".
            "  , FENIX.FNX_PEDIDOS  ".
            "  , FENIX.FNX_SUBPEDIDOS  ".
            "  , FENIX.FNX_TRABAJOS_SOLICITUDES  ".
            "  , FENIX.FNX_MUNICIPIOS    ".
            "  WHERE SOL.PEDIDO_ID='$pedido_id' ".
            "  AND ( SOL.TIPO_ELEMENTO_ID NOT IN ('CDMA','MODBA','ADMSER','NU')    ".
            "  AND  (( SOL.SUBPEDIDO_ID=FENIX.FNX_SUBPEDIDOS.SUBPEDIDO_ID    ".
            "  AND  SOL.PEDIDO_ID=FENIX.FNX_SUBPEDIDOS.PEDIDO_ID)    ".
            "  AND (FENIX.FNX_SUBPEDIDOS.PEDIDO_ID=FENIX.FNX_PEDIDOS.PEDIDO_ID)    ".
            "  AND (FENIX.FNX_PEDIDOS.CLIENTE_ID=FENIX.FNX_CLIENTES.CLIENTE_ID)    ".
            "  AND ( SOL.PEDIDO_ID=FENIX.FNX_TRABAJOS_SOLICITUDES.PEDIDO_ID)    ".
            "  AND ( SOL.SUBPEDIDO_ID=FENIX.FNX_TRABAJOS_SOLICITUDES.SUBPEDIDO_ID)    ".
            "  AND ( SOL.SOLICITUD_ID=FENIX.FNX_TRABAJOS_SOLICITUDES.SOLICITUD_ID))) ";

        $stid = oci_parse($connfb, $sqlfenix);
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
                die('There was an error running the query [' . $connfb->error. ' --'.$subinsert.'** ]');
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

	/*	private function demePedidoReconfiguracion(){
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
		}*/


    /**
     * @uses  updateParametro()
     */
    private function updateParametro(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $param = $this->_request['parametro'];
        $value = $this->_request['valor'];
        $user = $this->_request['user'];

        $sql="UPDATE gestor_parametros ".
            " SET VALOR='$value', USUARIO_ID='$user' where VARIABLE='$param'";
        $rr = $this->mysqli->query($sql);

        // SQL Feed----------------------------------
        $sql_log=   "insert into portalbd.activity_feed ( ".
            " USER ".
            ", USER_NAME ".
            ", GRUPO ".
            ", STATUS ".
            ", PEDIDO_OFERTA ".
            ", ACCION ".
            ", CONCEPTO_ID ".
            ", IP_HOST ".
            ", CP_HOST ".
            ") values( ".
            " UPPER('$usuarioGalleta')".
            ", UPPER('$nombreGalleta')".
            ", UPPER('$grupoGalleta')".
            ",'$value' ".
            ",'SIN PEDIDO' ".
            ",'ACTUALIZO PARAMETRO' ".
            ",'$param' ".
            ",'$usuarioIp' ".
            ",'$usuarioPc')";

        $rlog = $this->mysqli->query($sql_log);
        // ---------------------------------- SQL Feed
        //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$user','$username','ADMIN','','','UPDATEPARAMETRO','$param:$value') ";
        //$rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

        $this->response(json_encode(array("OK","PARAMETRO ACTUALIZADO")), 200);

    }

    private function buscarParametroFechaDemePedido($param){

        $sql="SELECT VALOR FROM gestor_parametros ".
            " WHERE VARIABLE='$param' limit 1";

        $rr = $this->mysqli->query($sql);
        if($rr->num_rows > 0){
            if($row = $rr->fetch_assoc()){
                return $row['VALOR'];
            }
        }else{
            return "SYSTEM PANIC";
        }
    }

    private function buscarParametro(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $param = $this->_request['parametro'];

        $sql="SELECT * FROM gestor_parametros ".
            " WHERE VARIABLE='$param' limit 1";
        //  echo $sql;

        $rr = $this->mysqli->query($sql);
        if($rr->num_rows > 0){
            if($row = $rr->fetch_assoc()){
                $this->response(json_encode($row), 200);
                //return $row['VALOR'];
            }
        }else{
            //return "SYSTEM PANIC";
            $this->response(json_encode(array("ERROR","NO ROWS!")), 200);
        }
    }


    private function demeCapacidadPorDistancia(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $distancia = $this->_request['distancia'];

        if($distancia<0) $capacidad='-1';

        $velomax=15;

        if($distancia>0&&$distancia<=300) $capacidad=$velomax;
        if($distancia>300&&$distancia<=500) $capacidad=$velomax-3;
        if($distancia>500&&$distancia<=800) $capacidad=$velomax-5;
        if($distancia>800&&$distancia<=1200) $capacidad=$velomax-7;
        if($distancia>1200&&$distancia<=1500) $capacidad=$velomax-9;
        if($distancia>1500&&$distancia<=1800) $capacidad=$velomax-10;
        if($distancia>1800&&$distancia<=2200) $capacidad=$velomax-11;
        if($distancia>2200&&$distancia<=2500) $capacidad=$velomax-12;
        if($distancia>2500&&$distancia<=2800) $capacidad=$velomax-13;
        if($distancia>2800&&$distancia<=3200) $capacidad=$velomax-14;
        if($distancia>3200) $capacidad=0;

        $this->response(json_encode(array("$distancia","$capacidad")), 200);


    }

    /**
     *Funcion principal para la entrega de peidos en el sistema.
     * Aplica para los grupos: Asignaciones, Reconfiguracion, Edatel, Siebel.
     */
    private function demePedido(){


        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $user           =   $this->_request['userID'];
        $concepto       =   $this->_request['concepto'];
        $plaza          =   $this->_request['plaza'];
        $fuente         =   $this->_request['fuente'];
        $username       =   $this->_request['username'];
        $prioridad      =   $this->_request['prioridad'];

        //echo var_dump($concepto);

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

        $sqlupdate="update informe_petec_pendientesm set ASESOR='' where ASESOR='$user'";
        //echo $sqlupdate;
        $xxx = $this->mysqli->query($sqlupdate);

        /*
        * 2017-07-28:
        *SE DEBE VOLVER A LA FORMA HABITUAL DE ENTREGA DE PEDIDOS YA QUE ESTE CODIGO CONTABA CUANDO SE DABA VARIAS VECES CONSECUTIVAS DEMEPEDIDO.....
        if($fuente=="SIEBEL"){//PARA LA FORMA DE SIEBEL SE QUIERE QUE ADMITA VARIOS PEDIDOS POR ASESOR!!!!

            if($pedido_actual!=''){
                $sqlupdate="update informe_petec_pendientesm set ASESOR='' where PEDIDO_ID='$user' AND STATUS IN ('PENDI_PETEC','MALO')";
                //echo $sqlupdate;
                $xxx = $this->mysqli->query($sqlupdate);
            }

            //1. validar el conteo de pedidos agarrados, si es superior a 3 devuelvo un objeto vacio...
            $sqlcount="select count(*) as counter from informe_petec_pendientesm where ASESOR='$user' ";
            $rr = $this->mysqli->query($sqlcount) or die($this->mysqli->error.__LINE__);

            if($rr->num_rows > 0){//recorro los registros de la consulta para
                if($row = $rr->fetch_assoc()){//si encuentra un pedido ENTREGUELO COMO SEA NECESARIO!!!!!!!
                    $result[] = $row;
                    $counter=$row['counter'];

                    if($counter>=3){
                        $this->response(json_encode('Limite de pedidos alcanzado!'),201);
                        return;
                    }
                }
            }

        }else {
            //NO SE PUEDE CONDICIONAR AL PEDIDO ACTUAL, SI LE DA F5 A LA PAGINA NO HAY PEDIDO ACTUAL.. ES MEJOR ASI!!!
            $sqlupdate="update informe_petec_pendientesm set ASESOR='' where ASESOR='$user'";
            //echo $sqlupdate;
            $xxx = $this->mysqli->query($sqlupdate);
        }
        */
        //}

        //echo "WTF";
        $user=strtoupper($user);
        $today = date("Y-m-d");

        //1.consulto  lo que tenga fecha cita de mañana
        $hora=date("G");
        $uphold="1";
        if($hora<11){
            $uphold="1";
        }else{
            $uphold="2";
        }

        //14B2B
        $llamadaReconfiguracion="0";

        $ATENCION_INMEDIATA="";
        $mypedido="";

        //2016-08-05: MAURICIO
        //SE UTILIZA ESTA VARIABLE PARA PARAMETRIZAR EL STATUS

        $STATUS="PENDI_PETEC";

        $parametroBusqueda= $this->buscarParametroFechaDemePedido('FECHA_ORDEN_DEMEPEDIDO');

        //echo var_dump("Michael ".$concepto);
        /* if($fuente="SIEBEL"){
			   $concepto=" and b.CONCEPTO_ID='$concepto'";
		   } */


        if($concepto=="PETEC"){
            if($plaza=="BOG-COBRE"){
                $concepto=" and b.CONCEPTO_ID IN ('PETEC','OKRED') ";
            }else {
                $concepto=" and b.CONCEPTO_ID IN ('PETEC','OKRED') and b.TIPO_ELEMENTO_ID IN ('ACCESP','INSIP','INSHFC','TO','TOIP','INSTA','INSTIP','STBOX','EQURED') ";
                if($plaza=="TODOS"){//para que sea posible obtener un registro de cualquier plaza


                $plaza2=" AND MUNICIPIO_ID IN (select a.MUNICIPIO_ID from tbl_plazas a where a.MUNICIPIO_ID NOT IN ('BOG-COBRE','BOGCUNCOL'))";
                    //Obtener un registro de cualquier plaza menos los de bogota
                    
                }else{

                    $plaza2=" AND MUNICIPIO_ID IN (select a.MUNICIPIO_ID from tbl_plazas a where a.PLAZA='$plaza') ";
                    //$plaza2=" AND b.MUNICIPIO_ID IN ('$plaza') ";
                }



                /*
                 * Prioridad vieja, Deshabilitada para que priorice, nuevos, hogares y/o Arbol
                 *
                //HAGO LA CONSULTA DE PRIORIDAD POR ARBOL
                $sqlllamadas="SELECT PEDIDO_ID,SUBPEDIDO_ID,SOLICITUD_ID,FECHA_ESTADO,FECHA_CITA ".
                    " FROM  informe_petec_pendientesm ".
                    " WHERE ".
                    " TIPO_TRABAJO = 'NUEVO' ".//CAMBIO DE PRIORIDAD 2017-02-16
                    " AND UEN_CALCULADA = 'HG' ". //CAMBIO DE PRIORIDAD 2017-02-16
                    " RADICADO_TEMPORAL IN ('ARBOL','INMEDIAT','TEM') ".
                    " AND ASESOR='' ".
                    " AND CONCEPTO_ID = '$concepto' ".
                    " AND STATUS='PENDI_PETEC' ".
                    $plaza2.
                    " ORDER BY FECHA_ESTADO ASC "; */


                /** 23-10-2017: SE MODIFICA LA FORMA DE ENTREGAR PEDIDOS, SE RETIRAN LAS PRIORIDADES DEFINIDDAS.
                $sqlllamadas=   "SELECT PEDIDO_ID, ".
                    " SUBPEDIDO_ID, ".
                    " SOLICITUD_ID, ".
                    " FECHA_ESTADO, ".
                    " FECHA_INGRESO, ".
                    " FECHA_CITA ".
                    " FROM  informe_petec_pendientesm ".
                    " WHERE 1=1 ".
                    //" and (TIPO_TRABAJO = 'NUEVO' ".//CAMBIO DE PRIORIDAD 2017-02-16
                    //" AND UEN_CALCULADA = 'HG' ". //CAMBIO DE PRIORIDAD 2017-02-16
                    //" and RADICADO_TEMPORAL IN ('ARBOL','INMEDIAT','TEM','MIG','REPARMIG','MIGGPON','GPON','AAA')  ".
                    " and RADICADO_TEMPORAL IN ('ARBOL','INMEDIAT','TEM','MIG','REPARMIG','MIGGPON')  ".
                    " AND ASESOR='' ".
                    " AND CONCEPTO_ID = '$concepto' ".
                    " AND STATUS='PENDI_PETEC' ".
                    $plaza2.
                    " ORDER BY RADICADO_TEMPORAL DESC ";

                //echo $sqlllamadas;

                $rr = $this->mysqli->query($sqlllamadas) or die($this->mysqli->error.__LINE__);

                if($rr->num_rows > 0){//recorro los registros de la consulta para
                    while($row = $rr->fetch_assoc()){//si encuentra un pedido ENTREGUELO COMO SEA NECESARIO!!!!!!!
                        $result[] = $row;
                        $mypedido=$row['PEDIDO_ID'];
                        $mypedidoresult=$rta;
                        $ATENCION_INMEDIATA="1";
                        break;
                    }
                }


                $concepto=" and b.CONCEPTO_ID IN ('PETEC','OKRED') and b.TIPO_ELEMENTO_ID IN ('ACCESP','INSIP','INSHFC','TO','TOIP','INSTA','INSTIP','STBOX','EQURED') ";
                */

            }

        }
        else if($concepto=="COORP"){
            $concepto=" and b.CONCEPTO_ID in ('PETEC') and (b.TIPO_ELEMENTO_ID IN ('E2MB','P2MB','INSTIP','CNTXIP','SEDECX','PLANT','PLP','PTLAN','MTLAN', 'PMULT','EPCM','PPCM','PBRI','PPRI','TV','TP','BDID','TDID','BDIDE1','TDIDE1','BDODE1','TDODE1','SLL','TC','SLLBRI','TCBRI','SLLE1','TCE1','SLLPRI','TCPRI','SEDEIP','CONECT','ACCESO') )";


        }

        else if($concepto=="14" || $concepto=="99" || $concepto=="O-101" || $concepto=="OT-C08" ){
            //echo var_dump("INGRESO");

            $parametroBusqueda= $this->buscarParametroFechaDemePedido('FECHA_ORDEN_DEMEPEDIDO_RECONFIGURACION');
            //reviso si hay llamadas que se deben hacer y las entrego de primeras
            $sqlllamadas="SELECT PEDIDO_ID,SUBPEDIDO_ID,SOLICITUD_ID,FECHA_ESTADO,FECHA_CITA, PROGRAMACION ".
                " FROM  informe_petec_pendientesm ".
                " WHERE ".
                " TIMEDIFF( NOW() , PROGRAMACION ) /3600 >0 ".
                " AND ASESOR='' ".
                " AND CONCEPTO_ID = '$concepto' ".
                " AND STATUS='PENDI_PETEC' ".
                " ORDER BY  TIMEDIFF( NOW() , PROGRAMACION ) /3600 DESC ";

            $rr = $this->mysqli->query($sqlllamadas) or die($this->mysqli->error.__LINE__);

            if($rr->num_rows > 0){//recorro los registros de la consulta para
                while($row = $rr->fetch_assoc()){//si encuentra un pedido ENTREGUELO COMO SEA NECESARIO!!!!!!!
                    $result[] = $row;
                    $mypedido=$row['PEDIDO_ID'];
                    $fechaprogramacion = $row['PROGRAMACION'];
                    $mypedidoresult=$rta;
                    break;
                }
            }

            //echo $mypedido;

            if($mypedido==""){
                //2017-02-03 Mauricio: se agrega funcionalidad para buscar por arbol en concepto 14
                //HAGO LA CONSULTA DE PRIORIDAD POR ARBOL

                $sqlllamadas="SELECT PEDIDO_ID,SUBPEDIDO_ID,SOLICITUD_ID,FECHA_ESTADO,FECHA_CITA ".
                    " FROM  informe_petec_pendientesm ".
                    " WHERE ".
                    " RADICADO_TEMPORAL IN ('ARBOL','INMEDIAT','TEM') ".
                    " AND ASESOR='' ".
                    " AND CONCEPTO_ID = '$concepto' ".
                    " AND STATUS='PENDI_PETEC' ".
                    $plaza2.
                    " ORDER BY FECHA_ESTADO ASC ";


                $rra = $this->mysqli->query($sqlllamadas) or die($this->mysqli->error.__LINE__);

                if($rra->num_rows > 0){//recorro los registros de la consulta para
                    while($row = $rra->fetch_assoc()){//si encuentra un pedido ENTREGUELO COMO SEA NECESARIO!!!!!!!
                        $result[] = $row;
                        $mypedido=$row['PEDIDO_ID'];
                        $mypedidoresult=$rta;
                        $ATENCION_INMEDIATA="1";
                        break;

                    }
                }

            }
            //$concepto=" and b.CONCEPTO_ID='$concepto' and b.TIPO_ELEMENTO_ID IN('ACCESP','INSIP','INSHFC','TO','TOIP','STBOX') and b.UEN_CALCULADA ='HG' AND b.PROGRAMACION='' ";
            $concepto=" and b.CONCEPTO_ID='$concepto' AND b.PROGRAMACION='' ";
        }

        else if($fuente=="SIEBEL"||$fuente=="EDATEL"){

            if($plaza=='TODOS'){
                $plaza2="";
            }else{
                $plaza2=" AND MUNICIPIO_ID='$plaza' ";
            }
            $parametroBusqueda= $this->buscarParametroFechaDemePedido('FECHA_ORDEN_DEMEPEDIDO_ASGINGACIONES_SIEBEL');
            $sqlllamadas=   "SELECT PEDIDO_ID, ".
                " SUBPEDIDO_ID, ".
                " SOLICITUD_ID, ".
                " FECHA_ESTADO, ".
                " FECHA_INGRESO, ".
                " FECHA_CITA ".
                " FROM  informe_petec_pendientesm ".
                " WHERE 1=1 ".
                //" and (TIPO_TRABAJO = 'NUEVO' ".//CAMBIO DE PRIORIDAD 2017-02-16
                //" AND UEN_CALCULADA = 'HG' ". //CAMBIO DE PRIORIDAD 2017-02-16
                " and RADICADO_TEMPORAL IN ('ARBOL','INMEDIAT','TEM','MIG','REPARMIG','MIGGPON','GPON','AAA')  ".
                " AND ASESOR='' ".
                " AND CONCEPTO_ID = '$concepto' ".
                " AND STATUS='PENDI_PETEC' ".
                $plaza2.
                " ORDER BY FECHA_INGRESO ASC ";


            $rr = $this->mysqli->query($sqlllamadas) or die($this->mysqli->error.__LINE__);

            if($rr->num_rows > 0){//recorro los registros de la consulta para
                while($row = $rr->fetch_assoc()){//si encuentra un pedido ENTREGUELO COMO SEA NECESARIO!!!!!!!
                    $result[] = $row;
                    $mypedido=$row['PEDIDO_ID'];
                    $mypedidoresult=$rta;
                    $ATENCION_INMEDIATA="1";
                    break;
                }
            }


            $concepto=" and b.CONCEPTO_ID in ('$concepto')";
        }else if($concepto=="STBOX"){
            $concepto=" and b.CONCEPTO_ID in ('PETEC','15') and (b.TIPO_ELEMENTO_ID IN ('STBOX') )";


        }else if($concepto=="EQURED"){

            $concepto=" and b.CONCEPTO_ID in ('PETEC') and (b.RADICADO_TEMPORAL = 'EQURED' )";

        }
        else if($concepto=="RENUM"){
            $concepto=" and b.CONCEPTO_ID='14' ";
            $STATUS="PENDI_RENUMS";
        }else if($concepto=="14B2B"){
            $concepto=" and b.CONCEPTO_ID='$concepto' and ( b.UEN_CALCULADA !='HG' ) ";
        }else{
            //$concepto=" and b.CONCEPTO_ID='$concepto' and b.TIPO_ELEMENTO_ID IN('ACCESP','INSIP','INSHFC','TO','TOIP','STBOX','EQURED')";
            $concepto=" and b.CONCEPTO_ID='$concepto' ";
        }

        if($plaza=="TODOS"){//para que sea posible obtener un registro de cualquier plaza

            if($fuente=='SIEBEL'){
                $plaza="";
            }else{
                $plaza=" AND MUNICIPIO_ID IN (select a.MUNICIPIO_ID from tbl_plazas a where a.MUNICIPIO_ID NOT IN ('BOG-COBRE','BOGCUNCOL'))";
            }
            //este

        }else{
            //$plaza=" AND b.MUNICIPIO_ID IN (select a.MUNICIPIO_ID from tbl_plazas a where a.PLAZA='$plaza') ";
            $plaza=" AND b.MUNICIPIO_ID IN ('$plaza') ";
        }

        //$parametroBusqueda= $this->buscarParametroFechaDemePedido('FECHA_ORDEN_DEMEPEDIDO');

        if($prioridad!=''){
            $parametroBusqueda=$prioridad;
        }


        //echo "Mi parametro: $parametroBusqueda";

        if($parametroBusqueda=="NUEVOS_PRIMERO"){
            $parametroBusqueda="FECHA_INGRESO,b.RADICADO_TEMPORAL ";
        }
        $pos = strrpos($concepto, "14");
        if ($pos === false) {} // note: three equal signs

        else{
            $parametroBusqueda = "FECHA_ESTADO";
        }


        $query1="select b.PEDIDO_ID,b.SUBPEDIDO_ID,b.SOLICITUD_ID,b.FECHA_ESTADO,b.FECHA_INGRESO,b.FECHA_CITA ".
            ",(SELECT a.user FROM vistas_pedidos  a where a.user='$user' AND b.PEDIDO_ID=a.pedido_id ".
            " AND a.fecha BETWEEN  '$today 00:00:00' AND  '$today 23:59:59' limit 1) as BEENHERE ".
            " from informe_petec_pendientesm b ".
            " where b.STATUS='$STATUS'  ".
            " and b.ASESOR ='' ".
            //" and b.TIPO_TRABAJO not in ('NUEVO') ".
            $concepto." ".
            $plaza.
            //" and b.CONCEPTO_ID='$concepto' ".
            //" AND b.MUNICIPIO_ID IN (select a.MUNICIPIO_ID from tbl_plazas a where a.PLAZA='$plaza') ".
            "order by b.$parametroBusqueda ASC";

            //echo $query1;

        if($mypedido==""){

            $rr = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);

            $mypedidoresult=array();
            $pedidos_ignorados="";
            if($rr->num_rows > 0){//recorro los registros de la consulta para
                while($row = $rr->fetch_assoc()){
                    $result[] = $row;

                    $pedidos_ignorados.=$row['PEDIDO_ID'].",";



                    //la idea es que este codigo permita optimizar el pedido entregado
                    //la idea es entregar de primero los pedidos con agenda para mañana cuando el parametro es fecha cita
                    /*if($parametroBusqueda=='FECHA_CITA'){
                        $today = date("Y-m-d");
                        $date = $row[FECHA_CITA];

                        if($today>=$date){
                            continue;
                        }
                    }*/

                    $rta=$this->pedidoOcupadoFenix($row);
                    //echo $rta;
                    if($rta=="No rows!!!!"){//me sirve, salgo del ciclo y busco este pedido...
                        //echo "el pedido es: ".$row['PEDIDO_ID'];

                        /*if($row['BEENHERE']==$user){
							$pedidos_ignorados=$pedidos_ignorados.$row['PEDIDO_ID'].',';
							//este pedido ya lo vio el dia de hoy
							//busco otro pedido----
							continue;
                                                }*/

                        $mypedido=$row['PEDIDO_ID'];
                        $mypedidoresult=$rta;
                        //echo var_dump($mypedido);
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
                $query1="select b.PEDIDO_ID,b.SUBPEDIDO_ID,b.SOLICITUD_ID,b.FECHA_ESTADO,b.FECHA_INGRESO, b.FECHA_CITA, b.TIPO_ELEMENTO_ID ".
                    " from informe_petec_pendientesm b ".
                    " where b.STATUS='$STATUS'  and b.ASESOR ='' ".
                    "  $concepto ".
                    $plaza.
                    //" AND b.MUNICIPIO_ID IN (select a.MUNICIPIO_ID from tbl_plazas a where a.PLAZA='$plaza') ".
                    " order by b.FECHA_INGRESO ASC";

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
            $SQL_UPDATE="update vistas_pedidos a set a.user='$user-CICLO' where a.user='$user' AND a.fecha BETWEEN '$today 00:00:00' AND '$today 23:59:59'";
            $xS = $this->mysqli->query($SQL_UPDATE);

            $pedds=explode(",", $pedidos_ignorados);
            if(count($pedds)>0){
                $mypedido=$pedds[0];
            }
        }
        $fecha_visto= date("Y-m-d H:i:s");
        //de una lo ocupo cucho cucho!!!!


        if($ATENCION_INMEDIATA=="1") $concepto="";

        $sqlupdate="update informe_petec_pendientesm set ASESOR='$user',PROGRAMACION='',VIEWS=VIEWS+1,FECHA_VISTO_ASESOR='$fecha_visto' where PEDIDO_ID = '$mypedido' and (STATUS='PENDI_PETEC'||STATUS='BUSCADO_PETEC' || STATUS='PENDI_RENUMS')";
        $x = $this->mysqli->query($sqlupdate);

        $query1="SELECT b.ID, ".
            " b.ID as PARENTID, ".
            " b.PEDIDO_ID, ".
            " b.SUBPEDIDO_ID, ".
            " b.SOLICITUD_ID, ".
            " b.TIPO_ELEMENTO_ID, ".
            " b.PRODUCTO, ".
            " b.PRODUCTO_ID,	".
            " b.UEN_CALCULADA, ".
            " b.ESTRATO, ".
            "  CASE ".
            "	 WHEN b.DESC_TIPO_TRABAJO='NUEVO-Identificador' AND b.TIPO_ELEMENTO_ID!='EQURED' AND b.UEN_CALCULADA IN ('HG','E3') AND b.ESTRATO='0' THEN TRUE ".
            "    WHEN b.DESC_TIPO_TRABAJO='NUEVO-Identificador' AND b.TIPO_ELEMENTO_ID!='EQURED' AND b.UEN_CALCULADA IN ('HG','E3') AND b.ESTRATO='' THEN TRUE ".
            "    WHEN b.DESC_TIPO_TRABAJO='NUEVO-Identificador' AND b.TIPO_ELEMENTO_ID!='EQURED' AND b.UEN_CALCULADA IN ('HG','E3') AND b.PAGINA_SERVICIO='' THEN TRUE ".
            "    ELSE FALSE ".
            "    END AS ESTRATOMALO, ".
            " b.MUNICIPIO_ID, ".
            " b.DEPARTAMENTO, ".
            " b.DIRECCION_SERVICIO, ".
            " b.PAGINA_SERVICIO, ".
            " b.TECNOLOGIA_ID,	".
            " CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(b.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA, ".
            " CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(b.FECHA_INGRESO)) AS CHAR(255)) as TIEMPO_INGRESO,	".
            " b.FUENTE, ".
            " b.GRUPO, ".
            " b.ACTIVIDAD, ".
            " b.CONCEPTO_ID, ".
            " b.FECHA_ESTADO, ".
            " b.FECHA_INGRESO, ".
            " b.USUARIO_BLOQUEO_FENIX, ".
            " b.TIPO_TRABAJO, ".
            " b.DESC_TIPO_TRABAJO,	".
            " b.CONCEPTO_ANTERIOR, ".
            " b.FECHA_CITA, ".
            " b.CANTIDAD_EQU, ".
            " b.EQUIPOS, ".
            " b.CONCEPTOS_EQU, ".
            " b.TIPO_EQUIPOS,	".
            " b.EXTENSIONES,  ".
            " b.OBSERVACIONES,  ".
            " b.EJECUTIVO_ID, ".
            " b.CANAL_ID, ".
            " b.VEL_IDEN, ".
            " b.VEL_SOLI, ".
            " b.IDENTIFICADOR_ID, ".
            " b.CELULAR_AVISAR, ".
            " b.TELEFONO_AVISAR,	".
            " b.PROGRAMACION, ".
            " case when b.RADICADO_TEMPORAL in ('ARBOL','INMEDIAT') then 'ALTA' else 'NORMAL' end as PRIORIDAD, 	".
            " b.APROVISIONADOR, ".
            " b.PEDIDO_CRM ".
            " from informe_petec_pendientesm b 	".
            " where b.PEDIDO_ID = '$mypedido' and b.STATUS='$STATUS' $concepto order by b.FECHA_INGRESO asc";



        //"SELECT b.ID,b.PEDIDO_ID,b.SUBPEDIDO_ID,b.SOLICITUD_ID,b.TIPO_ELEMENTO_ID,b.PRODUCTO,b.UEN_CALCULADA,b.ESTRATO,b.MUNICIPIO_ID,b.DIRECCION_SERVICIO,b.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(b.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,b.FUENTE,b.CONCEPTO_ID,b.FECHA_ESTADO,b.USUARIO_BLOQUEO_FENIX,b.TIPO_TRABAJO,b.CONCEPTO_ANTERIOR,b.FECHA_CITA,b.CANTIDAD_EQU,b.EQUIPOS,b.CONCEPTOS_EQU,b.TIPO_EQUIPOS,b.EXTENSIONES, b.OBSERVACIONES,  b.EJECUTIVO_ID, b.CANAL_ID from informe_petec_pendientesm b where b.PEDIDO_ID = '$mypedido' and b.STATUS='PENDI_PETEC' $concepto ";

        $r = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $ids="";
            $sep="";
            while($row = $r->fetch_assoc()){
                $observaciones = $this->quitar_tildes(utf8_encode($row['OBSERVACIONES']));
                $direccion = $this->quitar_tildes(utf8_encode($row['DIRECCION_SERVICIO']));
                $row['OBSERVACIONES'] = $observaciones;
                $row['DIRECCION_SERVICIO'] = $direccion;
                $row['PROGRAMACION']= $fechaprogramacion ;
                $result[] = $row;
                $ids=$ids.$sep.$row['ID'];
                $sep=",";
            }
            //$sqlupdate="update informe_petec_pendientesm set ASESOR='$user',VIEWS=VIEWS+1 where ID in ($ids)";
            //$x = $this->mysqli->query($sqlupdate);
            $INSERTLOG="insert into vistas_pedidos(user,pedido_id) values ('$user','$mypedido')";
            $x = $this->mysqli->query($INSERTLOG);

            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$mypedido' ".
                ",'USO DEMEPEDIDO' ".
                ",'PEDIDO GENERADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            //$sqlfeed="insert into portalbd.activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$user','$username','','','PEDIDO: $mypedido','DEMEPEDIDO')";
            //$rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

            //sleep(20);
            unlink($filename);
            echo json_encode($result);
            $this->response('', 200); // send user details
        }else{//i have pretty heavy problems over here...
            //$this->response('SYSTEM PANIC!',200);
            $this->response('No hay registros!',200);
        }
        unlink($filename);

        $this->response('nothing',200);        // If no records "No Content" status
    }

//***********************************Michael Edatel****************************************

private function demePedidoEdatel(){


        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $user           =   $this->_request['userID'];
        $concepto       =   $this->_request['concepto'];
        $localidad      =   $this->_request['plaza'];
        $fuente         =   $this->_request['fuente'];
        $username       =   $this->_request['username'];
        $prioridad      =   $this->_request['prioridad'];

        //echo "concepto: $concepto, plaza: $localidad\n\n";

        if($localidad=="TODOS"){
            $localidad="";
        }else{
            $localidad=" AND LOCALIDAD='$localidad' " ;
        }

        $sqlllamadas="SELECT * ".
            " FROM  portalbd.pendientes_edatel ".
            " WHERE TIPO_TRANSACCION = 'GEOREFERENCIA' ".
            " AND ASESOR='ND' ".
            " AND TIPO_TRANSACCION = '$concepto' ".
            " AND STATUS='PENDIENTE' ".
            $localidad.
            " ORDER BY FECHA_CARGA,ID ASC ";

        //echo $sqlllamadas;
        //echo var_dump($plaza2);

        $rra = $this->mysqli->query($sqlllamadas) or die($this->mysqli->error.__LINE__);



        if($rra->num_rows > 0){//recorro los registros de la consulta para
            while($row = $rra->fetch_assoc()){//si encuentra un pedido ENTREGUELO COMO SEA NECESARIO!!!!!!!
                $result[] = $row;
                $mypedido=$row['ID'];
                //echo var_dump($mypedido);
                $mypedidoresult=$rta;
                break;
            }


        }




        $query1="select * ".
        ",CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(FECHA_CARGA)) AS CHAR(255)) as TIEMPO_COLA ".
        " from pendientes_edatel where ID = '$mypedido' and STATUS='PENDIENTE'";


        //"SELECT b.ID,b.PEDIDO_ID,b.SUBPEDIDO_ID,b.SOLICITUD_ID,b.TIPO_ELEMENTO_ID,b.PRODUCTO,b.UEN_CALCULADA,b.ESTRATO,b.MUNICIPIO_ID,b.DIRECCION_SERVICIO,b.PAGINA_SERVICIO,CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(b.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA,b.FUENTE,b.CONCEPTO_ID,b.FECHA_ESTADO,b.USUARIO_BLOQUEO_FENIX,b.TIPO_TRABAJO,b.CONCEPTO_ANTERIOR,b.FECHA_CITA,b.CANTIDAD_EQU,b.EQUIPOS,b.CONCEPTOS_EQU,b.TIPO_EQUIPOS,b.EXTENSIONES, b.OBSERVACIONES,  b.EJECUTIVO_ID, b.CANAL_ID from informe_petec_pendientesm b where b.PEDIDO_ID = '$mypedido' and b.STATUS='PENDI_PETEC' $concepto ";

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
            $INSERTLOG="insert /**/into vistas_pedidos(user,pedido_id) values ('$user','$mypedido')";
            $x = $this->mysqli->query($INSERTLOG);

            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$mypedido' ".
                ",'USO DEMEPEDIDO' ".
                ",'PEDIDO GENERADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);

            //sleep(20);
            unlink($filename);
            echo json_encode($result);
            $this->response('', 200); // send user details
        }else{//i have pretty heavy problems over here...
            //$this->response('SYSTEM PANIC!',200);
            $this->response('No hay registros!',200);
        }
        unlink($filename);

        $this->response('nothing',200);        // If no records "No Content" status
    }


    private function municipiosAsignacionesEdatel(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $params 	= json_decode(file_get_contents('php://input'),true);
        $concepto 	= $params['concepto'];
        $fuente     = $params['fuente'];
        $today		= date("Y-m-d");


        $filtros= " and o.STATUS ='PENDIENTE' ".
                    " GROUP BY o.LOCALIDAD ORDER BY COUNT(*) DESC ";

        $query=	" SELECT ".
            "	o.LOCALIDAD ".
            ",	COUNT(*) AS COUNTER ".
            "	FROM portalbd.pendientes_edatel o ".
            "	where 1=1 and o.TIPO_TRANSACCION = 'GEOREFERENCIA' ".
            " 	$filtros ";

        //echo $query;

        $rst = $this->mysqli->query($query);

        if ($rst->num_rows > 0){

            $resultado=array();
            $resultado[]=array("LOCALIDAD"=>"TODOS","COUNTER"=>"TODOS");
            while($row=$rst->fetch_assoc()){
                $resultado[]=$row;


            }
            $this->response($this->json($resultado), 201);


        }
        else{
            $error="Error. Este concepto no tiene pendientes";
            $this->response($this->json(array($error)), 400);
        }

    }

//*********************************************************************************************

//--------------------------demepedido activacion----------------------
    private function demePedidoActivacion(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $user = $this->_request['userID'];
        $transaccion = $this->_request['transaccion'];
        $tabla = $this->_request['tabla'];
        $producto= $this->_request['producto'];

      //  echo " esta es la tabla ". $tabla ;
        $filename = '../tmp/control-threads-agen.txt';
        if(file_exists($filename)){
            sleep(1);
        }else{
            $file = fopen($filename, 'w') or die("can't create file");
            fclose($file);
        }


        $pedido_actual = $this->_request['pedido_actual'];

        $user=strtoupper($user);


        if($tabla=='ACTIVADOR_SUSPECORE'){

            $sqlupdate="update gestor_activacion_pendientes_activador_suspecore set ASESOR='' where ASESOR='$user'";
        }else {
            $sqlupdate="update gestor_activacion_pendientes_activador_dom set ASESOR='' where ASESOR='$user'";
        }

       // echo $sqlupdate;
        $xxx = $this->mysqli->query($sqlupdate);

        $today = date("Y-m-d");

        $parametroBusqueda= $this->buscarParametroFechaDemePedido('FECHA_ORDEN_DEMEPEDIDO_ACTIVACION');

        //  echo "carlitos1 ---$producto---";

        if($producto!=""){
            $producto=" and b.PRODUCTO='$producto' ";
        }else{
            $producto="";
        }

        if($transaccion!=""){
            $transaccion=" and b.TRANSACCION='$transaccion' ";
        }else{
            $transaccion="";
        }

        if($tabla=='ACTIVADOR_SUSPECORE'){

            $tabla1 = " from gestor_activacion_pendientes_activador_suspecore b " ;
            $MOTIVOEXCEPCIONACT = " and b.MOTIVOEXCEPCIONACT <> 'La Cuenta NO existe.' ";
        } else {

            $tabla1 = " from gestor_activacion_pendientes_activador_dom b " ;
            $MOTIVO_ERROR = " and b.MOTIVO_ERROR <> 'La Cuenta NO existe.' ";


        }

        $mypedido="";



        if($parametroBusqueda=='') $parametroBusqueda ='FECHA_EXCEPCION';

        $query1=" select distinct b.PEDIDO,b.FECHA_EXCEPCION ".
            " ,(SELECT a.user FROM vistas_pedidos  a where a.user='$user' AND b.PEDIDO=a.PEDIDO_ID ".
            " AND a.fecha BETWEEN '$today 00:00:00' AND '$today 23:59:59' limit 1) as BEENHERE ".
            " $tabla1 ".
            "  where b.STATUS='PENDI_ACTI' and b.ASESOR ='' ".
            "  $transaccion ".
            " $producto ".
            " $MOTIVOEXCEPCIONACT ".
            "  $MOTIVO_ERROR ".
            " order by b.$parametroBusqueda  ASC";

       // echo $query1;
        if($mypedido==""){

            $rr = $this->mysqli->query($query1);
            $mypedidoresult=array();
            $pedidos_ignorados="";
            if($rr->num_rows > 0){
                while($row = $rr->fetch_assoc()){
                    $result[] = $row;

                    if($row['BEENHERE']==$user){
                        $pedidos_ignorados=$pedidos_ignorados.$row['PEDIDO'].',';
                        continue;
                    }

                    $mypedido=$row['PEDIDO'];
                    $mypedidoresult=$rta;
                    break;

                }
                //pedidos viejos
            }/* else {
                $query2=" select distinct b.PEDIDO, b.FECHA_CARGA ,b.ID ".
                    $tabla.
                    " where b.STATUS='PENDI_ACTI' and b.ASESOR ='' ".
                    " and FECHA_CARGA between '$today 00:00:00' and '$today 23:59:59' ".
                    $transaccion.
                    $producto.
                     " order by b.$parametroBusqueda  ASC";
                echo $query2;
                $r = $this->mysqli->query($query2);
                $mypedido="";
                $mypedidoresult=array();
                if($r->num_rows > 0){
                    while($row = $r->fetch_assoc()){
                        $result[] = $row;

                  //      $rta=$this->pedidoOcupadoFenix($row);

                        if($rta=="No rows!!!!"){


                            $mypedido=$row['PEDIDO'];
                            $mypedidoresult=$rta;
                            break;
                        }

                    }

                }

            }//end if
        */
        }//end mypedido if

        if($mypedido==''){

            $SQL_UPDATE="update vistas_pedidos a set a.user='$user-CICLO' where a.user='$user' AND a.fecha BETWEEN '$today 00:00:00' AND '$today 23:59:59'";
            $xS = $this->mysqli->query($SQL_UPDATE);
            $pedds=explode(",", $pedidos_ignorados);
            if(count($pedds)>0){
                $mypedido=$pedds[0];
            }
        }
        $fecha_visto= date("Y-m-d H:i:s");




        $query1=" SELECT DISTINCT b.ID ".
            " ,b.PEDIDO,b.ORDER_SEQ_ID,b.ESTADO,b.TAREA_EXCEPCION,b.IDSERVICIORAIZ,b.TRANSACCION,b.STATUS,b.ASESOR,b.MOTIVOEXCEPCIONACT,b.DESCRIPCIONEXCEPCIONACT,b.VALOR_ERROR,b.MOTIVO_ERROR ".
            ",b.ACTIVIDAD,b.FUENTE,b.GRUPO".
            " , group_concat(distinct b.PRODUCTO) as  PRODUCTO ".
            " , min(b.FECHA_EXCEPCION) as FECHA_EXCEPCION ".
            " ,min(b.FECHA_CREACION) as FECHA_CREACION ".
            " ,cast(TIMESTAMPDIFF(HOUR,(b.FECHA_EXCEPCION),CURRENT_TIMESTAMP())/24 AS decimal(5,2)) as TIEMPO_TOTAL".
            " , (select a.TIPIFICACION from gestor_historico_activacion a  ".
            " where a.PEDIDO='$mypedido'and a.TIPIFICACION='' order by a.ID desc limit 1) as HISTORICO_TIPIFICACION  ".
            $tabla1.
            " where b.PEDIDO = '$mypedido'  ".
            " and b.STATUS='PENDI_ACTI' ".
            $transaccion.
            $producto.
            $MOTIVOEXCEPCIONACT.
            $MOTIVO_ERROR.
            " group by b.pedido ";


    // echo $query1;
        $r = $this->mysqli->query($query1);

        if($r->num_rows > 0){
            $result = array();
            $ids="";
            $sep="";
            while($row = $r->fetch_assoc()){
                $result[] = $row;
                $ids=$ids.$sep.$row['ID'];
                $sep=",";
            }
            
   if($tabla=='ACTIVADO_DOM'){
                       // echo "tabla es activaDOM".$tabla;   
        $sqlupdate1="update gestor_activacion_pendientes_activador_dom set ASESOR='$user' where ID in ($ids) and pedido='$mypedido'";
        }else {
           //  echo "tabla es suspecore".$tabla;  
            $sqlupdate1="update gestor_activacion_pendientes_activador_suspecore set ASESOR='$user' where ID in ($ids) and pedido='$mypedido'";
        }

           //  echo $sqlupdate1;          
            $x = $this->mysqli->query($sqlupdate1);

            $INSERTLOG="insert into vistas_pedidos(user,pedido_id) values ('$user','$mypedido')";
            $x = $this->mysqli->query($INSERTLOG);

            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$mypedido' ".
                ",'USO DEMEPEDIDO' ".
                ",'PEDIDO GENERADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed

            //sleep(20);
            unlink($filename);
            echo json_encode($result);
            $this->response('', 200); // send user details
        }else{
            $this->response(json_encode('No hay registros!'),204);
        }
        unlink($filename);

        $this->response('nothing',204);        // If no records "No Content" status
    }



//--------------------fin demepedido activacion------------------------------


//--------------------------demepedido activacion----------------------amarillas
    private function demePedidoAmarillas(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $user = $this->_request['userID'];

        $transaccion = $this->_request['transaccion'];



        $filename = '../tmp/control-threads-agen.txt';
        if(file_exists($filename)){
            sleep(1);
        }else{
            $file = fopen($filename, 'w') or die("can't create file");
            fclose($file);
        }


        $pedido_actual = $this->_request['pedido_actual'];

        $user=strtoupper($user);




        //echo $sqlupdate;
        $xxx = $this->mysqli->query($sqlupdate);

        $today = date("Y-m-d");

        // $parametroBusqueda= $this->buscarParametroFechaDemePedido('FECHA_ORDEN_DEMEPEDIDO_AMARILLAS');

        //  echo "carlitos1 ---$producto---";

        /*if($transaccion!=""){
            $transaccion=" and b.TRANSACCION='$transaccion' ";
        }else{
            $transaccion="";
        }
*/


        $mypedido="";



        //if($parametroBusqueda=='') $parametroBusqueda ='FECHA_EXCEPCION';

        $query2=" select  b.PEDIDO,b.FECHA_EXCEPCION,b.FECHA_CARGA ".
            " ,(SELECT a.user FROM vistas_pedidos  a where a.user='$user' AND b.PEDIDO=a.PEDIDO_ID ".
            " AND a.fecha BETWEEN '$today 00:00:00' AND '$today 23:59:59' limit 1) as BEENHERE ".
            " from pendientes_amarillas b".
            "  where b.STATUS='PENDI_ACTI' and b.asesor='' ".
            " order by b.FECHA_CARGA ASC";

       // echo $query2;
        if($mypedido==""){

            $rr = $this->mysqli->query($query2);
            $mypedidoresult=array();
            $pedidos_ignorados="";
            if($rr->num_rows > 0){
                while($row = $rr->fetch_assoc()){
                    $result[] = $row;

                    if($row['BEENHERE']==$user){
                        $pedidos_ignorados=$pedidos_ignorados.$row['PEDIDO'].',';
                        continue;
                    }

                    $mypedido=$row['PEDIDO'];
                    $mypedidoresult=$rta;
                    break;

                }
                //pedidos viejos
            }
        }//end mypedido if

        if($mypedido==''){

            $SQL_UPDATE="update vistas_pedidos a set a.user='$user-CICLO' where a.user='$user' AND a.fecha BETWEEN '$today 00:00:00' AND '$today 23:59:59'";
            $xS = $this->mysqli->query($SQL_UPDATE);
            $pedds=explode(",", $pedidos_ignorados);
            if(count($pedds)>0){
                $mypedido=$pedds[0];
            }
        }
        $fecha_visto= date("Y-m-d H:i:s");




        $query1=" SELECT b.ID  ".
            " ,b.PEDIDO,b.ORDER_SEQ_ID,b.ESTADO,b.TRANSACCION,b.PRODUCTO,b.FECHA_EXCEPCION,b.FECHA_CARGA,b.TABLA,b.TIPO_COMUNICACION,b.TAREA_EXCEPCION,b.DEPARTAMENTO,b.STATUS,b.ASESOR ".
            ",b.ACTIVIDAD,b.FUENTE,b.GRUPO".
            " , group_concat(b.PRODUCTO) as  PRODUCTO ".
            " , min(b.FECHA_EXCEPCION) as FECHA_EXCEPCION ".
            " ,cast(TIMESTAMPDIFF(HOUR,(b.FECHA_EXCEPCION),CURRENT_TIMESTAMP())/24 AS decimal(5,2)) as TIEMPO_TOTAL".
            " , (select a.TIPIFICACION from gestor_historico_activacion a  ".
            " where a.PEDIDO='$mypedido'and a.TIPIFICACION='' order by a.ID desc limit 1) as HISTORICO_TIPIFICACION  ".
            " from pendientes_amarillas b".
            " where b.PEDIDO = '$mypedido'  ".
            " and b.STATUS='PENDI_ACTI' ".
            " group by b.pedido ";


        //  echo $query1;
        $r = $this->mysqli->query($query1);

        if($r->num_rows > 0){
            $result = array();
            $ids="";
            $sep="";
            while($row = $r->fetch_assoc()){
                $row['PRODUCTO']=utf8_encode($row['PRODUCTO']);
                $result[] = $row;
                $ids=$ids.$sep.$row['ID'];
                $sep=",";
            }



            $sqlupdate1="update pendientes_amarillas set ASESOR='$user' where ID in ($ids) and pedido='$mypedido'";

            //echo $sqlupdate1;
            $x = $this->mysqli->query($sqlupdate1);

            $INSERTLOG="insert into vistas_pedidos(user,pedido_id) values ('$user','$mypedido')";
            $x = $this->mysqli->query($INSERTLOG);

            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$mypedido' ".
                ",'USO DEMEPEDIDO' ".
                ",'PEDIDO GENERADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed

            //sleep(20);
            unlink($filename);
            echo json_encode($result);
            $this->response('', 200); // send user details
        }else{
            $this->response(json_encode('No hay registros!'),204);
        }
        unlink($filename);

        $this->response('nothing',204);        // If no records "No Content" status
    }


//--------------------fin demepedido activacion------------------------------amarillas


//----------------------------------demepedido agendamiento----------------------------


    private function demePedidoAgendamiento(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $user = $this->_request['userID'];
        $departamento = $this->_request['departamento'];
        //$plaza = $this->_request['plaza'];
        $microzona = $this->_request['microzona'];
        $proceso = $this->_request['proceso'];
        $zona = $this->_request['zona'];
        $username=$this->_request['username'];

        $tipo_trabajo=$this->_request['tipo_trabajo'];

        //var_dump($proceso);
        $filename = '../tmp/control-threads-agen.txt';
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
        $user=strtoupper($user);
        //NO SE PUEDE CONDICIONAR AL PEDIDO ACTUAL, SI LE DA F5 A LA PAGINA NO HAY PEDIDO ACTUAL.. ES MEJOR ASI!!!
        $sqlupdate="update gestor_pendientes_reagendamiento set ASESOR='' where ASESOR='$user'";
        //echo $sqlupdate;
        $xxx = $this->mysqli->query($sqlupdate);
        //}
        //echo "WTF";
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

        if($departamento=="VACIOS"){
            $departamento="";
        }   else if ($departamento=="DTH" ){
            $departamento=" and b.TECNOLOGIA_ID='".utf8_decode($departamento)."' and b.PROCESO='$proceso' ";
        } else if ($departamento=="EDATEL"){
            if($proceso==""||$proceso=="TODO"){
                $departamento=" and b.DEPARTAMENTO='EDATEL' ";
            }else{
                $departamento=" and b.DEPARTAMENTO='EDATEL' and b.PROCESO='$proceso' ";
            }

        } else{
            if($proceso==""||$proceso=="TODO"){
                $departamento=" and b.DEPARTAMENTO='".utf8_decode($departamento)."' ";
            }else{
                $departamento=" and b.DEPARTAMENTO='".utf8_decode($departamento)."' and b.PROCESO='$proceso' ";
            }

        }

        if($zona!=""){
            $zona=" and b.SUBZONA_ID='".utf8_decode($zona)."' ";
        }

        if($departamento=="DTH") $departamento=" and b.TECNOLOGIA_ID='".utf8_decode($departamento)."' ";


        if($microzona!=""){
            $microzona=" and b.MICROZONA='".utf8_decode($microzona)."' ";
        }
        //var_dump($microzona) ;

        ##SE DEBE BUSCAR PRIMERO LOS PROGRAMADOS PARA ASIGNARLOS.....
        //PENDIENTE: COLOCAR CODIGO PARA TENER EN CUENTA LA PROGRAMACION....................
        $sql="SELECT b.PEDIDO_ID, b.FECHA_CITA_FENIX, TIMEDIFF( b.PROGRAMACION, NOW( ) ) /3600, b.PROGRAMACION ,b.PROCESO,b.IDENTIFICADOR_ID ".
            " FROM gestor_pendientes_reagendamiento b ".
            " WHERE b.ASESOR =  '' ".
            " AND b.STATUS =  'PENDI_AGEN' ".
            " AND TIMEDIFF( b.PROGRAMACION, NOW( ) ) /3600 <0 ".
            " and b.CONCEPTOS NOT LIKE '%REAGE%' ".
            $departamento.
            $zona.
            $microzona.
            " and (select count(*) from gestor_historicos_reagendamiento a where a.PEDIDO_ID=b.PEDIDO_ID and a.FECHA_FIN between '$today 00:00:00' and '$today 23:59:59') <1 ".
            //" and (b.MIGRACION='NO' or b.MIGRACION='' or b.MIGRACION is null ) ".
            " ORDER BY PROGRAMACION ASC ";
        //PENDIENTE: COLOCAR CODIGO PARA TENER EN CUENTA LA PROGRAMACION....................
        $PROGRAMADO="NOT";
        //echo $sql;

        $rr = $this->mysqli->query($sql) or die($this->mysqli->error.__LINE__);

        if($rr->num_rows > 0){//recorro los registros de la consulta para
            while($row = $rr->fetch_assoc()){//si encuentra un pedido ENTREGUELO
                $result[] = $row;
                $mypedido=$row['PEDIDO_ID'];
                $PROGRAMADO=$row['PROGRAMACION'];
                $mypedidoresult=$rta;
                break;
            }
        }

        //2017-03-29: se agrega codigo para que se entregue pedido segun prioridad definida...
        if($mypedido==""){

            $sqlPrioridad=   "SELECT b.PEDIDO_ID ".
                " FROM  gestor_pendientes_reagendamiento b ".
                " WHERE 1=1 ".
                " and b.RADICADO IN ('ARBOL')  ".
                " AND b.ASESOR='' ".
                " AND b.STATUS='PENDI_AGEN' ".
                $departamento.
                $zona.
                $microzona.
                " ORDER BY FECHA_INGRESO ASC ";

            $rr = $this->mysqli->query($sqlPrioridad) or die($this->mysqli->error.__LINE__);

            if($rr->num_rows > 0){//recorro los registros de la consulta para
                while($row = $rr->fetch_assoc()){//si encuentra un pedido ENTREGUELO COMO SEA NECESARIO!!!!!!!
                    $result[] = $row;
                    $mypedido=$row['PEDIDO_ID'];
                    $mypedidoresult=$rta;
                    $ATENCION_INMEDIATA="1";
                    break;
                }
            }

        }

        if($proceso=="INSTALACION"){
            $parametroBusqueda= $this->buscarParametroFechaDemePedido('FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO');
            $ordenamiento = $this->buscarParametroFechaDemePedido('PRIORIDAD_DEMEPEDIDO_AGENDAMIENTO');
        }else if($proceso=="REPARACION"){
            $parametroBusqueda= $this->buscarParametroFechaDemePedido('FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO_REPA');
            $ordenamiento = $this->buscarParametroFechaDemePedido('PRIORIDAD_DEMEPEDIDO_AGENDAMIENTO_REPA');
        }else{
            $parametroBusqueda= $this->buscarParametroFechaDemePedido('FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO_EDATEL');
            $ordenamiento = $this->buscarParametroFechaDemePedido('PRIORIDAD_DEMEPEDIDO_AGENDAMIENTO_EDATEL');

        }

        if($prioridad!=''){
            $parametroBusqueda=$prioridad;
        }
        if($parametroBusqueda=='') $parametroBusqueda ='FECHA_ESTADO';


        if($tipo_trabajo==""){
            $tipo_trabajo="NO APLICA";
        }

        if($tipo_trabajo!="NO APLICA"){
            $tipo_trabajo=" and b.TIPO_TRABAJO='$tipo_trabajo' ";
        }else{
            $tipo_trabajo="";
        }

        $query1="select b.PEDIDO_ID,b.FECHA_CITA_FENIX ".
            ",(SELECT a.user FROM vistas_pedidos  a where a.user='$user' AND b.PEDIDO_ID=a.pedido_id ".
            " AND a.fecha BETWEEN '$today 00:00:00' AND '$today 23:59:59' limit 1) as BEENHERE ".
            " from gestor_pendientes_reagendamiento b ".
            " where b.STATUS='PENDI_AGEN'  ".
            " and b.ASESOR ='' ".
            " and (b.FECHA_CITA_FENIX < CURDATE() OR b.FECHA_CITA_FENIX='9999-00-00') ".
            " and (b.FECHA_CITA_REAGENDA < CURDATE() OR b.FECHA_CITA_REAGENDA='9999-00-00' OR b.FECHA_CITA_REAGENDA='') ".
            //" and (b.MIGRACION='NO' or b.MIGRACION='' or b.MIGRACION is null ) ".
            " and b.CONCEPTOS NOT LIKE '%REAGE%' ".
            " AND (b.PROGRAMACION='' or b.PROGRAMACION is null ) ".
            " and (select count(*) from gestor_historicos_reagendamiento a where a.PEDIDO_ID=b.PEDIDO_ID and FECHA_FIN between '$today 00:00:00' and '$today 23:59:59') <1 ".
            " and (select count(*) from gestor_historicos_reagendamiento a where a.PEDIDO_ID=b.PEDIDO_ID and a.NOVEDAD IN ('ESCALADO CR','RECUPERAR EQUIPOS','RETIRAR ACCESO','ERROR SIEBEL','CUMPLIDO','PENDIENTE AGENDA','ANULADO','OTROS CONCEPTO ESCALADO','NO APLICA GESTION','DESINSTALAR','YA ESTA AGENDADO','ERRORES DE FACTURACION','INFORMACION-PARA CONFIRMAR LLEGADA DE TECNICO','ESCALAMIENTO INSTALACIONES ANTIOQUIA','INFORMACION GENERAL') and a.FECHA_FIN between '$today 00:00:00' and '$today 23:59:59' ) <1 ".
            $departamento.
            $zona.
            $microzona.
            $tipo_trabajo.
            //$plaza.
            //" order by b.$parametroBusqueda ASC";
            " order by b.$parametroBusqueda $ordenamiento ";
        //echo $query1;
        //echo "\nmypedicure: $mypedido";

        if($mypedido==""){

            $rr = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);
            $mypedidoresult=array();
            $pedidos_ignorados="";
            if($rr->num_rows > 0){//recorro los registros de la consulta para
                while($row = $rr->fetch_assoc()){
                    $result[] = $row;

                    //$rta=$this->pedidoOcupadoFenix($row);
                    //if($rta=="No rows!!!!"){//me sirve, salgo del ciclo y busco este pedido...
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
                    //} //2016-04-12: SE QUITO VALIDACION CONTRA FENIX

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
                $query1="select b.PEDIDO_ID, b.FECHA_CITA_FENIX ".
                    " from gestor_pendientes_reagendamiento b ".
                    " where b.STATUS='PENDI_AGEN'  and b.ASESOR ='' ".
                    "  $departamento ".
                    $zona.
                    $microzona.
                    // " and (b.MIGRACION='NO' or b.MIGRACION='' or b.MIGRACION is null ) ".
                    " and (select NOVEDAD from gestor_historicos_reagendamiento a where a.PEDIDO_ID=b.PEDIDO_ID and FECHA_FIN between '$today 00:00:00' and '$today 23:59:59' order by id desc limit 1) not like '%AGENDADO%' ".
                    //$plaza.
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

                    }

                }

            }//end if

        }//end mypedido if

        if($mypedido==''){//ya los visite todos, que hago???
            //echo "YA LOS VISITE TODOS, QUE HAGO?";
            //EN LAS VISTAS, ACTUALIZO EL USUARIO PARA RESETEAR LA BUSQUEDA
            $SQL_UPDATE="update vistas_pedidos a set a.user='$user-CICLO' where a.user='$user' AND a.fecha BETWEEN '$today 00:00:00' AND '$today 23:59:59'";
            $xS = $this->mysqli->query($SQL_UPDATE);
            $pedds=explode(",", $pedidos_ignorados);
            if(count($pedds)>0){
                $mypedido=$pedds[0];
            }
        }
        $fecha_visto= date("Y-m-d H:i:s");
        //de una lo ocupo cucho cucho!!!!
        $sqlupdate="update gestor_pendientes_reagendamiento set ASESOR='$user',PROGRAMACION='',VIEWS=VIEWS+1,FECHA_VISTO_ASESOR='$fecha_visto' where PEDIDO_ID = '$mypedido' and STATUS='PENDI_AGEN'";
        $x = $this->mysqli->query($sqlupdate);


        if($PROGRAMADO!="NOT"){
            $PROGRAMADO=", '$PROGRAMADO' as PROGRAMADO ";
        }else{
            $PROGRAMADO="";
        }

        $query1="SELECT b.ID as PARENT_ID,b.PEDIDO_ID,b.FECHA_CITA_FENIX,b.CLIENTE_ID,b.CELULAR_AVISAR,b.CORREO_UNE,b.DIRECCION_ENVIO,b.E_MAIL_AVISAR,b.NOMBRE_USUARIO,b.FECHA_INGRESO,b.TELEFONO_AVISAR,b.CONCEPTOS,b.ACTIVIDADES,b.PROCESO,b.SUBZONA_ID,cast(TIMESTAMPDIFF(HOUR,(b.FECHA_INGRESO),CURRENT_TIMESTAMP())/24 AS decimal(5,2)) as TIEMPO_TOTAL,b.MICROZONA,b.OBSERVACION_FENIX,b.FUENTE,b.TODAY_TRIES,b.PROGRAMACION,b.FECHA_ESTADO $PROGRAMADO,'AUTO' as source,(select a.NOVEDAD from gestor_historicos_reagendamiento a where a.PEDIDO_ID='$mypedido' order by a.ID desc limit 1) as HISTORICO_NOVEDAD from gestor_pendientes_reagendamiento b where b.PEDIDO_ID = '$mypedido' and b.STATUS='PENDI_AGEN' ";

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
            $sqlupdate="update gestor_pendientes_reagendamiento set ASESOR='$user',VIEWS=VIEWS+1 where ID in ($ids)";
            $x = $this->mysqli->query($sqlupdate);
            $INSERTLOG="insert into vistas_pedidos(user,pedido_id) values ('$user','$mypedido')";
            $x = $this->mysqli->query($INSERTLOG);

            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$mypedido' ".
                ",'USO DEMEPEDIDO' ".
                ",'PEDIDO GENERADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            //$sqlfeed="insert into portalbd.activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$user','$username','','','PEDIDO: $mypedido','DEMEPEDIDO')";
            //$rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

            //sleep(20);
            unlink($filename);
            echo json_encode($result);
            $this->response('', 200); // send user details
        }else{//i have pretty heavy problems over here...
            //$this->response('SYSTEM PANIC!',200);
            $this->response(json_encode('No hay registros!'),200);
        }
        unlink($filename);

        $this->response('nothing',204);        // If no records "No Content" status
    }

//---------------------------------- fin demepedido agendamiento----------------------------

    private function demePedidoAgendamientomalo(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $user = $this->_request['userID'];
        $proceso = $this->_request['proceso'];
        $departamento = $this->_request['departamento'];
        //$plaza = $this->_request['plaza'];
        // $microzona = $this->_request['microzona'];
        // $zona = $this->_request['zona'];
        $username=$this->_request['username'];
        $NOVEDAD='';
        $filename = '../tmp/control-threads-agen.txt';
        if(file_exists($filename)){
            sleep(1);
        }else{
            $file = fopen($filename, 'w') or die("can't create file");
            fclose($file);
        }
        // var_dump($departamento);
        $user=strtoupper($user);
        //si el actual usuario tenia un pedido "agarrado, hay que liberarlo"
        $pedido_actual = $this->_request['pedido_actual'];
        //if($pedido_actual!=''){//en este caso tenia pedido antes, estaba trabajando uno, debo actualizarlo para dejarlo libre
        $user=strtoupper($user);
        //NO SE PUEDE CONDICIONAR AL PEDIDO ACTUAL, SI LE DA F5 A LA PAGINA NO HAY PEDIDO ACTUAL.. ES MEJOR ASI!!!
        $sqlupdate="update gestor_pendientes_reagendamiento set ASESOR='' where ASESOR='$user'";
        //echo $sqlupdate;
        $xxx = $this->mysqli->query($sqlupdate);
        //}
        //echo "WTF";
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

        $mypedido="";

        $parametroBusqueda= $this->buscarParametroFechaDemePedido('FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO');
        $ordenamiento = $this->buscarParametroFechaDemePedido('PRIORIDAD_DEMEPEDIDO_AGENDAMIENTO');

        if($proceso!=""){
            $proceso=" and b.PROCESO='$proceso' ";
        }else{
            $proceso="";
        }

        if($prioridad!=''){
            $parametroBusqueda=$prioridad;
        }
        if($parametroBusqueda=='') $parametroBusqueda ='FECHA_ESTADO';

        $query1="select b.PEDIDO_ID,b.FECHA_CITA_FENIX".
            ",(SELECT a.user FROM vistas_pedidos  a where a.user='$user' AND b.PEDIDO_ID=a.pedido_id ".
            " AND a.fecha BETWEEN '$today 00:00:00' AND '$today 23:59:59' limit 1) as BEENHERE ".
            " from gestor_pendientes_reagendamiento b ".
            " where b.STATUS='MALO'  ".
            " and b.ASESOR ='' ".
            " and (b.FECHA_CITA_FENIX < CURDATE() OR b.FECHA_CITA_FENIX='9999-00-00') ".
            " and (b.FECHA_CITA_REAGENDA < CURDATE() OR b.FECHA_CITA_REAGENDA='9999-00-00' OR b.FECHA_CITA_REAGENDA='') ".
            " and b.CONCEPTOS NOT LIKE '%REAGE%' ".
            " AND (b.PROGRAMACION='' or b.PROGRAMACION is null ) ".
            $proceso.
            " and (select count(*) from gestor_historicos_reagendamiento a where a.PEDIDO_ID=b.PEDIDO_ID and NOVEDAD IN ('ESCALADO CR','RECUPERAR EQUIPOS','RETIRAR ACCESO','ERROR SIEBEL','CUMPLIDO','PENDIENTE AGENDA','ANULADO','OTROS CONCEPTO ESCALADO','NO APLICA GESTION','DESINSTALAR','YA ESTA AGENDADO','ERRORES DE FACTURACION','INFORMACION-PARA CONFIRMAR LLEGADA DE TECNICO','ESCALAMIENTO INSTALACIONES ANTIOQUIA','INFORMACION GENERAL') ) <1 ".
            " order by b.$parametroBusqueda $ordenamiento ";

        if($mypedido==""){

            $rr = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);
            $mypedidoresult=array();
            $pedidos_ignorados="";
            if($rr->num_rows > 0){//recorro los registros de la consulta para
                while($row = $rr->fetch_assoc()){
                    $result[] = $row;


                    if($row['BEENHERE']==$user){
                        $pedidos_ignorados=$pedidos_ignorados.$row['PEDIDO_ID'].',';
                        //este pedido ya lo vio el dia de hoy
                        //busco otro pedido----
                        continue;
                    }

                    $mypedido=$row['PEDIDO_ID'];
                    $mypedidoresult=$rta;
                    break;
                    //} //2016-04-12: SE QUITO VALIDACION CONTRA FENIX


                }
                //2.traigo solo los pedidos mas viejos en la base de datos...
            } else {
                $query1="select b.PEDIDO_ID, b.FECHA_CITA_FENIX ".
                    " from gestor_pendientes_reagendamiento b ".
                    " where b.STATUS='MALO'  and b.ASESOR ='' ".
                    $proceso.
                    " and (select NOVEDAD from gestor_historicos_reagendamiento a where a.PEDIDO_ID=b.PEDIDO_ID and FECHA_FIN between '$today 00:00:00' and '$today 23:59:59' order by id desc limit 1) not like '%AGENDADO%' ".
                    " order by b.VIEWS,b.FECHA_INGRESO ASC";
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

                    }

                }

            }//end if

        }//end mypedido if

        if($mypedido==''){//ya los visite todos, que hago???
            //echo "YA LOS VISITE TODOS, QUE HAGO?";
            //EN LAS VISTAS, ACTUALIZO EL USUARIO PARA RESETEAR LA BUSQUEDA
            $SQL_UPDATE="update vistas_pedidos a set a.user='$user-CICLO' where a.user='$user' AND a.fecha BETWEEN '$today 00:00:00' AND '$today 23:59:59'";
            $xS = $this->mysqli->query($SQL_UPDATE);
            $pedds=explode(",", $pedidos_ignorados);
            if(count($pedds)>0){
                $mypedido=$pedds[0];
            }
        }
        $fecha_visto= date("Y-m-d H:i:s");
        //de una lo ocupo cucho cucho!!!!
        $sqlupdate="update gestor_pendientes_reagendamiento set ASESOR='$user',PROGRAMACION='',VIEWS=VIEWS+1,FECHA_VISTO_ASESOR='$fecha_visto' where PEDIDO_ID = '$mypedido' and STATUS='MALO'";
        $x = $this->mysqli->query($sqlupdate);


        if($PROGRAMADO!="NOT"){
            $PROGRAMADO=", '$PROGRAMADO' as PROGRAMADO ";
        }else{
            $PROGRAMADO="";
        }

        $query1=" SELECT b.ID as PARENT_ID,b.PEDIDO_ID,b.FECHA_CITA_FENIX ".
            " ,b.CLIENTE_ID,b.CELULAR_AVISAR,b.CORREO_UNE,b.DIRECCION_ENVIO ".
            " ,b.E_MAIL_AVISAR,b.NOMBRE_USUARIO,b.FECHA_INGRESO,b.TELEFONO_AVISAR ".
            " ,b.CONCEPTOS,b.ACTIVIDADES ".
            " ,cast(TIMESTAMPDIFF(HOUR,(b.FECHA_INGRESO),CURRENT_TIMESTAMP())/24 AS decimal(5,2)) as TIEMPO_TOTAL ".
            " ,b.MICROZONA,b.OBSERVACION_FENIX,b.FUENTE,b.TODAY_TRIES ".
            " ,b.PROGRAMACION,b.PROCESO,b.FECHA_ESTADO $PROGRAMADO ".
            " ,'AUTO' as source ".
            " ,(select a.NOVEDAD from gestor_historicos_reagendamiento a where a.PEDIDO_ID='$mypedido' order by a.ID desc limit 1) as HISTORICO_NOVEDAD ".
            " from gestor_pendientes_reagendamiento b where b.PEDIDO_ID = '$mypedido' ".
            $proceso.
            " and b.STATUS='MALO' ".
            " order by b.FECHA_INGRESO ASC";


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
            $sqlupdate="update gestor_pendientes_reagendamiento set ASESOR='$user',VIEWS=VIEWS+1 where ID in ($ids)";
            $x = $this->mysqli->query($sqlupdate);
            $INSERTLOG="insert into vistas_pedidos(user,pedido_id) values ('$user','$mypedido')";
            $x = $this->mysqli->query($INSERTLOG);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$mypedido' ".
                ",'USO DEMEPEDIDO' ".
                ",'PEDIDO GENERADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            //$sqlfeed="insert into portalbd.activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$user','$username','','','PEDIDO: $mypedido','DEMEPEDIDO')";
            //$rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

            //sleep(20);
            unlink($filename);
            echo json_encode($result);
            $this->response('', 200); // send user details
        }else{//i have pretty heavy problems over here...
            //$this->response('SYSTEM PANIC!',200);
            $this->response('No hay registros!',204);
        }
        unlink($filename);

        $this->response('nothing',204);        // If no records "No Content" status
    }



    private function pedidosPorPedidoAgendamiento(){//historico por 1 pedido
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $pedido = $this->_request['pedido'];
        $today = date("Y-m-d");
        $query="SELECT ID,FUENTE,NOVEDAD,FECHA_FIN,DURACION,ASESOR,ACCESO,OBSERVACION_GESTOR from gestor_historicos_reagendamiento where PEDIDO_ID = '$pedido' order by FECHA_FIN desc limit 10";
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            $this->response($this->json($result), 200); // send user details
        }
        $this->response('',200);        // If no records "No Content" status
    }

    private function listadoPedidosAgendamiento(){//historico por 1 pedido agendamiento
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
        $query="SELECT count(*) as counter from gestor_historicos_reagendamiento where fecha_inicio between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter = $row['counter'];
            }
        }



        $query="SELECT id, pedido_id, fuente, actividad_gestor, fecha_fin, duracion, novedad, asesor,fecha_cita_reagenda,conceptos,actividades,proceso from gestor_historicos_reagendamiento where fecha_inicio between '$fechaini 00:00:00' and '$fechafin 23:59:59' order by fecha_fin desc limit 100 offset $page";

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





    private function buscarPedidoAgendamiento(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $pedido = $this->_request['pedidoID'];

        $user = $this->_request['userID'];
        $username = $this->_request['username'];
        //si el actual usuario tenia un pedido "agarrado, hay que liberarlo"
        $pedido_actual = $this->_request['pedido_actual'];
        $user=strtoupper($user);

        $sqlupdate="update gestor_pendientes_reagendamiento set ASESOR='' where ASESOR='$user' ";
        $xxx = $this->mysqli->query($sqlupdate);

        $today = date("Y-m-d");

        $query1="SELECT b.ID as PARENT_ID,b.PEDIDO_ID,b.FECHA_CITA_FENIX,b.CLIENTE_ID,b.CELULAR_AVISAR,b.CORREO_UNE,b.DIRECCION_ENVIO,b.E_MAIL_AVISAR,b.NOMBRE_USUARIO,b.FECHA_INGRESO,b.TELEFONO_AVISAR,b.CONCEPTOS,b.ACTIVIDADES,b.PROCESO,b.SUBZONA_ID,b.IDENTIFICADOR_ID,cast(TIMESTAMPDIFF(HOUR,(b.FECHA_INGRESO),CURRENT_TIMESTAMP())/24 AS decimal(5,2)) as TIEMPO_TOTAL,b.MICROZONA,b.OBSERVACION_FENIX,b.FUENTE,b.TODAY_TRIES,b.PROGRAMACION,b.FECHA_ESTADO,(select a.NOVEDAD from gestor_historicos_reagendamiento a where a.PEDIDO_ID='$pedido' order by a.ID desc limit 1) as HISTORICO_NOVEDAD from gestor_pendientes_reagendamiento b where b.PEDIDO_ID = '$pedido' and STATUS IN ('PENDI_AGEN','MALO') order by b.ID desc limit 1 ";
        //echo $query1;

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
                $sqlupdate="update gestor_pendientes_reagendamiento set VIEWS=VIEWS+1 where ID in ($ids)";
            }else{
                $fecha_visto=date("Y-m-d H:i:s");
                $sqlupdate="update gestor_pendientes_reagendamiento set VIEWS=VIEWS+1,ASESOR='$user',FECHA_VISTO_ASESOR='$fecha_visto' where ID in ($ids)";
            }

            $x = $this->mysqli->query($sqlupdate);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$pedido' ".
                ",'BUSCO PEDIDO' ".
                ",'PEDIDO BUSCADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$user','$username','','','PEDIDO: $pedido','BUSCARPEDIDOREAGEN','') ";
            //$xx = $this->mysqli->query($sqlfeed);
            //echo json_encode($result);
            $this->response(json_encode($result), 200); // send user details
        }else {
            //si el pedido no esta en la base de datos buscar en los dos fenix, esto implica insertar en la tabla core..

            /*
                            $success=$this->buscarPedidoFenix($pedido);

                            if($success=="OK"){//logro encontrar el pedido en fenix he hizo el insert local...
                                    //recursion?????
                                    $this->buscarPedido();
                            }*/

        }

        $this->response('nothing',204);
    }


    private function logout(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $params = json_decode(file_get_contents('php://input'),true);
        //$params = file_get_contents('php://input');

        $login = $params['user'];
        $fecha = $params['fecha'];
        $today = date("Y-m-d");

        // SQL Feed----------------------------------
        $sql_log=   "insert into portalbd.activity_feed ( ".
            " USER ".
            ", USER_NAME ".
            ", GRUPO ".
            ", STATUS ".
            ", PEDIDO_OFERTA ".
            ", ACCION ".
            ", CONCEPTO_ID ".
            ", IP_HOST ".
            ", CP_HOST ".
            ") values( ".
            " UPPER('$login')".
            ", UPPER('$login')".
            ", UPPER('LOGIN')".
            ",'OK' ".
            ",'SIN PEDIDO' ".
            ",'SE DESLOGUEO' ".
            ",'LOGGED OFF' ".
            ",'$usuarioIp' ".
            ",'$usuarioPc')";
        //echo $sql_log;
        $rlog = $this->mysqli->query($sql_log);
        // ---------------------------------- SQL Feed
        //$sqlfeed="insert into portalbd.activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$login','$login','LOGIN','logged off','','LOGIN') ";
        //$rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);


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
                unset($_SESSION["loginsession"]);
                $rr = $this->mysqli->query($sqllogin);

                $this->response($this->json('logged out'), 201);

            }//doesnt have sense, do nothing
            $this->response($this->json('User do not exist!!!'), 400);      // If no records "No Content" status


        }

        $error = array('status' => "Failed", "msg" => "Invalid User Name or password ($login)");
        $this->response($this->json($error), 400);
    }
    //Funcion para Buscar nodos CMTS


    private function buscarNodoHFCFenix($nodo){
        $this->dbFenixConnect();
        $connf=$this->connf;

        $sqlfenix=" select N.NODO_OPTICO_ELECTRICO_ID as NODO, N.DIRECCION, DECODE(N.SOPORTA_TOIP,'S','SI','N','NO') as SOPORTA_TOIP".
            ", DECODE(N.SOPORTA_TVDIGITAL,'S','SI','N','NO') ".
            " as SOPORTA_TVDIGITAL, DECODE(N.TVDIGITAL_INYECTADA,'S','SI','N','NO') as TVDIGITAL_INYECTADA".
            ", DECODE(N.NODO_DIGITAL,'S','SI','N','NO')  as NODO_DIGITAL ".
            " from FNX_NODOS_OPTICOS_ELECTRICO N where N.NODO_OPTICO_ELECTRICO_ID like '%$nodo%' ";


        $stid = oci_parse($connf, $sqlfenix);
        oci_execute($stid);

        while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){

            //if($concepto_id!=$row['CONCEPTO_ID']){
            //
            //}
            $result[] = $row;
        }

        return $result;
    }


    private function buscarcmts()
    {

        if($this->get_request_method() != "GET")
        {
            $this->response('',406);
        }
        $nodo = $this->_request['nodo_id'];
        $today = date("Y-m-d");


        //consulta nodo en fenix

        $result0=$this->buscarNodoHFCFenix($nodo);

        //var_dump($result0);

        $query="SELECT Nodo, trim(Diez_Mbps) as Diez_Mbps, trim(Doce_Mbps) as Doce_Mbps, trim(Quince_Mbps) as Quince_Mbps, trim(Veinte_Mbps) as Veinte_Mbps, trim(Treinta_Mbps) as Treinta_Mbps, CDI, MUNICIPIO FROM portalbd.gestor_cmts where nodo like '%$nodo%' limit 100";

        //$this->response($query1,200);
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);


        if($r->num_rows > 0)
        {
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            //$this->response(json_encode($result), 200); // send user details
        }

        $return =array($result0,$result);
        $this->response(json_encode($return), 200);
        //$this->response('nothing',204);        // If no records "No Content" status

    }

    private function buscarcmts2()
    {

        if($this->get_request_method() != "GET")
        {
            $this->response('',406);
        }
        $nodo = $this->_request['nodo_id'];
        $today = date("Y-m-d");


        //consulta nodo en fenix

        //$result0=$this->buscarNodoHFCFenix($nodo);

        //var_dump($result0);

        $query="SELECT CMTS_CD,ND_CD,PORCENTAJE_OCUPACION, ".
                "CASE ".
                "WHEN  PORCENTAJE_OCUPACION < 0.78 THEN 'DISPONIBLE' ELSE 'NO DISPONIBLE' END AS PLAN_15MB ".
                ",CASE ".
                "WHEN  PORCENTAJE_OCUPACION < 0.78 THEN 'DISPONIBLE' ELSE 'NO DISPONIBLE' END AS PLAN_20MB ".
                ",CASE ".
                "WHEN  PORCENTAJE_OCUPACION < 0.75 THEN 'DISPONIBLE' ELSE 'NO DISPONIBLE' END AS PLAN_30MB ".
                ",CASE ".
                "WHEN  PORCENTAJE_OCUPACION < 0.68 THEN 'DISPONIBLE' ELSE 'NO DISPONIBLE' END AS PLAN_50MB ".
                " FROM portalbd.gestor_cmts_por_archivo ".
                " WHERE ND_CD like '%$nodo%' limit 100";

        //$this->response($query1,200);
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);


        if($r->num_rows > 0)
        {
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }
            //$this->response(json_encode($result), 200); // send user details
        }

        $return =array($result);
        $this->response(json_encode($return), 200);
        //$this->response('nothing',204);        // If no records "No Content" status

    }

    private function login(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $params = json_decode(file_get_contents('php://input'),true);


        $login = $params['username'];
        $password = $params['password'];
        $fecha = $params['fecha'];

        if(!empty($login) and !empty($password)){

            $login=strtoupper($login);

            /*Mauricio 2016-12-15: this code have some problems!!! $stmt->get_result(); thorws an unholy error!!!
				$stmt = $this->mysqli->prepare("SELECT ID as id, USUARIO_NOMBRE as name, USUARIO_ID as login, GRUPO,CARGO_ID FROM tbl_usuarios WHERE USUARIO_ID = ? AND PASSWORD = MD5(?) LIMIT 1");
				$stmt->bind_param('ss', $login,$password);

				$stmt->execute();

				$r = $stmt->get_result();
				//while ($row = $result->fetch_assoc()) {
				    // do something with $row
				//}
                */

            $query="SELECT ID as id, USUARIO_NOMBRE as name, USUARIO_ID as login, GRUPO,CARGO_ID FROM tbl_usuarios WHERE USUARIO_ID = '$login' AND PASSWORD = MD5('$password') LIMIT 1";
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);


            if($result = $r->fetch_assoc()) {
                //$result = $r->fetch_assoc();
                // If success everythig is good send header as "OK" and user details

                $login=$result['login'];
                //here i can control this session....

                //its a login, search if theres a login today
                $today = date("Y-m-d");
                $name=$result['name'];
                $fechaunica=date("Ymd");
                $uniqueid=$login."_".$fechaunica;



                //$sqlfeed="insert into portalbd.activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$login','$name','LOGIN','logged in','','LOGIN') ";
                //$rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

                //echo "nombre: $name";
                //echo $uniqueid;
                $sqllogin="SELECT id,fecha_ingreso, date_format(fecha_ingreso,'%H:%i:%s') as hora_ingreso FROM registro_ingreso_usuarios WHERE fecha_ingreso between '$today 00:00:00' and '$today 23:59:59' and usuario='$login' limit 1";
                //echo $sqllogin;

                $rr = $this->mysqli->query($sqllogin);

                if($rr->num_rows > 0){//update just the status, not dates cuz he already loged in early
                    $result1 = $rr->fetch_assoc();
                    $idd=$result1['id'];
                    $sqllogin="update registro_ingreso_usuarios set status='logged in',ingresos=ingresos+1 where id=$idd";
                    $rrr = $this->mysqli->query($sqllogin);
                    $result['fecha_ingreso']=$result1['fecha_ingreso'];
                    $result['hora_ingreso']=$result1['hora_ingreso'];
                    $name=$result['name'];
                    //echo "kaiden!! ";
                    //var_dump($result);
                }else{//make an insert, first time logged in today

                    //session_start();

                    //$_SESSION['loginsession'] = time();

                    $ip=$_SERVER['REMOTE_ADDR'];

                    $sqllogin="insert into registro_ingreso_usuarios(usuario,status,ip,fecha_ingreso) values('$login','logged in','$ip','$fecha')";


                    //$sqllogin="insert into registro_ingreso_usuarios(idusuario, usuario,status,ip,fecha_ingreso) values('$uniqueid',$login','logged in','$ip','$fecha')"; //Agregar cuando estemos listos


                    $rrr = $this->mysqli->query($sqllogin);

                    if(($this->mysqli->errno)==1062){

                        $sqlfeed="insert into portalbd.activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$login','$name','ERROR','Duplicado','$uniqueid','LOGIN') ";
                        $rsqlfeed = $this->mysqli->query($sqlfeed);

                    }

                    $idi=$this->mysqli->insert_id;
                    $sqllogin="SELECT fecha_ingreso, date_format(fecha_ingreso,'%H:%i:%s') as hora_ingreso FROM registro_ingreso_usuarios WHERE fecha_ingreso between '$today 00:00:00' and '$today 23:59:59' and usuario='$login' limit 1";

                    $rs = $this->mysqli->query($sqllogin);
                    if($rs->num_rows > 0){
                        $result1 = $rs->fetch_assoc();
                        $result['fecha_ingreso']=$result1['fecha_ingreso'];
                        $result['hora_ingreso']=$result1['hora_ingreso'];
                    }else{
                        $result['fecha_ingreso']='N/A';
                        $result['hora_ingreso']='Sin login';
                    }
                    //echo "kai!! ";
                    //var_dump($result);
                }

                $result['name']=utf8_encode($result['name']);
                // SQL Feed----------------------------------
                $sql_log=   "insert into portalbd.activity_feed ( ".
                    " USER ".
                    ", USER_NAME ".
                    ", GRUPO ".
                    ", STATUS ".
                    ", PEDIDO_OFERTA ".
                    ", ACCION ".
                    ", CONCEPTO_ID ".
                    ", IP_HOST ".
                    ", CP_HOST ".
                    ") values( ".
                    " UPPER('$login')".
                    ", UPPER('$name')".
                    ", UPPER('LOGIN')".
                    ",'OK' ".
                    ",'SIN PEDIDO' ".
                    ",'SE LOGUEO' ".
                    ",'LOGGED IN' ".
                    ",'$usuarioIp' ".
                    ",'$usuarioPc')";

                $rlog = $this->mysqli->query($sql_log);
                // ---------------------------------- SQL Feed
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

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

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
        $ID=$transaccion['ID'];
        $STATUS=$transaccion['STATUS'];

        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
            if($desired_key=='ID'||$desired_key=='STATUS'){
                continue;
            }
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

            $sqlupdate="update informe_petec_pendientesm set FECHA_FINAL='$today', STATUS='$STATUS', ASESOR='' WHERE ID=$ID ";
            $rUpdate = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);

            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$useri')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$oferta' ".
                ",'GUARDO PEDIDO NCA' ".
                ",'$estado_final' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$useri','$username','NCA','$estado_final','OFERTA: $oferta','NCA') ";
            //$rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);
            $this->response(json_encode(array("msg"=>"OK","transaccion" => $transaccion)),200);

        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }


    //***********************************Michael KPIS Infraestructura *****************************
    private function insertTransaccionKPIS(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $transaccion = json_decode(file_get_contents("php://input"),true);


        $transaccion = $transaccion['gestion'];

        //echo var_dump ($transaccion);

        $column_names = array('NEGOCIO','FECHASOLICI','ITEMS','ANSACTIVIDAD','SISTEMAINFO','RESULTADOCARGA','ITEMSPROCESADO','ITEMSINCONSISTENTES','OBSERVACIONES','FECHAPROCESADO','RESPONSABLE');

        $keys = array_keys($transaccion);
        $columns = '';
        $values = '';

        $useri=$transaccion['USUARIO'];
        $username=$transaccion['USERNAME'];

        $Negocio=$transaccion['Negocio'];
        $estado_final=$transaccion['ESTADO_FINAL'];
        $ID=$transaccion['ID'];


        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
            if($desired_key=='ID'){
                continue;
            }
            if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
            }else{
                $$desired_key = $transaccion[$desired_key];
            }
            $columns = $columns.$desired_key.',';
            $values = $values."'".$transaccion[$desired_key]."',";
        }
        $today = date("Y-m-d H:i:s");

        $query = " INSERT INTO  tbl_KpisInfraestructura (".trim($columns,',').") VALUES(".trim($values,',').")";
        echo $query;
        if(!empty($transaccion)){
            //echo $query;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

            $this->response(json_encode(array("msg"=>"OK","transaccion" => $transaccion)),200);

        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }

    private function ActualizarTransaccionKPIS(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $transaccion = json_decode(file_get_contents("php://input"),true);

        $transaccion = $transaccion['gestion'];

        //fecho var_dump ($transaccion);

        $column_names = array('NEGOCIO','FECHASOLICI','ITEMS','ANSACTIVIDAD','SISTEMAINFO','RESULTADOCARGA','ITEMSPROCESADO','ITEMSINCONSISTENTES','OBSERVACIONES','FECHAPROCESADO','RESPONSABLE');

        $keys = array_keys($transaccion);
        $columns = '';
        $values = '';

        $useri=$transaccion['USUARIO'];
        $username=$transaccion['USERNAME'];

        $NEGOCIO=$transaccion['NEGOCIO'];
        $FECHASOLICI=$transaccion['FECHASOLICI'];
        $ITEMS=$transaccion['ITEMS'];
        $ANSACTIVIDAD=$transaccion['ANSACTIVIDAD'];
        $SISTEMAINFO=$transaccion['SISTEMAINFO'];
        $RESULTADOCARGA=$transaccion['RESULTADOCARGA'];
        $ITEMSPROCESADO=$transaccion['ITEMSPROCESADO'];
        $ITEMSINCONSISTENTES=$transaccion['ITEMSINCONSISTENTES'];
        $OBSERVACIONES=$transaccion['OBSERVACIONES'];
        $FECHAPROCESADO=$transaccion['FECHAPROCESADO'];
        $RESPONSABLE=$transaccion['RESPONSABLE'];


        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
            if($desired_key=='ID'){
                continue;
            }
            if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
            }else{
                $$desired_key = $transaccion[$desired_key];
            }
            $columns = $columns.$desired_key.',';
            $values = $values."'".$transaccion[$desired_key]."',";
        }
        $today = date("Y-m-d H:i:s");

        $query = " UPDATE tbl_KpisInfraestructura set NEGOCIO = '$NEGOCIO', FECHASOLICI = '$FECHASOLICI', ITEMS = '$ITEMS', ANSACTIVIDAD = '$ANSACTIVIDAD', SISTEMAINFO = '$SISTEMAINFO', RESULTADOCARGA = '$RESULTADOCARGA', ITEMSPROCESADO = '$ITEMSPROCESADO', ITEMSINCONSISTENTES = '$ITEMSINCONSISTENTES', OBSERVACIONES = '$OBSERVACIONES', FECHAPROCESADO = '$FECHAPROCESADO',RESPONSABLE = '$RESPONSABLE' where ID = 1 ";
        echo $query;

        if(!empty($transaccion)){
            //echo $query;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

            $this->response(json_encode(array("msg"=>"OK","transaccion" => $transaccion)),200);

        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }

    private function buscarRegistroKPIS(){//pendientes
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $bregistro = $this->_request['bregistro'];


        //$in_stmt = "'".str_replace(" ", "','", $bpedido)."'";

        $query="select * from tbl_KpisInfraestructura where ID = '$bregistro'";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }

            // ---------------------------------- SQL Feed
            $this->response($this->json(array($result)), 200); // send user details


        }
        $this->response('',204);        // If no records "No Content" status
    }


//**********************************************************************************************************************



    //-----------------------insertactivacion----------------

    private function insertTransaccionsiebelactivacion(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $pedido = json_decode(file_get_contents("php://input"),true);
        //var_dump($pedido);
        $column_names = array('ORDER_SEQ_ID','PEDIDO','REFERENCE_NUMBER','ESTADO','FECHA_CREACION','TAREA_EXCEPCION','FECHA_EXCEPCION','PRODUCTO','IDSERVICIORAIZ','TRANSACCION','CODIGO_CIUDAD','ASESOR','FECHA_GESTION','TIPIFICACION','FECHA_INICIO','FECHA_FIN','DURACION','OBSERVACION','NUMERO_CR','TABLA','FUENTE','GRUPO','ACTIVIDAD','ESTADO_ID', 'OBSERVACION_ID','NUMERO_PSR','PSR','DESCRIPCIONEXCEPCIONACT','MOTIVOEXCEPCIONACT');
        $pedido=$pedido['pedido'];
        $keys = array_keys($pedido);
        $today = date("Y-m-d H:i:s");
        $today2 = date("Y-m-d");
        $columns = '';
        $values = '';
        $FECHA_GESTION='';
        $ID=$pedido['ID'];
        $ASESOR=$pedido['ASESOR'];
        $PEDIDO=$pedido['PEDIDO'];
        $ORDER_SEQ_ID=$pedido['ORDER_SEQ_ID'];
        $REFERENCE_NUMBER=$pedido['REFERENCE_NUMBER'];
        $ESTADO=$pedido['ESTADO'];
        $FECHA_CREACION=$pedido['FECHA_CREACION'];
        $TAREA_EXCEPCION=$pedido['TAREA_EXCEPCION'];
        $FECHA_EXCEPCION=$pedido['FECHA_EXCEPCION'];
        $PRODUCTO=$pedido['PRODUCTO'];
        $IDSERVICIORAIZ=$pedido['IDSERVICIORAIZ'];
        $TRANSACCION=$pedido['TRANSACCION'];
        $CODIGO_CIUDAD=$pedido['CODIGO_CIUDAD'];
        //$STATUS=$pedido['STATUS'];
        $TIPIFICACION=$pedido['TIPIFICACION'];
        $FECHA_INICIO=$pedido['FECHA_INICIO'];
        $FECHA_FIN=$pedido['FECHA_FIN'];
        $DURACION=$pedido['DURACION'];
        $tabla = $pedido['TABLA'];
        $OBSERVACION=$pedido['OBSERVACION'];

        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
            if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
            }else{
                $$desired_key = $pedido[$desired_key];
            }
            $columns = $columns.$desired_key.',';
            $values = $values."'".$pedido[$desired_key]."',";
        }

        if(!empty($pedido)){

            $query = "INSERT INTO gestor_historico_activacion(".trim($columns,',').",source) VALUES(".trim($values,',').",'AUTO')";
            //  echo $query,$pedido;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

            //----------insert

            if($tabla=='ACTIVADOR_SUSPECORE' ){
                if($TIPIFICACION=='FINALIZADA' || $TIPIFICACION=='RENUMERAR' ){
                    $sqlupdate="update gestor_activacion_pendientes_activador_suspecore set FECHA_CARGA = '$today',STATUS='CERRADO_ACTI',FECHA_EXCEPCION = '$FECHA_EXCEPCION' WHERE ID=$ID AND STATUS='PENDI_ACTI'";
                    //echo $sqlupdate;
                }else {

                    $sqlupdate="update gestor_activacion_pendientes_activador_suspecore set FECHA_CARGA = '$today',STATUS='MALO',FECHA_EXCEPCION = '$FECHA_EXCEPCION' WHERE ID=$ID AND STATUS='PENDI_ACTI'";
                    //   echo $sqlupdate;
                }
            } else {
                if($TIPIFICACION=='FINALIZADA' || $TIPIFICACION=='RENUMERAR'){
                    $sqlupdate="update gestor_activacion_pendientes_activador_dom set FECHA_CARGA = '$today',STATUS='CERRADO_ACTI',FECHA_EXCEPCION = '$FECHA_EXCEPCION' WHERE ID=$ID AND STATUS='PENDI_ACTI'";
                    //echo $sqlupdate;
                }else {

                    $sqlupdate="update gestor_activacion_pendientes_activador_dom set FECHA_CARGA = '$today',STATUS='MALO',FECHA_EXCEPCION = '$FECHA_EXCEPCION' WHERE ID=$ID AND STATUS='PENDI_ACTI'";
                }
            }
            // echo $sqlupdate;
            $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);

            //  echo "(1)";
            $this->response(json_encode(array("msg"=>"N/A","data" => $today)),200);

        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }

    private function insertTransaccionsiebelamarillas(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $pedido = json_decode(file_get_contents("php://input"),true);
        //var_dump($pedido);
        $column_names = array('ORDER_SEQ_ID','PEDIDO','REFERENCE_NUMBER','ESTADO','FECHA_CREACION','TAREA_EXCEPCION','FECHA_EXCEPCION','PRODUCTO','IDSERVICIORAIZ','TRANSACCION','CODIGO_CIUDAD','ASESOR','FECHA_GESTION','TIPIFICACION','FECHA_INICIO','FECHA_FIN','DURACION','OBSERVACION','NUMERO_CR','TABLA','FUENTE','GRUPO','ACTIVIDAD','ESTADO_ID', 'OBSERVACION_ID','NUMERO_PSR','PSR','DESCRIPCIONEXCEPCIONACT','MOTIVOEXCEPCIONACT');
        $pedido=$pedido['pedido'];
        $keys = array_keys($pedido);
        $today = date("Y-m-d H:i:s");
        $today2 = date("Y-m-d");
        $columns = '';
        $values = '';
        $FECHA_GESTION='';
        $ID=$pedido['ID'];
        $ASESOR=$pedido['ASESOR'];
        $PEDIDO=$pedido['PEDIDO'];
        $ORDER_SEQ_ID=$pedido['ORDER_SEQ_ID'];
        $REFERENCE_NUMBER=$pedido['REFERENCE_NUMBER'];
        $ESTADO=$pedido['ESTADO'];
        $FECHA_CREACION=$pedido['FECHA_CREACION'];
        $TAREA_EXCEPCION=$pedido['TAREA_EXCEPCION'];
        $FECHA_EXCEPCION=$pedido['FECHA_EXCEPCION'];
        $PRODUCTO=$pedido['PRODUCTO'];
        $IDSERVICIORAIZ=$pedido['IDSERVICIORAIZ'];
        $TRANSACCION=$pedido['TRANSACCION'];
        $CODIGO_CIUDAD=$pedido['CODIGO_CIUDAD'];
        //$STATUS=$pedido['STATUS'];
        $TIPIFICACION=$pedido['TIPIFICACION'];
        $FECHA_INICIO=$pedido['FECHA_INICIO'];
        $FECHA_FIN=$pedido['FECHA_FIN'];
        $DURACION=$pedido['DURACION'];
        $tabla = $pedido['TABLA'];
        $OBSERVACION=$pedido['OBSERVACION'];

        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
            if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
            }else{
                $$desired_key = $pedido[$desired_key];
            }
            $columns = $columns.$desired_key.',';
            $values = $values."'".$pedido[$desired_key]."',";
        }

        if(!empty($pedido)){

            $query = "INSERT INTO gestor_historico_activacion(".trim($columns,',').",source) VALUES(".trim($values,',').",'AUTO')";
            //  echo $query;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

            //----------insert


            if($tabla=='AMARILLAS' ){
                if($TIPIFICACION=='FINALIZADA' || $TIPIFICACION=='RENUMERAR'){
                    $sqlupdate="update pendientes_amarillas set FECHA_CARGA = '$today',STATUS='CERRADO_ACTI',FECHA_EXCEPCION = '$FECHA_EXCEPCION' WHERE PEDIDO='$PEDIDO' and STATUS='PENDI_ACTI'";
                    //echo $sqlupdate;
                }else {

                    $sqlupdate="update pendientes_amarillas set FECHA_CARGA = '$today',STATUS='MALO',FECHA_EXCEPCION = '$FECHA_EXCEPCION' WHERE PEDIDO='$PEDIDO' and STATUS='PENDI_ACTI'";
                }
            }
            // echo $sqlupdate;
            $rr = $this->mysqli->query($sqlupdate) or die($this->mysqli->error.__LINE__);

            //  echo "(1)";
            $this->response(json_encode(array("msg"=>"N/A","data" => $today)),200);

        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }
//-------------------------fininsertactivacion*------------------


    private function insertTransaccionActividades(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $transaccion = json_decode(file_get_contents("php://input"),true);

        $transaccion = $transaccion['transaccion'];
        $column_names = array('PEDIDO_ID','DIA','FECHA','TIPO_TRABAJO','APLICACION_ACTIVIDADES','COLA','AMANECIERON','GESTIONADO_DIA','QUEDAN_PENDIENTES','OBSERVACIONES','USUARIO','FECHA_INICIO','FECHA_FIN');
        $keys = array_keys($transaccion);
        $columns = '';
        $values = '';

        $useri=$transaccion['USUARIO'];
        $username=$transaccion['USERNAME'];
        $TIPO_TRABAJO=implode(",",$transaccion['TIPO_TRABAJO']);

        $transaccion['TIPO_TRABAJO']=$TIPO_TRABAJO;

        // echo $TIPO_TRABAJO;

        // var_dump($transaccion);
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
        $query = "INSERT INTO  transacciones_actividades (".trim($columns,',').") VALUES(".trim($values,',').")";
        // echo $query;
        if(!empty($transaccion)){
            //echo $query;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

            // $sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$useri','$username','NCA','$estado_final','OFERTA: $oferta','NCA') ";
            //$rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);
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
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
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
            $r = $this->mysqli->query($query);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'CREO USUARIO' ".
                ",'USUARIO CREADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response(json_encode(array("msg"=>"OK","transaccion" => $usuario)),200);
        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }

    private function editTransaccionNCA(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $transa = json_decode(file_get_contents("php://input"),true);
        //echo var_dump($usuario);

        $transa = $transa['transaccionNCA'];
        $column_names = array('OFERTA','MUNICIPIO_ID','TRANSACCION','ESTADO','FECHA','INCIDENTE','ESTADO_FINAL','OBSERVACION');
        $keys = array_keys($transa);
        $columns = '';
        $values = '';

        $UPDATE="";
        $SEP="";
        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
            if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
            }else{
                $$desired_key = $transa[$desired_key];
            }
            $columns = $columns.$desired_key.',';
            $values = $values."'".$transa[$desired_key]."',";
            $UPDATE=$UPDATE.$SEP.$desired_key." = '".strtoupper($transa[$desired_key])."' ";
            $SEP=",";
        }
        $today = date("Y-m-d H:i:s");

        $passcode="";
        //if($transaccion['PASSWORD']!=""){
        //  $passcode=" , PASSWORD=MD5('".$transaccion['PASSWORD']."')";
        //}
        $query = "UPDATE transacciones_nca SET $UPDATE $passcode WHERE ID=".$transa['ID'];
        //echo $query;

        if(!empty($transa)){
            //echo $query;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EDITO PEDIDO SIEBEL' ".
                ",'PEDIDO EDITADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response(json_encode(array("msg"=>"OK","transaccion" => $transa)),200);
        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }


    private function editTransaccionActividades(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $transa = json_decode(file_get_contents("php://input"),true);
        //echo var_dump($usuario);

        $transa = $transa['transaccionA'];
        $column_names = array('DIA','FECHA','TIPO_TRABAJO','APLICACION_ACTIVIDADES','COLA','AMANECIERON','GESTIONADO_DIA','QUEDAN_PENDIENTES','OBSERVACIONES');
        $keys = array_keys($transa);
        $columns = '';
        $values = '';
        $TIPO_TRABAJO=implode(",",$transa['TIPO_TRABAJO']);
        $transa['TIPO_TRABAJO']=$TIPO_TRABAJO;
        $UPDATE="";
        $SEP="";
        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
            if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
            }else{
                $$desired_key = $transa[$desired_key];
            }
            $columns = $columns.$desired_key.',';
            $values = $values."'".$transa[$desired_key]."',";
            $UPDATE=$UPDATE.$SEP.$desired_key." = '".strtoupper($transa[$desired_key])."' ";
            $SEP=",";
        }
        $today = date("Y-m-d H:i:s");

        $passcode="";
        //if($transaccion['PASSWORD']!=""){
        //  $passcode=" , PASSWORD=MD5('".$transaccion['PASSWORD']."')";
        //}
        $query = "UPDATE transacciones_actividades SET $UPDATE $passcode WHERE ID=".$transa['ID'];
        //echo $query;

        if(!empty($transa)){
            //echo $query;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
            $this->response(json_encode(array("msg"=>"OK","transaccion" => $transa)),200);
        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }


    private function getTransaccionNCA(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $nca = $this->_request['ncaID'];


        $query="select * from transacciones_nca where ID=$nca";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $ids="";
            $sep="";
            $transaccion='';
            if($row = $r->fetch_assoc()){
                $transaccion = $row;
            }
            //$transaccion["PASSWORD"]="";
            $this->response($this->json(array($transaccion,"OK")), 200); // send user details
        }

        $this->response('',204);

    }

    //************************************Michael*******************************************
    private function getTransaccionKPIS(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        //$nca = $this->_request['ncaID'];


        $query="select * from tbl_KpisInfraestructura order by ID desc";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $ids="";
            $sep="";
            $transaccion='';
            if($row = $r->fetch_assoc()){
                $transaccion = $row;
            }
            //$transaccion["PASSWORD"]="";
            $this->response($this->json(array($transaccion,"OK")), 200); // send user details
        }

        $this->response('',204);

    }
    //***************************************************************************************

    private function getTransaccionActividades(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $actividades = $this->_request['actividadesID'];


        $query="select * from transacciones_actividades where ID=$actividades";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $ids="";
            $sep="";
            $transaccion='';
            if($row = $r->fetch_assoc()){
                $transaccion = $row;
            }
            //$transaccion["PASSWORD"]="";
            $this->response($this->json(array($transaccion,"OK")), 200); // send user details
        }

        $this->response('',204);

    }




    private function editUsuarioR(){
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


        $query="select * from portalbd.tbl_usuarios where ID=$user";

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
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
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
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO HISTORICO SIEBEL' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }



    private function csvactividades(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $login = $this->_request['login'];
        $fechaIni = $this->_request['fechaIni'];
        $fechaFin = $this->_request['fechaFin'];

        $today = date("Y-m-d h:i:s");

        $filename="ACTIVIDADES-$login-$today.csv";

        $query= " SELECT FECHA,TIPO_TRABAJO ".
            " ,APLICACION_ACTIVIDADES,COLA,AMANECIERON ".
            " ,GESTIONADO_DIA,QUEDAN_PENDIENTES ".
            " ,OBSERVACIONES,USUARIO,FECHA_INICIO,FECHA_FIN ".
            " ,my_sec_to_time(timestampdiff(second,fecha_inicio,fecha_fin)) as DURACION".
            " ,(timestampdiff(second,fecha_inicio,fecha_fin)) as DURACION_SEGUNDOS".
            " from transacciones_actividades ".
            " order by FECHA ASC ";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('FECHA','TIPO_TRABAJO','APLICACION_ACTIVIDADES','COLA','AMANECIERON','GESTIONADO_DIA','QUEDAN_PENDIENTES','OBSERVACIONES','USUARIO','FECHA_INICIO','FECHA_FIN','DURACION','DURACION_SEGUNDOS'));
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
        $query="SELECT count(*) as counter from portalbd.pedidos where fuente='SIEBEL' and FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter = $row['counter'];
            }
        }


        $query="SELECT * FROM portalbd.pedidos where fuente='SIEBEL' and FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59' order by FECHA_FIN desc limit 100 offset $page";
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

    //*****************************************MICHAEL LISTADO TRANSACCIONES*****************************
    private function listadoTransaccionesKPIS(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        /*$page = $this->_request['page'];

        if($page=="undefined"){
            $page="0";
        }else{
            $page=$page-1;
        }
        $page=$page*100;*/

        //counter
        $query="SELECT count(*) as counter from tbl_KpisInfraestructura ";
        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter = $row['counter'];
            }
        }


        $query="select * from tbl_KpisInfraestructura order by ID desc limit 50; ";
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


    private function listadoTransaccionesActividades(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $fechaini = $this->_request['fechaInicio'];
        $fechafin = $this->_request['fechaFin'];
        $page = $this->_request['page'];
        $id = $this->_request['userID'];
        $today = date("Y-m-d");

        //echo ($fecha);

        if($page=="undefined"){
            $page="0";
        }else{
            $page=$page-1;
        }
        $page=$page*100;
        //counter
        $query="SELECT count(*) as counter from transacciones_actividades where FECHA between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter = $row['counter'];
            }
        }


        $query="SELECT * FROM transacciones_actividades where USUARIO='$id' and FECHA between '$fechaini 00:00:00' and '$fechafin 23:59:59' order by FECHA desc limit 100 offset $page";
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

    private function listadoTransaccionesActividades1(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $fechaini = $this->_request['fechaInicio'];
        $fechafin = $this->_request['fechaFin'];
        $page = $this->_request['page'];
        $id = $this->_request['userID'];
        $today = date("Y-m-d");

        //echo ($fecha);

        if($page=="undefined"){
            $page="0";
        }else{
            $page=$page-1;
        }
        $page=$page*100;
        //counter
        $query="SELECT count(*) as counter from transacciones_actividades order by FECHA desc limit 100 offset $page";
        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter = $row['counter'];
            }
        }


        $query=" SELECT * ".
            " FROM transacciones_actividades ".
            " where fecha between '$fechaini 00:00:00' and '$fechafin 23:59:59' ".
            " order by FECHA desc limit 100 offset $page ";
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

    private function getZonasOcupaagenda(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $today = date("Y-m-d");

        $dep = $this->_request['departamento'];

        //echo "departamento: $dep";
        $conna=getConnAgendamiento();

        $query=" SELECT DISTINCT ".
            " ( CASE WHEN C1.ZONA =  '' THEN  'VACIOS' ".
            "  WHEN C1.ZONA = null THEN 'VACIOS' ".
            "  ELSE C1.ZONA END) AS SUBZONA_ID ".
            " FROM (select ".
            "        CASE WHEN dp.dep_departamento = 'Antioquia' THEN 'ANTIOQUIA'   ".
            "             WHEN dp.dep_departamento = 'Atlantico' THEN 'ATLANTICO' ".
            "             WHEN dp.dep_departamento = 'Cundinamarca' THEN 'CUNDINAMARCA' ".
            "             WHEN dp.dep_departamento = 'Bolivar' THEN 'BOLIVAR'  ".
            "             WHEN dp.dep_departamento = 'Valle del Cauca' THEN 'VALLE DEL CAUCA' END AS DEPARTAMENTO, ".
            "                   CASE  ".
            "                     WHEN (dp.dep_departamento = 'Antioquia'  ".
            "                     AND subz.sbz_subzona IN ('AMERICA','BUENOS_AIR','CEN','CENTRO','COLON','MIRAFLORES','NUTIBARA','OTRABANDA','SAN_BERN_1','SAN_BERN_2','SAN_JAVIER','VILLAHERMO') ".
            "                 AND sbag.sag_prioridad <> 'migracion') THEN 'CENTRO' ".
            "             WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('BARCOPGIR','BELLO_1','BELLO_2','BELLO_3','BERLIN','BOSQUE_1','BOSQUE_2','CARIBE','CASTILLA','FLORENCIA','GIR','IGUANA','IGUSANCRI','NIQUIA','NOR')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'NORTE' ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('CALDAS','ENVIGADO_1','ENVIGADO_2','ENVIGADO_3','ESTRELLA','GUAYABAL','ITAGUI_1','ITAGUI_2','ITAGUI_3','POBLADO_1','POBLADO_2','SABANETA','SANANTPRA','SUR-ENV','SUR','SUR-SAB')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'SUR'    ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('M1_ORIENTE','M2_ORIENTE','M3_ORIENTE','RIO')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'ORIENTE'     ".
            "			WHEN subz.sbz_subzona IN ('CAN','DEFAULT','ENG','QCA','SUB','NORTE') THEN 'BOGOTA NORTE'  ".
            "			WHEN subz.sbz_subzona IN ('BOSA','ECA','FRG','TIMIZA','SUR') THEN 'BOGOTA SUR'   ".
            "			WHEN subz.sbz_subzona IN ('CAR','M1_CARTAGE','M2_CARTAGE','M3_CARTAGE','M4_CARTAGE','M5_CARTAGE') ".
            "			THEN 'CARTAGENA' ".
            "			WHEN subz.sbz_subzona IN ('TUR', 'M6_CARTAGE')  ".
            "			THEN 'TURBACO'  ".
            "			WHEN subz.sbz_subzona IN ('VAL','Valle del Cauca') THEN 'CALI'   ".
            "			WHEN subz.sbz_subzona = 'PAL' THEN 'PALMIRA'  ".
            "			WHEN subz.sbz_subzona = 'JAM' THEN 'JAMUNDI'  ".
            "           WHEN dp.dep_departamento IN ('Bolivar','Atlantico','Cundinamarca','Valle del Cauca')    ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN UPPER(dp.dep_departamento) ".
            "             END AS ZONA, ".
            "           sbz_subzona AS MICROZONA,sbag.sag_segmento, ".
            "                 sbag.sag_segmento AS SEGMENTO,agm.agm_fechacita AS FECHA_DISP, ".
            "                 sbag.sag_cuposam AS PARAM_AM, ".
            "                 IFNULL((sbag.sag_cuposam-IFNULL(COUNT(IF(agm.agm_jornadacita='AM',1,NULL)),0)),0) AS DISP_AM,sbag.sag_cupospm AS PARAM_PM, ".
            "                 IFNULL((sbag.sag_cupospm-IFNULL(COUNT(IF(agm.agm_jornadacita='PM',1,NULL)),0)),0) AS DISP_PM,(IFNULL(sbag.sag_cupos7,0)+IFNULL(sbag.sag_cupos8,0)+IFNULL(sbag.sag_cupos9,0)+IFNULL(sbag.sag_cupos10,0)+  ".
            "                 IFNULL(sbag.sag_cupos11,0)+IFNULL(sbag.sag_cupos12,0)+IFNULL(sbag.sag_cupos13,0)+IFNULL(sbag.sag_cupos14,0)+ ".
            "                 IFNULL(sbag.sag_cupos15,0)+IFNULL(sbag.sag_cupos16,0)+IFNULL(sbag.sag_cupos17,0)+IFNULL(sbag.sag_cupos18,0)+ ".
            "                 IFNULL(sbag.sag_cupos19,0))AS PARAM_HF,IFNULL(IF(sbag.sag_cupos7 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)))),IFNULL((sbag.sag_cupos7-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)),0)),0)),0) AS 'N7', ".
            "         IFNULL(IF(sbag.sag_cupos8 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)))),IFNULL((sbag.sag_cupos8-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)),0)),0)),0) AS 'N8', ".
            "                 IFNULL(IF(sbag.sag_cupos9 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)))),IFNULL((sbag.sag_cupos9-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)),0)),0)),0) AS 'N9', ".
            "                 IFNULL(IF(sbag.sag_cupos10 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)))),IFNULL((sbag.sag_cupos10-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)),0)),0)),0) AS 'N10', ".
            "         IFNULL(IF(sbag.sag_cupos11 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)))),IFNULL((sbag.sag_cupos11-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)),0)),0)),0) AS 'N11', ".
            "                 IFNULL(IF(sbag.sag_cupos12 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)))),IFNULL((sbag.sag_cupos12-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)),0)),0)),0) AS 'N12', ".
            "                 IFNULL(IF(sbag.sag_cupos13 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)))),IFNULL((sbag.sag_cupos13-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)),0)),0)),0) AS 'N13', ".
            "                 IFNULL(IF(sbag.sag_cupos14 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)))),IFNULL((sbag.sag_cupos14-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)),0)),0)),0) AS 'N14', ".
            "                 IFNULL(IF(sbag.sag_cupos15 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)))),IFNULL((sbag.sag_cupos15-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)),0)),0)),0) AS 'N15', ".
            "                 IFNULL(IF(sbag.sag_cupos16 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)))),IFNULL((sbag.sag_cupos16-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)),0)),0)),0) AS 'N16', ".
            "                 IFNULL(IF(sbag.sag_cupos17 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)))),IFNULL((sbag.sag_cupos17-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)),0)),0)),0) AS 'N17', ".
            "                 IFNULL(IF(sbag.sag_cupos18 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)))),IFNULL((sbag.sag_cupos18-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)),0)),0)),0) AS 'N18', ".
            "                 IFNULL(IF(sbag.sag_cupos19 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)))),IFNULL((sbag.sag_cupos19-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)),0)),0)),0) AS 'N19' ".
            " from agn_agendamientos agm  ".
            " left join agn_subagendas sbag on agm.agm_agenda = sbag.sag_id  ".
            " left join agn_agendas ag on sbag.sag_agenda = ag.ads_id ".
            " left join agn_subzonas  subz on sbag.sag_subzona = subz.sbz_id ".
            " left join agn_departamentos  dp on ag.ags_departamento = dp.dep_id ".
            " where agm.agm_fechacita >= ag.ags_fechainicial  ".
            " AND agm.agm_fechacita <= ag.ags_fechafinal  ".
            " AND ag.ags_fechafinal >= CURDATE()  ".
            " AND sbag.sag_prioridad <> 'migracion' ".
            " AND (agm.agm_estadototal IN ('Agendar','Agendamientos') OR (agm.agm_estadototal IN('Pendiente','Prospecto','')  ".
            " AND (agm.agm_fechacita > IF(HOUR(CURTIME()) < 16,CURDATE(), DATE_ADD(NOW(), INTERVAL 1 DAY)))))  ".
            " and agm.agm_fechacita >= date_add(curdate(), interval 1 day) ".
            " group by agm.agm_agenda,agm.agm_fechacita ".
            " order by ag.ags_fechafinal ASC ) C1 ".
            " where C1.DEPARTAMENTO = '$dep' ".
            " and C1.ZONA  is not null".
            " and C1.FECHA_DISP > CURDATE() ";
        //echo $query;
        $r = $conna->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){

                $result[] = $row;

            }
            $this->response($this->json(array($result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }


    private function getDepartamentosOcupaagenda(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $today = date("Y-m-d");

        $conna=getConnAgendamiento();

        $query=" SELECT DISTINCT ".
            " ( CASE WHEN C1.DEPARTAMENTO =  '' THEN  'VACIOS' ".
            "  WHEN C1.DEPARTAMENTO = null THEN 'VACIOS' ".
            "  ELSE C1.DEPARTAMENTO END) AS DEPARTAMENT ".
            " FROM (select ".
            "        CASE WHEN dp.dep_departamento = 'Antioquia' THEN 'ANTIOQUIA'   ".
            "             WHEN dp.dep_departamento = 'Atlantico' THEN 'ATLANTICO' ".
            "             WHEN dp.dep_departamento = 'Cundinamarca' THEN 'CUNDINAMARCA' ".
            "             WHEN dp.dep_departamento = 'Bolivar' THEN 'BOLIVAR'  ".
            "             WHEN dp.dep_departamento = 'Valle del Cauca' THEN 'VALLE DEL CAUCA' END AS DEPARTAMENTO, ".
            "                   CASE  ".
            "                     WHEN (dp.dep_departamento = 'Antioquia'  ".
            "                     AND subz.sbz_subzona IN ('AMERICA','BUENOS_AIR','CEN','CENTRO','COLON','MIRAFLORES','NUTIBARA','OTRABANDA','SAN_BERN_1','SAN_BERN_2','SAN_JAVIER','VILLAHERMO') ".
            "                 AND sbag.sag_prioridad <> 'migracion') THEN 'CENTRO' ".
            "             WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('BARCOPGIR','BELLO_1','BELLO_2','BELLO_3','BERLIN','BOSQUE_1','BOSQUE_2','CARIBE','CASTILLA','FLORENCIA','GIR','IGUANA','IGUSANCRI','NIQUIA','NOR')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'NORTE' ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('CALDAS','ENVIGADO_1','ENVIGADO_2','ENVIGADO_3','ESTRELLA','GUAYABAL','ITAGUI_1','ITAGUI_2','ITAGUI_3','POBLADO_1','POBLADO_2','SABANETA','SANANTPRA','SUR-ENV','SUR','SUR-SAB')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'SUR'    ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('M1_ORIENTE','M2_ORIENTE','M3_ORIENTE','RIO')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'ORIENTE'     ".
            "            WHEN dp.dep_departamento IN ('Bolivar','Atlantico','Cundinamarca','Valle del Cauca')    ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN UPPER(dp.dep_departamento) ".
            "             END AS ZONA, ".
            "           sbz_subzona AS MICROZONA,sbag.sag_segmento, ".
            "                 sbag.sag_segmento AS SEGMENTO,agm.agm_fechacita AS FECHA_DISP, ".
            "                 sbag.sag_cuposam AS PARAM_AM, ".
            "                 IFNULL((sbag.sag_cuposam-IFNULL(COUNT(IF(agm.agm_jornadacita='AM',1,NULL)),0)),0) AS DISP_AM,sbag.sag_cupospm AS PARAM_PM, ".
            "                 IFNULL((sbag.sag_cupospm-IFNULL(COUNT(IF(agm.agm_jornadacita='PM',1,NULL)),0)),0) AS DISP_PM,(IFNULL(sbag.sag_cupos7,0)+IFNULL(sbag.sag_cupos8,0)+IFNULL(sbag.sag_cupos9,0)+IFNULL(sbag.sag_cupos10,0)+  ".
            "                 IFNULL(sbag.sag_cupos11,0)+IFNULL(sbag.sag_cupos12,0)+IFNULL(sbag.sag_cupos13,0)+IFNULL(sbag.sag_cupos14,0)+ ".
            "                 IFNULL(sbag.sag_cupos15,0)+IFNULL(sbag.sag_cupos16,0)+IFNULL(sbag.sag_cupos17,0)+IFNULL(sbag.sag_cupos18,0)+ ".
            "                 IFNULL(sbag.sag_cupos19,0))AS PARAM_HF,IFNULL(IF(sbag.sag_cupos7 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)))),IFNULL((sbag.sag_cupos7-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)),0)),0)),0) AS 'N7', ".
            "         IFNULL(IF(sbag.sag_cupos8 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)))),IFNULL((sbag.sag_cupos8-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)),0)),0)),0) AS 'N8', ".
            "                 IFNULL(IF(sbag.sag_cupos9 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)))),IFNULL((sbag.sag_cupos9-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)),0)),0)),0) AS 'N9', ".
            "                 IFNULL(IF(sbag.sag_cupos10 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)))),IFNULL((sbag.sag_cupos10-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)),0)),0)),0) AS 'N10', ".
            "         IFNULL(IF(sbag.sag_cupos11 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)))),IFNULL((sbag.sag_cupos11-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)),0)),0)),0) AS 'N11', ".
            "                 IFNULL(IF(sbag.sag_cupos12 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)))),IFNULL((sbag.sag_cupos12-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)),0)),0)),0) AS 'N12', ".
            "                 IFNULL(IF(sbag.sag_cupos13 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)))),IFNULL((sbag.sag_cupos13-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)),0)),0)),0) AS 'N13', ".
            "                 IFNULL(IF(sbag.sag_cupos14 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)))),IFNULL((sbag.sag_cupos14-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)),0)),0)),0) AS 'N14', ".
            "                 IFNULL(IF(sbag.sag_cupos15 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)))),IFNULL((sbag.sag_cupos15-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)),0)),0)),0) AS 'N15', ".
            "                 IFNULL(IF(sbag.sag_cupos16 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)))),IFNULL((sbag.sag_cupos16-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)),0)),0)),0) AS 'N16', ".
            "                 IFNULL(IF(sbag.sag_cupos17 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)))),IFNULL((sbag.sag_cupos17-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)),0)),0)),0) AS 'N17', ".
            "                 IFNULL(IF(sbag.sag_cupos18 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)))),IFNULL((sbag.sag_cupos18-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)),0)),0)),0) AS 'N18', ".
            "                 IFNULL(IF(sbag.sag_cupos19 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)))),IFNULL((sbag.sag_cupos19-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)),0)),0)),0) AS 'N19' ".
            " from agn_agendamientos agm  ".
            " left join agn_subagendas sbag on agm.agm_agenda = sbag.sag_id  ".
            " left join agn_agendas ag on sbag.sag_agenda = ag.ads_id ".
            " left join agn_subzonas  subz on sbag.sag_subzona = subz.sbz_id ".
            " left join agn_departamentos  dp on ag.ags_departamento = dp.dep_id ".
            " where agm.agm_fechacita >= ag.ags_fechainicial  ".
            " AND agm.agm_fechacita <= ag.ags_fechafinal  ".
            " AND ag.ags_fechafinal >= CURDATE()  ".
            " AND sbag.sag_prioridad <> 'migracion' ".
            " AND (agm.agm_estadototal IN ('Agendar','Agendamientos') OR (agm.agm_estadototal IN('Pendiente','Prospecto','')  ".
            " AND (agm.agm_fechacita > IF(HOUR(CURTIME()) < 16,CURDATE(), DATE_ADD(NOW(), INTERVAL 1 DAY)))))  ".
            " and agm.agm_fechacita >= date_add(curdate(), interval 1 day) ".
            " group by agm.agm_agenda,agm.agm_fechacita ".
            " order by ag.ags_fechafinal ASC ) C1 ".
            " where C1.FECHA_DISP > CURDATE() ";
        //echo $query;
        $r = $conna->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){

                $result[] = $row;

            }
            $this->response($this->json(array($result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

    private function getOcupacionAgendamiento(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $fecha = $this->_request['fecha'];
        $depa = $this->_request['depa'];
        $zona = $this->_request['zona'];
        $today = date("Y-m-d");

        if ($depa == "" || $depa == "undefined"){
            $depa = "";
        }
        else {
            $depa = "AND C1.DEPARTAMENTO = '$depa' ";
        }

        if ($zona == "" || $zona == "undefined"){
            $zona = "";
        }
        else {
            $zona = "AND C1.ZONA = '$zona'";
        }


        $conna=getConnAgendamiento();

        //$query="SELECT count(*) as counter from transacciones_nca where FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
        //$rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        //$counter=0;
        //if($rr->num_rows > 0){
        //        $result = array();
        //        if($row = $rr->fetch_assoc()){
        //                $counter = $row['counter'];
        //        }
        // }


        $query="SELECT ".
            " C1.FECHA_DISP ".
            " ,C1.DEPARTAMENTO ".
            " , C1.ZONA ".
            " , C1.MICROZONA ".
            " , C1.SEGMENTO ".
            " , C1.MIGRACION ".
            " , C1.VIP ".
            " , C1.BRONZE ".
            " , C1.GPON ".
            " , C1.PARAM_AM ".
            " , C1.DISP_AM ".
            " , C1.PARAM_PM ".
            " , C1.DISP_PM ".
            " , C1.PARAM_HF ".
            " ,(C1.N7+C1.N8+C1.N9+C1.N10+C1.N11+C1.N12+C1.N13+C1.N14+C1.N15+C1.N16+C1.N17+C1.N18+C1.N19)AS DISP_HF ".
            " ,(C1.DISP_AM + C1.DISP_PM + (C1.N7+C1.N8+C1.N9+C1.N10+C1.N11+C1.N12+C1.N13+C1.N14+C1.N15+C1.N16+C1.N17+C1.N18+C1.N19))AS TOTAL_DISP ".
            " FROM (select ".
            "        CASE WHEN dp.dep_departamento = 'Antioquia' THEN 'ANTIOQUIA'   ".
            "             WHEN dp.dep_departamento = 'Atlantico' THEN 'ATLANTICO' ".
            "             WHEN dp.dep_departamento = 'Cundinamarca' THEN 'CUNDINAMARCA' ".
            "             WHEN dp.dep_departamento = 'Bolivar' THEN 'BOLIVAR'  ".
            "             WHEN dp.dep_departamento = 'Valle del Cauca' THEN 'VALLE DEL CAUCA' END AS DEPARTAMENTO, ".
            "                   CASE  ".
            "                     WHEN (dp.dep_departamento = 'Antioquia'  ".
            "                     AND subz.sbz_subzona IN ('AMERICA','BUENOS_AIR','CEN','CENTRO','COLON','MIRAFLORES','NUTIBARA','OTRABANDA','SAN_BERN_1','SAN_BERN_2','SAN_JAVIER','VILLAHERMO')) ".
            "                 THEN 'CENTRO' ".
            "             WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('BARCOPGIR','BELLO_1','BELLO_2','BELLO_3','BERLIN','BOSQUE_1','BOSQUE_2','CARIBE','CASTILLA','FLORENCIA','GIR','IGUANA','IGUSANCRI','NIQUIA','NOR')  ".
            "                 THEN 'NORTE' ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('CALDAS','ENVIGADO_1','ENVIGADO_2','ENVIGADO_3','ESTRELLA','GUAYABAL','ITAGUI_1','ITAGUI_2','ITAGUI_3','POBLADO_1','POBLADO_2','SABANETA','SANANTPRA','SUR-ENV','SUR','SUR-SAB')  ".
            "                 THEN 'SUR'    ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('M1_ORIENTE', 'M2_ORIENTE', 'M3_ORIENTE', 'M4_ORIENTE' , 'M5_ORIENTE' ,'M6_ORIENTE','M7_ORIENTE','M8_ORIENTE','RIO', 'PALMAS', 'SANTAELENA')  ".
            "                 THEN 'ORIENTE'     ".
            "			WHEN subz.sbz_subzona IN ('CAR','M1_CARTAGE','M2_CARTAGE','M3_CARTAGE','M4_CARTAGE','M5_CARTAGE') ".
            "			THEN 'CARTAGENA' ".
            "			WHEN subz.sbz_subzona IN ('TUR', 'M6_CARTAGE')  ".
            "			THEN 'TURBACO'  ".
            "			WHEN subz.sbz_subzona IN ('CAN','DEFAULT','ENG','QCA','SUB','NORTE') THEN 'BOGOTA NORTE'  ".
            "			WHEN subz.sbz_subzona IN ('BOSA','ECA','FRG','TIMIZA','SUR') THEN 'BOGOTA SUR'  ".
            "			WHEN subz.sbz_subzona IN ('VAL','Valle del Cauca') THEN 'CALI'   ".
            "			WHEN subz.sbz_subzona = 'PAL' THEN 'PALMIRA'  ".
            "			WHEN subz.sbz_subzona = 'JAM' THEN 'JAMUNDI'  ".
            "           WHEN dp.dep_departamento IN ('Bolivar','Atlantico','Cundinamarca','Valle del Cauca')    ".
            "            THEN UPPER(dp.dep_departamento) ".
            "             END AS ZONA, ".
            "           sbz_subzona AS MICROZONA,sbag.sag_segmento, ".
            "                 sbag.sag_segmento AS SEGMENTO,agm.agm_fechacita AS FECHA_DISP, ".
            "                 sbag.sag_cuposam AS PARAM_AM, ".
            "                 IFNULL((sbag.sag_cuposam-IFNULL(COUNT(IF(agm.agm_jornadacita='AM',1,NULL)),0)),0) AS DISP_AM,sbag.sag_cupospm AS PARAM_PM, ".
            "                 IFNULL((sbag.sag_cupospm-IFNULL(COUNT(IF(agm.agm_jornadacita='PM',1,NULL)),0)),0) AS DISP_PM,(IFNULL(sbag.sag_cupos7,0)+IFNULL(sbag.sag_cupos8,0)+IFNULL(sbag.sag_cupos9,0)+IFNULL(sbag.sag_cupos10,0)+  ".
            "                 IFNULL(sbag.sag_cupos11,0)+IFNULL(sbag.sag_cupos12,0)+IFNULL(sbag.sag_cupos13,0)+IFNULL(sbag.sag_cupos14,0)+ ".
            "                 IFNULL(sbag.sag_cupos15,0)+IFNULL(sbag.sag_cupos16,0)+IFNULL(sbag.sag_cupos17,0)+IFNULL(sbag.sag_cupos18,0)+ ".
            "                 IFNULL(sbag.sag_cupos19,0))AS PARAM_HF,IFNULL(IF(sbag.sag_cupos7 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)))),IFNULL((sbag.sag_cupos7-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)),0)),0)),0) AS 'N7', ".
            "         IFNULL(IF(sbag.sag_cupos8 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)))),IFNULL((sbag.sag_cupos8-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)),0)),0)),0) AS 'N8', ".
            "                 IFNULL(IF(sbag.sag_cupos9 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)))),IFNULL((sbag.sag_cupos9-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)),0)),0)),0) AS 'N9', ".
            "                 IFNULL(IF(sbag.sag_cupos10 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)))),IFNULL((sbag.sag_cupos10-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)),0)),0)),0) AS 'N10', ".
            "         IFNULL(IF(sbag.sag_cupos11 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)))),IFNULL((sbag.sag_cupos11-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)),0)),0)),0) AS 'N11', ".
            "                 IFNULL(IF(sbag.sag_cupos12 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)))),IFNULL((sbag.sag_cupos12-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)),0)),0)),0) AS 'N12', ".
            "                 IFNULL(IF(sbag.sag_cupos13 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)))),IFNULL((sbag.sag_cupos13-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)),0)),0)),0) AS 'N13', ".
            "                 IFNULL(IF(sbag.sag_cupos14 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)))),IFNULL((sbag.sag_cupos14-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)),0)),0)),0) AS 'N14', ".
            "                 IFNULL(IF(sbag.sag_cupos15 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)))),IFNULL((sbag.sag_cupos15-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)),0)),0)),0) AS 'N15', ".
            "                 IFNULL(IF(sbag.sag_cupos16 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)))),IFNULL((sbag.sag_cupos16-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)),0)),0)),0) AS 'N16', ".
            "                 IFNULL(IF(sbag.sag_cupos17 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)))),IFNULL((sbag.sag_cupos17-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)),0)),0)),0) AS 'N17', ".
            "                 IFNULL(IF(sbag.sag_cupos18 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)))),IFNULL((sbag.sag_cupos18-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)),0)),0)),0) AS 'N18', ".
            "                 IFNULL(IF(sbag.sag_cupos19 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)))),IFNULL((sbag.sag_cupos19-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)),0)),0)),0) AS 'N19', ".
            "SUM(CASE WHEN sbag.sag_prioridad='migracion' THEN 1 else 0 end ) as MIGRACION ".
            ",SUM(CASE WHEN sbag.sag_prioridad='vip' THEN 1 else 0 end ) as VIP ".
            ",SUM(CASE WHEN sbag.sag_prioridad='Bronze' THEN 1 else 0 end ) as BRONZE ".
            ",SUM(CASE WHEN sbag.sag_prioridad='gpon' THEN 1 else 0 end ) as GPON ".
            " from agn_agendamientos agm  ".
            " left join agn_subagendas sbag on agm.agm_agenda = sbag.sag_id  ".
            " left join agn_agendas ag on sbag.sag_agenda = ag.ads_id ".
            " left join agn_subzonas  subz on sbag.sag_subzona = subz.sbz_id ".
            " left join agn_departamentos  dp on ag.ags_departamento = dp.dep_id ".
            " where agm.agm_fechacita >= ag.ags_fechainicial  ".
            " AND agm.agm_fechacita <= ag.ags_fechafinal  ".
            " AND ag.ags_fechafinal >= CURDATE()  ".
            " AND (agm.agm_estadototal IN ('Agendar','Agendamientos') OR (agm.agm_estadototal IN('Pendiente','Prospecto','')  ".
            " AND (agm.agm_fechacita > IF(HOUR(CURTIME()) < 16,CURDATE(), DATE_ADD(NOW(), INTERVAL 1 DAY)))))  ".
            " and agm.agm_fechacita >= date_add(curdate(), interval 1 day) ".
            " group by agm.agm_agenda,agm.agm_fechacita ".
            " order by ag.ags_fechafinal ASC ) C1 ".
            " where C1.FECHA_DISP ='$fecha' ".
            " $depa ".
            " $zona ".
            " order by C1.DEPARTAMENTO asc, C1.ZONA asc, C1.MICROZONA asc ";
        //echo $query;
        $r = $conna->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){

                $result[] = $row;

            }
            $this->response($this->json(array($result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }



    private function getcodigo_resultado(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $fecha = $this->_request['fecha'];
        $today = date("Y-m-d");

        $conna=getConnAgendamiento();

        //$query="SELECT count(*) as counter from transacciones_nca where FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
        //$rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        //$counter=0;
        //if($rr->num_rows > 0){
        //        $result = array();
        //        if($row = $rr->fetch_assoc()){
        //                $counter = $row['counter'];
        //        }
        // }


        $query= "SELECT * ".
            " FROM portalbd.gestor_interacciones_agendamiento ";
        //echo $query;
        $r = $conna->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){

                $result[] = $row;

            }
            $this->response($this->json(array($result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }


    private function cargar_datos_cmts(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        require_once '../librerias/importar_excel/reader/Classes/PHPExcel/IOFactory.php';
        $pedido=json_decode(file_get_contents("php://input"),true);

        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($_FILES["fileUpload"]["name"]);
        //$name     = $_FILES['fileUpload']['name'];
        $tname    = $_FILES['fileUpload']['tmp_name'];
        $type     = $_FILES['fileUpload']['type'];
        $NOMBRE_ARCHIVO=$_FILES["fileUpload"]["name"];
        $TAMANO =$_FILES["fileUpload"]["size"];

        $usas = $this->_request['user'];
        $today = date("Y-m-d");

        $sqlupload="insert into portalbd.gestor_log_fileupload (ASESOR,NOMBRE_ARCHIVO,TAMANO,VISTA) values ('$usas','$NOMBRE_ARCHIVO','$TAMANO','CARGA DATOS CMTS')";
        // echo  $sqlupload;
        $r = $this->mysqli->query($sqlupload) or die($this->mysqli->error.__LINE__);

        $sqlfeed="insert into portalbd.activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$usas','','','','','CARGA DATOS CMTS')";
        //echo  $sqlfeed;
        $rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

       if (move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $target_file)){
            echo "El archivo ". basename( $_FILES["fileUpload"]["name"]). " se ha subido.";

        } else {

            echo "Ha habido un error al subir el archivo.";
           $this->response(json_encode(array("msg"=>"ERROR: HA HABIDO UN ERROR AL SUBIR EL ARCHIVO.","data" => $today)),200);
           return;
        }

        //var_dump($_FILES);
        $tname1 = basename( $_FILES["fileUpload"]["name"]);

        if($type == 'application/vnd.ms-excel')
        {
            // Extension excel 97
            $ext = 'xls';
        }
        else if($type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        {
            // Extension excel 2007 y 2010
            $ext = 'xlsx';
        }else{
            // Extension no valida
            echo "Extension no valida.";
            $this->response(json_encode(array("msg"=>"ERROR: EXTENSION NO VALIDA, SE REQUIERE UN ARCHIVO EXCEL.","data" => $today)),200);
            exit();
        }

        $xlsx = 'Excel2007';
        $xls  = 'Excel5';

        //creando el lector
        $objReader = PHPExcel_IOFactory::createReader($$ext);

        //cargamos el archivo
        $objPHPExcel = $objReader->load($target_file);

        $dim = $objPHPExcel->getActiveSheet()->calculateWorksheetDimension();

        // list coloca en array $start y $end Lista Coloca en array $ inicio y final $
        list($start, $end) = explode(':', $dim);

        if(!preg_match('#([A-Z]+)([0-9]+)#', $start, $rslt)){
            $this->response(json_encode(array("msg"=>"ERROR: OCURRIO UN ERROR CON LOS CAMPOS DEL ARCHIVO EXCEL.","data" => $today)),200);
            return false;
        }
        list($start, $start_h, $start_v) = $rslt;
        if(!preg_match('#([A-Z]+)([0-9]+)#', $end, $rslt)){
            $this->response(json_encode(array("msg"=>"ERROR: OCURRIO UN ERROR CON LOS CAMPOS DEL ARCHIVO EXCEL.","data" => $today)),200);
            return false;
        }
        list($end, $end_h, $end_v) = $rslt;

        $sqltruncate="truncate table gestor_cmts_por_archivo ";
        $r = $this->mysqli->query($sqltruncate) or die($this->mysqli->error.__LINE__);

        //empieza  lectura vertical
        for($v=$start_v; $v<=$end_v; $v++){
            //empieza lectura horizontal

                if ($v==1) {
                        //VERIFICO QUE EL ENCABEZADO SEA EL QUE ESTOY ESPERANDO.....
                        $vars="";
                        $header="CMTS_CD,ND_CD,PUERTOS,BW TOTAL,MAX CAP BW x Cluster,% Ocupacion,BW Disponible,";
                        for($h=$start_h; ord($h)<=ord($end_h); $this->pp($h)){
                                $cellValue = $this->get_cell($h.$v, $objPHPExcel);
                                if($h=="H") break;//esto controla si por alguna razon hay columnas vacias mas alla del limite....

                                $vars=$vars."$cellValue,";
                        }
                        //echo "HEADER: $vars";
                        if($header==$vars){
                                echo "ARCHIVO CORRECTO!!!!";
                        }else{
                                echo "ARCHIVO NO CORRESPONDE AL ESPERADO: SE ESPERA \[$header\] Y SE OBTUVO: \[$vars\]";
                                $this->response(json_encode(array("msg"=>"ERROR: ARCHIVO NO CORRESPONDE AL ESPERADO: SE ESPERA \[$header\] Y SE OBTUVO: \[$vars\] ","data" => $today)),200);
                                return;
                        }
                        continue;
                }
                $vars="";
                $sep="";
                for($h=$start_h; ord($h)<=ord($end_h); $this->pp($h)){
                        $cellValue = $this->get_cell($h.$v, $objPHPExcel);

                        if($h=="H") break;

                        $vars=$vars."$sep'$cellValue'";
                        $sep=",";
                }
            $INSERT="INSERT INTO gestor_cmts_por_archivo (CMTS_CD,ND_CD,PUERTOS,BW_TOTAL,MAX_CAP_BWxCLUSTER,PORCENTAJE_OCUPACION,BW_DISPONIBLE) VALUES ($vars)";
            $r = $this->mysqli->query($INSERT) or die($this->mysqli->error.__LINE__);
                //echo "\n";

        }//end FORME

        $this->response(json_encode(array("msg"=>"OK","data" => $today)),200);


    }





    private function cargar_datos(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        //$user = $this->_request['userID'];
        require_once '../librerias/importar_excel/reader/Classes/PHPExcel/IOFactory.php';


        $pedido=json_decode(file_get_contents("php://input"),true);

        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($_FILES["fileUpload"]["name"]);
        //$name     = $_FILES['fileUpload']['name'];
        $tname    = $_FILES['fileUpload']['tmp_name'];
        $type     = $_FILES['fileUpload']['type'];
        $NOMBRE_ARCHIVO=$_FILES["fileUpload"]["name"];
        $TAMANO =$_FILES["fileUpload"]["size"];
        //$pedido = json_decode(file_get_contents("php://input"),true);
        $usas = $this->_request['user'];
        //echo var_dump($_FILES);
        //echo var_dump($_FILES );
        //$this->response(json_encode(""),200);
        $PEDIDO_ID='';
        $cliente_id='';
        $ACCESO='';
        $ESTADO='';
        $FECHA_INGRESO='';
        $today = date("Y-m-d");


        $sqlupload="insert into portalbd.gestor_log_fileupload (ASESOR,NOMBRE_ARCHIVO,TAMANO,VISTA) values ('$usas','$NOMBRE_ARCHIVO','$TAMANO','BODEGA DATOS')";
        // echo  $sqlupload;
        $r = $this->mysqli->query($sqlupload) or die($this->mysqli->error.__LINE__);

        $sqlfeed="insert into portalbd.activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$usas','','','','','BODEGA DATOS')";
        //echo  $sqlfeed;
        $rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);



        //$target_file = basename($_FILES["fileUpload"]["name"]);
        $uploadOk = 1;
        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            echo  "Lo sentimos , el archivo no se ha subido.";
            // if everything is ok, try to upload file
        } else {

            if (move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $target_file)){
                echo "El archivo ". basename( $_FILES["fileUpload"]["name"]). " se ha subido.";

            } else {

                echo "Ha habido un error al subir el archivo.";
            }
        }
        //var_dump($_FILES);
        $tname1 = basename( $_FILES["fileUpload"]["name"]);

        if($type == 'application/vnd.ms-excel')
        {
            // Extension excel 97
            $ext = 'xls';
        }
        else if($type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        {
            // Extension excel 2007 y 2010
            $ext = 'xlsx';
        }else{
            // Extension no valida
            echo "Extension no valida.";
            exit();
        }

        $xlsx = 'Excel2007';
        $xls  = 'Excel5';

        //creando el lector
        $objReader = PHPExcel_IOFactory::createReader($$ext);

        //cargamos el archivo
        $objPHPExcel = $objReader->load($target_file);

        $dim = $objPHPExcel->getActiveSheet()->calculateWorksheetDimension();

        // list coloca en array $start y $end Lista Coloca en array $ inicio y final $
        list($start, $end) = explode(':', $dim);

        if(!preg_match('#([A-Z]+)([0-9]+)#', $start, $rslt)){
            return false;
        }
        list($start, $start_h, $start_v) = $rslt;
        if(!preg_match('#([A-Z]+)([0-9]+)#', $end, $rslt)){
            return false;
        }
        list($end, $end_h, $end_v) = $rslt;

        //empieza  lectura vertical
        $table = "<table  border='1'>";
        for($v=$start_v; $v<=$end_v; $v++){
            //empieza lectura horizontal

            if ($v==1) continue;
            $table .= "<tr>";
            //$filas= $start_h + 1;


            for($h=$start_h; ord($h)<=ord($end_h);$this->pp($h)){
                $cellValue = $this->get_cell($h.$v, $objPHPExcel);



                $table .= "<td>";
                $guardar .=" '$cellValue',";
                //echo $cellValue;
                if($cellValue!== null){
                    $table .= $cellValue;
                }
                if($h=="L"){
                    $PEDIDO_ID=$cellValue;
                }
                if($h=="J"){
                    $cliente_id=$cellValue;
                }
                if($h=="Z"){
                    $ACCESO=$cellValue;
                }
                if($h=="Y"){
                    $FUENTE=$cellValue;
                }
                if($h=="G"){
                    $timestamp = PHPExcel_Shared_Date::ExcelToPHP($cellValue);//fecha larga
                    $FECHA_FIN = gmdate("Y-m-d 00:00:00",$timestamp);//fecha formateada+
                    $table .= "<td>";
                }

                if($h=="I"){
                    $NOVEDAD=strtoupper($cellValue);
                    //$NOVEDAD=strtoupper($NOVEDAD);

                }
                if($h=="E"){
                    $OBSERVACION_GESTOR=strtoupper($cellValue);


                }

            }

            $guardar=rtrim($guardar,',');



            if ($tname1  = "bodega_datos.xlsx"){


                $sqlbodega_datos="insert into portalbd.gestor_bodega_bodega_datos (CAMPANAID,LANZAMIENTO,IDLLAMADA1,TELEFONO,MENSAJE,ACCION,FECHA,IDLLAMADA2,ESTADO,CEDULA,DETALLE,PEDIDO,CODIGO_RESULTADO,FECHA_AGENDA,JORNADA_AGENDA,CAUSA,MUNICIPIO,ZONA,TIPO_TRANSACCION,NOMBRE_CLIENTE,DEPARTAMENTO,EMAIL,FECHA_ENVIO,HORA_ENVIO,INTERFAZ,ACCESO) values ($guardar) ";
                //echo  $sqlbodega_datos;
                $r = $this->mysqli->query($sqlbodega_datos) or die($this->mysqli->error.__LINE__);
                // echo  $guardar2;
                $sqldatos="insert into portalbd.gestor_historicos_reagendamiento (PEDIDO_ID,CLIENTE_ID,ACCESO,FUENTE,FECHA_FIN,ASESOR,NOVEDAD,OBSERVACION_GESTOR) values ('$PEDIDO_ID','$cliente_id','$ACCESO','$FUENTE','$FECHA_FIN','$usas','$NOVEDAD','$OBSERVACION_GESTOR')";
                //echo  $sqldatos;
                $r = $this->mysqli->query($sqldatos) or die($this->mysqli->error.__LINE__);



            }


            $guardar="";
            $PEDIDO_ID="";
            $cliente_id="";
            $ACCESO="";
            $FUENTE="";
            $FECHA_FIN="";
            $NOVEDAD="";
            $NOMBRE_ARCHIVO="";
            $TAMANO="";
            $VISTA="";
            $tname1="";



            $table .= "</tr>";
        }

        for($v=$start_v; $v<=$end_v; $v++){
            //empieza lectura horizontal

            if ($v==1) continue;
            $table .= "<tr>";
            //$filas= $start_h + 1;


            for($h=$start_h; ord($h)<=ord($end_h);$this->pp($h)){
                $cellValue = $this->get_cell($h.$v, $objPHPExcel);

                $table .= "<td>";
                $guardar .=" '$cellValue',";

                if($cellValue !== null){
                    $table .= $cellValue;
                }

            }

            $guardar=rtrim($guardar,',');
            //var_dump($guardar);
            //echo $tname1;
            if ($tname1 <> "" && $tname1 <>"bodega_datos.xlsx"){




                $che=explode(",",$guardar);//validacion de datos que carguen pedidos diferentes y omita los repetidos
                $pedido=$che[0];

                //echo var_dump($che);

                //$this->response('okidokie',200);

                $pend=" SELECT PEDIDO_ID ".
                    " FROM portalbd.gestor_pendientes_reagendamiento ".
                    " WHERE PEDIDO_ID=$pedido and STATUS IN ('MALO','PENDI_AGEN')";
                // echo $pend;
                $rst = $this->mysqli->query($pend);


                if ($rst->num_rows > 0){
                    continue;
                }

                $sqlemail="insert into portalbd.gestor_pendientes_reagendamiento (PEDIDO_ID,CONCEPTOS,CLIENTE_ID,NOMBRE_USUARIO,DEPARTAMENTO,SUBZONA_ID,DIRECCION_ENVIO,FUENTE,PROCESO,CELULAR_AVISAR,TELEFONO_AVISAR,IDENTIFICADOR_ID,FECHA_INGRESO,MICROZONA,OBSERVACION_FENIX,TECNOLOGIA_ID) values ($guardar) ";
                // echo($sqlemail);
                $r = $this->mysqli->query($sqlemail) or die($this->mysqli->error.__LINE__);

                $sqlupload="insert into portalbd.gestor_log_fileupload (ASESOR,NOMBRE_ARCHIVO,TAMANO,VISTA) values ('$usas','$NOMBRE_ARCHIVO','$TAMANO','PENDIENTES REAGENDAMIENTO')";
                //echo  $sqlupload;
                $r = $this->mysqli->query($sqlupload) or die($this->mysqli->error.__LINE__);

            }



            $guardar="";
            $PEDIDO_ID="";
            $cliente_id="";
            $ACCESO="";
            $FUENTE="";
            $FECHA_FIN="";
            $NOVEDAD="";
            $NOMBRE_ARCHIVO="";
            $TAMANO="";
            $VISTA="";
            $FECHA_INGRESO="";


            $table .= "</tr>";
        }


        $this->response(json_encode(array("msg"=>"OK","data" => $today)),200);


    }

//-------------------------importar listadoactivacion

    private function cargar_datos_activacion(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        //$user = $this->_request['userID'];
        require_once '../librerias/importar_excel/reader/Classes/PHPExcel/IOFactory.php';


        $pedido=json_decode(file_get_contents("php://input"),true);

        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($_FILES["fileUpload"]["name"]);
        //$name     = $_FILES['fileUpload']['name'];
        $tname    = $_FILES['fileUpload']['tmp_name'];
        $type     = $_FILES['fileUpload']['type'];
        $NOMBRE_ARCHIVO=$_FILES["fileUpload"]["name"];
        $TAMANO =$_FILES["fileUpload"]["size"];
        //$pedido = json_decode(file_get_contents("php://input"),true);
        $usas = $this->_request['user'];

        $ORDER_SEQ_ID='';
        $PEDIDO='';
        $REFERENCE_NUMBER='';
        $ESTADO='';
        $FECHA_EXCEPCION='';
        $PRODUCTO='';
        $TRANSACCION='';
        $ASESOR='';
        $TIPIFICACION='';
        $SOURCE='';
        $FECHA_INICIO='';
        $FECHA_FIN='';
        $FECHA_GESTION='';
        $TABLA='';
        $today = date("Y-m-d");





        $sqlupload="insert into portalbd.gestor_log_fileupload (ASESOR,NOMBRE_ARCHIVO,TAMANO,VISTA) values ('$usas','$NOMBRE_ARCHIVO','$TAMANO','IMPORTAR ACTIVACION')";
        //echo  $user;
        $r = $this->mysqli->query($sqlupload) or die($this->mysqli->error.__LINE__);

        $sqlfeed="insert into portalbd.activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$usas','','','','','IMPORTAR ACTIVACION')";
        $rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);



        //$target_file = basename($_FILES["fileUpload"]["name"]);
        $uploadOk = 1;
        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            echo  "Lo sentimos , el archivo no se ha subido.";
            // if everything is ok, try to upload file
        } else {

            if (move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $target_file)){
                echo "El archivo ". basename( $_FILES["fileUpload"]["name"]). " se ha subido.";

            } else {

                echo "Ha habido un error al subir el archivo.";
            }
        }
        // var_dump($_FILES);
        $tname1 = basename( $_FILES["fileUpload"]["name"]);

        if($type == 'application/vnd.ms-excel')
        {
            // Extension excel 97
            $ext = 'xls';
        }
        else if($type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        {
            // Extension excel 2007 y 2010
            $ext = 'xlsx';
        }else{
            // Extension no valida
            echo "Extension no valida.";
            exit();
        }

        $xlsx = 'Excel2007';
        $xls  = 'Excel5';

        //creando el lector
        $objReader = PHPExcel_IOFactory::createReader($$ext);

        //cargamos el archivo
        $objPHPExcel = $objReader->load($target_file);

        $dim = $objPHPExcel->getActiveSheet()->calculateWorksheetDimension();

        // list coloca en array $start y $end Lista Coloca en array $ inicio y final $
        list($start, $end) = explode(':', $dim);

        if(!preg_match('#([A-Z]+)([0-9]+)#', $start, $rslt)){
            return false;
        }
        list($start, $start_h, $start_v) = $rslt;
        if(!preg_match('#([A-Z]+)([0-9]+)#', $end, $rslt)){
            return false;
        }
        list($end, $end_h, $end_v) = $rslt;

        //empieza  lectura vertical
        $table = "<table  border='1'>";
        for($v=$start_v; $v<=$end_v; $v++){
            //empieza lectura horizontal

            if ($v==1) continue;
            $table .= "<tr>";
            $filas= $start_h + 1;


            for($h=$start_h; ord($h)<=ord($end_h);$this->pp($h)){
                $cellValue = $this->get_cell($h.$v, $objPHPExcel);

                $cellValue1 = $cellValue;

                if($h=="A"){
                    $timestamp = PHPExcel_Shared_Date::ExcelToPHP($cellValue1);//fecha larga
                    $cellValue1 = gmdate("Y-m-d H:i:s",$timestamp);//fecha formateada+
                    $table .= "<td>";
                }
                if($h=="B"){
                    $timestamp = PHPExcel_Shared_Date::ExcelToPHP($cellValue1);//fecha larga
                    $cellValue1 = gmdate("H:i:s",$timestamp);//fecha formateada+
                    $table .= "<td>";
                }
                if($h=="M"){
                    $timestamp = PHPExcel_Shared_Date::ExcelToPHP($cellValue1);//fecha larga
                    $cellValue1 = gmdate("Y-m-d H:i:s",$timestamp);//fecha formateada+
                    $table .= "<td>";
                }


                $table .= "<td>";
                if($cellValue1 !== null){
                    $table .= $cellValue1;
                    $table .= "</td>";
                    // echo  $cellValue1;
                }

                $guardar .=" '$cellValue1',";

            }

            $guardar=rtrim($guardar,',');



            if ($tname1 <> ""){



                $sqldatos="insert into gestor_historico_activacion (FECHA_EXCEPCION,HORA,PEDIDO,PRODUCTO,TRANSACCION,APLICATIVO,OBSERVACION,NUMERO_CR,TIPIFICACION,ASESOR
,PEDIDO_FENIX,TABLA,FECHA_GESTION,SOURCE,FECHA_INICIO,FECHA_FIN) values ($guardar,'MANUAL','$today','$today'                )";
                //  echo  $sqldatos;
                $r = $this->mysqli->query($sqldatos) or die($this->mysqli->error.__LINE__);

            }


            $guardar="";
            $ORDER_SEQ_ID='';
            $PEDIDO="";
            $REFERENCE_NUMBER="";
            $ESTADO="";
            $FECHA_EXCEPCION="";
            $PRODUCTO="";
            $TRANSACCION="";
            $ASESOR="";
            $TIPIFICACION="";
            $SOURCE="";
            $NOMBRE_ARCHIVO="";
            $TAMANO="";
            $VISTA="";
            $FECHA_INICIO="";
            $FECHA_FIN="";
            $TABLA="";
            $FECHA_GESTION="";



            $table .= "</tr>";
        }



        $this->response(json_encode(array("msg"=>"OK","data" => $today)),200);


    }

//-------------------------fin importar cargar_datos

    //-------------------pruebacargardatos
    private function listadoarchivosdocu1(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $dir = "../uploads/";
        // Abre un directorio conocido, y procede a leer el contenido
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if(filetype($file) !== "dir"){
                        //echo "nombre archivo: $file : tipo archivo: " . filesize($dir . $file) . "\n";
                        $result[] = array('nombre'=>$file, 'size'=>round(filesize($dir . $file)/ (1024), 2)." Kb", 'fecha'=>date ("Y-m-d H:i:s",filemtime($dir . $file)));
                    }
                }
                $this->response($this->json(array($result)), 200);
                closedir($dh);
            }
        }
    }



    private function UploadFile2(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        require_once '../librerias/importar_excel/reader/Classes/PHPExcel/IOFactory.php';
        $pedido=json_decode(file_get_contents("php://input"),true);
        //ini_set('display_errors', '1');
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($_FILES["file"]["name"]);
        //$name     = $_FILES['fileUpload']['name'];
        $tname    = $_FILES['file']['tmp_name'];
        $type     = $_FILES['file']['type'];
        move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
        echo $target_file;
        $NOMBRE_ARCHIVO=$_FILES["file"]["name"];
        $TAMANO =$_FILES["file"]["size"];
        //$usas =$_FILES["file"]["user"];
        //$pedido = json_decode(file_get_contents("php://input"),true);
        $usas = $this->_request['user'];
        //echo var_dump($usas);
        //echo var_dump($_FILES );
        //$this->response(json_encode(""),200);
        $PEDIDO_ID='';
        $cliente_id='';
        $ACCESO='';
        $ESTADO='';


        $sqlupload="insert into portalbd.gestor_log_fileupload (ASESOR,NOMBRE_ARCHIVO,TAMANO,VISTA) values ('$usas','$NOMBRE_ARCHIVO','$TAMANO','PENDIENTES')";
        //echo  $user;
        $r = $this->mysqli->query($sqlupload) or die($this->mysqli->error.__LINE__);

        // SQL Feed----------------------------------
        $sql_log=   "insert into portalbd.activity_feed ( ".
            " USER ".
            ", USER_NAME ".
            ", GRUPO ".
            ", STATUS ".
            ", PEDIDO_OFERTA ".
            ", ACCION ".
            ", CONCEPTO_ID ".
            ", IP_HOST ".
            ", CP_HOST ".
            ") values( ".
            " UPPER('$usuarioGalleta')".
            ", UPPER('$nombreGalleta')".
            ", UPPER('$grupoGalleta')".
            ",'OK' ".
            ",'SIN PEDIDO' ".
            ",'SUBIO ARCHIVO' ".
            ",'ARCHIVO SUBIDO' ".
            ",'$usuarioIp' ".
            ",'$usuarioPc')";

        $rlog = $this->mysqli->query($sql_log);
        // ---------------------------------- SQL Feed
        //$sqlfeed="insert into portalbd.activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$usas','','','','','PENDIENTES')";
        //$rrr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);


    }



    //------------------fin prueba

    private function listadoarchivosdocu(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $dir = "../documentacion/activacion/";
        // Abre un directorio conocido, y procede a leer el contenido
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if(filetype($file) !== "dir"){
                        //echo "nombre archivo: $file : tipo archivo: " . filesize($dir . $file) . "\n";
                        $result[] = array('nombre'=>$file, 'size'=>round(filesize($dir . $file)/ (1024), 2)." Kb", 'fecha'=>date ("Y-m-d H:i:s",filemtime($dir . $file)));
                    }
                }
                $this->response($this->json(array($result)), 200);
                closedir($dh);
            }
        }
    }

    private function UploadFile1(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        //ini_set('display_errors', '1');
        $target_dir = "../documentacion/activacion/";
        $target_file = $target_dir . basename($_FILES["file"]["name"]);
        //$name     = $_FILES['fileUpload']['name'];
        $tname    = $_FILES['file']['tmp_name'];
        $type     = $_FILES['file']['type'];
        move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
        echo $target_file;

    }
///////////////////////////////////////////////////
    private function cargar_datosparame(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        require_once '../librerias/importar_excel/reader/Classes/PHPExcel/IOFactory.php';

        //ini_set('display_errors', '1');
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($_FILES["fileUpload"]["name"]);
        //$name     = $_FILES['fileUpload']['name'];
        $tname    = $_FILES['fileUpload']['tmp_name'];
        $type     = $_FILES['fileUpload']['type'];

        $carga = json_decode(file_get_contents("php://input"),true);
        $fecha='';
        $departamento='';
        $zona='';
        $am='';
        $pm='';
        $today = date("Y-m-d");


        //$target_file = basename($_FILES["fileUpload"]["name"]);
        $uploadOk = 1;
        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            echo  "Lo sentimos , el archivo no se ha subido.";
            // if everything is ok, try to upload file
        } else {

            if (move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $target_file)){
                echo "El archivo ". basename( $_FILES["fileUpload"]["name"]). " se ha subido.";
            } else {

                echo "Ha habido un error al subir el archivo.";
            }
        }

        //$this->response(json_encode(array("msg"=>"OK","data" => $today)),200);


        // var_dump($_FILES);
        $tname1 = basename( $_FILES["fileUpload"]["name"]);

        if($type == 'application/vnd.ms-excel')
        {
            // Extension excel 97
            $ext = 'xls';
        }
        else if($type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        {
            // Extension excel 2007 y 2010
            $ext = 'xlsx';
        }else{
            // Extension no valida
            echo -1;
            exit();
        }

        $xlsx = 'Excel2007';
        $xls  = 'Excel5';

        //creando el lector
        $objReader = PHPExcel_IOFactory::createReader($$ext);

        //cargamos el archivo
        $objPHPExcel = $objReader->load($target_file);

        $dim = $objPHPExcel->getActiveSheet()->calculateWorksheetDimension();

        // list coloca en array $start y $end Lista Coloca en array $ inicio y final $
        list($start, $end) = explode(':', $dim);

        if(!preg_match('#([A-Z]+)([0-9]+)#', $start, $rslt)){
            return false;
        }
        list($start, $start_h, $start_v) = $rslt;
        if(!preg_match('#([A-Z]+)([0-9]+)#', $end, $rslt)){
            return false;
        }
        list($end, $end_h, $end_v) = $rslt;

        //empieza  lectura vertical
        $table = "<table  border='1'>";
        for($v=$start_v; $v<=$end_v; $v++){
            //empieza lectura horizontal
            if ($v==1) continue;
            $table .= "<tr>";
            //$filas= $start_h + 1;
            for($h=$start_h; ord($h)<=ord($end_h);$this->pp($h)){
                $cellValue = $this->get_cell($h.$v, $objPHPExcel);
                if($h=="A"){
                    $timestamp = PHPExcel_Shared_Date::ExcelToPHP($cellValue);//fecha larga
                    $fecha = gmdate("Y-m-d",$timestamp);//fecha formateada+
                    $table .= "<td>";
                    if($cellValue !== null){
                        $table .= $cellValue;
                    }
                }else{
                    $table .= "<td>";
                    $guardar .=" '$cellValue',";
                    if($cellValue !== null){
                        $table .= $cellValue;
                    }
                    if($h=="B"){
                        $departamento=$cellValue;
                    }
                    if($h=="C"){
                        $zona=$cellValue;
                    }
                    if($h=="D"){
                        $AM=$cellValue;
                    }
                    if($h=="E"){
                        $PM=$cellValue;
                    }
                }
            }

            $no_permitidas= array ("á","é","í","ó","ú","Á","É","Í","Ó","Ú","ñ","À","Ã","Ì","Ò","Ù","Ã™","Ã ","Ã¨","Ã¬","Ã²","Ã¹","ç","Ç","Ã¢","ê","Ã®","Ã´","Ã»","Ã‚","ÃŠ","ÃŽ","Ã”","Ã›","ü","Ã¶","Ã–","Ã¯","Ã¤","«","Ò","Ã","Ã„","Ã‹","Ñ");
            $permitidas= array ("a","e","i","o","u","A","E","I","O","U","n","N","A","E","I","O","U","a","e","i","o","u","c","C","a","e","i","o","u","A","E","I","O","U","u","o","O","i","a","e","U","I","A","E","N");
            $departamento = str_replace($no_permitidas, $permitidas ,$departamento);
            $zona = str_replace($no_permitidas, $permitidas ,$zona);

            $this->dbConnect03();

            $sql= " SELECT ID, FECHA, AM , PM ".
                "FROM alistamiento.parametrizacion_siebel ".
                "WHERE DEPARTAMENTO = '$departamento' ".
                "AND ZONA = '$zona' ".
                "AND FECHA = '$fecha'";
            $r = $this->mysqli03->query($sql) or die($this->mysqli->error.__LINE__);

            if ($departamento !== ""){

                if($r->num_rows == 0){
                    $sql="INSERT INTO alistamiento.parametrizacion_siebel ".
                        " (FECHA, DEPARTAMENTO, ZONA, AM, PM) ".
                        " VALUES ('$fecha', '$departamento', '$zona', '$AM', '$PM')";
                    $r = $this->mysqli03->query($sql) or die($this->mysqli->error.__LINE__);

                }
                else {
                    $row= $r->fetch_assoc();
                    $ID = $row['ID'];
                    $sql= "update alistamiento.parametrizacion_siebel ".
                        "set AM='$AM', PM='$PM' WHERE ID='$ID' ";
                    $r = $this->mysqli03->query($sql) or die($this->mysqli->error.__LINE__);

                }
            }
            $departamento="";
            $fecha="";
            $zona="";
            $AM="";
            $PM="";
            $table .= "</tr>";
        }

        $this->response(json_encode(array("msg"=>"OK","data" => $today)),200);

    }
////////////////////////////////////////////////////////
    private  function get_cell($cell, $objPHPExcel){
        //select one cell seleccionar una célda
        $objCell = ($objPHPExcel->getActiveSheet()->getCell($cell));
        //get cell value obtener valor de la celda
        return $objCell->getvalue();
    }
    private function pp(&$var){
        $var = chr(ord($var)+1);
        return true;
    }



    private function getPedidos_Microzonas(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $fecha = $this->_request['fecha'];
        $today = date("Y-m-d");

        $conna=getConnAgendamiento();

        //$query="SELECT count(*) as counter from transacciones_nca where FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
        //$rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        //$counter=0;
        //if($rr->num_rows > 0){
        //        $result = array();
        //        if($row = $rr->fetch_assoc()){
        //                $counter = $row['counter'];
        //        }
        // }


        $query="SELECT ".
            " C1.FECHA_DISP ".
            " ,C1.DEPARTAMENTO ".
            " , C1.ZONA ".
            " , C1.MICROZONA ".
            " , C1.SEGMENTO ".
            " , C1.PARAM_AM ".
            " , C1.DISP_AM ".
            " , C1.PARAM_PM ".
            " , C1.DISP_PM ".
            " , C1.PARAM_HF ".
            " ,(C1.N7+C1.N8+C1.N9+C1.N10+C1.N11+C1.N12+C1.N13+C1.N14+C1.N15+C1.N16+C1.N17+C1.N18+C1.N19)AS DISP_HF ".
            " ,(C1.DISP_AM + C1.DISP_PM + (C1.N7+C1.N8+C1.N9+C1.N10+C1.N11+C1.N12+C1.N13+C1.N14+C1.N15+C1.N16+C1.N17+C1.N18+C1.N19))AS TOTAL_DISP ".
            " FROM (select ".
            "        CASE WHEN dp.dep_departamento = 'Antioquia' THEN 'ANTIOQUIA'   ".
            "             WHEN dp.dep_departamento = 'Atlantico' THEN 'ATLANTICO' ".
            "             WHEN dp.dep_departamento = 'Cundinamarca' THEN 'CUNDINAMARCA' ".
            "             WHEN dp.dep_departamento = 'Bolivar' THEN 'BOLIVAR'  ".
            "             WHEN dp.dep_departamento = 'Valle del Cauca' THEN 'VALLE DEL CAUCA' END AS DEPARTAMENTO, ".
            "                   CASE  ".
            "                     WHEN (dp.dep_departamento = 'Antioquia'  ".
            "                     AND subz.sbz_subzona IN ('AMERICA','BUENOS_AIR','CEN','CENTRO','COLON','MIRAFLORES','NUTIBARA','OTRABANDA','SAN_BERN_1','SAN_BERN_2','SAN_JAVIER','VILLAHERMO') ".
            "                 AND sbag.sag_prioridad <> 'migracion') THEN 'CENTRO' ".
            "             WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('BARCOPGIR','BELLO_1','BELLO_2','BELLO_3','BERLIN','BOSQUE_1','BOSQUE_2','CARIBE','CASTILLA','FLORENCIA','GIR','IGUANA','IGUSANCRI','NIQUIA','NOR')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'NORTE' ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('CALDAS','ENVIGADO_1','ENVIGADO_2','ENVIGADO_3','ESTRELLA','GUAYABAL','ITAGUI_1','ITAGUI_2','ITAGUI_3','POBLADO_1','POBLADO_2','SABANETA','SANANTPRA','SUR-ENV','SUR','SUR-SAB')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'SUR'    ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('M1_ORIENTE','M2_ORIENTE','M3_ORIENTE','RIO')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'ORIENTE'     ".
            "            WHEN dp.dep_departamento IN ('Bolivar','Atlantico','Cundinamarca','Valle del Cauca')    ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN UPPER(dp.dep_departamento) ".
            "             END AS ZONA, ".
            "           sbz_subzona AS MICROZONA,sbag.sag_segmento, ".
            "                 sbag.sag_segmento AS SEGMENTO,agm.agm_fechacita AS FECHA_DISP, ".
            "                 sbag.sag_cuposam AS PARAM_AM, ".
            "                 IFNULL((sbag.sag_cuposam-IFNULL(COUNT(IF(agm.agm_jornadacita='AM',1,NULL)),0)),0) AS DISP_AM,sbag.sag_cupospm AS PARAM_PM, ".
            "                 IFNULL((sbag.sag_cupospm-IFNULL(COUNT(IF(agm.agm_jornadacita='PM',1,NULL)),0)),0) AS DISP_PM,(IFNULL(sbag.sag_cupos7,0)+IFNULL(sbag.sag_cupos8,0)+IFNULL(sbag.sag_cupos9,0)+IFNULL(sbag.sag_cupos10,0)+  ".
            "                 IFNULL(sbag.sag_cupos11,0)+IFNULL(sbag.sag_cupos12,0)+IFNULL(sbag.sag_cupos13,0)+IFNULL(sbag.sag_cupos14,0)+ ".
            "                 IFNULL(sbag.sag_cupos15,0)+IFNULL(sbag.sag_cupos16,0)+IFNULL(sbag.sag_cupos17,0)+IFNULL(sbag.sag_cupos18,0)+ ".
            "                 IFNULL(sbag.sag_cupos19,0))AS PARAM_HF,IFNULL(IF(sbag.sag_cupos7 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)))),IFNULL((sbag.sag_cupos7-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)),0)),0)),0) AS 'N7', ".
            "         IFNULL(IF(sbag.sag_cupos8 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)))),IFNULL((sbag.sag_cupos8-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)),0)),0)),0) AS 'N8', ".
            "                 IFNULL(IF(sbag.sag_cupos9 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)))),IFNULL((sbag.sag_cupos9-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)),0)),0)),0) AS 'N9', ".
            "                 IFNULL(IF(sbag.sag_cupos10 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)))),IFNULL((sbag.sag_cupos10-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)),0)),0)),0) AS 'N10', ".
            "         IFNULL(IF(sbag.sag_cupos11 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)))),IFNULL((sbag.sag_cupos11-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)),0)),0)),0) AS 'N11', ".
            "                 IFNULL(IF(sbag.sag_cupos12 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)))),IFNULL((sbag.sag_cupos12-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)),0)),0)),0) AS 'N12', ".
            "                 IFNULL(IF(sbag.sag_cupos13 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)))),IFNULL((sbag.sag_cupos13-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)),0)),0)),0) AS 'N13', ".
            "                 IFNULL(IF(sbag.sag_cupos14 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)))),IFNULL((sbag.sag_cupos14-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)),0)),0)),0) AS 'N14', ".
            "                 IFNULL(IF(sbag.sag_cupos15 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)))),IFNULL((sbag.sag_cupos15-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)),0)),0)),0) AS 'N15', ".
            "                 IFNULL(IF(sbag.sag_cupos16 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)))),IFNULL((sbag.sag_cupos16-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)),0)),0)),0) AS 'N16', ".
            "                 IFNULL(IF(sbag.sag_cupos17 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)))),IFNULL((sbag.sag_cupos17-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)),0)),0)),0) AS 'N17', ".
            "                 IFNULL(IF(sbag.sag_cupos18 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)))),IFNULL((sbag.sag_cupos18-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)),0)),0)),0) AS 'N18', ".
            "                 IFNULL(IF(sbag.sag_cupos19 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)))),IFNULL((sbag.sag_cupos19-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)),0)),0)),0) AS 'N19' ".
            " from agn_agendamientos agm  ".
            " left join agn_subagendas sbag on agm.agm_agenda = sbag.sag_id  ".
            " left join agn_agendas ag on sbag.sag_agenda = ag.ads_id ".
            " left join agn_subzonas  subz on sbag.sag_subzona = subz.sbz_id ".
            " left join agn_departamentos  dp on ag.ags_departamento = dp.dep_id ".
            " where agm.agm_fechacita >= ag.ags_fechainicial  ".
            " AND agm.agm_fechacita <= ag.ags_fechafinal  ".
            " AND ag.ags_fechafinal >= CURDATE()  ".
            " AND sbag.sag_prioridad <> 'migracion' ".
            " AND (agm.agm_estadototal IN ('Agendar','Agendamientos') OR (agm.agm_estadototal IN('Pendiente','Prospecto','')  ".
            " AND (agm.agm_fechacita > IF(HOUR(CURTIME()) < 16,CURDATE(), DATE_ADD(NOW(), INTERVAL 1 DAY)))))  ".
            " and agm.agm_fechacita >= date_add(curdate(), interval 1 day) ".
            " group by agm.agm_agenda,agm.agm_fechacita ".
            " order by ag.ags_fechafinal ASC ) C1 ".
            " where C1.FECHA_DISP ='$fecha' ";
        //echo $query;
        $r = $conna->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){

                $result[] = $row;

            }
            $this->response($this->json(array($result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }




    private function listadoAlarmasActivacion(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $query="select a.COLA_ID, a.RESPONSABLE1 AS USUARIO_ID1 ".
            " , (select b.USUARIO_NOMBRE from tbl_usuarios b where a.RESPONSABLE1=b.USUARIO_ID) as RESPONSABLE1 ".
            " , a.RESPONSABLE2 AS USUARIO_ID2 , (select b.USUARIO_NOMBRE from tbl_usuarios b where a.RESPONSABLE2=b.USUARIO_ID) as RESPONSABLE2 ".
            " , a.FECHA_ACTUALIZACION ".
            " from gestor_responsables_activacion a ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                //echo "name: ".utf8_encode($row['USUARIO_NOMBRE'])."\n ";
                $row['RESPONSABLE1']=utf8_encode($row['RESPONSABLE1']);
                $row['RESPONSABLE2']=utf8_encode($row['RESPONSABLE2']);
                $result[] = $row;
            }
            $this->response($this->json(array($result,$counter)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

    private function usuariosAlarmasActivacion(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $query="select USUARIO_ID, USUARIO_NOMBRE, GRUPO from tbl_usuarios where grupo='ACTIVACION'";
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
            $counter=0;
            $this->response($this->json(array($result,$counter)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

    private function insertarAlarmaActivacion(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $nuevaCola = json_decode(file_get_contents("php://input"),true);

        $nuevaCola = $nuevaCola['nuevaCola'];

        $column_names = array('COLA_ID','RESPONSABLE1','RESPONSABLE2','FECHA_ACTUALIZACION');
        $keys = array_keys($nuevaCola);
        $columns = '';
        $values = '';

        //echo var_dump($transaccion);
        //echo var_dump($keys);
        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
            if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
            }else{
                $$desired_key = $nuevaCola[$desired_key];
            }
            $columns = $columns.$desired_key.',';
            $values = $values."'".strtoupper($nuevaCola[$desired_key])."',";
        }
        //$today = date("Y-m-d H:i:s");
        $query = "INSERT INTO  gestor_responsables_activacion (".trim($columns,',').") VALUES(".trim($values,',').")";
        //echo $query;
        if(!empty($nuevaCola)){
            //echo $query;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
            $this->response(json_encode(array("msg"=>"OK","transaccion" => $nuevaCola)),200);

        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }
    }


    private function actualizarAlarmaActivacion(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $RESPONSABLE1 = $this->_request['responsable1'];
        $RESPONSABLE2 = $this->_request['responsable2'];
        $COLA_ID = $this->_request['cola_id'];
        $today = date("Y-m-d");

        $sql="UPDATE gestor_responsables_activacion ".
            " SET RESPONSABLE1='$RESPONSABLE1', RESPONSABLE2='$RESPONSABLE2', FECHA_ACTUALIZACION='$today'  where COLA_ID='$COLA_ID'";

        $rr = $this->mysqli->query($sql);

        $this->response(json_encode(array("OK","PARAMETRO ACTUALIZADO")), 200);

    }

    private function getListadoTips(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $query="SELECT ID, TITULO, USER_POST, CATEGORIA, TAGS, PRIORIDAD, POST_TIME, USUARIO_ID, ".
            " CASE POST_STATUS ".
            " WHEN 0 THEN 'ACTIVO' ".
            " ELSE 'INACTIVO' ".
            " END AS POST_STATUS ".
            " FROM gestor_tips_post ".
            " WHERE POST_STATUS=0 ".
            " ORDER BY ID DESC ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                //echo "name: ".utf8_encode($row['USUARIO_NOMBRE'])."\n ";
                $row['USER_POST']=utf8_encode($row['USER_POST']);
                $row['TITULO']=utf8_encode($row['TITULO']);
                $row['TAGS']=utf8_encode($row['TAGS']);
                $result[] = $row;
            }
            $counter=0;
            $this->response($this->json(array($result,$counter)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }


    private function actualizarTablaGraficaCambioNuevoREDCO(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $this->dbConnect03();

        $query="SELECT B.FECHA, B.MANUAL_CAMBIO, B.MANUAL_NUEVO, B.TOTAL_MANUAL, ".
            " B.AUTOMATICO_NUEVO, B.AUTOMATICO_CAMBIO, B.TOTAL_AUTOMATICO ".
            " FROM ( SELECT DATE_FORMAT(FECHA,'%M') as FECHA, ".
            " SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN REDCO ELSE 0 END)AS TOTAL_AUTOMATICO, ".
            " SUM(TOTAL_TIPO_USUARIO-HFC-GPON-OTRA-SIN) AS TOTAL, ".
            " SUM( CASE WHEN  TIPO_USUARIO='MANUAL' THEN REDCO ELSE 0 END) AS TOTAL_MANUAL,  ".
            " SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN NUEVO_REDCO ELSE 0 END)/SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN REDCO ELSE 0 END) AS AUTOMATICO_NUEVO, ".
            " SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN CAMBIO_REDCO ELSE 0 END)/SUM( CASE WHEN  TIPO_USUARIO='AUTOMATICO' THEN REDCO ELSE 0 END) AS AUTOMATICO_CAMBIO, ".
            " SUM( CASE WHEN  TIPO_USUARIO='MANUAL' THEN NUEVO_REDCO ELSE 0 END)/SUM( CASE WHEN  TIPO_USUARIO='MANUAL' THEN REDCO ELSE 0 END)  AS MANUAL_NUEVO, ".
            " SUM( CASE WHEN  TIPO_USUARIO='MANUAL' THEN CAMBIO_REDCO ELSE 0 END)/SUM( CASE WHEN  TIPO_USUARIO='MANUAL' THEN REDCO ELSE 0 END)  AS MANUAL_CAMBIO ".
            " FROM gestor_informes.kpi_seguimiento_automatico ".
            " where year(FECHA)= YEAR(NOW()) ".
            " group by DATE_FORMAT(FECHA,'%M') order by DATE_FORMAT(FECHA,'%m') asc limit 12) B ";
        $r = $this->mysqli03->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                //echo "name: ".utf8_encode($row['USUARIO_NOMBRE'])."\n ";
                $row['MANUAL_CAMBIO']=$row['MANUAL_CAMBIO']*100;
                $row['MANUAL_NUEVO']=$row['MANUAL_NUEVO']*100;
                $row['AUTOMATICO_CAMBIO']=$row['AUTOMATICO_CAMBIO']*100;
                $row['AUTOMATICO_NUEVO']=$row['AUTOMATICO_NUEVO']*100;

                $result[] = $row;
                //echo $result;
            }
            $this->response($this->json(array($result)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

    private function listadoAdmonTips(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $query="SELECT ID, TITULO, USER_POST, CATEGORIA, TAGS, PRIORIDAD, POST_TIME, USUARIO_ID, ".
            " CASE POST_STATUS ".
            " WHEN 0 THEN 'ACTIVO' ".
            " ELSE 'INACTIVO' ".
            " END AS POST_STATUS ".
            " FROM gestor_tips_post ".
            " ORDER BY ID DESC ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                //echo "name: ".utf8_encode($row['USUARIO_NOMBRE'])."\n ";
                $row['USER_POST']=utf8_encode($row['USER_POST']);
                $row['TITULO']=utf8_encode($row['TITULO']);
                $row['TAGS']=utf8_encode($row['TAGS']);
                $result[] = $row;
            }
            $counter=0;
            $this->response($this->json(array($result,$counter)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }


    private function getTransaccionTip(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $id = $this->_request['id'];


        $query="select * from gestor_tips_post where ID=$id";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $ids="";
            $sep="";
            $transaccion='';
            if($row = $r->fetch_assoc()){
                $row['USER_POST']=utf8_encode($row['USER_POST']);
                $row['TITULO']=utf8_encode($row['TITULO']);
                $row['TAGS']=utf8_encode($row['TAGS']);
                $transaccion = $row;
            }
            $this->response($this->json($transaccion), 200); // send user details
        }
        $this->response('',204);
    }


    private function getVisualizacionTip(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $id = $this->_request['id'];


        $query="select b.USUARIO_NOMBRE as USUARIO_ID , a.TITULO ".
            " , a.USER_POST ".
            " , a.POST_TIME ".
            " , CASE a.POST_STATUS ".
            " WHEN 0 THEN 'ACTIVO' ".
            " ELSE 'INACTIVO' END AS POST_STATUS ".
            " , a.CATEGORIA, a.TAGS, a.PRIORIDAD ".
            " from gestor_tips_post a INNER JOIN tbl_usuarios b ".
            " ON a.USUARIO_ID = b.USUARIO_ID where a.ID=$id";

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $ids="";
            $sep="";
            $transaccion='';
            if($row = $r->fetch_assoc()){
                $row['USER_POST']=utf8_encode($row['USER_POST']);
                $row['USUARIO_ID']=utf8_encode($row['USUARIO_ID']);
                $row['TITULO']=utf8_encode($row['TITULO']);
                $row['TAGS']=utf8_encode($row['TAGS']);
                $transaccion = $row;
            }
            $this->response($this->json($transaccion), 200); // send user details
        }
        $this->response('',204);
    }


    private function actualizarTip(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $updateTip = json_decode(file_get_contents("php://input"),true);
        //echo var_dump($usuario);

        $updateTip = $updateTip['guardarEdicion'];
        $column_names = array('USUARIO_ID','TITULO','USER_POST','POST_TIME','POST_STATUS','CATEGORIA','TAGS','PRIORIDAD');
        $keys = array_keys($updateTip);
        $columns = '';
        $values = '';
        $UPDATE="";
        $SEP="";

        $updateTip['TITULO']=utf8_decode($updateTip['TITULO']);
        $updateTip['TAGS']=utf8_decode($updateTip['TAGS']);
        $updateTip['USER_POST']=utf8_decode($updateTip['USER_POST']);
        $updateTip['USER_POST']=str_replace(array("\n","\r"), '', $updateTip['USER_POST']);
        $updateTip['USER_POST']=str_replace(array("'"), "\"", $updateTip['USER_POST']);

        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
            if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
            }else{
                $$desired_key = $updateTip[$desired_key];
            }
            $columns = $columns.$desired_key.',';
            $values = $values."'".$updateTip[$desired_key]."',";
            $UPDATE=$UPDATE.$SEP.$desired_key." = '".$updateTip[$desired_key]."' ";
            $SEP=",";
        }
        $today = date("Y-m-d H:i:s");

        $passcode="";

        //}
        $query = "UPDATE gestor_tips_post SET $UPDATE $passcode WHERE ID=".$updateTip['ID'];
        //echo $query;
        if(!empty($updateTip)){
            //echo $query;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
            $this->response(json_encode(array("msg"=>"OK","transaccion" => $updateTip)),200);
        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }
    }


    private function insertarTip(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $nuevoTip = json_decode(file_get_contents("php://input"),true);

        $nuevoTip = $nuevoTip['nuevoTip'];
        $column_names = array('USUARIO_ID','TITULO','USER_POST','POST_TIME','POST_STATUS','CATEGORIA','TAGS','PRIORIDAD');
        $keys = array_keys($nuevoTip);
        $columns = '';
        $values = '';
        $usuario_id=$nuevoTip['USUARIO_ID'];
        $titulo=$nuevoTip['TITULO'];
        $user_post=$nuevoTip['USER_POST'];
        $post_time=$nuevoTip['POST_TIME'];
        $post_status=$nuevoTip['POST_STATUS'];
        $categoria=$nuevoTip['CATEGORIA'];
        $tags=$nuevoTip['TAGS'];
        $prioridad=$nuevoTip['PRIORIDAD'];


        $nuevoTip['TITULO']=utf8_decode($nuevoTip['TITULO']);
        $nuevoTip['TAGS']=utf8_decode($nuevoTip['TAGS']);
        $nuevoTip['USER_POST']=utf8_decode($nuevoTip['USER_POST']);
        $nuevoTip['USER_POST']=str_replace(array("\n","\r"), '', $nuevoTip['USER_POST']);
        $nuevoTip['USER_POST']=str_replace(array("'"), "\"", $nuevoTip['USER_POST']);

        //echo var_dump($transaccion);
        //echo var_dump($keys);
        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
            if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
            }else{
                $$desired_key = $nuevoTip[$desired_key];
            }
            $columns = $columns.$desired_key.',';
            $values = $values."'".$nuevoTip[$desired_key]."',";
        }
        $today = date("Y-m-d H:i:s");
        $query = "INSERT INTO  gestor_tips_post (".trim($columns,',').") VALUES(".trim($values,',').")";
        //echo $query;
        if(!empty($nuevoTip)){
            //echo $query;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'CREO TIP' ".
                ",'TIP CREADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response(json_encode(array("msg"=>"OK","transaccion" => $nuevoTip)),200);

        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }

    private function deleteTip(){
        if($this->get_request_method() != "DELETE"){
            $this->response('',406);
        }
        $id = (int)$this->_request['id'];
        if($id > 0){
            $query="DELETE FROM gestor_tips_post WHERE ID = $id";
            //echo($query);
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
            //$success = array('status' => "Success", "msg" => "Successfully deleted one record.");
            $this->response(json_encode(array('msg'=>'Se realizo la eliminación del TIP','id' => $id)),200);
            //$this->response($this->json($success),200);
        }else
        {
            $this->response('',204);    // If no records "No Content" status
        }
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


        $query=	" SELECT u.ID, ".
            " u.USUARIO_ID, ".
            " u.USUARIO_NOMBRE, ".
            " u.CEDULA_ID, ".
            " u.GRUPO, ".
            " u.EQUIPO_ID, ".
            " u.CORREO_USUARIO, ".
            " u.FUNCION, ".
            " u.TURNO, ".
            " u.CARGO_ID, ".
            " c.NOMBRE_CARGO, ".
            " u.SUPERVISOR, ".
            " u.INTERVENTOR, ".
            " u.ESTADO ".
            " FROM portalbd.tbl_usuarios u ".
            " left join portalbd.tbl_cargos c on u.CARGO_ID=c.ID_CARGO ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                //echo "name: ".utf8_encode($row['USUARIO_NOMBRE'])."\n ";
                $row['USUARIO_NOMBRE']=utf8_encode($row['USUARIO_NOMBRE']);
                $row['INTERVENTOR']=utf8_encode($row['INTERVENTOR']);
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

        /*
         * Query viejo

        $query=	" SELECT ".
            " a.FECHA ".
            ", a.USER ".
            ", a.GRUPO ".
            ", case ".
            "	when a.ACCION='DEMEPEDIDO' then 'Auto' ".
            "	when a.ACCION='BUSCARPEDIDO' then 'Busco' ".
            "	when a.ACCION='ESTUDIO' then 'Guardo' ".
            "	when a.ACCION='LOGIN' then 'Se logueo' ".
            "	when a.ACCION='NCA' then 'Siebel' ".
            "	when a.ACCION='ORD' then 'Audito' ".
            "	when a.ACCION='BODEGA DATOS' then 'Bodega' ".
            "	when a.ACCION like '%PENDIENTE' then 'Pendiente' ".
            "	when a.ACCION like '%UPDATE' then 'Parametro' ".
            "	else a.ACCION ".
            " end as ACCION ".
            " FROM activity_feed a ".
            " WHERE a.fecha BETWEEN '$today 00:00:00' AND '$today 23:59:59' ".
            " ORDER BY a.id DESC  ".
            " LIMIT 10 ";
        //echo $query; */

        $query=	" SELECT  ".
            " a.FECHA ".
            " , a.USER ".
            " , case ".
            "    when a.USER='GESTOR' then 'AUTOMATICO' ".
            "    else a.GRUPO ".
            "    end as GRUPO ".
            " , trim(case ".
            "    when a.USER='GESTOR' then 'DEMONIO' ".
            "    when a.ACCION='' then 'SIN' ".
            "    when a.ACCION='SE LOGUEO' then 'LOGIN' ".
            "    else substr(a.ACCION,1, INSTR(a.ACCION, ' ' )) ".
            "    end )as ACCION ".
            " , a.ACCION AS DETALLE ".
            " FROM activity_feed a ".
            " WHERE a.fecha BETWEEN '$today 00:00:00' AND '$today 23:59:59' ".
            " ORDER BY a.id DESC ".
            " LIMIT 15 ";
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

    private function csvGPON(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $OLT = $this->_request['OLT'];
        $TARJETA = $this->_request['TARJETA'];
        $PUERTO = $this->_request['PUERTO'];
        $login = $this->_request['login'];

        $today = date("Y-m-d h:i:s");

        $filename="ASIGNACIONES-GPON-$login-$today.csv";
        $query="select ".
            "IDENTIFICADOR_ID,PRODUCTO_ID,TIPO_ELEMENTO_ID,CLIENTE_ID,NOMBRE_COMPLETO,VELOCIDAD_ACCESO".",NODO,CENTRAL,OLT_ID,SLOT_ID,PUERTO_ID,PTO_LOGICO,TARJETA_ID,PUNTOS_TRKSIP,PUNTOS_SD,PUNTOS_HD ".
            " from gestor_buscador_gpon  where ".
            " olt_id = '$OLT' ".
            " AND tarjeta_id = '$TARJETA' ".
            " AND puerto_id = $PUERTO ";


        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('IDENTIFICADOR_ID','PRODUCTO_ID','TIPO_ELEMENTO_ID','CLIENTE_ID','NOMBRE_COMPLETO','VELOCIDAD_ACCESO','NODO','CENTRAL','OLT_ID','SLOT_ID','PUERTO_ID','PTO_LOGICO','TARJETA_ID','PUNTOS_TRKSIP','PUNTOS_SD','PUNTOS_HD'));
            while($row = $r->fetch_assoc()){
                fputcsv($fp, $row);
            }
            fclose($fp);

            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }


    private function consultaFenixServicesGPON($OLT,$TARJETA,$PUERTO){

        $this->dbFenixSTBYConnect();
        $connfstby=$this->connfstby;

//2. consulta para buscar informacion sobre puerto de gpon


        //BORRAR INFORMACION DE LA TABLA CON PARAMETROS....

        $sql="delete from gestor_buscador_gpon where OLT_ID='$OLT' and PUERTO_ID='$PUERTO' and TARJETA_ID='$TARJETA' ";
        $r = $this->mysqli->query($sql) or die($this->mysqli->error.__LINE__);

        $sqlfenix=
            " SELECT   ga.identificador_id, ".
            "          i.producto_id, ".
            "          i.tipo_elemento_id, ".
            "          i.cliente_id, ".
            "          c.nombre_completo, ".
            "          CASE ".
            "             WHEN UPPER(fn_valor_caracteristica_lista ( ".
            "                           DECODE (producto_id, ".
            "                                   'INTER', 124, ".
            "                                   'CONECT', 2787, ".
            "                                   NULL), ".
            "                           fn_valor_caract_identif ( ".
            "                              ga.identificador_id, ".
            "                              DECODE (producto_id, ".
            "                                      'INTER', 124, ".
            "                                      'CONECT', 2787, ".
            "                                      NULL) ".
            "                           ) ".
            "                        )) LIKE ".
            "                     '%K%' ".
            "             THEN ".
            "                fn_valor_caract_identif ( ".
            "                   ga.identificador_id, ".
            "                   DECODE (producto_id, 'INTER', 124, 'CONECT', 2787, NULL) ".
            "                ) ".
            "                / 1000 ".
            "             ELSE ".
            "                fn_valor_caract_identif ( ".
            "                   ga.identificador_id, ".
            "                   DECODE (producto_id, 'INTER', 124, 'CONECT', 2787, NULL) ".
            "                ) ".
            "                / 1 ".
            "          END ".
            "             velocidad_acceso, ".
            "          o.nodo, ".
            "          o.central, ".
            "          ga.olt_id, ".
            "          t.slot_id, ".
            "          ga.puerto_id, ".
            "          ga.pto_logico, ".
            "          ga.tarjeta_id, ".
            "          DECODE ( ".
            "             producto_id, ".
            "             'TRKSIP', ".
            "             (SELECT   COUNT ( * ) ".
            "                FROM   fnx_configuraciones_identif ci, fnx_identificadores i ".
            "               WHERE       ci.identificador_id = i.identificador_id ".
            "                       AND i.tipo_elemento_id = 'NUMIP' ".
            "                       AND i.cliente_id IS NOT NULL ".
            "                       AND i.estado = 'OCU' ".
            "                       AND ci.caracteristica_id = 2856 ".
            "                       AND ci.valor = ga.identificador_id) ".
            "          ) ".
            "             puntos_trksip, ".
            "          DECODE ( ".
            "             producto_id, ".
            "             'TV', ".
            "             (SELECT   COUNT ( * ) ".
            "                FROM   fnx_configuraciones_identif ci, ".
            "                       fnx_configuraciones_equipos ce, ".
            "                       fnx_equipos e ".
            "               WHERE       ci.identificador_id = e.identificador_id ".
            "                       AND ci.caracteristica_id = 4715 ".
            "                       AND ci.valor = ga.identificador_id ".
            "                       AND e.equipo_id = ce.equipo_id ".
            "                       AND e.estado = 'OCU' ".
            "                       AND ce.caracteristica_id = 1067 ".
            "                       AND ce.valor = 'STBOX'), ".
            "             'TELEV', ".
            "             (SELECT   COUNT ( * ) ".
            "                FROM   fnx_configuraciones_equipos ce, fnx_equipos e ".
            "               WHERE   e.identificador_id = ".
            "                          REPLACE (ga.identificador_id, '-IP', '') ".
            "                       AND e.equipo_id = ce.equipo_id ".
            "                       AND e.estado = 'OCU' ".
            "                       AND ce.caracteristica_id = 1067 ".
            "                       AND ce.valor = 'STBOX') ".
            "          ) ".
            "             puntos_sd, ".
            "          DECODE ( ".
            "             producto_id, ".
            "             'TV', ".
            "             (SELECT   COUNT ( * ) ".
            "                FROM   fnx_configuraciones_identif ci, ".
            "                       fnx_configuraciones_equipos ce, ".
            "                       fnx_equipos e ".
            "               WHERE       ci.identificador_id = e.identificador_id ".
            "                       AND ci.caracteristica_id = 4715 ".
            "                       AND ci.valor = ga.identificador_id ".
            "                       AND e.equipo_id = ce.equipo_id ".
            "                       AND e.estado = 'OCU' ".
            "                       AND ce.caracteristica_id = 1067 ".
            "                       AND ce.valor <> 'STBOX'), ".
            "             'TELEV', ".
            "             (SELECT   COUNT ( * ) ".
            "                FROM   fnx_configuraciones_equipos ce, fnx_equipos e ".
            "               WHERE   e.identificador_id = ".
            "                          REPLACE (ga.identificador_id, '-IP', '') ".
            "                       AND e.equipo_id = ce.equipo_id ".
            "                       AND e.estado = 'OCU' ".
            "                       AND e.tipo_elemento_id = 'STBOX' ".
            "                       AND ce.caracteristica_id = 1067 ".
            "                       AND ce.valor <> 'STBOX') ".
            "          ) ".
            "             puntos_hd ".
            " FROM   fnx_inf_gpon_activas ga, ".
            "          fnx_identificadores i, ".
            "          fnx_clientes c, ".
            "          fnx_gpon_olt o, ".
            "          fnx_gpon_tarjetas_olt t ".
            " WHERE    ga.identificador_id = i.identificador_id ".
            "          AND ga.olt_id = o.olt_id ".
            "          AND ga.municipio_id = o.municipio_id ".
            "          AND ga.olt_id = t.olt_id ".
            "          AND ga.municipio_id = t.municipio_id ".
            "          AND ga.tarjeta_id = t.tarjeta_id ".
            "          AND i.cliente_id = c.cliente_id ".
            "          AND ga.olt_id = '$OLT' ".
            "          AND ga.tarjeta_id = '$TARJETA' ".
            "          AND puerto_id = $PUERTO ";

        //echo  $sqlfenix." \n ";
        $stid = oci_parse($connfstby, $sqlfenix);

        oci_execute($stid);

        $returnn="No rows!!!!";

        while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
            $IDENTIFICADOR_ID= $row['IDENTIFICADOR_ID'];
            $PRODUCTO_ID = $row['PRODUCTO_ID'];
            $TIPO_ELEMENTO_ID= $row["TIPO_ELEMENTO_ID"];
            $CLIENTE_ID = $row["CLIENTE_ID"];
            $NOMBRE_COMPLETO= $row["NOMBRE_COMPLETO"];
            $VELOCIDAD_ACCESO= $row["VELOCIDAD_ACCESO"];
            $NODO= $row["NODO"];
            $CENTRAL= $row["CENTRAL"];
            $OLT_ID = $row["OLT_ID"];
            $SLOT_ID= $row["SLOT_ID"];
            $PUERTO_ID= $row["PUERTO_ID"];
            $PTO_LOGICO = $row["PTO_LOGICO"];
            $TARJETA_ID= $row["TARJETA_ID"];
            $PUNTOS_TRKSIP= $row["PUNTOS_TRKSIP"];
            $PUNTOS_SD= $row["PUNTOS_SD"];
            $PUNTOS_HD= $row["PUNTOS_HD"];


            $query="INSERT INTO gestor_buscador_gpon ".
                "(IDENTIFICADOR_ID, PRODUCTO_ID, TIPO_ELEMENTO_ID, CLIENTE_ID, NOMBRE_COMPLETO, VELOCIDAD_ACCESO, NODO, CENTRAL, OLT_ID, SLOT_ID, PUERTO_ID, PTO_LOGICO, TARJETA_ID, PUNTOS_TRKSIP, PUNTOS_SD, PUNTOS_HD) VALUES ('$IDENTIFICADOR_ID','$PRODUCTO_ID','$TIPO_ELEMENTO_ID','$CLIENTE_ID','$NOMBRE_COMPLETO','$VELOCIDAD_ACCESO','$NODO','$CENTRAL','$OLT_ID','$SLOT_ID','$PUERTO_ID','$PTO_LOGICO','$TARJETA_ID','$PUNTOS_TRKSIP','$PUNTOS_SD','$PUNTOS_HD') ";

            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

            $returnn="OK";
        }

        return $returnn;

    }

    private function getServicesGPON(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $OLT = $this->_request['OLT'];
        $TARJETA= $this->_request['TARJETA'];
        $PUERTO= $this->_request['PUERTO'];

        $respuesta=$this->consultaFenixServicesGPON($OLT,$TARJETA,$PUERTO);
        //$today = date("Y-m-d");

        $query= " select  ".
            " c1.OLT_ID ".
            " ,c1.TARJETA_ID ".
            " , c1.PUERTO_ID ".
            " , sum(c1.VELOCIDAD_ACCESO) as VELOCIDAD_ACCESO ".
            " , (SUM(c1.PUNTOS_TRKSIP))*(SELECT a.EQUIVALENCIA FROM portalbd.gestor_parametros_gpon a WHERE ID=1) AS PUNTOS_TRKSIP ".
            " , (SUM(c1.PUNTOS_SD))*(SELECT a.EQUIVALENCIA FROM portalbd.gestor_parametros_gpon a WHERE ID=2) AS PUNTOS_SD ".
            " , (SUM(c1.PUNTOS_hd))*(SELECT a.EQUIVALENCIA FROM portalbd.gestor_parametros_gpon a WHERE ID=3) AS PUNTOS_HD ".
            " , ((sum(c1.VELOCIDAD_ACCESO)) ".
            "            +(SUM(c1.PUNTOS_TRKSIP))*(SELECT a.EQUIVALENCIA FROM portalbd.gestor_parametros_gpon a WHERE ID=1) ".
            "            +(SUM(c1.PUNTOS_SD))*(SELECT a.EQUIVALENCIA FROM portalbd.gestor_parametros_gpon a WHERE ID=2) ".
            "    +(SUM(c1.PUNTOS_hd))*(SELECT a.EQUIVALENCIA FROM portalbd.gestor_parametros_gpon a WHERE ID=3)) AS TOTAL ".
            " from (SELECT  ".
            "     g.IDENTIFICADOR_ID, ".
            "     g.PRODUCTO_ID, ".
            "     g.TIPO_ELEMENTO_ID, ".
            "     g.CLIENTE_ID, ".
            "     g.NOMBRE_COMPLETO, ".
            "     g.VELOCIDAD_ACCESO, ".
            "     g.NODO, ".
            "     g.CENTRAL, ".
            "     g.OLT_ID, ".
            "     g.SLOT_ID, ".
            "     g.PUERTO_ID, ".
            "     g.PTO_LOGICO, ".
            "     g.TARJETA_ID, ".
            "     g.PUNTOS_TRKSIP, ".
            "     g.PUNTOS_SD, ".
            "     g.PUNTOS_HD ".
            " FROM portalbd.gestor_buscador_gpon g) c1 ".
            " WHERE c1.OLT_ID='$OLT' ".
            " AND c1.TARJETA_ID='$TARJETA' ".
            " AND c1.PUERTO_ID='$PUERTO' ".
            " group by c1.OLT_ID,c1.TARJETA_ID, c1.PUERTO_ID ";


        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                //echo "name: ".utf8_encode($row['USUARIO_NOMBRE'])."\n ";
                //$row['USUARIO_NOMBRE']=utf8_encode($row['USUARIO_NOMBRE']);
                $result[] = $row;
            }
            $this->response($this->json($result), 200);
            //echo $this; // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }


//BuscadorCapacidad Red de Cobre
    private function buscarCapaCobre(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $armario = "";
        $armario = $this->_request['armario'];

        $this->dbFenixSTBYConnect();
        $connfstby=$this->connfstby;

        $query="SELECT ".
            "  C1.ARMARIO_ID   ".
            "	, C1.CAJA_ID   ".
            "	, C1.NODOS   ".
            "	, C1.ADIN as ADIN_PRI   ".
            "	, C1.NODO_SECUNDARIA AS ADIN_SEC   ".
            "	, C1.DISTANCIA_CAJA   ".
            "	, C1.DISTANCIA_ARMARIO  ".
            "	, C1.DISTANCIA_SECUNDARIA  ".
            "	, C1.DISTANCIA_TOTAL   ".
            "	, TO_CHAR(CASE   ".
            "	WHEN C1.NODO_SECUNDARIA<>'NO' AND C1.ADIN='SI' AND C1.DISTANCIA_CAJA > C1.DISTANCIA_SECUNDARIA  THEN C1.MEGASMAX_SECUNDARIA   ".
            "	 WHEN C1.NODO_SECUNDARIA<>'NO' AND C1.ADIN='SI' AND C1.DISTANCIA_CAJA < C1.DISTANCIA_SECUNDARIA  THEN C1.MEGASMAX_ADIN   ".
            "	 WHEN (C1.NODO_SECUNDARIA<>'NO' AND C1.ADIN='NO' ) THEN C1.MEGASMAX_SECUNDARIA   ".
            "	 WHEN C1.NODO_SECUNDARIA='NO' AND C1.ADIN='SI' THEN C1.MEGASMAX_ADIN   ".
            "	  ELSE C1.MEGASMAX_SINADIN   ".
            "	END) || ' Mb' AS VELOCIDAD_SPORTADA   ".
            "	FROM (SELECT NVL(TO_CHAR(MAX(SEC.NODO_CONMUTADOR_ID)),'NO') AS NODO_SECUNDARIA, MAX(SEC.DISTANCIA) AS DISTANCIA_SECUNDARIA,   ".
            "	        C.ARMARIO_ID   ".
            "	        , C.CAJA_ID   ".
            "	        , RTRIM(REGEXP_REPLACE((LISTAGG(N.NODO_CONMUTADOR_ID,'-') WITHIN GROUP (ORDER BY 1 ASC)) ,      ".
            "	            '([^-]*)(-\\1)+($|-)', '\\1\\3'),'-') AS  NODOS   ".
            "	        , CASE   ".
            "	           WHEN REGEXP_COUNT(('-'||RTRIM(REGEXP_REPLACE((LISTAGG(N.NODO_CONMUTADOR_ID,'-') WITHIN GROUP (ORDER BY 1 ASC)) , '([^-]*)(-\\1)+($|-)', '\\1\\3'),'-')),'[O-9]+{5}' )>=1 THEN 'SI'      ".
            "	            ELSE 'NO'   ".
            "	        END AS ADIN   ".
            "	        , MAX(C.DISTANCIA) AS  DISTANCIA_CAJA   ".
            "	        , MAX(A.DISTANCIA) AS DISTANCIA_ARMARIO   ".
            "	        , (MAX(A.DISTANCIA) + MAX(C.DISTANCIA)) AS DISTANCIA_TOTAL   ".
            "	        , CASE   ".
            "	          WHEN MAX(C.DISTANCIA)>=0 AND MAX(C.DISTANCIA)<=300 THEN 15   ".
            "	          WHEN MAX(C.DISTANCIA)>300 AND MAX(C.DISTANCIA)<=500 THEN 12   ".
            "	          WHEN MAX(C.DISTANCIA)>500 AND MAX(C.DISTANCIA)<=800 THEN 10   ".
            "	          WHEN MAX(C.DISTANCIA)>800 AND MAX(C.DISTANCIA)<=1200 THEN 8   ".
            "	          WHEN MAX(C.DISTANCIA)>1200 AND MAX(C.DISTANCIA)<=1500 THEN 6   ".
            "	          WHEN MAX(C.DISTANCIA)>1500 AND MAX(C.DISTANCIA)<=1800 THEN 5   ".
            "	          WHEN MAX(C.DISTANCIA)>1800 AND MAX(C.DISTANCIA)<=2200 THEN 4   ".
            "	          WHEN MAX(C.DISTANCIA)>2200 AND MAX(C.DISTANCIA)<=2500 THEN 3   ".
            "	          WHEN MAX(C.DISTANCIA)>2500 AND MAX(C.DISTANCIA)<=2800 THEN 2   ".
            "	          WHEN MAX(C.DISTANCIA)>2800 AND MAX(C.DISTANCIA)<=3200 THEN 1   ".
            "	          WHEN MAX(C.DISTANCIA)>3200  THEN 0   ".
            "	          ELSE 0   ".
            "	        END AS MEGASMAX_ADIN   ".
            "	        , CASE   ".
            "	          WHEN (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))>=0 AND (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))<=300 THEN 15   ".
            "	          WHEN (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))>300 AND (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))<=500 THEN 12   ".
            "	          WHEN (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))>500 AND (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))<=800 THEN 10   ".
            "	          WHEN (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))>800 AND (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))<=1200 THEN 8   ".
            "	          WHEN (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))>1200 AND (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))<=1500 THEN 6   ".
            "	          WHEN (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))>1500 AND (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))<=1800 THEN 5   ".
            "	          WHEN (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))>1800 AND (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))<=2200 THEN 4   ".
            "	          WHEN (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))>2200 AND (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))<=2500 THEN 3   ".
            "	          WHEN (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))>2500 AND (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))<=2800 THEN 2   ".
            "	          WHEN (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))>2800 AND (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))<=3200 THEN 1   ".
            "	          WHEN (MAX(A.DISTANCIA) + MAX(C.DISTANCIA))>3200 THEN 0   ".
            "	          ELSE 0   ".
            "	        END AS MEGASMAX_SINADIN   ".
            "	        , CASE   ".
            "	          WHEN MAX(SEC.DISTANCIA) >=0 AND MAX(SEC.DISTANCIA) <=300 THEN 15   ".
            "	          WHEN MAX(SEC.DISTANCIA) >300 AND MAX(SEC.DISTANCIA) <=500 THEN 12   ".
            "	          WHEN MAX(SEC.DISTANCIA) >500 AND MAX(SEC.DISTANCIA) <=800 THEN 10   ".
            "	          WHEN MAX(SEC.DISTANCIA) >800 AND MAX(SEC.DISTANCIA) <=1200 THEN 8   ".
            "	          WHEN MAX(SEC.DISTANCIA) >1200 AND MAX(SEC.DISTANCIA) <=1500 THEN 6   ".
            "	          WHEN MAX(SEC.DISTANCIA) >1500 AND MAX(SEC.DISTANCIA) <=1800 THEN 5   ".
            "	          WHEN MAX(SEC.DISTANCIA) >1800 AND MAX(SEC.DISTANCIA) <=2200 THEN 4   ".
            "	          WHEN MAX(SEC.DISTANCIA) >2200 AND MAX(SEC.DISTANCIA) <=2500 THEN 3   ".
            "	          WHEN MAX(SEC.DISTANCIA) >2500 AND MAX(SEC.DISTANCIA) <=2800 THEN 2   ".
            "	          WHEN MAX(SEC.DISTANCIA) >2800 AND MAX(SEC.DISTANCIA) <=3200 THEN 1   ".
            "	          WHEN MAX(SEC.DISTANCIA) >3200 THEN 0   ".
            "	          ELSE 0   ".
            "	        END AS MEGASMAX_SECUNDARIA   ".
            "	        FROM FNX_CAJAS_DISPERSION C   ".
            "	        LEFT JOIN FNX_ARMARIOS A   ".
            "	        ON C.ARMARIO_ID=A.ARMARIO_ID ".
            "	        LEFT JOIN FNX_ARMARIOS_NODOS N ".
            "	        ON C.ARMARIO_ID=N.ARMARIO_ID   ".
            "	        LEFT JOIN FNX_NODOS_CONMUTADORES NC   ".
            "	        ON N.NODO_CONMUTADOR_ID=NC.NODO_CONMUTADOR_ID   ".
            "	        LEFT JOIN (SELECT    ".
            "	                        B.NODO_CONMUTADOR_ID   ".
            "	                        , A.ARMARIO_ID   ".
            "	                        , A.CAJA_ID   ".
            "	                        , ABS(A.DISTANCIA-C.DISTANCIA_DSLAM) AS DISTANCIA   ".
            "	                      FROM FNX_CAJAS_DISPERSION A   ".
            "	                      , FNX_NODOS_ARMARIOS_CAJAS  B   ".
            "	                      , FNX_NODOS_CONMUTADORES C   ".
            "	                      WHERE A.ARMARIO_ID = B.ARMARIO_ID   ".
            "	                      AND A.CAJA_ID = B.CAJA_ID   ".
            "	                      AND B.NODO_CONMUTADOR_ID = C.NODO_CONMUTADOR_ID ) SEC   ".
            "	        ON C.ARMARIO_ID=SEC.ARMARIO_ID   ".
            "	        AND C.CAJA_ID=SEC.CAJA_ID   ".
            "	        WHERE 1=1   ".
            "	        AND C.ARMARIO_ID='$armario'    ".
            "	        AND NC.TIPO_NODO NOT IN ('DSLI')    ".
            "	        GROUP BY C.ARMARIO_ID, C.CAJA_ID ) C1   ";
        //echo $query;
        //var_dump($query);
        $stid = oci_parse($connfstby, $query);
        oci_execute($stid);
        $returnn="No rows!!!!";
        //$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $result = array();
        while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
            //echo $concepto_id." - ".$row['CONCEPTO_ID']."\n";
            $result[] = $row;
        }
        $this->response($this->json($result), 200);
        /*
                        if($r->num_rows > 0){
                                $result = array();
                                while($row = $r->fetch_assoc()){
                                        //$result[] = $row;
                                        //echo "name: ".utf8_encode($row['USUARIO_NOMBRE'])."\n ";
                                        //$row['USUARIO_NOMBRE']=utf8_encode($row['USUARIO_NOMBRE']);
                                        $result[] = $row;
                                }
                                $this->response($this->json($result), 200);
                                //echo $this; // send user details
                        }*/

        //$this->response('',200);        // If no records "No Content" status

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
    private function csvDatosAgendamiento(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $login = $this->_request['login'];



        //echo "mi login: $login";

        $fechaAgendamiento= $this->_request['fecha'];
        $depa= $this->_request['depa'];
        $zona= $this->_request['zona'];
        $today = date("Y-m-d");

        if ($depa == "" || $depa == "undefined"){
            $depa = "";
        }
        else {
            $depa = "AND C1.DEPARTAMENTO = '$depa' ";
        }

        if ($zona == "" || $zona == "undefined"){
            $zona = "";
        }
        else {
            $zona = "AND C1.ZONA = '$zona'";
        }



        $conna=getConnAgendamiento();
        $filename="ocupacionagenda.csv";
        //$query="SELECT count(*) as counter from transacciones_nca where FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
        //$rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        //$counter=0;
        //if($rr->num_rows > 0){
        //        $result = array();
        //        if($row = $rr->fetch_assoc()){
        //                $counter = $row['counter'];
        //        }
        // }


        $query="SELECT ".
            " C1.FECHA_DISP ".
            " ,C1.DEPARTAMENTO ".
            " , C1.ZONA ".
            " , C1.MICROZONA ".
            " , C1.SEGMENTO ".
            " , C1.MIGRACION ".
            " , C1.VIP ".
            " , C1.BRONZE ".
            " , C1.GPON ".
            " , C1.PARAM_AM ".
            " , C1.DISP_AM ".
            " , C1.PARAM_PM ".
            " , C1.DISP_PM ".
            " , C1.PARAM_HF ".
            " ,(C1.N7+C1.N8+C1.N9+C1.N10+C1.N11+C1.N12+C1.N13+C1.N14+C1.N15+C1.N16+C1.N17+C1.N18+C1.N19)AS DISP_HF ".
            " ,(C1.DISP_AM + C1.DISP_PM + (C1.N7+C1.N8+C1.N9+C1.N10+C1.N11+C1.N12+C1.N13+C1.N14+C1.N15+C1.N16+C1.N17+C1.N18+C1.N19))AS TOTAL_DISP ".
            " FROM (select ".
            "        CASE WHEN dp.dep_departamento = 'Antioquia' THEN 'ANTIOQUIA'   ".
            "             WHEN dp.dep_departamento = 'Atlantico' THEN 'ATLANTICO' ".
            "             WHEN dp.dep_departamento = 'Cundinamarca' THEN 'CUNDINAMARCA' ".
            "             WHEN dp.dep_departamento = 'Bolivar' THEN 'BOLIVAR'  ".
            "             WHEN dp.dep_departamento = 'Valle del Cauca' THEN 'VALLE DEL CAUCA' END AS DEPARTAMENTO, ".
            "                  CASE  ".
            "                     WHEN (dp.dep_departamento = 'Antioquia'  ".
            "                     AND subz.sbz_subzona IN ('AMERICA','BUENOS_AIR','CEN','CENTRO','COLON','MIRAFLORES','NUTIBARA','OTRABANDA','SAN_BERN_1','SAN_BERN_2','SAN_JAVIER','VILLAHERMO')) ".
            "                 THEN 'CENTRO' ".
            "             WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('BARCOPGIR','BELLO_1','BELLO_2','BELLO_3','BERLIN','BOSQUE_1','BOSQUE_2','CARIBE','CASTILLA','FLORENCIA','GIR','IGUANA','IGUSANCRI','NIQUIA','NOR')  ".
            "                 THEN 'NORTE' ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('CALDAS','ENVIGADO_1','ENVIGADO_2','ENVIGADO_3','ESTRELLA','GUAYABAL','ITAGUI_1','ITAGUI_2','ITAGUI_3','POBLADO_1','POBLADO_2','SABANETA','SANANTPRA','SUR-ENV','SUR','SUR-SAB')  ".
            "                 THEN 'SUR'    ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('M1_ORIENTE', 'M2_ORIENTE', 'M3_ORIENTE', 'M4_ORIENTE' , 'M5_ORIENTE' ,'M6_ORIENTE','M7_ORIENTE','M8_ORIENTE','RIO', 'PALMAS', 'SANTAELENA')  ".
            "                 THEN 'ORIENTE'     ".
            "			WHEN subz.sbz_subzona IN ('CAN','DEFAULT','ENG','QCA','SUB','NORTE') THEN 'BOGOTA NORTE'  ".
            "			WHEN subz.sbz_subzona IN ('BOSA','ECA','FRG','TIMIZA','SUR') THEN 'BOGOTA SUR'   ".
            "			WHEN subz.sbz_subzona IN ('CAR','M1_CARTAGE','M2_CARTAGE','M3_CARTAGE','M4_CARTAGE','M5_CARTAGE') ".
            "			THEN 'CARTAGENA' ".
            "			WHEN subz.sbz_subzona IN ('TUR', 'M6_CARTAGE')  ".
            "			THEN 'TURBACO'  ".
            "			WHEN subz.sbz_subzona IN ('VAL','Valle del Cauca') THEN 'CALI'   ".
            "			WHEN subz.sbz_subzona = 'PAL' THEN 'PALMIRA'  ".
            "			WHEN subz.sbz_subzona = 'JAM' THEN 'JAMUNDI'  ".
            "           WHEN dp.dep_departamento IN ('Bolivar','Atlantico','Cundinamarca','Valle del Cauca')    ".
            "                 THEN UPPER(dp.dep_departamento) ".
            "             END AS ZONA, ".
            "           sbz_subzona AS MICROZONA,sbag.sag_segmento, ".
            "                 sbag.sag_segmento AS SEGMENTO,agm.agm_fechacita AS FECHA_DISP, ".
            "                 sbag.sag_cuposam AS PARAM_AM, ".
            "                 IFNULL((sbag.sag_cuposam-IFNULL(COUNT(IF(agm.agm_jornadacita='AM',1,NULL)),0)),0) AS DISP_AM,sbag.sag_cupospm AS PARAM_PM, ".
            "                 IFNULL((sbag.sag_cupospm-IFNULL(COUNT(IF(agm.agm_jornadacita='PM',1,NULL)),0)),0) AS DISP_PM,(IFNULL(sbag.sag_cupos7,0)+IFNULL(sbag.sag_cupos8,0)+IFNULL(sbag.sag_cupos9,0)+IFNULL(sbag.sag_cupos10,0)+  ".
            "                 IFNULL(sbag.sag_cupos11,0)+IFNULL(sbag.sag_cupos12,0)+IFNULL(sbag.sag_cupos13,0)+IFNULL(sbag.sag_cupos14,0)+ ".
            "                 IFNULL(sbag.sag_cupos15,0)+IFNULL(sbag.sag_cupos16,0)+IFNULL(sbag.sag_cupos17,0)+IFNULL(sbag.sag_cupos18,0)+ ".
            "                 IFNULL(sbag.sag_cupos19,0))AS PARAM_HF,IFNULL(IF(sbag.sag_cupos7 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)))),IFNULL((sbag.sag_cupos7-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)),0)),0)),0) AS 'N7', ".
            "         IFNULL(IF(sbag.sag_cupos8 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)))),IFNULL((sbag.sag_cupos8-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)),0)),0)),0) AS 'N8', ".
            "                 IFNULL(IF(sbag.sag_cupos9 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)))),IFNULL((sbag.sag_cupos9-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)),0)),0)),0) AS 'N9', ".
            "                 IFNULL(IF(sbag.sag_cupos10 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)))),IFNULL((sbag.sag_cupos10-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)),0)),0)),0) AS 'N10', ".
            "         IFNULL(IF(sbag.sag_cupos11 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)))),IFNULL((sbag.sag_cupos11-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)),0)),0)),0) AS 'N11', ".
            "                 IFNULL(IF(sbag.sag_cupos12 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)))),IFNULL((sbag.sag_cupos12-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)),0)),0)),0) AS 'N12', ".
            "                 IFNULL(IF(sbag.sag_cupos13 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)))),IFNULL((sbag.sag_cupos13-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)),0)),0)),0) AS 'N13', ".
            "                 IFNULL(IF(sbag.sag_cupos14 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)))),IFNULL((sbag.sag_cupos14-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)),0)),0)),0) AS 'N14', ".
            "                 IFNULL(IF(sbag.sag_cupos15 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)))),IFNULL((sbag.sag_cupos15-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)),0)),0)),0) AS 'N15', ".
            "                 IFNULL(IF(sbag.sag_cupos16 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)))),IFNULL((sbag.sag_cupos16-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)),0)),0)),0) AS 'N16', ".
            "                 IFNULL(IF(sbag.sag_cupos17 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)))),IFNULL((sbag.sag_cupos17-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)),0)),0)),0) AS 'N17', ".
            "                 IFNULL(IF(sbag.sag_cupos18 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)))),IFNULL((sbag.sag_cupos18-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)),0)),0)),0) AS 'N18', ".
            "                 IFNULL(IF(sbag.sag_cupos19 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)))),IFNULL((sbag.sag_cupos19-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)),0)),0)),0) AS 'N19', ".
            "SUM(CASE WHEN sbag.sag_prioridad='migracion' THEN 1 else 0 end ) as MIGRACION ".
            ",(CASE WHEN sbag.sag_prioridad='vip' THEN 1 else 0 end ) as VIP ".
            ",SUM(CASE WHEN sbag.sag_prioridad='bronze' THEN 1 else 0 end ) as BRONZE ".
            ",SUM(CASE WHEN sbag.sag_prioridad='gpon' THEN 1 else 0 end ) as GPON ".
            " from agn_agendamientos agm  ".
            " left join agn_subagendas sbag on agm.agm_agenda = sbag.sag_id  ".
            " left join agn_agendas ag on sbag.sag_agenda = ag.ads_id ".
            " left join agn_subzonas  subz on sbag.sag_subzona = subz.sbz_id ".
            " left join agn_departamentos  dp on ag.ags_departamento = dp.dep_id ".
            " where agm.agm_fechacita >= ag.ags_fechainicial  ".
            " AND agm.agm_fechacita <= ag.ags_fechafinal  ".
            " AND ag.ags_fechafinal >= CURDATE()  ".
            " AND (agm.agm_estadototal IN ('Agendar','Agendamientos') OR (agm.agm_estadototal IN('Pendiente','Prospecto','')  ".
            " AND (agm.agm_fechacita > IF(HOUR(CURTIME()) < 16,CURDATE(), DATE_ADD(NOW(), INTERVAL 1 DAY)))))  ".
            " and agm.agm_fechacita >= date_add(curdate(), interval 1 day) ".
            " group by agm.agm_agenda,agm.agm_fechacita ".
            " order by ag.ags_fechafinal ASC ) C1 ".
            " where C1.FECHA_DISP ='$fechaAgendamiento' ".
            " $depa ".
            " $zona ".
            " order by C1.DEPARTAMENTO asc, C1.ZONA asc, C1.MICROZONA asc ";



        //	echo $query;
        $filename="EXPORTE-OCUPACION-$login-$today.csv";
        $r = $conna->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('FECHA','DEPARTAMENTO_ID','ZONA','MICROZONA','SEGMENTO','MIGRACION','VIP','BRONZE','GPON','PARAM_AM','DISP_AM','PARAM_PM','DISP_PM','PARAM_HF','DISP_HF','TOTAL_DISP'));
            while($row = $r->fetch_assoc()){
                fputcsv($fp, $row);

            }
            fclose($fp);

            $this->response($this->json(array($filename,$login)), 200); // send user details
        }
        $this->response('',200);        // If no records "No Content" status

    }

    private function csvPedidosMicrozonas(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $login = $this->_request['login'];



        //echo "mi login: $login";

        $fechaAgendamiento= $this->_request['fecha'];
        $today = date("Y-m-d");

        $conna=getConnAgendamiento();
        $filename="ocupacionagenda.csv";
        //$query="SELECT count(*) as counter from transacciones_nca where FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
        //$rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        //$counter=0;
        //if($rr->num_rows > 0){
        //        $result = array();
        //        if($row = $rr->fetch_assoc()){
        //                $counter = $row['counter'];
        //        }
        // }


        $query="SELECT ".
            " C1.FECHA_DISP ".
            " ,C1.DEPARTAMENTO ".
            " , C1.ZONA ".
            " , C1.MICROZONA ".
            " , C1.SEGMENTO ".
            " , C1.DISP_AM ".
            " , C1.DISP_PM ".
            " ,(C1.N7+C1.N8+C1.N9+C1.N10+C1.N11+C1.N12+C1.N13+C1.N14+C1.N15+C1.N16+C1.N17+C1.N18+C1.N19)AS DISP_HF ".
            " ,(C1.DISP_AM + C1.DISP_PM + (C1.N7+C1.N8+C1.N9+C1.N10+C1.N11+C1.N12+C1.N13+C1.N14+C1.N15+C1.N16+C1.N17+C1.N18+C1.N19))AS TOTAL_DISP ".
            " FROM (select ".
            "        CASE WHEN dp.dep_departamento = 'Antioquia' THEN 'ANTIOQUIA'   ".
            "             WHEN dp.dep_departamento = 'Atlantico' THEN 'ATLANTICO' ".
            "             WHEN dp.dep_departamento = 'Cundinamarca' THEN 'CUNDINAMARCA' ".
            "             WHEN dp.dep_departamento = 'Bolivar' THEN 'BOLIVAR'  ".
            "             WHEN dp.dep_departamento = 'Valle del Cauca' THEN 'VALLE DEL CAUCA' END AS DEPARTAMENTO, ".
            "                   CASE  ".
            "                     WHEN (dp.dep_departamento = 'Antioquia'  ".
            "                     AND subz.sbz_subzona IN ('AMERICA','BUENOS_AIR','CEN','CENTRO','COLON','MIRAFLORES','NUTIBARA','OTRABANDA','SAN_BERN_1','SAN_BERN_2','SAN_JAVIER','VILLAHERMO') ".
            "                 AND sbag.sag_prioridad <> 'migracion') THEN 'CENTRO' ".
            "             WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('BARCOPGIR','BELLO_1','BELLO_2','BELLO_3','BERLIN','BOSQUE_1','BOSQUE_2','CARIBE','CASTILLA','FLORENCIA','GIR','IGUANA','IGUSANCRI','NIQUIA','NOR')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'NORTE' ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('CALDAS','ENVIGADO_1','ENVIGADO_2','ENVIGADO_3','ESTRELLA','GUAYABAL','ITAGUI_1','ITAGUI_2','ITAGUI_3','POBLADO_1','POBLADO_2','SABANETA','SANANTPRA','SUR-ENV','SUR','SUR-SAB')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'SUR'    ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('M1_ORIENTE','M2_ORIENTE','M3_ORIENTE','RIO')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'ORIENTE'     ".
            "            WHEN dp.dep_departamento IN ('Bolivar','Atlantico','Cundinamarca','Valle del Cauca')    ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN UPPER(dp.dep_departamento) ".
            "             END AS ZONA, ".
            "           sbz_subzona AS MICROZONA,sbag.sag_segmento, ".
            "                 sbag.sag_segmento AS SEGMENTO,agm.agm_fechacita AS FECHA_DISP, ".
            "                 sbag.sag_cuposam AS PARAM_AM, ".
            "                 IFNULL((sbag.sag_cuposam-IFNULL(COUNT(IF(agm.agm_jornadacita='AM',1,NULL)),0)),0) AS DISP_AM,sbag.sag_cupospm AS PARAM_PM, ".
            "                 IFNULL((sbag.sag_cupospm-IFNULL(COUNT(IF(agm.agm_jornadacita='PM',1,NULL)),0)),0) AS DISP_PM,(IFNULL(sbag.sag_cupos7,0)+IFNULL(sbag.sag_cupos8,0)+IFNULL(sbag.sag_cupos9,0)+IFNULL(sbag.sag_cupos10,0)+  ".
            "                 IFNULL(sbag.sag_cupos11,0)+IFNULL(sbag.sag_cupos12,0)+IFNULL(sbag.sag_cupos13,0)+IFNULL(sbag.sag_cupos14,0)+ ".
            "                 IFNULL(sbag.sag_cupos15,0)+IFNULL(sbag.sag_cupos16,0)+IFNULL(sbag.sag_cupos17,0)+IFNULL(sbag.sag_cupos18,0)+ ".
            "                 IFNULL(sbag.sag_cupos19,0))AS PARAM_HF,IFNULL(IF(sbag.sag_cupos7 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)))),IFNULL((sbag.sag_cupos7-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)),0)),0)),0) AS 'N7', ".
            "         IFNULL(IF(sbag.sag_cupos8 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)))),IFNULL((sbag.sag_cupos8-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)),0)),0)),0) AS 'N8', ".
            "                 IFNULL(IF(sbag.sag_cupos9 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)))),IFNULL((sbag.sag_cupos9-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)),0)),0)),0) AS 'N9', ".
            "                 IFNULL(IF(sbag.sag_cupos10 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)))),IFNULL((sbag.sag_cupos10-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)),0)),0)),0) AS 'N10', ".
            "         IFNULL(IF(sbag.sag_cupos11 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)))),IFNULL((sbag.sag_cupos11-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)),0)),0)),0) AS 'N11', ".
            "                 IFNULL(IF(sbag.sag_cupos12 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)))),IFNULL((sbag.sag_cupos12-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)),0)),0)),0) AS 'N12', ".
            "                 IFNULL(IF(sbag.sag_cupos13 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)))),IFNULL((sbag.sag_cupos13-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)),0)),0)),0) AS 'N13', ".
            "                 IFNULL(IF(sbag.sag_cupos14 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)))),IFNULL((sbag.sag_cupos14-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)),0)),0)),0) AS 'N14', ".
            "                 IFNULL(IF(sbag.sag_cupos15 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)))),IFNULL((sbag.sag_cupos15-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)),0)),0)),0) AS 'N15', ".
            "                 IFNULL(IF(sbag.sag_cupos16 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)))),IFNULL((sbag.sag_cupos16-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)),0)),0)),0) AS 'N16', ".
            "                 IFNULL(IF(sbag.sag_cupos17 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)))),IFNULL((sbag.sag_cupos17-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)),0)),0)),0) AS 'N17', ".
            "                 IFNULL(IF(sbag.sag_cupos18 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)))),IFNULL((sbag.sag_cupos18-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)),0)),0)),0) AS 'N18', ".
            "                 IFNULL(IF(sbag.sag_cupos19 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)))),IFNULL((sbag.sag_cupos19-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)),0)),0)),0) AS 'N19' ".
            " from agn_agendamientos agm  ".
            " left join agn_subagendas sbag on agm.agm_agenda = sbag.sag_id  ".
            " left join agn_agendas ag on sbag.sag_agenda = ag.ads_id ".
            " left join agn_subzonas  subz on sbag.sag_subzona = subz.sbz_id ".
            " left join agn_departamentos  dp on ag.ags_departamento = dp.dep_id ".
            " where agm.agm_fechacita >= ag.ags_fechainicial  ".
            " AND agm.agm_fechacita <= ag.ags_fechafinal  ".
            " AND ag.ags_fechafinal >= CURDATE()  ".
            " AND sbag.sag_prioridad <> 'migracion' ".
            " AND (agm.agm_estadototal IN ('Agendar','Agendamientos') OR (agm.agm_estadototal IN('Pendiente','Prospecto','')  ".
            " AND (agm.agm_fechacita > IF(HOUR(CURTIME()) < 16,CURDATE(), DATE_ADD(NOW(), INTERVAL 1 DAY)))))  ".
            " and agm.agm_fechacita >= date_add(curdate(), interval 1 day) ".
            " group by agm.agm_agenda,agm.agm_fechacita ".
            " order by ag.ags_fechafinal ASC ) C1 ".
            " where C1.FECHA_DISP ='$fechaAgendamiento' ";


        //	echo $query;
        $filename="EXPORTE-OCUPACION-$login-$today.csv";
        $r = $conna->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('FECHA','DEPARTAMENTO_ID','ZONA','MICROZONA','SEGMENTO','DISP_AM','DISP_PM','DISP_HF','TOTAL_DISP'));
            while($row = $r->fetch_assoc()){
                fputcsv($fp, $row);

            }
            fclose($fp);

            $this->response($this->json(array($filename,$login)), 200); // send user details
        }
        $this->response('',200);        // If no records "No Content" status

    }

    private function csvCodigoResultado(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $login = $this->_request['login'];



        //echo "mi login: $login";

        $fechaAgendamiento= $this->_request['fecha'];
        $today = date("Y-m-d");

        $conna=getConnAgendamiento();
        $filename="ocupacionagenda.csv";
        //$query="SELECT count(*) as counter from transacciones_nca where FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
        //$rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        //$counter=0;
        //if($rr->num_rows > 0){
        //        $result = array();
        //        if($row = $rr->fetch_assoc()){
        //                $counter = $row['counter'];
        //        }
        // }


        $query="SELECT ".
            " C1.FECHA_DISP ".
            " ,C1.DEPARTAMENTO ".
            " , C1.ZONA ".
            " , C1.MICROZONA ".
            " , C1.SEGMENTO ".
            " , C1.DISP_AM ".
            " , C1.DISP_PM ".
            " ,(C1.N7+C1.N8+C1.N9+C1.N10+C1.N11+C1.N12+C1.N13+C1.N14+C1.N15+C1.N16+C1.N17+C1.N18+C1.N19)AS DISP_HF ".
            " ,(C1.DISP_AM + C1.DISP_PM + (C1.N7+C1.N8+C1.N9+C1.N10+C1.N11+C1.N12+C1.N13+C1.N14+C1.N15+C1.N16+C1.N17+C1.N18+C1.N19))AS TOTAL_DISP ".
            " FROM (select ".
            "        CASE WHEN dp.dep_departamento = 'Antioquia' THEN 'ANTIOQUIA'   ".
            "             WHEN dp.dep_departamento = 'Atlantico' THEN 'ATLANTICO' ".
            "             WHEN dp.dep_departamento = 'Cundinamarca' THEN 'CUNDINAMARCA' ".
            "             WHEN dp.dep_departamento = 'Bolivar' THEN 'BOLIVAR'  ".
            "             WHEN dp.dep_departamento = 'Valle del Cauca' THEN 'VALLE DEL CAUCA' END AS DEPARTAMENTO, ".
            "                   CASE  ".
            "                     WHEN (dp.dep_departamento = 'Antioquia'  ".
            "                     AND subz.sbz_subzona IN ('AMERICA','BUENOS_AIR','CEN','CENTRO','COLON','MIRAFLORES','NUTIBARA','OTRABANDA','SAN_BERN_1','SAN_BERN_2','SAN_JAVIER','VILLAHERMO') ".
            "                 AND sbag.sag_prioridad <> 'migracion') THEN 'CENTRO' ".
            "             WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('BARCOPGIR','BELLO_1','BELLO_2','BELLO_3','BERLIN','BOSQUE_1','BOSQUE_2','CARIBE','CASTILLA','FLORENCIA','GIR','IGUANA','IGUSANCRI','NIQUIA','NOR')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'NORTE' ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('CALDAS','ENVIGADO_1','ENVIGADO_2','ENVIGADO_3','ESTRELLA','GUAYABAL','ITAGUI_1','ITAGUI_2','ITAGUI_3','POBLADO_1','POBLADO_2','SABANETA','SANANTPRA','SUR-ENV','SUR','SUR-SAB')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'SUR'    ".
            "            WHEN dp.dep_departamento = 'Antioquia'    ".
            "                 AND subz.sbz_subzona IN ('M1_ORIENTE','M2_ORIENTE','M3_ORIENTE','RIO')  ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN 'ORIENTE'     ".
            "            WHEN dp.dep_departamento IN ('Bolivar','Atlantico','Cundinamarca','Valle del Cauca')    ".
            "                 AND sbag.sag_prioridad <> 'migracion'THEN UPPER(dp.dep_departamento) ".
            "             END AS ZONA, ".
            "           sbz_subzona AS MICROZONA,sbag.sag_segmento, ".
            "                 sbag.sag_segmento AS SEGMENTO,agm.agm_fechacita AS FECHA_DISP, ".
            "                 sbag.sag_cuposam AS PARAM_AM, ".
            "                 IFNULL((sbag.sag_cuposam-IFNULL(COUNT(IF(agm.agm_jornadacita='AM',1,NULL)),0)),0) AS DISP_AM,sbag.sag_cupospm AS PARAM_PM, ".
            "                 IFNULL((sbag.sag_cupospm-IFNULL(COUNT(IF(agm.agm_jornadacita='PM',1,NULL)),0)),0) AS DISP_PM,(IFNULL(sbag.sag_cupos7,0)+IFNULL(sbag.sag_cupos8,0)+IFNULL(sbag.sag_cupos9,0)+IFNULL(sbag.sag_cupos10,0)+  ".
            "                 IFNULL(sbag.sag_cupos11,0)+IFNULL(sbag.sag_cupos12,0)+IFNULL(sbag.sag_cupos13,0)+IFNULL(sbag.sag_cupos14,0)+ ".
            "                 IFNULL(sbag.sag_cupos15,0)+IFNULL(sbag.sag_cupos16,0)+IFNULL(sbag.sag_cupos17,0)+IFNULL(sbag.sag_cupos18,0)+ ".
            "                 IFNULL(sbag.sag_cupos19,0))AS PARAM_HF,IFNULL(IF(sbag.sag_cupos7 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)))),IFNULL((sbag.sag_cupos7-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='7:00' ,1,NULL)),0)),0)),0) AS 'N7', ".
            "         IFNULL(IF(sbag.sag_cupos8 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)))),IFNULL((sbag.sag_cupos8-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='8:00' ,1,NULL)),0)),0)),0) AS 'N8', ".
            "                 IFNULL(IF(sbag.sag_cupos9 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)))),IFNULL((sbag.sag_cupos9-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='9:00' ,1,NULL)),0)),0)),0) AS 'N9', ".
            "                 IFNULL(IF(sbag.sag_cupos10 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)))),IFNULL((sbag.sag_cupos10-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='10:00' ,1,NULL)),0)),0)),0) AS 'N10', ".
            "         IFNULL(IF(sbag.sag_cupos11 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)))),IFNULL((sbag.sag_cupos11-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='11:00' ,1,NULL)),0)),0)),0) AS 'N11', ".
            "                 IFNULL(IF(sbag.sag_cupos12 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)))),IFNULL((sbag.sag_cupos12-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='12:00' ,1,NULL)),0)),0)),0) AS 'N12', ".
            "                 IFNULL(IF(sbag.sag_cupos13 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)))),IFNULL((sbag.sag_cupos13-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='13:00' ,1,NULL)),0)),0)),0) AS 'N13', ".
            "                 IFNULL(IF(sbag.sag_cupos14 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)))),IFNULL((sbag.sag_cupos14-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='14:00' ,1,NULL)),0)),0)),0) AS 'N14', ".
            "                 IFNULL(IF(sbag.sag_cupos15 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)))),IFNULL((sbag.sag_cupos15-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='15:00' ,1,NULL)),0)),0)),0) AS 'N15', ".
            "                 IFNULL(IF(sbag.sag_cupos16 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)))),IFNULL((sbag.sag_cupos16-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='16:00' ,1,NULL)),0)),0)),0) AS 'N16', ".
            "                 IFNULL(IF(sbag.sag_cupos17 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)))),IFNULL((sbag.sag_cupos17-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='17:00' ,1,NULL)),0)),0)),0) AS 'N17', ".
            "                 IFNULL(IF(sbag.sag_cupos18 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)))),IFNULL((sbag.sag_cupos18-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='18:00' ,1,NULL)),0)),0)),0) AS 'N18', ".
            "                 IFNULL(IF(sbag.sag_cupos19 IS NULL,(-1*(count(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)))),IFNULL((sbag.sag_cupos19-IFNULL(COUNT(IF(agm.agm_jornadacita='Hora Fija' AND agm.agm_horacita='19:00' ,1,NULL)),0)),0)),0) AS 'N19' ".
            " from agn_agendamientos agm  ".
            " left join agn_subagendas sbag on agm.agm_agenda = sbag.sag_id  ".
            " left join agn_agendas ag on sbag.sag_agenda = ag.ads_id ".
            " left join agn_subzonas  subz on sbag.sag_subzona = subz.sbz_id ".
            " left join agn_departamentos  dp on ag.ags_departamento = dp.dep_id ".
            " where agm.agm_fechacita >= ag.ags_fechainicial  ".
            " AND agm.agm_fechacita <= ag.ags_fechafinal  ".
            " AND ag.ags_fechafinal >= CURDATE()  ".
            " AND sbag.sag_prioridad <> 'migracion' ".
            " AND (agm.agm_estadototal IN ('Agendar','Agendamientos') OR (agm.agm_estadototal IN('Pendiente','Prospecto','')  ".
            " AND (agm.agm_fechacita > IF(HOUR(CURTIME()) < 16,CURDATE(), DATE_ADD(NOW(), INTERVAL 1 DAY)))))  ".
            " and agm.agm_fechacita >= date_add(curdate(), interval 1 day) ".
            " group by agm.agm_agenda,agm.agm_fechacita ".
            " order by ag.ags_fechafinal ASC ) C1 ".
            " where C1.FECHA_DISP ='$fechaAgendamiento' ";


        //	echo $query;
        $filename="EXPORTE-OCUPACION-$login-$today.csv";
        $r = $conna->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('FECHA','DEPARTAMENTO_ID','ZONA','MICROZONA','SEGMENTO','DISP_AM','DISP_PM','DISP_HF','TOTAL_DISP'));
            while($row = $r->fetch_assoc()){
                fputcsv($fp, $row);

            }
            fclose($fp);

            $this->response($this->json(array($filename,$login)), 200); // send user details
        }
        $this->response('',200);        // If no records "No Content" status

    }


    private function csvScheduling(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $login = $this->_request['login'];
        $this->dbConnectScheduling();

        $today = date("Y-m-d h:i:s");
        $filename="SCHEDULING-$login-$today.csv";
        $query=" select pedido_id, subpedido_id, solicitud_id, ".
            " concepto_id, DATE_FORMAT(FECHA_ESTADO,'%d-%m-%Y %T') as FECHA_ESTADO, FECHA_CITA, DATE_FORMAT(FECHA_INGRESO,'%d-%m-%Y %T') as FECHA_INGRESO, TIPO_SOLICITUD,".
            " DEPARTAMENTO, MUNICIPIO, DESCRIPCION_CONCEPTO, DESCRIPCION_ESTADO ".
            " from agendamientoxfenix order by FECHA_CITA asc";

        $r = $this->mysqliScheduling->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO_ID','SUBPEDIDO_ID','SOLICITUD_ID','CONCEPTO_ID','FECHA_ESTADO','FECHA_CITA','FECHA_INGRESO','TIPO_SOLICITUD','DEPARTAMENTO','MUNICIPIO','DESCRIPCION_CONCEPTO','DESCRIPCION_ESTADO'));
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);

            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }

    private function csvSchedulingPre(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $login = $this->_request['login'];
        $this->dbConnectScheduling();

        $today = date("Y-m-d h:i:s");
        $filename="SCHEDULING_PRE-$login-$today.csv";
        $query=" select pedido_id, subpedido_id, solicitud_id, ".
            " concepto_id, DATE_FORMAT(FECHA_ESTADO,'%d-%m-%Y %T') ".
            " as FECHA_ESTADO, FECHA_CITA, DATE_FORMAT(FECHA_INGRESO,'%d-%m-%Y %T') as FECHA_INGRESO, ".
            " TIPO_SOLICITUD, DEPARTAMENTO, MUNICIPIO, DESCRIPCION_CONCEPTO, DESCRIPCION_ESTADO ".
            " from agendamientoxfenix ".
            " where concepto_id is null ".
            " order by FECHA_CITA asc ";

        $r = $this->mysqliScheduling->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO_ID','SUBPEDIDO_ID','SOLICITUD_ID','CONCEPTO_ID','FECHA_ESTADO','FECHA_CITA','FECHA_INGRESO','TIPO_SOLICITUD','DEPARTAMENTO','MUNICIPIO','DESCRIPCION_CONCEPTO','DESCRIPCION_ESTADO'));
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);

            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }

    private function csvSchedulingPedidos(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $login = $this->_request['login'];
        $this->dbConnectScheduling();

        $today = date("Y-m-d h:i:s");
        $filename="SCHEDULING_PEDIDOS-$login-$today.csv";
        $query=" select pedido_id, subpedido_id, solicitud_id, ".
            " concepto_id, DATE_FORMAT(FECHA_ESTADO,'%d-%m-%Y %T') ".
            " as FECHA_ESTADO, FECHA_CITA, DATE_FORMAT(FECHA_INGRESO,'%d-%m-%Y %T') as FECHA_INGRESO, ".
            " TIPO_SOLICITUD, DEPARTAMENTO, MUNICIPIO, DESCRIPCION_CONCEPTO, DESCRIPCION_ESTADO,TIPO_ELEMENTO_ID, PRODUCTO_ID ".
            " from agendamientoxfenix ".
            " where concepto_id is not null ".
            " order by FECHA_CITA asc ";

        $r = $this->mysqliScheduling->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO_ID','SUBPEDIDO_ID','SOLICITUD_ID','CONCEPTO_ID','FECHA_ESTADO','FECHA_CITA','FECHA_INGRESO','TIPO_SOLICITUD','DEPARTAMENTO','MUNICIPIO','DESCRIPCION_CONCEPTO','DESCRIPCION_ESTADO','TIPO_ELEMENTO_ID','PRODUCTO_ID'));
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);

            $this->response($this->json(array($filename,$login)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }

    private function listadoScheduling(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $page = $this->_request['page'];
        //counter
        if($page=="undefined"){
            $page="0";
        }else{
            $page=$page-1;
        }
        $page=$page*100;

        $this->dbConnectScheduling();


        $query=" SELECT count(*) as counter from agendamientoxfenix";
        $rr = $this->mysqliScheduling->query($query) or die($this->mysqliScheduling->error.__LINE__);
        $counter=0;

        //$this->response($this->json('malo'), 200);
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter = $row['counter'];
            }
        }

        $query=" SELECT count(*) as counter from agendamientoxfenix where concepto_id is null ";
        $pre = $this->mysqliScheduling->query($query) or die($this->mysqliScheduling->error.__LINE__);
        $preformularios=0;

        //$this->response($this->json('malo'), 200);
        if($pre->num_rows > 0){
            $result = array();
            if($row = $pre->fetch_assoc()){
                $preformularios = $row['counter'];
            }
        }

        $query=" SELECT count(*) as counter from agendamientoxfenix where concepto_id is not null ";
        $ped = $this->mysqliScheduling->query($query) or die($this->mysqliScheduling->error.__LINE__);
        $pedidos=0;

        //$this->response($this->json('malo'), 200);
        if($ped->num_rows > 0){
            $result = array();
            if($row = $ped->fetch_assoc()){
                $pedidos = $row['counter'];
            }
        }

        $query=" SELECT ULTIMA_ACTUALIZACION as FECHA FROM scheduling.agendamientoxfenix order by ULTIMA_ACTUALIZACION desc limit 1; ";
        $fechA = $this->mysqliScheduling->query($query);
        //$fechaUpdate='';

        //$this->response($this->json('malo'), 200);
        if($fechA->num_rows > 0){
            $result = array();
            if($row = $fechA->fetch_assoc()){
                $fechaUpdate = $row['FECHA'];
            }
        }


        $query=" select pedido_id, subpedido_id, solicitud_id, ".
            " concepto_id, DATE_FORMAT(FECHA_ESTADO,'%d-%m-%Y %T') as FECHA_ESTADO, FECHA_CITA, DATE_FORMAT(FECHA_INGRESO,'%d-%m-%Y %T') as FECHA_INGRESO, TIPO_SOLICITUD,".
            " DEPARTAMENTO, MUNICIPIO, DESCRIPCION_CONCEPTO, DESCRIPCION_ESTADO ".
            " from agendamientoxfenix order by FECHA_CITA asc limit 100 offset $page";
        //echo $query;
        $r = $this->mysqliScheduling->query($query) or die($this->mysqliScheduling->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                //echo "name: ".utf8_encode($row['USUARIO_NOMBRE'])."\n ";
                $result[] = $row;
            }
            $this->response($this->json(array($result,$counter,$preformularios,$pedidos,$fechaUpdate)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

    //Incia funciones para  Transacciones Ordens O-XXX

    //Funcion para traer los conceptos de FNX guardados localmente en MySQL
    private function getConceptos(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        //counter
        $today = date("Y-m-d");

        $query="SELECT count(*) as counter from tabla_estados_conceptos_fnx ";
        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter = $row['counter'];
            }
        }


        $query="SELECT ".
            " e.ESTADO_ID, ".
            " e.CONCEPTO_ID, ".
            " e.TIPO_CONCEPTO, ".
            " e.DESCRIPCION_ESTADO, ".
            " e.NOMBRE_CONCEPTO, ".
            " e.RESPONSABLE, ".
            " e.GRUPO_ANULADO ".
            " FROM portalbd.tabla_estados_conceptos_fnx e";
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

    private function getTransaccionORD(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $ord = $this->_request['ordID'];


        $query="select * from go_transacciones_oxxx where ID=$ord";
        echo $query;

        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();
            $ids="";
            $sep="";
            $transaccion='';
            if($row = $r->fetch_assoc()){
                $transaccion = $row;
            }
            //$transaccion["PASSWORD"]="";
            $this->response($this->json(array($transaccion,"OK")), 200); // send user details
        }

        $this->response('',204);

    }


    private function listadoTransaccionesORD(){
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
        $query="SELECT count(*) as counter from go_transacciones_oxxx where FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
        $rr = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $counter=0;
        if($rr->num_rows > 0){
            $result = array();
            if($row = $rr->fetch_assoc()){
                $counter = $row['counter'];
            }
        }


        $query="SELECT * FROM go_transacciones_oxxx where FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59' order by FECHA_FIN desc limit 100 offset $page";
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
//Funcion para insertar Transacciones de OXXX
    private function insertTransaccionORD(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $transaccion = json_decode(file_get_contents("php://input"),true);

        $transaccion = $transaccion['transaccion'];
        $column_names = array('FECHA_GESTION','PEDIDO_ID','TIPO_ELEMENTO_ID','USUARIO_ID_GESTION','USUARIO_NOMBRE','ANALISIS','CONCEPTO_ACTUAL','CONCEPTO_FINAL','OBSERVACIONES','USUARIO_ID','FECHA_INICIO','FECHA_FIN','PUNTAJE');
        $keys = array_keys($transaccion);
        $columns = '';
        $values = '';

        $useri=$transaccion['USUARIO_ID'];
        $username=$transaccion['USERNAME'];

        $oferta=$transaccion['PEDIDO_ID'];
        $estado_final=$transaccion['CONCEPTO_FINAL'];
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
        $query = "INSERT INTO  gestor_transacciones_oxxx (".trim($columns,',').") VALUES(".trim($values,',').")";
        //echo $query;
        if(!empty($transaccion)){
            //echo $query;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

            $sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$useri','$username','AUDITO PEDIDO','$estado_final','$oferta','ORD') ";
            $rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);
            $this->response(json_encode(array("msg"=>"OK","transaccion" => $transaccion)),200);

        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }
//Funcion para insertar Transacciones de OXXX
    private function guardarAuditoriaAsignaciones(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $transaccion = json_decode(file_get_contents("php://input"),true);

        $transaccion = $transaccion['transaccion'];
        //var_dump($transaccion);
        $column_names = array('FECHA_GESTION','PEDIDO_ID','TIPO_ELEMENTO_ID','USUARIO_ID_GESTION','USUARIO_NOMBRE','ANALISIS','CONCEPTO_ACTUAL','CONCEPTO_FINAL','OBSERVACIONES','USUARIO_ID','FECHA_INICIO','FECHA_FIN','PUNTAJE');



        $useri=$transaccion['USUARIO_ID'];
        $username=$transaccion['USERNAME'];

        $oferta=$transaccion['PEDIDO_ID'];
        $estado_final=$transaccion['CONCEPTO_FINAL'];
        //echo var_dump($transaccion);
        //echo var_dump($keys);
        for ($x = 0; $x <= count($transaccion); $x++) {
            $keys = array_keys($transaccion[$x]);
            $columns = '';
            $values = '';
            foreach ($column_names as $desired_key) { // Check the customer received. If blank insert blank into the array.
                if (!in_array ($desired_key, $keys)) {
                    $$desired_key = '';
                } else {
                    $$desired_key = $transaccion[$x][$desired_key];
                }
                $columns = $columns . $desired_key . ',';
                $values = $values . "'" . $transaccion[$x][$desired_key] . "',";
            }
            $today = date ("Y-m-d H:i:s");
            $query = "INSERT INTO  gestor_transacciones_oxxx (" . trim ($columns, ',') . ") VALUES(" . trim ($values, ',') . ")";
            echo $query;
            if (!empty($transaccion[$x])) {
                //echo $query;
                $r = $this->mysqli->query ($query) or die($this->mysqli->error . __LINE__);
                $exito = true;

            }
        }
        if ($exito) {

            // SQL Feed----------------------------------
            $sql_log = "insert into portalbd.activity_feed ( " .
                " USER " .
                ", USER_NAME " .
                ", GRUPO " .
                ", STATUS " .
                ", PEDIDO_OFERTA " .
                ", ACCION " .
                ", CONCEPTO_ID " .
                ", IP_HOST " .
                ", CP_HOST " .
                ") values( " .
                " UPPER('$useri')" .
                ", UPPER('$nombreGalleta')" .
                ", UPPER('$grupoGalleta')" .
                ",'OK' " .
                ",'$oferta' " .
                ",'AUDITO PEDIDO' " .
                ",'$estado_final' " .
                ",'$usuarioIp' " .
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query ($sql_log);
            // ---------------------------------- SQL Feed
            //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion) values ('$useri','$username','ORD','$estado_final','PEDIDO: $oferta','ORD') ";
            //$rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);
            $this->response (json_encode (array("msg" => "OK", "transaccion" => $transaccion)), 200);

        } else {
            $this->response (json_encode('Error'), 403);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }


    private function editTransaccionORD(){
        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $transa = json_decode(file_get_contents("php://input"),true);
        //echo var_dump($usuario);

        $transa = $transa['transaccionORD'];
        $column_names = array('FECHA_GESTION','PEDIDO_ID','USUARIO_ID_GESTION','USUARIO_NOMBRE','TIPO_ELEMENTO_ID','RESULTADO','CONCEPTO_ACTUAL','CONCEPTO_FINAL','OBSERVACIONES');
        $keys = array_keys($transa);
        $columns = '';
        $values = '';

        $UPDATE="";
        $SEP="";
        foreach($column_names as $desired_key){ // Check the customer received. If blank insert blank into the array.
            if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
            }else{
                $$desired_key = $transa[$desired_key];
            }
            $columns = $columns.$desired_key.',';
            $values = $values."'".$transa[$desired_key]."',";
            $UPDATE=$UPDATE.$SEP.$desired_key." = '".strtoupper($transa[$desired_key])."' ";
            $SEP=",";
        }
        $today = date("Y-m-d H:i:s");

        $passcode="";
        //if($transaccion['PASSWORD']!=""){
        //  $passcode=" , PASSWORD=MD5('".$transaccion['PASSWORD']."')";
        //}
        $query = "UPDATE go_transacciones_oxxx SET $UPDATE $passcode WHERE ID=".$transa['ID'];
        //echo $query;

        if(!empty($transa)){
            //echo $query;
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
            $this->response(json_encode(array("msg"=>"OK","transaccion" => $transa)),200);
        }else{
            $this->response('',200);        //"No Content" status
            //$this->response("$query",200);        //"No Content" status
        }

    }

//Terminan funciones para  Transacciones Ordens O-XXX

    private function listadoOpcionesSiebel(){


        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $today = date("Y-m-d");
        $estados = array();
        $observaciones = array();

        $query= " SELECT ".
            "	ID ".
            "	, ESTADO_ID ".
            "	, OBSERVACION_ID ".
            "	, STATUS ".
            "	, USUARIO_ID ".
            "	FROM portalbd.gestor_opciones_siebel ";

        $queryEstado=" SELECT ".
            "	distinct ESTADO_ID ".
            "	FROM portalbd.gestor_opciones_siebel ";

        $queryObservacion=" SELECT ".
            " ESTADO_ID ".
            " , OBSERVACION_ID ".
            " , STATUS ".
            " FROM portalbd.gestor_opciones_siebel ";

        $rObservacion = $this->mysqli->query($queryObservacion);

        if($rObservacion->num_rows > 0){

            while($row=$rObservacion->fetch_assoc()){
                $observaciones[]=$row;


            }
        }

        $rEstado = $this->mysqli->query($queryEstado);

        if($rEstado->num_rows > 0){

            while($row=$rEstado->fetch_assoc()){
                $estados[]=$row;


            }
        }

        $rst = $this->mysqli->query($query);

        if ($rst->num_rows > 0){

            $resultado=array();

            while($row=$rst->fetch_assoc()){
                $resultado[]=$row;

            }
            $this->response($this->json(array($observaciones,$estados,$resultado)), 201);


        }else{

            $error="Ops";
            $this->response($this->json($error), 400);
        }

    }
    private function causaRaiz(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbSeguimientoConnect();

        $query=" SELECT DISTINCT causaRaiz".
            " FROM causaRaiz_Responsables ".
            " where 1=1 ".
            " ORDER BY causaRaiz ASC ";

        $rst = $this->connseguimiento->query($query);
        //echo $query;
        if ($rst->num_rows > 0){

            $resultado=array();
            while($row = $rst->fetch_assoc()){
                $resultado[] = $row;
            }
            $this->response($this->json(array($resultado)), 201);
        }else{
            $error = "Error";
            $this->response($this->json($error), 400);
        }  // If no records "No Content" status
    }


    private function ResponsablePendiente(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $causaraiz = $this->_request['causaraiz'];


        $this->dbSeguimientoConnect();

        $query=" SELECT responsables".
            " FROM causaRaiz_Responsables ".
            " where 1=1 ".
            " and causaRaiz = '$causaraiz'";

        $rst = $this->connseguimiento->query($query);
        // echo $query;

        if ($rst->num_rows > 0){
            $resultado=array();
            while($row = $rst->fetch_assoc()){
                $resultado[] = $row;

            }
            $this->response($this->json(array($resultado)), 201);
        }else{
            $error = "Error";
            $this->response($this->json($error), 400);
        }  // If no records "No Content" status
    }
//gestionMalosPendiInsta
    private function servicesgestionPendientesInstaMalos(){


        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $params = json_decode(file_get_contents('php://input'),true);

        $datos = $params['datosPendientes'];
        $gestion = $params['datosGestion'];
        $today=	date("Y")."-".date("m")."-".date("d");
        $active = $gestion[active];

        if ( $active == '1'){
            $HISTORICO_NOVEDAD = $gestion[NOVEDAD];
            $ASESOR = $gestion[ASESOR];
            $OBSERVACION_GESTOR = $gestion[OBSERVACION_GESTOR]."----Creado Manual----";
            $causaraiz = $datos[causaraiz];
            $responsable = $datos[responsable];
            $pedido = $gestion[PEDIDO_ID];
        }else{
            $NOVEDAD = $gestion[NOVEDAD];
            $ASESOR = $gestion[ASESOR];
            $OBSERVACION_GESTOR = $gestion[OBSERVACION_GESTOR];
            $HISTORICO_NOVEDAD = $gestion[HISTORICO_NOVEDAD];
            $causaraiz = $datos[causaraiz];
            $responsable = $datos[responsable];
            $pedido = $gestion[PEDIDO_ID];
            $CR = $gestion[NUMERO_CR];

            if($causaraiz == "Suin"){
                $OBSERVACION_GESTOR = $OBSERVACION_GESTOR."-NÚMERO DE CR: ". $CR;
            }
        }

        $this->dbSeguimientoConnect();

        $queryselectID=	"select id from historicoGestionPendientes ".
            " where pedido = '$pedido' and fecha_gestion between ('$today 00:00:00') and ('$today 23:59:59') ";

        $rstselect = $this->connseguimiento->query($queryselectID);

        if ($rstselect->num_rows > 0){
            while($row=$rstselect->fetch_assoc()){
                $id=$row['id'];
            }
            // echo "entro y es: ".$rstselect->num_rows;
        }

        if ($id == "") {
            $sql_gestionPendientes= "insert into historicoGestionPendientes ( ".
                " pedido ".
                ", causa_raiz ".
                ", responsable ".
                ", observacion ".
                ", novedad_malo ".
                ", usuario ".
                ") values( ".
                " '$pedido'".
                ", '$causaraiz'".
                ", '$responsable'".
                ", '$OBSERVACION_GESTOR' ".
                ", '$HISTORICO_NOVEDAD' ".
                ", '$ASESOR')";
            //     echo  $sql_gestionPendientes;
            $rst = $this->connseguimiento->query($sql_gestionPendientes);
        }else{
            $sqlupdate = "UPDATE historicoGestionPendientes SET ".
                "causa_raiz='$causaraiz', responsable ='$responsable', ".
                "observacion='$OBSERVACION_GESTOR', novedad_malo='$HISTORICO_NOVEDAD', fecha_gestion = '$today', usuario='$ASESOR' ".
                " WHERE id='$id' ";
            $rstupdate = $this->connseguimiento->query($sqlupdate);
            echo  $sqlupdate;
        }

        // SQL Feed----------------------------------

        // echo    $sql_gestionPendientes;
        // ---------------------------------- SQL Feed
        //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$user','$username','ADMIN','','','UPDATEPARAMETRO','$param:$value') ";
        //$rr = $this->mysqli->query($sqlfeed) or die($this->mysqli->error.__LINE__);

        $this->response(json_encode(array("OK","PARAMETRO ACTUALIZADO")), 200);
    }



// Busca Pedido Siebel Asignaciones -------------------------
    private function buscarOfertaSiebelAsignaciones(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido = $this->_request['pedidoID'];
        $pedido=trim($pedido," ");

        $user = $this->_request['userID'];

        //si el actual usuario tenia un pedido "agarrado, hay que liberarlo"
        $pedido_actual = $this->_request['pedido_actual'];

        if($pedido_actual!=''){//en este caso tenia pedido antes, estaba trabajando uno, debo actualizarlo para dejarlo libre
            $sqlupdate="update informe_petec_pendientesm set ASESOR='' where ASESOR='$user' ";
            $xxx = $this->mysqli->query($sqlupdate);
        }

        $user=strtoupper($user);
        $today = date("Y-m-d");

        $query1=" SELECT ".
            "	m.ID ".
            "	, m.PEDIDO_ID ".
            "	, m.SUBPEDIDO_ID ".
            "	, m.TIPO_ELEMENTO_ID ".
            "	, m.TIPO_TRABAJO ".
            "	, m.DESC_TIPO_TRABAJO ".
            "	, m.FECHA_INGRESO ".
            "	, m.FECHA_ESTADO ".
            "	, m.FECHA_CITA ".
            "	, m.PRODUCTO_ID ".
            "	, m.PRODUCTO ".
            "	, m.UEN_CALCULADA ".
            "	, m.ESTRATO ".
            "	, m.CONCEPTO_ID ".
            "	, m.TECNOLOGIA_ID ".
            "	, m.MUNICIPIO_ID ".
            "	, m.DIRECCION_SERVICIO ".
            "	, m.PAGINA_SERVICIO ".
            "	, m.FECHA_CARGA ".
            "	, m.FUENTE ".
            "   , m.GRUPO ".
            "   , m.ACTIVIDAD ".
            "	, m.STATUS ".
            "	, m.ASESOR ".
            "	, m.FECHA_VISTO_ASESOR ".
            "	, m.ESTUDIOS ".
            "	, m.VIEWS ".
            "	, m.CONCEPTO_ANTERIOR ".
            "   , CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(m.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA ".
            "	, m.UP2DATE ".
            "	FROM portalbd.informe_petec_pendientesm m ".
            "	where m.PEDIDO_ID='$pedido' ".
            "	AND m.STATUS IN ('PENDI_PETEC','MALO') ";

        $sqlCerrados=   " SELECT ".
            "	m.ID ".
            "	, m.PEDIDO_ID ".
            "	, m.SUBPEDIDO_ID ".
            "	, m.TIPO_ELEMENTO_ID ".
            "	, m.TIPO_TRABAJO ".
            "	, m.DESC_TIPO_TRABAJO ".
            "	, m.FECHA_INGRESO ".
            "	, m.FECHA_ESTADO ".
            "	, m.FECHA_CITA ".
            "	, m.PRODUCTO_ID ".
            "	, m.PRODUCTO ".
            "	, m.UEN_CALCULADA ".
            "	, m.ESTRATO ".
            "	, m.CONCEPTO_ID ".
            "	, m.TECNOLOGIA_ID ".
            "	, m.MUNICIPIO_ID ".
            "	, m.DIRECCION_SERVICIO ".
            "	, m.PAGINA_SERVICIO ".
            "	, m.FECHA_CARGA ".
            "	, m.FUENTE ".
            "   , m.GRUPO ".
            "   , m.ACTIVIDAD ".
            "	, m.STATUS ".
            "	, m.ASESOR ".
            "	, m.FECHA_VISTO_ASESOR ".
            "	, m.ESTUDIOS ".
            "	, m.VIEWS ".
            "	, m.CONCEPTO_ANTERIOR ".
            "   , CAST(TIMEDIFF(CURRENT_TIMESTAMP(),(m.FECHA_ESTADO)) AS CHAR(255)) as TIEMPO_COLA ".
            "	, m.UP2DATE ".
            "	FROM portalbd.informe_petec_pendientesm m ".
            "	where m.PEDIDO_ID='$pedido' ".
            "	AND m.STATUS IN ('CERRADO_PETEC') ".
            " 	order by m.ID desc limit 1";

        $rPendi = $this->mysqli->query($query1);


        $busy=false;

        if($rPendi->num_rows > 0){
            $result = array();
            while($row = $rPendi->fetch_assoc()){

                $result[] = $row;
                $ids=$row['ID'];
                $asess=$row['ASESOR'];

                if($asess!='' && $asess!=$user){//este pedido esta ocupado, no deberia hacer la actualizacion de abajo..
                    $busy=true;
                }

            }//chao While

            $sqlupdate="";

            if($busy==true){
                $sqlupdate="update informe_petec_pendientesm set VIEWS=VIEWS+1 where ID in ($ids)";


            }else{
                $fecha_visto=date("Y-m-d H:i:s");
                $sqlupdate="update informe_petec_pendientesm set VIEWS=VIEWS+1,ASESOR='$user',FECHA_VISTO_ASESOR='$fecha_visto' where ID in ($ids)";

            }

            $x = $this->mysqli->query($sqlupdate);

            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$pedido' ".
                ",'BUSCO PEDIDO SIEBEL' ".
                ",'PEDIDO BUSCADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            // Feed ----------------------
            //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$user','$username','','','PEDIDO: $pedido','BUSCARPEDIDO','') ";
            //$xx = $this->mysqli->query($sqlfeed);
            //  ---------------------- Feed

            $this->response(json_encode($result), 200); //Resultado final si encontro registros


        }else{ // Si el pedido no esta abierto lo busco en cerrado pero solo devuelvo un solo registro
            $rCerrado = $this->mysqli->query($sqlCerrados);

            if($rCerrado->num_rows > 0){

                $busy=false;
                $result = array();
                while($row = $rCerrado->fetch_assoc()){

                    $result[] = $row;
                    $ids=$row['ID'];
                    $asess=$row['ASESOR'];


                }//chao While

                $sqlupdate="";

                $fecha_visto=date("Y-m-d H:i:s");
                $sqlupdate="update informe_petec_pendientesm set VIEWS=VIEWS+1,ASESOR='$user',FECHA_VISTO_ASESOR='$fecha_visto' where ID in ($ids)";



                $xCerrado = $this->mysqli->query($sqlupdate);

                // SQL Feed----------------------------------
                $sql_log=   "insert into portalbd.activity_feed ( ".
                    " USER ".
                    ", USER_NAME ".
                    ", GRUPO ".
                    ", STATUS ".
                    ", PEDIDO_OFERTA ".
                    ", ACCION ".
                    ", CONCEPTO_ID ".
                    ", IP_HOST ".
                    ", CP_HOST ".
                    ") values( ".
                    " UPPER('$usuarioGalleta')".
                    ", UPPER('$nombreGalleta')".
                    ", UPPER('$grupoGalleta')".
                    ",'OK' ".
                    ",'$pedido' ".
                    ",'BUSCO PEDIDO SIEBEL' ".
                    ",'PEDIDO BUSCADO' ".
                    ",'$usuarioIp' ".
                    ",'$usuarioPc')";

                $rlog = $this->mysqli->query($sql_log);
                // Feed ----------------------
                //$sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$user','$username','','','PEDIDO: $pedido','BUSCARPEDIDO','Reabrio pedido') ";
                //$xxCerrado = $this->mysqli->query($sqlfeed);
                //  ---------------------- Feed



                $this->response(json_encode($result), 200); //Resultado final si encontro registros


            }else{



                $error='No existe';
                $this->response(json_encode($error),204);        // No encontramos nada.
            }


        }
    }// -------------------------Busca Pedido Siebel Asignaciones




//------------------------buscarpedido activacion dom----------------------

    private function buscarpedidoactivacion(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $pedido = $this->_request['pedidoID'];

        $user = $this->_request['userID'];
        $tabla = $this->_request['tabla'];

        $user=strtoupper($user);
        $today = date("Y-m-d");


        /* if($tabla=='ACTIVADOR_SUSPECORE'){

             $tabla = " from gestor_activacion_pendientes_activador_suspecore p " ;

         } else {

             $tabla = " from gestor_activacion_pendientes_activador_dom p " ;


         }
 */

        $query1=" SELECT p.ID ".
            " , p.PEDIDO,group_concat(distinct p.ORDER_SEQ_ID) as ORDER_SEQ_ID,p.ESTADO,p.TAREA_EXCEPCION,p.IDSERVICIORAIZ,p.TRANSACCION ".
            " ,p.ACTIVIDAD,p.FUENTE,p.GRUPO,p.MOTIVOEXCEPCIONACT,p.MOTIVO_ERROR,p.DESCRIPCIONEXCEPCIONACT ".
            " ,p.VALOR_ERROR, p.STATUS ".
            " , group_concat(distinct p.PRODUCTO) as  PRODUCTO ".
            " , min(p.FECHA_EXCEPCION) as FECHA_EXCEPCION ".
            " ,min(p.FECHA_CREACION) as FECHA_CREACION ".
            " , (select a.TIPIFICACION from gestor_historico_activacion a  ".
            " where a.PEDIDO='$pedido' order by a.ID desc limit 1) as HISTORICO_TIPIFICACION  ".
            " from gestor_activacion_pendientes_activador_suspecore p ".
            " where p.PEDIDO = '$pedido'  ".
            " and p.STATUS in ('PENDI_ACTI','MALO') ".
            " group by p.pedido ".
            " UNION ".
            " SELECT p.ID ".
            " , p.PEDIDO,group_concat(distinct p.ORDER_SEQ_ID) as ORDER_SEQ_ID,p.ESTADO,p.TAREA_EXCEPCION,p.IDSERVICIORAIZ,p.TRANSACCION ".
            " ,p.ACTIVIDAD,p.FUENTE,p.GRUPO,p.MOTIVOEXCEPCIONACT,p.MOTIVO_ERROR,p.DESCRIPCIONEXCEPCIONACT ".
            " ,p.VALOR_ERROR, p.STATUS ".
            " , group_concat(distinct p.PRODUCTO) as  PRODUCTO ".
            " , min(p.FECHA_EXCEPCION) as FECHA_EXCEPCION ".
            " ,min(p.FECHA_CREACION) as FECHA_CREACION ".
            " , (select a.TIPIFICACION from gestor_historico_activacion a  ".
            " where a.PEDIDO='$pedido' order by a.ID desc limit 1) as HISTORICO_TIPIFICACION  ".
            " from gestor_activacion_pendientes_activador_dom p ".
            " where p.PEDIDO = '$pedido'  ".
            " and p.STATUS in ('PENDI_ACTI','MALO') ".
            " group by p.pedido ";

        //echo $query1;

        $rPendi = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);

        $busy=false;

        if($rPendi->num_rows > 0){
            $result = array();
            while($row = $rPendi->fetch_assoc()){

                $result[] = $row;
                $ids=$row['ID'];
                $asess=$row['ASESOR'];

                if($asess!='' && $asess!=$user){//este pedido esta ocupado, no deberia hacer la actualizacion de abajo..
                    $busy=true;
                }

            }//chao While

            $sqlupdate="";


            $x = $this->mysqli->query($sqlupdate);

            // Feed ----------------------
            $sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$user','$username','','','PEDIDO: $pedido','BUSCARPEDIDO','') ";
            $xx = $this->mysqli->query($sqlfeed);
            //  ---------------------- Feed



            $this->response(json_encode(array($busy,$result)), 200); //Resultado final si encontro registros
            $this->response($this->json(array($result)), 200);

        }else{
            $error='No existe';
            $this->response(json_encode(array($error)),204);        // No encontramos nada.
        }


    } // -------------------------Busca Pedido Siebel Activacion dom----------------------

//------------------------buscarpedido activacion amarillas----------------------

    private function buscarpedidoamarillas(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $pedido = $this->_request['pedidoID'];

        $user = $this->_request['userID'];
        $tabla = $this->_request['tabla'];

        $user=strtoupper($user);
        $today = date("Y-m-d");




        $query1= " SELECT b.ID ".
            " ,b.PEDIDO,b.ORDER_SEQ_ID,b.ESTADO,b.TRANSACCION,b.PRODUCTO,b.FECHA_EXCEPCION,b.FECHA_CARGA,b.TABLA,b.TIPO_COMUNICACION,b.TAREA_EXCEPCION,b.DEPARTAMENTO,b.STATUS,b.ASESOR ".
            ",b.ACTIVIDAD,b.FUENTE,b.GRUPO".
            " , group_concat(b.PRODUCTO) as  PRODUCTO ".
            " , min(b.FECHA_EXCEPCION) as FECHA_EXCEPCION ".
            " ,cast(TIMESTAMPDIFF(HOUR,(b.FECHA_EXCEPCION),CURRENT_TIMESTAMP())/24 AS decimal(5,2)) as TIEMPO_TOTAL".
            " , (select a.TIPIFICACION from gestor_historico_activacion a  ".
            " where a.PEDIDO='$pedido'and a.TIPIFICACION='' order by a.ID desc limit 1) as HISTORICO_TIPIFICACION  ".
            " from pendientes_amarillas b".
            " where b.PEDIDO = '$pedido'  ".
            " and b.STATUS='PENDI_ACTI' ".
            " group by b.pedido ";

        //echo $query1;

        $rPendi = $this->mysqli->query($query1) or die($this->mysqli->error.__LINE__);

        $busy=false;

        if($rPendi->num_rows > 0){
            $result = array();
            while($row = $rPendi->fetch_assoc()){
                $row['PRODUCTO']=utf8_encode($row['PRODUCTO']);
                $result[] = $row;
                $ids=$row['ID'];
                $asess=$row['ASESOR'];

                if($asess!='' && $asess!=$user){//este pedido esta ocupado, no deberia hacer la actualizacion de abajo..
                    $busy=true;
                }

            }//chao While

            $sqlupdate="";


            $x = $this->mysqli->query($sqlupdate);

            // Feed ----------------------
            $sqlfeed="insert into activity_feed(user,user_name, grupo,status,pedido_oferta,accion,concepto_id) values ('$user','$username','','','PEDIDO: $pedido','BUSCARPEDIDO','') ";
            $xx = $this->mysqli->query($sqlfeed);
            //  ---------------------- Feed



            $this->response(json_encode(array($busy,$result)), 200); //Resultado final si encontro registros
            $this->response($this->json(array($result)), 200);

        }else{
            $error='No existe';
            $this->response(json_encode(array($error)),204);        // No encontramos nada.
        }


    } // -------------------------Busca Pedido Siebel Activacion amarillas----------------------

    //Listado de Pedidos por Usuario dia actual----------------------------------------

    private function PedidosGestorUser(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $today = date("Y-m-d");
        $grupo=$this->_request['grupo'];

        if($grupo==""||$grupo=="undefined"){
            $grupo="ASIGNACIONES";
        }

        $query= " SET @rank=0  ";
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $query=	" 	select ".
            " case ".
            "	when ul.RANK <= round(ul.DIVISOR*0.25) then 1 ".
            "    when ul.RANK > round(ul.DIVISOR*0.25) and ul.RANK <= round(ul.DIVISOR*0.50)  then 2 ".
            "    when ul.RANK > round(ul.DIVISOR*0.50) and ul.RANK <= round(ul.DIVISOR*0.75)  then 3 ".
            "    else 4 ".
            " end as CUARTIL ".
            " , ul.RANK ".
            " , ul.USUARIO_ID ".
            " , ifnull((SELECT ".
            "		case
            
             ".
            "		when r.status='logged in' then 'on' ".
            "		else 'off' ".
            "		end as estado ".
            "		FROM portalbd.registro_ingreso_usuarios r ".
            "		where 1=1 ".
            "		and r.fecha_ingreso between '$today 00:00:00' and '$today 23:59:59' ".
            "		and r.usuario=ul.USUARIO_ID ".
            "		limit 1 ),'off') as ESTADO ".
            " , ifnull((SELECT ".
            "		date_format(r.fecha_ingreso,'%H:%i') as HORA ".
            "		FROM portalbd.registro_ingreso_usuarios r ".
            "		where 1=1 ".
            "		and r.fecha_ingreso between '$today 00:00:00' and '$today 23:59:59' ".
            "		and r.usuario=ul.USUARIO_ID ".
            "		limit 1 ),'00:00') as HORAINICIO ".
            " , ul.PEDIDOS ".
            " , (ul.BUSCADOS-1) as BUSCADOS  ".
            " , ul.DIVISOR ".
            " from( ".
            "		select ".
            "		@rank:=@rank+1 AS RANK ".
            "		,z1.USUARIO_ID ".
            "		, z1.PEDIDOS ".
            "       , z1.BUSCADOS ".
            "		, z1.DIVISOR ".
            "		from(SELECT ".
            "			c1.USUARIO_ID ".
            "			, c1.GRUPO ".
            "			, group_concat(distinct c1.ACTIVIDAD) as ACTIVIDADES ".
            "			, count(distinct c1.PEDIDO_ID) as PEDIDOS ".
            "			, (count(distinct case when c1.source='BUSCADO' then c1.PEDIDO_ID else 0 end)) as BUSCADOS ".
            "			, a2.DIVISOR ".
            "			FROM (SELECT  ".
            "				p.USER AS USUARIO_ID ".
            "				, u.GRUPO ".
            "				, p.PEDIDO_ID ".
            "				, p.ACTIVIDAD ".
            "				, p.FUENTE ".
            "				, p.source ".
            "				FROM portalbd.pedidos p ".
            "				left join portalbd.tbl_usuarios u ".
            "				on p.USER=u.USUARIO_ID ".
            "				where p.fecha_fin between '$today 00:00:00' and '$today 23:59:59' ".
            "               and p.pedido_id!='' ".
            "				UNION ALL ".
            "				SELECT  ".
            "				p.USUARIO AS USUARIO_ID ".
            "				, u.GRUPO ".
            "				, p.OFERTA AS PEDIDO_ID ".
            "				, 'NCA' AS ACTIVIDAD ".
            "				, 'SIEBEL' AS FUENTE ".
            "				, 'AUTO' as source ".
            "				FROM portalbd.transacciones_nca p ".
            "				left join portalbd.tbl_usuarios u ".
            "				on p.USUARIO=u.USUARIO_ID ".
            "				where p.fecha_fin between '$today 00:00:00' and '$today 23:59:59' ) c1, ".
            "				(select count(*) as DIVISOR from  ".
            "					( SELECT ".
            "					c1.USUARIO_ID ".
            "					, c1.GRUPO ".
            "					, group_concat(distinct c1.ACTIVIDAD) as ACTIVIDADES ".
            "					, count(distinct c1.PEDIDO_ID) as PEDIDOS ".
            "					FROM (SELECT  ".
            "					p.USER AS USUARIO_ID ".
            "					, u.GRUPO ".
            "					, p.PEDIDO_ID ".
            "					, p.ACTIVIDAD ".
            "					, p.FUENTE ".
            "					, p.source ".
            "					FROM portalbd.pedidos p ".
            "					left join portalbd.tbl_usuarios u ".
            "					on p.USER=u.USUARIO_ID ".
            "					where p.fecha_fin between '$today 00:00:00' and '$today 23:59:59' ".
            "                   and p.pedido_id!='' ".
            "					UNION ALL ".
            "					SELECT  ".
            "					p.USUARIO AS USUARIO_ID ".
            "					, u.GRUPO ".
            "					, p.OFERTA AS PEDIDO_ID ".
            "					, 'NCA' AS ACTIVIDAD ".
            "					, 'SIEBEL' AS FUENTE ".
            "					, 'AUTO' as source ".
            "					FROM portalbd.transacciones_nca p ".
            "					left join portalbd.tbl_usuarios u ".
            "					on p.USUARIO=u.USUARIO_ID ".
            "					where p.fecha_fin between '$today 00:00:00' and '$today 23:59:59'  ".
            "					) c1 ".
            "					where c1.GRUPO in ('$grupo') ".
            "				group by c1.USUARIO_ID ) a1 ) a2 ".
            "		where c1.GRUPO in ('$grupo') ".
            "		group by c1.USUARIO_ID ".
            "		order by  count(distinct c1.PEDIDO_ID) desc ) z1  ".
            "        order by 3 desc) ul ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();

            while($row = $r->fetch_assoc()){

                $result[] = $row;
            }

            $this->response($this->json(array($result,$grupo,$today)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

    //------------------------Listado de Pedidos por Usuario dia actual

    //Listado de Pedidos por Usuario dia actual REAGENDAMIENTO----------------------------------------

    private function PedidosGestorUserReagendamiento(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $today = date("Y-m-d");
        $grupo=$this->_request['grupo'];

        if($grupo==""||$grupo=="undefined"){
            $grupo="INSTALACION";
        }

        $query= " SET @rank=0  ";
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $query=	" SELECT ".
            " case  ".
            "	when L1.RANK <= round(L1.DIVISOR*0.25) then 1  ".
            "	when L1.RANK > round(L1.DIVISOR*0.25) and L1.RANK <= round(L1.DIVISOR*0.50)  then 2  ".
            "	when L1.RANK > round(L1.DIVISOR*0.50) and L1.RANK <= round(L1.DIVISOR*0.75)  then 3  ".
            "	else 4  ".
            " end as CUARTIL ".
            ", L1.RANK ".
            ", L1.USUARIO_ID ".
            ", ifnull((SELECT  ".
            "	case  ".
            "	when r.status='logged in' then 'on'  ".
            "	else 'off'  ".
            "	end as estado  ".
            "	FROM portalbd.registro_ingreso_usuarios r  ".
            "	where 1=1  ".
            "	and r.fecha_ingreso between '$today 00:00:00' and '$today 23:59:59'  ".
            "	and r.usuario=L1.USUARIO_ID  ".
            "	limit 1 ),'off') as ESTADO  ".
            ", ifnull((SELECT  ".
            "	date_format(r.fecha_ingreso,'%H:%i') as HORA  ".
            "	FROM portalbd.registro_ingreso_usuarios r  ".
            "	where 1=1  ".
            "	and r.fecha_ingreso between '$today 00:00:00' and '$today 23:59:59'  ".
            "	and r.usuario=L1.USUARIO_ID  ".
            "	limit 1 ),'00:00') as HORAINICIO  ".
            ", L1.PEDIDOS ".
            ", L1.DIVISOR ".
            " FROM (SELECT ".
            " @rank:=@rank+1 AS RANK ".
            ", Z1.USUARIO_ID ".
            ", Z1.PEDIDOS ".
            ", Z1.DIVISOR ".
            " FROM(SELECT ".
            "  C1.USUARIO_ID ".
            ", C1.PEDIDOS ".
            ", C2.DIVISOR ".
            " FROM (SELECT  ".
            "	R.ASESOR AS USUARIO_ID ".
            "	, COUNT(DISTINCT R.PEDIDO_ID) AS PEDIDOS ".
            "	FROM portalbd.gestor_historicos_reagendamiento R ".
            "	where R.acceso='CONTACT_CENTER' ".
            "	AND R.PROCESO='$grupo' ".
            "	AND R.FECHA_FIN between '$today 00:00:00' and '$today 23:59:59'  ".
            " GROUP BY R.ASESOR) C1,  ".
            "	(SELECT COUNT(distinct A.ASESOR) AS DIVISOR ".
            "	FROM portalbd.gestor_historicos_reagendamiento A ".
            "	where A.acceso='CONTACT_CENTER' ".
            "	AND A.PROCESO='$grupo' ".
            "	AND A.FECHA_FIN between '$today 00:00:00' and '$today 23:59:59' ) C2 ".
            " ORDER BY 2 DESC) Z1 ) L1 ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();

            while($row = $r->fetch_assoc()){

                $result[] = $row;
            }

            $this->response($this->json(array($result,$grupo,$today)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

    //------------------------Listado de Pedidos por Usuario dia actual REAGENDAMIENTO

    //Listado de Pedidos por Usuario dia actual ACTIVACION----------------------------------------

    private function PedidosGestorUserActivacion(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $today = date("Y-m-d");
        $grupo=$this->_request['grupo'];


        if($grupo==""||$grupo=="undefined"){
            $grupo="ACTIVADOR_SUSPECORE";
        }

        $query= " SET @rank=0  ";
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
        $query=	" SELECT ".
            " case  ".
            "	when L1.RANK <= round(L1.DIVISOR*0.25) then 1  ".
            "	when L1.RANK > round(L1.DIVISOR*0.25) and L1.RANK <= round(L1.DIVISOR*0.50)  then 2  ".
            "	when L1.RANK > round(L1.DIVISOR*0.50) and L1.RANK <= round(L1.DIVISOR*0.75)  then 3  ".
            "	else 4  ".
            " end as CUARTIL ".
            ", L1.RANK ".
            ", L1.USUARIO_ID ".
            ", ifnull((SELECT  ".
            "	case  ".
            "	when r.status='logged in' then 'on'  ".
            "	else 'off'  ".
            "	end as estado  ".
            "	FROM portalbd.registro_ingreso_usuarios r  ".
            "	where 1=1  ".
            "	and r.fecha_ingreso between '$today 00:00:00' and '$today 23:59:59'  ".
            "	and r.usuario=L1.USUARIO_ID  ".
            "	limit 1 ),'off') as ESTADO  ".
            ", ifnull((SELECT  ".
            "	date_format(r.fecha_ingreso,'%H:%i') as HORA  ".
            "	FROM portalbd.registro_ingreso_usuarios r  ".
            "	where 1=1  ".
            "	and r.fecha_ingreso between '$today 00:00:00' and '$today 23:59:59'  ".
            "	and r.usuario=L1.USUARIO_ID  ".
            "	limit 1 ),'00:00') as HORAINICIO  ".
            ", L1.PEDIDOS ".
            ", L1.DIVISOR ".
            " FROM (SELECT ".
            " @rank:=@rank+1 AS RANK ".
            ", Z1.USUARIO_ID ".
            ", Z1.PEDIDOS ".
            ", Z1.DIVISOR ".
            " FROM(SELECT ".
            "  C1.USUARIO_ID ".
            ", C1.PEDIDOS ".
            ", C2.DIVISOR ".
            " FROM (SELECT  ".
            "	R.ASESOR AS USUARIO_ID ".
            "	, COUNT(R.PEDIDO) AS PEDIDOS ".
            "	FROM portalbd.gestor_historico_activacion R ".
            "	where R.TABLA='$grupo' ".
            "	AND R.FECHA_FIN between '$today 00:00:00' and '$today 23:59:59'  ".
            " GROUP BY R.ASESOR) C1,  ".
            "	(SELECT COUNT(A.ASESOR) AS DIVISOR ".
            "	FROM portalbd.gestor_historico_activacion A ".
            "	where A.TABLA='$grupo' ".
            "	AND A.FECHA_FIN between '$today 00:00:00' and '$today 23:59:59' ) C2 ".
            " ORDER BY 2 DESC) Z1 ) L1 ";
        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();

            while($row = $r->fetch_assoc()){

                $result[] = $row;
            }

            $this->response($this->json(array($result,$grupo,$today)), 200); // send user details
        }
        $this->response('',204);        // If no records "No Content" status

    }

    //------------------------Listado de Pedidos por Usuario dia actual activacion

    // Listado de Localidades Edatel Asginaciones ------------------------------------


    private function LocalidadesEdatel(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbConnect03();

        $today = date("Y-m-d");


        $query=	" SELECT DEPARTAMENTO, LOCALIDAD, REGION FROM gestor_informes.eda_localidades order by 1,2 asc";
        //" where e.LOCALIDAD='$localidad' ";

        //echo $query;
        $r = $this->mysqli03->query($query) or die($this->mysqli03->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();

            while($row = $r->fetch_assoc()){

                $result[] = $row;
            }

            $this->response($this->json(array($result,$today)), 200);
        }
        $this->response('',406);        // If no records "No Content" status

    }


    // -----------------------------------------------------------------Listado de Localidades Edatel

    // Listado de Clientes Edatel -----------------------------------------------------------------
    private function clientesEdatel(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbConnect03();

        $today = date("Y-m-d");
        $localidad=$this->_request['localidad'];
        $direccion=$this->_request['direccion'];

        if($localidad==""||$localidad=="undefined"){
            $localidad="MEDELLIN";
        }
        if($direccion==""||$direccion=="undefined"){
            $paramdir="";
        }else{
            $paramdir=" and upper(e.DIREC_INSTALACION) like '%$direccion%' ";
        }

        $query=	" SELECT ".
            "	e.LOCALIDAD ".
            "	, e.PRODUCT_ID ".
            "	, e. DIREC_INSTALACION ".
            "	, e.RUTA ".
            "	, e.DISTRIB ".
            "	, e.ARM ".
            "	, e.CAJA ".
            "	, e.PSCAJA ".
            "	, e.LISTON ".
            "	, e.PSLIS ".
            "	, e.BDOR ".
            "	, e.PUERTO ".
            "	, e.IDPTO ".
            "	, e.HOR ".
            "	, e.PH ".
            "	, e.EST_COMP ".
            "	, e.TIP_PDTO ".
            "	, e.DESCP_ESTRA ".
            "	FROM gestor_informes.eda_clientes e ".
            "	where e.LOCALIDAD='$localidad' ".
            "	$paramdir ";

        //echo $query;
        $r = $this->mysqli03->query($query) or die($this->mysqli03->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();

            while($row = $r->fetch_assoc()){

                $result[] = $row;
            }

            $this->response($this->json(array($result,$localidad)), 200); // send user details
        }
        $this->response('',406);        // If no records "No Content" status

    }

    //------------------------Listado de Clientes Edatel

    // Opciones Edatel ------------------------------------


    private function opcionesEdatelAsignaciones(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        //$this->dbConnect03();

        $today = date("Y-m-d");


        $query= " SELECT ".
            "	ID ".
            "	, ESTADO_ID ".
            "	, OBSERVACION_ID ".
            "	, STATUS ".
            "	, USUARIO_ID ".
            "	FROM portalbd.gestor_opciones_edatel ";

        $queryEstado=" SELECT ".
            "	distinct ESTADO_ID ".
            "	FROM portalbd.gestor_opciones_edatel ";

        $queryObservacion=" SELECT ".
            " ESTADO_ID ".
            " , OBSERVACION_ID ".
            " , STATUS ".
            " FROM portalbd.gestor_opciones_edatel ";

        $rObservacion = $this->mysqli->query($queryObservacion);

        if($rObservacion->num_rows > 0){
            $result = array();
            while($row=$rObservacion->fetch_assoc()){
                $observaciones[]=$row;


            }
        }

        $rEstado = $this->mysqli->query($queryEstado);

        if($rEstado->num_rows > 0){
            $result = array();
            while($row=$rEstado->fetch_assoc()){
                $estados[]=$row;


            }
        }

        $rst = $this->mysqli->query($query);

        if ($rst->num_rows > 0){

            $resultado=array();

            while($row=$rst->fetch_assoc()){
                $resultado[]=$row;


            }
            $this->response($this->json(array($observaciones,$estados,$resultado)), 201);


        }else{

            $error="Ops";
            $this->response($this->json($error), 400);
        }

    }


    //------------------------Listado de Localidades Edatel

    // Listado de Distribuidores Edatel -----------------------------------------------------------------
    private function distribuidoresEdatel(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbConnect03();

        $today = date("Y-m-d");
        $localidad=$this->_request['localidad'];
        //$direccion=$this->_request['direccion'];

        if($localidad==""||$localidad=="undefined"){
            $localidad="MEDELLIN";
        }

        $query=	" SELECT d.ID, ".
            "	d.DESCRIPTION_DISTRIBUIDOR, ".
            "   d.LOCALIDAD, ".
            "	d.LOC, ".
            "	d.CEN, ".
            "	d.DISTRI, ".
            "	d.DEPARTAMENTO, ".
            "	d.REGION, ".
            "	d.ZONA, ".
            "	d.DESCRIPTION_CENTRAL ".
            " FROM gestor_informes.eda_distribuidores d ".
            " where d.LOCALIDAD='$localidad' ";


        $r = $this->mysqli03->query($query) or die($this->mysqli03->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();

            while($row = $r->fetch_assoc()){

                $result[] = $row;
            }

            $this->response($this->json(array($result,$localidad)), 200); // send user details
        }
        $this->response('',406);        // If no records "No Content" status

    }

    //------------------------Listado de Clientes Edatel

//------------------------Listado concepts sistema


    private function gestorConceptos(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }


        $query=	" SELECT * FROM portalbd.gestor_conceptos ";

        //echo $query;
        $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($r->num_rows > 0){
            $result = array();

            while($row = $r->fetch_assoc()){

                $result[] = $row;
            }

            $this->response($this->json($result), 201); // send user details
        }
        $this->response('',406);        // If no records "No Content" status

    }

    //------------------------Listado de Gestor Conceptos



//PEDIDOS PROGRAMADOS POR USER -----------------------------------------------

    private function listaProgramadosUser(){

        $usuario_id="";
        $parametro="";


        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $params         =   json_decode(file_get_contents('php://input'),true);
        $usuario_id     =   $params['usuario_id'];
        $today          =   date("Y-m-d");


        if($usuario_id!=''){

            $parametro=" and u.USUARIO_ID ='$usuario_id'";
        };


        $query="	select ".
            " c2.PEDIDO_ID ".
            ", c2.MUNICIPIO_ID ".
            ", c2.FECHA_CITA ".
            ", c2.STATUS ".
            ", c2.MOTIVO_MALO ".
            ", c2.MENSAJE ".
            ", c2.USUARIO_ID ".
            ", c2.PROGRAMACION ".
            ", c2.FECHA_GESTION ".
            " from (SELECT  ".
            "	P.PEDIDO_ID  ".
            "	, MAX(P.MUNICIPIO_ID) as MUNICIPIO_ID  ".
            "	, max(P.FECHA_CITA) as FECHA_CITA  ".
            "	, case  ".
            "		when max(P.STATUS)='PENDI_PETEC' then 'PROGRAMADO' ".
            "        else max(P.STATUS)  ".
            "        END as STATUS  ".
            "    , max(P.PROGRAMACION) as PROGRAMACION ".
            "	, case   ".
            "		when max(P.FECHA_CITA)='9999-00-00' THEN 'SIN CITA'  ".
            "		when max(P.FECHA_CITA)<=CURRENT_DATE() THEN 'ALARMADO'  ".
            "	    when max(P.FECHA_CITA)=DATE_ADD(CURDATE(), INTERVAL 1 DAY) then 'GESTIONAR'  ".
            "	    else 'EN ESPERA'   ".
            "	    end as MENSAJE ".
            "	, max(P.CONCEPTO_ID) as ULTCONCEPTO ".
            "	, max(P.FUENTE) as FUENTE ".
            "    , (SELECT h.user as USUARIO_ID ".
            "		FROM   ".
            "			portalbd.pedidos h   ".
            "		WHERE   ".
            "			1 = 1 AND h.estado  in ('MALO','VOLVER A LLAMAR','PENDIENTE','GESTIONAR MAS TARDE')  ".
            "				  AND h.ID = (SELECT  MAX(hh.ID)  FROM  portalbd.pedidos hh   ".
            "				  WHERE  hh.PEDIDO_ID = h.PEDIDO_ID and hh.estado in ('MALO','VOLVER A LLAMAR','PENDIENTE','GESTIONAR MAS TARDE') ".
            "				GROUP BY hh.PEDIDO_ID) ".
            "				and h.PEDIDO_ID=P.PEDIDO_ID) as USUARIO_ID ".
            "	, (SELECT h.motivo_malo ".
            "		FROM   ".
            "			portalbd.pedidos h   ".
            "		WHERE   ".
            "			1 = 1 AND h.estado  in ('MALO','VOLVER A LLAMAR','PENDIENTE','GESTIONAR MAS TARDE')  ".
            "				  AND h.ID = (SELECT  MAX(hh.ID)  FROM  portalbd.pedidos hh   ".
            "				  WHERE  hh.PEDIDO_ID = h.PEDIDO_ID and hh.estado in ('MALO','VOLVER A LLAMAR','PENDIENTE','GESTIONAR MAS TARDE') ".
            "				GROUP BY hh.PEDIDO_ID) ".
            "				and h.PEDIDO_ID=P.PEDIDO_ID) as MOTIVO_MALO ".
            "	, (SELECT h.fecha_fin ".
            "		FROM   ".
            "			portalbd.pedidos h   ".
            "		WHERE   ".
            "			1 = 1 AND h.estado  in ('MALO','VOLVER A LLAMAR','PENDIENTE','GESTIONAR MAS TARDE')  ".
            "				  AND h.ID = (SELECT  MAX(hh.ID)  FROM  portalbd.pedidos hh   ".
            "				  WHERE  hh.PEDIDO_ID = h.PEDIDO_ID and hh.estado in ('MALO','VOLVER A LLAMAR','PENDIENTE','GESTIONAR MAS TARDE') ".
            "				GROUP BY hh.PEDIDO_ID) ".
            "				and h.PEDIDO_ID=P.PEDIDO_ID) as FECHA_GESTION  ".
            "	FROM portalbd.informe_petec_pendientesm P  ".
            "	WHERE P.STATUS in ('MALO') ".
            "    OR (P.STATUS in ('PENDI_PETEC')  ".
            "    and P.PROGRAMACION!='') ".
            "	group by P.PEDIDO_ID ) c2 ".
            "    where c2.USUARIO_ID='$usuario_id' ";

        $rst = $this->mysqli->query($query);

        if ($rst->num_rows > 0){

            $resultado=array();

            while($row=$rst->fetch_assoc()){

                $resultado[]=$row;

            }
            $this->response($this->json(array($resultado)), 201);


        }else{
            $error="Sin registros";
            $this->response($this->json($error), 400);
        }

    }//-----------------------------------------------PEDIDOS PROGRAMADOS POR USER

    //Historico de Pedidos --------------------------------------------------------

    private function listaHistoricoPedidos(){

        //$usuario_id="";
        $parametro="";


        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        //$this->dbDespachoConnect();

        //$params = json_decode(file_get_contents('php://input'),true);
        $params = json_decode(file_get_contents('php://input'),true);
        //$params = file_get_contents('php://input');

        $pedido = $params['pedido'];
        $today = date("Y-m-d");

        $query="	SELECT ".
            "	p.ID ".
            "	, p.ACTIVIDAD ".
            "	, p.FECHA_FIN as FECHA_GESTION ".
            "	, p.ESTADO ".
            "	, p.USER AS USUARIO_ID ".
            "	, p.CONCEPTO_ANTERIOR ".
            "	, p.CONCEPTO_FINAL ".
            "	, p.IDLLAMADA ".
            "	, p.NUEVOPEDIDO ".
            "	, p.MOTIVO_MALO ".
            "	from portalbd.pedidos p ".
            "	where p.pedido_id='$pedido' ".
            "	order by p.ID desc ";

        // echo $query;
        $rst = $this->mysqli->query($query);

        //echo $this->mysqli->query($sqlLogin);
        //
        if ($rst->num_rows > 0){

            $resultado=array();

            while($row=$rst->fetch_assoc()){

                //$row['USUARIO_NOMBRE']=utf8_encode($row['USUARIO_NOMBRE']);
                $resultado[]=$row;


            }
            $this->response($this->json($resultado), 201);


        }else{
            $error="Sin registros";
            $this->response($this->json($error), 403);
        }

    }//----------------------------------------------- Historico de Pedidos


    private function csvUsuarios(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }




        $params = json_decode(file_get_contents('php://input'),true);
        $usuarioIp=$_SERVER['REMOTE_ADDR'];
        $usuarioPc=gethostbyaddr($usuarioIp);
        $galleta=json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta=stripslashes($_COOKIE['logedUser']);
        $galleta= json_decode($galleta);
        $galleta = json_decode(json_encode($galleta), True);
        $usuarioid=$galleta['USUARIO_ID'];

        //echo

        $today = date("Y-m-d");
        $ano=date("Y");

        $filename="Usuarios_$today.csv";


        $query="SELECT ".
            " u.ID, ".
            " u.USUARIO_ID, ".
            " u.USUARIO_NOMBRE, ".
            " SUBSTRING_INDEX(u.USUARIO_NOMBRE, ' ', 1) as NOMBRE, ".
            " u.CEDULA_ID, ".
            " u.GRUPO, ".
            " u.CORREO_USUARIO, ".
            " concat(u.CARGO_ID,'-',c.NOMBRE_CARGO) as CARGO_ID, ".
            " u.SUPERVISOR, ".
            " u.INTERVENTOR, ".
            " u.ESTADO ".
            " FROM portalbd.tbl_usuarios u ".
            " left join portalbd.tbl_cargos c on u.CARGO_ID=c.ID_CARGO ".
            " where 1=1  ".
            " order by 5, 3 asc ";

        //echo $query;

        $rst = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);

        if($rst->num_rows > 0){

            /*Insert en log
								$sql_log="insert into emtelco.re_logoperaciones (USUARIO_ID, TIPO_ACTIVIDAD, DESCRIPCION, IDENTIFICADOR, IP, PC) values(UPPER('$usuarioid'),'EXPORTE','EXPORTO CSV_USUARIOS','LOGIN: $usuarioid','$usuarioIp','$usuarioPc')";

								$rlog = $this->connemtel->query($sql_log) or die($this->connemtel->error.__LINE__);
								//Insert en log*/

            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            //echo $fp;
            $columnas=array( 'REGISTROID',
                'USUARIO_ID',
                'USUARIO_NOMBRE',
                'NOMBRE',
                'CEDULA_ID',
                'GRUPO',
                'CORREO_USUARIO',
                'CARGO_ID',
                'SUPERVISOR',
                'INTERVENTOR',
                'ESTADO'
            );

            fputcsv($fp, $columnas,',');
            //$carlitos=0;
            while($row = $rst->fetch_assoc()){

                //$row['OBSERVACIONES']=utf8_decode($row['OBSERVACIONES']);
                //$result[] = $row;
                fputcsv($fp, $row);
                //if($carlitos==0){var_dump($row);$carlitos=1;};
            }

            fclose($fp);

            $this->response($this->json(array($filename)), 200);


        }


        $this->response('',203);        // If no records "No Content" status

    }
//CRUD para Usuarios
//
//Funcion para crear Usuario Nuevo
    private function crearUsuario(){


        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }



        $params = json_decode(file_get_contents('php://input'),true);
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];


        //$id=$params['editaInfo']['ID'];
        $usuarioEdita=$params['editaInfo']['USUARIO_ID'];
        $usuarionombreEdita=utf8_decode($params['editaInfo']['USUARIO_NOMBRE']);
        $cedulaidEdita=$params['editaInfo']['CEDULA_ID'];
        $grupoEdita=$params['editaInfo']['GRUPO'];
        $equipoidEdita='MANUAL';
        $correoEdita=$params['editaInfo']['CORREO_USUARIO'];
        $funcionEdita=$params['editaInfo']['FUNCION'];
        $turnoEdita=$params['editaInfo']['TURNO'];
        $cargoidEdita=$params['editaInfo']['CARGO_ID'];
        $interventorEdita=utf8_decode($params['editaInfo']['INTERVENTOR']);
        $supervisorEdita=utf8_decode($params['editaInfo']['SUPERVISOR']);
        $passEdita=$params['editaInfo']['PASSWORD'];
        $estadoEdita=$params['editaInfo']['ESTADO'];
        $funcionEdita=$params['editaInfo']['FUNCION'];

        //echo $grupoEdita;

        $sql = " INSERT INTO portalbd.tbl_usuarios ( ".
            " USUARIO_ID, ".
            " USUARIO_NOMBRE, ".
            " CEDULA_ID, ".
            " GRUPO, ".
            " EQUIPO_ID, ".
            " CORREO_USUARIO, ".
            " TURNO, ".
            " CARGO_ID, ".
            " INTERVENTOR, ".
            " SUPERVISOR, ".
            " PASSWORD, ".
            " ESTADO, ".
            " FUNCION) values ( ".
            " '$usuarioEdita', ".
            " '$usuarionombreEdita', ".
            " '$cedulaidEdita', ".
            " '$grupoEdita', ".
            " '$equipoidEdita', ".
            " '$correoEdita', ".
            " '$turnoEdita', ".
            " '$cargoidEdita', ".
            " '$interventorEdita', ".
            " '$supervisorEdita', ".
            "  MD5('$passEdita'), ".
            " '$estadoEdita', ".
            " '$funcionEdita') ";

        $rst = $this->mysqli->query($sql);

        // SQL Feed----------------------------------
        $sql_log=   "insert into portalbd.activity_feed ( ".
            " USER ".
            ", USER_NAME ".
            ", GRUPO ".
            ", STATUS ".
            ", PEDIDO_OFERTA ".
            ", ACCION ".
            ", CONCEPTO_ID ".
            ", IP_HOST ".
            ", CP_HOST ".
            ") values( ".
            " UPPER('$usuarioGalleta')".
            ", UPPER('$nombreGalleta')".
            ", UPPER('$grupoGalleta')".
            ",'OK' ".
            ",'SIN PEDIDO' ".
            ",'CREO USUARIO' ".
            ",'$usuarioEdita CREADO' ".
            ",'$usuarioIp' ".
            ",'$usuarioPc')";

        $rlog = $this->mysqli->query($sql_log);
        // ---------------------------------- SQL Feed

        $error="Usuario Creado.";
        $this->response($this->json($error), 200);


    }//Funcion para crear Usuario Nuevo
    //
//Funcion para Borrar la novedad
    private function borrarUsuario(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $params = json_decode(file_get_contents('php://input'),true);
        $id=$params['id'];



        $sql = "delete from portalbd.tbl_usuarios where ID=$id ";

        $rst = $this->mysqli->query($sql);

        // SQL Feed----------------------------------
        $sql_log=   "insert into portalbd.activity_feed ( ".
            " USER ".
            ", USER_NAME ".
            ", GRUPO ".
            ", STATUS ".
            ", PEDIDO_OFERTA ".
            ", ACCION ".
            ", CONCEPTO_ID ".
            ", IP_HOST ".
            ", CP_HOST ".
            ") values( ".
            " UPPER('$usuarioGalleta')".
            ", UPPER('$nombreGalleta')".
            ", UPPER('$grupoGalleta')".
            ",'OK' ".
            ",'SIN PEDIDO' ".
            ",'BORRO USUARIO' ".
            ",'USUARIO BORRADO' ".
            ",'$usuarioIp' ".
            ",'$usuarioPc')";

        $rlog = $this->mysqli->query($sql_log);
        // ---------------------------------- SQL Feed
        $error="Usuario borrado";
        $this->response($this->json($error), 200);


    }//Funcion para listar la productividad del grupo

//Funcion para Editar novedades
    private function editarUsuario(){


        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }


        $params = json_decode(file_get_contents('php://input'),true);

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];


        $id=$params['editaInfo']['ID'];
        $usuarioEdita=$params['editaInfo']['USUARIO_ID'];
        $usuarionombreEdita=utf8_decode($params['editaInfo']['USUARIO_NOMBRE']);
        $cedulaidEdita=$params['editaInfo']['CEDULA_ID'];
        $grupoEdita=$params['editaInfo']['GRUPO'];
        $equipoidEdita='MANUAL';
        $correoEdita=$params['editaInfo']['CORREO_USUARIO'];
        $funcionEdita=$params['editaInfo']['FUNCION'];
        $turnoEdita=$params['editaInfo']['TURNO'];
        $cargoidEdita=$params['editaInfo']['CARGO_ID'];
        $interventorEdita=utf8_decode($params['editaInfo']['INTERVENTOR']);
        $supervisorEdita=utf8_decode($params['editaInfo']['SUPERVISOR']);
        $passEdita=$params['editaInfo']['PASSWORD'];
        $estadoEdita=$params['editaInfo']['ESTADO'];
        $funcionEdita=$params['editaInfo']['FUNCION'];


        //var_dump($params['editaInfo']);

        if($passEdita!=""){
            $passcode=" , PASSWORD=MD5('".$passEdita."')";
        }

        $sql = " UPDATE portalbd.tbl_usuarios ".
            " SET USUARIO_ID='$usuarioEdita' ".
            " , USUARIO_NOMBRE='$usuarionombreEdita' ".
            " , CEDULA_ID='$cedulaidEdita' ".
            " , GRUPO='$grupoEdita' ".
            " , EQUIPO_ID='$equipoidEdita' ".
            " , CORREO_USUARIO='$correoEdita' ".
            " , FUNCION='$funcionEdita' ".
            " , TURNO='$turnoEdita' ".
            " , CARGO_ID='$cargoidEdita' ".
            " , SUPERVISOR='$supervisorEdita' ".
            " , INTERVENTOR='$interventorEdita' ".
            " $passcode ".
            " , ESTADO='$estadoEdita' ".
            " where ID='$id' ";

        //echo $sql;


        $rst = $this->mysqli->query($sql) or die($this->mysqli->error.__LINE__);

        // SQL Feed----------------------------------
        $sql_log=   "insert into portalbd.activity_feed ( ".
            " USER ".
            ", USER_NAME ".
            ", GRUPO ".
            ", STATUS ".
            ", PEDIDO_OFERTA ".
            ", ACCION ".
            ", CONCEPTO_ID ".
            ", IP_HOST ".
            ", CP_HOST ".
            ") values( ".
            " UPPER('$usuarioGalleta')".
            ", UPPER('$nombreGalleta')".
            ", UPPER('$grupoGalleta')".
            ",'OK' ".
            ",'SIN PEDIDO' ".
            ",'EDITO USUARIO' ".
            ",'$usuarioEdita EDITADO' ".
            ",'$usuarioIp' ".
            ",'$usuarioPc')";

        $rlog = $this->mysqli->query($sql_log);
        // ---------------------------------- SQL Feed

        $error="Usuario Editado.";
        $this->response($this->json($error), 200);


    }//Funcion para listar la productividad del grupo


    private function municipiosAsignacionesSiebel(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $params 	= json_decode(file_get_contents('php://input'),true);
        $concepto 	= $params['concepto'];
        $fuente     = $params['fuente'];
        $today		= date("Y-m-d");

        if($fuente=='FENIX_NAL'){
            $paramFuente = " o.FUENTE in ('FENIX_NAL','FENIX_BOG')";
        }else{
            $paramFuente = " o.FUENTE='$fuente'";
        }

        $filtros= " and o.STATUS ='PENDI_PETEC' and $paramFuente AND o.CONCEPTO_ID='$concepto' ".
                    " GROUP BY o.MUNICIPIO_ID ORDER BY COUNT(*) DESC ";

        if ($concepto == "12-EDATEL")
        {
            //$municipios = "1 = 1";
            $municipios = "MUNICIPIO_ID IN ('GUATAPE','CIUDAD BOLIVAR','AMAGA','SANTAFE DE ANTIOQUIA','FREDONIA','VENECIA','SANTA BARBARA','ANDES','PEÑOL','SONSON','LA PINTADA','TAMASIS','JARDIN','URRAO','JERICO','APARTADO')";
        }
        else{
            $municipios = "MUNICIPIO_ID like 'BOG%' ";
        }

        $query=	" SELECT ".
            "	o.MUNICIPIO_ID ".
            ",	COUNT(*) AS COUNTER ".
            "	FROM portalbd.informe_petec_pendientesm o ".
            "	where $municipios ".
                //where 1 = 1 ".
            " 	$filtros ";

        //echo var_dump($query);

        //echo $query;

        $rst = $this->mysqli->query($query);

        if ($rst->num_rows > 0){

            $resultado=array();
            $resultado[]=array("MUNICIPIO_ID"=>"TODOS","COUNTER"=>"TODOS");
            while($row=$rst->fetch_assoc()){

                //$row['nombre']=utf8_encode($row['nombre']);
                $resultado[]=$row;


            }
            $this->response($this->json($resultado), 201);


        }else{
            $error="Error. Este concepto no tiene pendientes";
            $this->response($this->json(array($error)), 400);
        }

    }

    // ------------------------------------------------------------------------ Parametros Acciones Nuevo


    private function opcionesGestionAsignaciones(){


        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $params 	= json_decode(file_get_contents('php://input'),true);
        $fuente 	= $params['fuente'];
        $grupo 	    = $params['grupo'];
        $actividad  = $params['actividad'];
        $today		= date("Y-m-d");

        //var_dump($fuente);
        /* if($fuente=='SIEBEL'){
            $grupo      =   "ASIGNACIONES";
            $actividad  =   "ESTUDIO";
            $filtros="and o.ESTADO=1 and o.FUENTE='$fuente' and o.GRUPO='$grupo' and o.ACTIVIDAD='$actividad'";
        }else{
            if($grupo=='ADMINISTRACION'){
                $filtros= "";
            }else{
                $filtros= " and o.ESTADO=1 and o.FUENTE='$fuente' and o.GRUPO='$grupo' and o.ACTIVIDAD='$actividad' ";
            };
        } */
        $filtros= " and o.ESTADO=1 and o.FUENTE='$fuente' and o.GRUPO='$grupo' and o.ACTIVIDAD='$actividad' ";

        $query=	" SELECT ".
            "	o.ID ".
            "	,	o.FUENTE ".
            "	,	o.GRUPO ".
            "	,	o.ACTIVIDAD ".
            "	,	o.ESTADO_ID ".
            "	,	o.OBSERVACION_ID ".
            "	,	o.USUARIO_ID ".
            "	,	o.STATUS ".
            "	,	o.ESTADO ".
            "	FROM portalbd.gestor_opciones_gestion o ".
            "	where 1=1 ".
            " 	$filtros ";

        //echo $query;

        $rst = $this->mysqli->query($query);

        if ($rst->num_rows > 0){

            $resultado=array();

            while($row=$rst->fetch_assoc()){

                //$row['nombre']=utf8_encode($row['nombre']);
                $resultado[]=$row;


            }
            $this->response($this->json($resultado), 201);


        }else{
            $error="Verificar Opciones.";
            $this->response($this->json($error), 400);
        }

    }// ------------------------------------------------------------------------ Parametros Acciones Nuevo

    /**
     * Funcion para productividad el grupo de asignaciones cada hora-
     */
    private function productivdadAsignacionesPorHora(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }


        $params = json_decode(file_get_contents('php://input'),true);
        $fecha = $params['fecha'];
        $today = date("Y-m-d");

        if($fecha==''|| $fecha=='undefined'){
            $fecha=$today;
        }


        $query=    " SELECT ".
            " G.HORA ".
            " , ifnull(G.INGRESOS,0) AS INGRESOS ".
            " , ifnull(G.ASIGNADOS,0) AS ASIGNADOS ".
            " , ifnull(G.SIEBEL,0) AS SIEBEL ".
            " , ifnull(G.RECONFIGURADOS,0) AS RECONFIGURADOS ".
            " , (ifnull(G.ASIGNADOS,0)+ifnull(G.SIEBEL,0)+ifnull(G.RECONFIGURADOS,0) ) AS GESTIONADOS ".
            " , ifnull(G.USUARIOS,0) as USUARIOS ".
            " FROM(SELECT  ".
            " h.HORA ".
            " , (select C2.CANTUSER ".
            "    from ".
            "    (select c1.HORA, count(distinct c1.USUARIO) as CANTUSER ".
            "    from ( select  ".
            "            p.user as USUARIO,  ".
            "             p.fecha_fin AS FECHAFIN,  ".
            "             DATE_FORMAT(p.fecha_fin,'%H') AS HORA,  ".
            "             DATE_FORMAT(p.fecha_fin,'%H:00') AS HORA_FULL,  ".
            "             p.fecha_estado AS FECHAESTADO  ".
            "        FROM portalbd.pedidos p  ".
            "        where 1=1 ".
            "            and p.fecha_fin between '$fecha 00:00:00' and '$fecha 23:59:59' ".
            "            AND p.PEDIDO_ID!='' ) c1 ".
            "    group by c1.HORA ) C2 ".
            "    WHERE C2.HORA=h.HORA ) AS USUARIOS ".
            " , (SELECT  ".
            " 	COUNT(*) AS INGRESOS ".
            " 	FROM( SELECT ".
            " 	M.PEDIDO_ID ".
            " 	, MIN(M.FECHA_ESTADO) AS FECHA_ESTADO ".
            " 	, hour(MIN(M.FECHA_ESTADO)) AS HORA ".
            " 	FROM informe_petec_pendientesm M ".
            " 	WHERE 1=1 ".
            " 	AND M.FECHA_ESTADO between '$fecha 00:00:00' and '$fecha 23:59:59' ".
            "   AND M.CONCEPTO_ANTERIOR IN ('PETEC','PUMED','PEOPP','O-15', 'O-13', 'O-106', '19' ".
            "   ,'OKRED','21','70','65','ESTTX','DISPONIBILIDAD','CONSTRUCCION','COBERTURA','12','14','99','O-101','DISENO','OT-T04' ".
            "   , 'OT-T01', 'OT-T05', 'OT-C11','OT-C08') ".
            " 	GROUP BY M.PEDIDO_ID ) ING ".
            "    where ING.HORA=h.HORA ".
            " 	GROUP BY ING.HORA) as INGRESOS ".
            " , (SELECT ".
            " 	COUNT(*) AS GESTIONADO ".
            " 	FROM (SELECT  ".
            " 		p.pedido_id, ".
            " 		 MAX(p.fecha_fin) AS FECHAFIN, ".
            " 		 DATE_FORMAT(MAX(p.fecha_fin),'%H') AS HORA, ".
            " 		 DATE_FORMAT(MAX(p.fecha_fin),'%H:00') AS HORA_FULL, ".
            " 		 MAX(p.fecha_estado) AS FECHAESTADO ".
            " 	FROM portalbd.pedidos p ".
            " 	where 1=1 ".
            " 	and p.fecha_fin between '$fecha 00:00:00' and '$fecha 23:59:59' ".
            " 	and p.actividad='ESTUDIO' ".
            " 	AND p.PEDIDO_ID!='' ".
            " 	GROUP BY p.pedido_id ) GES ".
            "     where GES.HORA=h.HORA ".
            " 	GROUP BY GES.HORA) as ASIGNADOS ".
            " , (SELECT ".
            " 	COUNT(*) AS GESTIONADOS ".
            " 	FROM (SELECT  ".
            " 	n.OFERTA as PEDIDO_ID ".
            " 	, max(n.fecha_fin) as FECHA_FIN ".
            " 	, HOUR(MAX(n.fecha_fin)) AS HORA ".
            " 	, DATE_FORMAT(MAX(n.fecha_fin),'%H:00') AS HORA_FULL ".
            " 	FROM portalbd.transacciones_nca n ".
            " 	where n.fecha_fin between '$fecha 00:00:00' and '$fecha 23:59:59' ".
            " 	group by n.OFERTA ) SI ".
            "     WHERE SI.HORA=h.HORA ".
            " 	GROUP BY SI.HORA) AS SIEBEL ".
            " , (SELECT ".
            " 	COUNT(*) AS GESTIONADO ".
            " 	FROM (SELECT  ".
            " 		p.pedido_id, ".
            " 		 MAX(p.fecha_fin) AS FECHAFIN, ".
            " 		 DATE_FORMAT(MAX(p.fecha_fin),'%H') AS HORA, ".
            " 		 DATE_FORMAT(MAX(p.fecha_fin),'%H:00') AS HORA_FULL, ".
            " 		 MAX(p.fecha_estado) AS FECHAESTADO ".
            " 	FROM portalbd.pedidos p ".
            " 	where 1=1 ".
            " 	and p.fecha_fin between '$fecha 00:00:00' and '$fecha 23:59:59' ".
            " 	and p.actividad='RECONFIGURACION' ".
            " 	AND p.PEDIDO_ID!='' ".
            " 	GROUP BY p.pedido_id ) GES ".
            "     where GES.HORA=h.HORA ".
            " 	GROUP BY GES.HORA) as RECONFIGURADOS ".
            " FROM portalbd.informe_petec_horam h ".
            " where 1=1 ".
            " and h.FECHA='$fecha' ".
            " and h.CONCEPTO='PENDIENTES') G ";

        $rst = $this->mysqli->query($query);
        if ($rst->num_rows > 0){
            $resultado=array();
            while($row=$rst->fetch_assoc()){
                $resultado[]=$row;
            }
            $this->response($this->json($resultado), 201);
        }else{
            $error="Sin registros";
            $this->response($this->json($error), 204);
        }

    }//-----------------------------------------------Funcion para productividad el grupo de asignaciones cada hora
    /**
     * Funcion para que los pedidos tengan prioridad absoluta.
     */
    private function otorgarPrioridadAbsoluta(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $params         = json_decode(file_get_contents('php://input'),true);
        $prioridad      = $params['prioridad'];
        $pedido         = $params['pedido_id'];
        $usuario_id     = $params['usuario_id'];
        $today          = date("Y-m-d H:i:s");
        $multiple       = $params['multiple'];

        if($multiple){
            $in_stmt = "'".str_replace(",", "','", $pedido)."'";
            $paramlst = " and PEDIDO_ID in (".$in_stmt.") ";
        }else{
            $paramlst = " and PEDIDO_ID='$pedido' ";
        }

        if($prioridad){
            $prioridad='ARBOL';
        }else{
            $prioridad='NO';
        }


        $query= " update portalbd.informe_petec_pendientesm ".
            " set RADICADO_TEMPORAL='$prioridad' ".
            " where 1=1 ".
            " and STATUS='PENDI_PETEC' ".
            " $paramlst ";


        $rst = $this->mysqli->query($query);

        if($rst===TRUE){
            $cant = $this->mysqli->affected_rows;
            $msg = "($cant) Pedidos actualizados";

            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuario_id')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$pedido' ".
                ",'PRIORIZO PEDIDO' ".
                ",'$prioridad' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            // echo $sql_log;
            $rlog = $this->mysqli->query($sql_log);

            $this->response($this->json(array($msg)), 201);

        }else{
            $msg = "No se pudo dar prioridad";
            $this->response($this->json(array($msg)), 403);
        }

    }//-----------------------------------------------Fin funcion
    /**
     * Funcion para que los pedidos tengan prioridad absoluta.
     */
    private function otorgarPrioridadAbsolutaAgen(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $params         = json_decode(file_get_contents('php://input'),true);
        $prioridad      = $params['prioridad'];
        $pedido         = $params['pedido_id'];
        $usuario_id     = $params['usuario_id'];
        $today          = date("Y-m-d H:i:s");
        $multiple       = $params['multiple'];

        if($multiple){
            $in_stmt = "'".str_replace(",", "','", $pedido)."'";
            $paramlst = " and PEDIDO_ID in (".$in_stmt.") ";
        }else{
            $paramlst = " and PEDIDO_ID='$pedido' ";
        }

        if($prioridad){
            $prioridad='ARBOL';
        }else{
            $prioridad='NO';
        }

        $query= " update portalbd.gestor_pendientes_reagendamiento ".
            " set RADICADO='$prioridad' ".
            " where 1=1 ".
            " $paramlst ";

        $rst = $this->mysqli->query($query);
        if($rst===TRUE){
            $cant = $this->mysqli->affected_rows;
            $msg = "($cant) Pedidos actualizados";
            //$msg="Prioridad Actualizada";

            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuario_id')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'$pedido' ".
                ",'PRIORIZO PEDIDO AGENDAMIENTO' ".
                ",'$prioridad' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            // echo $sql_log;
            $rlog = $this->mysqli->query($sql_log);

            $this->response($this->json(array($msg)), 201);

        }else{
            $msg = "No se pudo dar prioridad";
            $this->response($this->json(array($msg)), 403);
        }

    }//-----------------------------------------------Fin funcion

    /**
     * Funcion para editar el stado de los Pedidos
     * Malo - Pendi_petec - Cerrado_petec
     */
    private function actualizarSatusPedidosAsignacion(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $params         = json_decode(file_get_contents('php://input'),true);
        $idped          = $params['id'];
        $pedido         = $params['pedido'];
        $status         = $params['status'];
        $usuario_id     = $params['usuario'];
        $obs            = $params['obs'];
        $updateobs      = $params['updateobs'];
        $today          = date("Y-m-d H:i:s");

        if($updateobs){

            $query= " update portalbd.pedidos ".
                " set OBSERVACIONES_PROCESO='$obs' ".
                " , MOTIVO_MALO = '$obs' ".
                " where PEDIDO_ID='$pedido' and ESTADO_ID='MALO' ";

        }else{
            $query= " update portalbd.informe_petec_pendientesm ".
                " set STATUS='$status' ".
                " where ID='$idped' and PEDIDO_ID='$pedido' ";

        }



        $rst = $this->mysqli->query($query);
        if($rst===TRUE){
            $msg="Status Actualizado";

            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuario_id')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'ACTUALIZAR' ".
                ",'$pedido' ".
                ",'ACTUALIZO ESTADO' ".
                ",'$status' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            // echo $sql_log;
            $rlog = $this->mysqli->query($sql_log);

            $this->response($this->json(array($msg)), 201);

        }else{
            $msg = "No se pudo actualizar";
            $this->response($this->json(array($msg)), 403);
        }

    }//-----------------------------------------------Fin funcion
    private function actualizarSatusPedidosAgendamiento(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $params = json_decode(file_get_contents('php://input'),true);
        $idped = $params['id'];
        $pedido = $params['pedido'];
        $status = $params['status'];
        $usuario_id = $params['usuario'];
        $today = date("Y-m-d H:i:s");

        $query= " update portalbd.gestor_pendientes_reagendamiento ".
            " set STATUS='$status' ".
            " where ID='$idped' and PEDIDO_ID='$pedido' ";

        $rst = $this->mysqli->query($query);
        if($rst===TRUE){
            $msg="Status Actualizado";

            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuario_id')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'ACTUALIZAR' ".
                ",'$pedido' ".
                ",'ACTUALIZO ESTADO' ".
                ",'$status' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            // echo $sql_log;
            $rlog = $this->mysqli->query($sql_log);

            $this->response($this->json(array($msg)), 201);

        }else{
            $msg = "No se pudo actualizar";
            $this->response($this->json(array($msg)), 403);
        }

    }//-----------------------------------------------Fin funcion

    /**
     * Funcion de Prueba, Borrar luego.
     * Descripcion: Funcion para recrear la tabla de Ocupacion de Agendas
     * Grupo: Agendamiento
     */
    private function GenerarOcupacionAgendas(){
        /**
         * Pasos:
         * 1. Truncamos la tabla donde se almacenara la info
         * 2. Traemos e insertamos las microzonas del modulo de agendamiento - Fuente MAGENDA
         * 3. Traemos e insertamos las microzonas del modulo de Siebel - Fuente SIEBEL
         * 4. Traemos e insertamos los pedidos con fecha cita de hoy en adelante de Modulo.
         */

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $conna = getConnAgendamiento();
        $this->dbConnect03();
        $today = date("Y-m-d");
        $iZa=0;
        $time_start = microtime(true);

        // 1.
        $trucanteTable = "truncate table portalbd.go_agen_microzonas";
        $trucanteTableo = "truncate table portalbd.go_agen_ocupacionmicrozonas";
        $rTruncm = $this->mysqli->query($trucanteTable);
        $rTrunco = $this->mysqli->query($trucanteTableo);

        //2. desde Subzonas
        $sqlZonasAgendamiento = " 	SELECT ".
            " CONCAT(SUBSTR(C1.DEPARTAMENTO,1,2),SUBSTR(C1.CIUDAD,1,2),SUBSTR(C1.ZONA,1,2),C1.MICROZONA,'_MODULO') AS IDZONA ".
            " , C1.DEPARTAMENTO ".
            " , C1.CIUDAD ".
            " , C1.ZONA ".
            " , C1.MICROZONA ".
            " , 'MODULO' as FUENTE ".
            " FROM(SELECT ".
            " 	upper(d.dep_departamento) as DEPARTAMENTO ".
            " ,	IFNULL(UPPER(c.cda_ciudad),'SIN_CIUDAD') AS CIUDAD ".
            " ,	CASE   ".
            " 		WHEN (d.dep_departamento = 'Antioquia'   ".
            " 			AND sz.sbz_subzona IN ('AMERICA','BUENOS_AIR','CEN','CENTRO','COLON','MIRAFLORES','NUTIBARA','OTRABANDA','SAN_BERN_1','SAN_BERN_2','SAN_JAVIER','VILLAHERMO'))  ".
            " 		THEN 'CENTRO'  ".
            " 		WHEN d.dep_departamento = 'Antioquia'     ".
            " 			AND sz.sbz_subzona IN ('BARCOPGIR','BELLO_1','BELLO_2','BELLO_3','BERLIN','BOSQUE_1','BOSQUE_2','CARIBE','CASTILLA','FLORENCIA','GIR','IGUANA','IGUSANCRI','NIQUIA','NOR')   ".
            " 		THEN 'NORTE'  ".
            " 		WHEN d.dep_departamento = 'Antioquia'     ".
            " 			AND sz.sbz_subzona IN ('CALDAS','ENVIGADO_1','ENVIGADO_2','ENVIGADO_3','ESTRELLA','GUAYABAL','ITAGUI_1','ITAGUI_2','ITAGUI_3','POBLADO_1','POBLADO_2','SABANETA','SANANTPRA','SUR-ENV','SUR','SUR-SAB')   ".
            " 		THEN 'SUR'     ".
            " 		WHEN d.dep_departamento = 'Antioquia'     ".
            " 			AND sz.sbz_subzona IN ('M1_ORIENTE', 'M2_ORIENTE', 'M3_ORIENTE', 'M4_ORIENTE' , 'M5_ORIENTE' ,'M6_ORIENTE','M7_ORIENTE','M8_ORIENTE','RIO', 'PALMAS', 'SANTAELENA')   ".
            " 		THEN 'ORIENTE'      ".
            " 		WHEN sz.sbz_subzona IN ('CAR','M1_CARTAGE','M2_CARTAGE','M3_CARTAGE','M4_CARTAGE','M5_CARTAGE') THEN 'CARTAGENA'  ".
            " 		WHEN sz.sbz_subzona IN ('TUR', 'M6_CARTAGE')  THEN 'TURBACO'   ".
            " 		WHEN sz.sbz_subzona IN ('CAN','DEFAULT','ENG','QCA','SUB','NORTE') THEN 'BOGOTA NORTE'    ".
            " 		WHEN sz.sbz_subzona IN ('BOSA','ECA','FRG','TIMIZA','SUR') THEN 'BOGOTA SUR'   ".
            " 		WHEN sz.sbz_subzona IN ('VAL','Valle del Cauca') THEN 'CALI'     ".
            " 		WHEN sz.sbz_subzona = 'PAL' THEN 'PALMIRA'   ".
            " 		WHEN sz.sbz_subzona = 'JAM' THEN 'JAMUNDI'   ".
            " 		WHEN d.dep_departamento IN ('Bolivar','Atlantico','Cundinamarca','Valle del Cauca') THEN UPPER(d.dep_departamento)  ".
            "         else 'SIN_ZONA' ".
            " END AS ZONA ".
            " , 	upper(sz.sbz_subzona) as MICROZONA ".
            " FROM dbAgendamiento.agn_subzonas sz ".
            " INNER join dbAgendamiento.agn_departamentos d on sz.sbz_departamento=d.dep_id ".
            " INNER join dbAgendamiento.agn_ciudades c on sz.sbz_ciudad=c.cda_id ".
            " order by 1, 2,3,4 asc ) C1 ".
            " GROUP BY  ".
            " C1.DEPARTAMENTO ".
            " , C1.CIUDAD ".
            " , C1.ZONA ".
            " , C1.MICROZONA ";

        $rSZA = $conna->query($sqlZonasAgendamiento);

        if($rSZA->num_rows > 0){
            //$result = array();

            while($row = $rSZA->fetch_assoc()){

                $sqlinsert=" INSERT INTO portalbd.go_agen_microzonas ".
                    " ( IDZONA, DEPARTAMENTO, CIUDAD, ZONA, MICROZONA, FUENTE) ".
                    " VALUES ".
                    " ('".$row['IDZONA']."','".$row['DEPARTAMENTO']."','".$row['CIUDAD']."','".$row['ZONA']."','".$row['MICROZONA']."','".$row['FUENTE']."') ";
                $rInsertSZA = $this->mysqli->query($sqlinsert);
                $rowsA=$this->mysqli->affected_rows;
                if($rowsA==1){
                    ++$iZa;
                }
            }

        }
        // Desde Ocupados con fecha cita
        $sqlZonasAgenOcu = 	"select CONCAT(SUBSTR(C1.DEPARTAMENTO,1,2),SUBSTR(C1.CIUDAD,1,2),SUBSTR(C1.ZONA,1,2),C1.MICROZONA,'_MODULO') AS IDZONA ".
            " , C1.DEPARTAMENTO ".
            " , C1.CIUDAD ".
            " , C1.ZONA ".
            " , C1.MICROZONA ".
            " , 'MODULO' as FUENTE ".
            " from(SELECT ".
            " a.agm_id as IDGENDAMIENTO ".
            " , a.agm_pedido as PEDIDO_ID ".
            " , upper(d.dep_departamento) as DEPARTAMENTO ".
            " , ifnull(upper(c.cda_ciudad),'SIN_CIUDAD') as CIUDAD ".
            " , a.agm_fechacita as FECHA_CITA ".
            " , case ".
            " 	when a.agm_jornadacita='Hora Fija' then 'HF'  ".
            "     else a.agm_jornadacita ".
            " 	end as JORNADA ".
            " , a.agm_agenda as AGENDAID ".
            " , a.agm_segmento as UEN ".
            " ,	CASE   ".
            " 		WHEN (d.dep_departamento = 'Antioquia'   ".
            " 			AND a.agm_microzona IN ('AMERICA','BUENOS_AIR','CEN','CENTRO','COLON','MIRAFLORES','NUTIBARA','OTRABANDA','SAN_BERN_1','SAN_BERN_2','SAN_JAVIER','VILLAHERMO'))  ".
            " 		THEN 'CENTRO'  ".
            " 		WHEN d.dep_departamento = 'Antioquia'     ".
            " 			AND a.agm_microzona IN ('BARCOPGIR','BELLO_1','BELLO_2','BELLO_3','BERLIN','BOSQUE_1','BOSQUE_2','CARIBE','CASTILLA','FLORENCIA','GIR','IGUANA','IGUSANCRI','NIQUIA','NOR')   ".
            " 		THEN 'NORTE'  ".
            " 		WHEN d.dep_departamento = 'Antioquia'     ".
            " 			AND a.agm_microzona IN ('CALDAS','ENVIGADO_1','ENVIGADO_2','ENVIGADO_3','ESTRELLA','GUAYABAL','ITAGUI_1','ITAGUI_2','ITAGUI_3','POBLADO_1','POBLADO_2','SABANETA','SANANTPRA','SUR-ENV','SUR','SUR-SAB')   ".
            " 		THEN 'SUR'     ".
            " 		WHEN d.dep_departamento = 'Antioquia'     ".
            " 			AND a.agm_microzona IN ('M1_ORIENTE', 'M2_ORIENTE', 'M3_ORIENTE', 'M4_ORIENTE' , 'M5_ORIENTE' ,'M6_ORIENTE','M7_ORIENTE','M8_ORIENTE','RIO', 'PALMAS', 'SANTAELENA')   ".
            " 		THEN 'ORIENTE'      ".
            " 		WHEN a.agm_microzona IN ('CAR','M1_CARTAGE','M2_CARTAGE','M3_CARTAGE','M4_CARTAGE','M5_CARTAGE') THEN 'CARTAGENA'  ".
            " 		WHEN a.agm_microzona IN ('TUR', 'M6_CARTAGE')  THEN 'TURBACO'   ".
            " 		WHEN a.agm_microzona IN ('CAN','DEFAULT','ENG','QCA','SUB','NORTE') THEN 'BOGOTA NORTE'    ".
            " 		WHEN a.agm_microzona IN ('BOSA','ECA','FRG','TIMIZA','SUR') THEN 'BOGOTA SUR'   ".
            " 		WHEN a.agm_microzona IN ('VAL','Valle del Cauca') THEN 'CALI'     ".
            " 		WHEN a.agm_microzona = 'PAL' THEN 'PALMIRA'   ".
            " 		WHEN a.agm_microzona = 'JAM' THEN 'JAMUNDI'   ".
            " 		WHEN d.dep_departamento IN ('Bolivar','Atlantico','Cundinamarca','Valle del Cauca') THEN UPPER(d.dep_departamento)  ".
            "         else 'SIN_ZONA' ".
            " END AS ZONA ".
            " , case ".
            " 	 WHEN (a.agm_microzona='' or a.agm_microzona is null )then 'MICRODEFAULT'  ".
            " 	else upper(a.agm_microzona) ".
            " end as MICROZONA ".
            " , case ".
            " 	 WHEN (sbag.sag_prioridad='' or sbag.sag_prioridad is null )then 'SIN_PRIORIDAD'  ".
            " 	else upper(sbag.sag_prioridad) ".
            " end as PRIORIDAD ".
            " FROM dbAgendamiento.agn_agendamientos a ".
            " left join agn_subagendas sbag on a.agm_agenda = sbag.sag_id ".
            " left join agn_agendas ag on sbag.sag_agenda = ag.ads_id ".
            " left join agn_departamentos d on a.agm_departamento=d.dep_id ".
            " left join agn_ciudades c on a.agm_ciudad=c.cda_id ".
            " where 1=1 ".
            " and (a.agm_estadototal not in ('Anulado','Cumplido') ".
            " or a.agm_estadototal='') ".
            " and a.agm_fechacita >= CURDATE() ) C1 ".
            " GROUP BY  ".
            "  C1.DEPARTAMENTO ".
            " , C1.CIUDAD ".
            " , C1.ZONA ".
            " , C1.MICROZONA ";

        $rSZAOcu = $conna->query($sqlZonasAgenOcu);

        if($rSZAOcu->num_rows > 0){
            //$result = array();

            while($row = $rSZAOcu->fetch_assoc()){

                $sqlinsert=" INSERT INTO portalbd.go_agen_microzonas ".
                    " ( IDZONA, DEPARTAMENTO, CIUDAD, ZONA, MICROZONA, FUENTE) ".
                    " VALUES ".
                    " ('".$row['IDZONA']."','".$row['DEPARTAMENTO']."','".$row['CIUDAD']."','".$row['ZONA']."','".$row['MICROZONA']."','".$row['FUENTE']."') ";
                $rInsertSZAOcu = $this->mysqli->query($sqlinsert);
                $rowsB=$this->mysqli->affected_rows;
                if($rowsB==1){
                    ++$iZa;
                }
            }
        }

        //3.
        $sqlZonasSiebel = " SELECT ".
            " CONCAT(SUBSTR(DEPARTAMENTO,1,2),ZONA,'MICRO_SIEBEL') AS IDZONA ".
            " , DEPARTAMENTO ".
            " , ZONA as CIUDAD ".
            " , ZONA ".
            " , 'MICRODEFAULT' as MICROZONA ".
            " , 'SIEBEL' as FUENTE ".
            " FROM alistamiento.parametrizacion_siebel ".
            " group by  ".
            " DEPARTAMENTO,  ".
            " ZONA ";

        $rSZS = $this->mysqli03->query($sqlZonasSiebel);

        if($rSZS->num_rows > 0){

            while($row = $rSZS->fetch_assoc()){

                $sqlinsert=" INSERT INTO portalbd.go_agen_microzonas ".
                    " ( IDZONA, DEPARTAMENTO, CIUDAD, ZONA, MICROZONA, FUENTE) ".
                    " VALUES ".
                    " ('".$row['IDZONA']."','".$row['DEPARTAMENTO']."','".$row['CIUDAD']."','".$row['ZONA']."','".$row['MICROZONA']."','".$row['FUENTE']."') ";
                $rInsertSZS = $this->mysqli->query($sqlinsert);
                $rowsC=$this->mysqli->affected_rows;
                if($rowsC==1){
                    ++$iZa;
                }
            }
            $smg1=$iZa." Microzonas Insertadas";

        }

        //4.
        $sqlOcuModulo = " 	SELECT ".
            " C1.PEDIDO_ID ".
            " , C1.FECHA_CITA ".
            " , C1.JORNADA ".
            " , C1.UEN ".
            " , C1.PRIORIDAD ".
            " , C1.DEPARTAMENTO ".
            " , C1.CIUDAD ".
            " , C1.ZONA ".
            " , C1.MICROZONA ".
            " , C1.FUENTE ".
            " FROM (SELECT ".
            "  a.agm_pedido as PEDIDO_ID ".
            "  , a.agm_fechacita as FECHA_CITA ".
            " , case ".
            " 	when a.agm_jornadacita='Hora Fija' then 'HF'  ".
            "     else a.agm_jornadacita ".
            " 	end as JORNADA ".
            " , a.agm_segmento as UEN ".
            " , case ".
            " 	 WHEN (sbag.sag_prioridad='' or sbag.sag_prioridad is null )then 'SIN_PRIORIDAD'  ".
            " 	else upper(sbag.sag_prioridad) ".
            " end as PRIORIDAD ".
            " , upper(d.dep_departamento) as DEPARTAMENTO ".
            " , ifnull(upper(c.cda_ciudad),'SIN_CIUDAD') as CIUDAD ".
            " ,	CASE   ".
            " 		WHEN (d.dep_departamento = 'Antioquia'   ".
            " 			AND a.agm_microzona IN ('AMERICA','BUENOS_AIR','CEN','CENTRO','COLON','MIRAFLORES','NUTIBARA','OTRABANDA','SAN_BERN_1','SAN_BERN_2','SAN_JAVIER','VILLAHERMO'))  ".
            " 		THEN 'CENTRO'  ".
            " 		WHEN d.dep_departamento = 'Antioquia'     ".
            " 			AND a.agm_microzona IN ('BARCOPGIR','BELLO_1','BELLO_2','BELLO_3','BERLIN','BOSQUE_1','BOSQUE_2','CARIBE','CASTILLA','FLORENCIA','GIR','IGUANA','IGUSANCRI','NIQUIA','NOR')   ".
            " 		THEN 'NORTE'  ".
            " 		WHEN d.dep_departamento = 'Antioquia'     ".
            " 			AND a.agm_microzona IN ('CALDAS','ENVIGADO_1','ENVIGADO_2','ENVIGADO_3','ESTRELLA','GUAYABAL','ITAGUI_1','ITAGUI_2','ITAGUI_3','POBLADO_1','POBLADO_2','SABANETA','SANANTPRA','SUR-ENV','SUR','SUR-SAB')   ".
            " 		THEN 'SUR'     ".
            " 		WHEN d.dep_departamento = 'Antioquia'     ".
            " 			AND a.agm_microzona IN ('M1_ORIENTE', 'M2_ORIENTE', 'M3_ORIENTE', 'M4_ORIENTE' , 'M5_ORIENTE' ,'M6_ORIENTE','M7_ORIENTE','M8_ORIENTE','RIO', 'PALMAS', 'SANTAELENA')   ".
            " 		THEN 'ORIENTE'      ".
            " 		WHEN a.agm_microzona IN ('CAR','M1_CARTAGE','M2_CARTAGE','M3_CARTAGE','M4_CARTAGE','M5_CARTAGE') THEN 'CARTAGENA'  ".
            " 		WHEN a.agm_microzona IN ('TUR', 'M6_CARTAGE')  THEN 'TURBACO'   ".
            " 		WHEN a.agm_microzona IN ('CAN','DEFAULT','ENG','QCA','SUB','NORTE') THEN 'BOGOTA NORTE'    ".
            " 		WHEN a.agm_microzona IN ('BOSA','ECA','FRG','TIMIZA','SUR') THEN 'BOGOTA SUR'   ".
            " 		WHEN a.agm_microzona IN ('VAL','Valle del Cauca') THEN 'CALI'     ".
            " 		WHEN a.agm_microzona = 'PAL' THEN 'PALMIRA'   ".
            " 		WHEN a.agm_microzona = 'JAM' THEN 'JAMUNDI'   ".
            " 		WHEN d.dep_departamento IN ('Bolivar','Atlantico','Cundinamarca','Valle del Cauca') THEN UPPER(d.dep_departamento)  ".
            "         else 'SIN_ZONA' ".
            " END AS ZONA ".
            " , case ".
            " 	 WHEN (a.agm_microzona='' or a.agm_microzona is null )then 'MICRODEFAULT'  ".
            " 	else upper(a.agm_microzona) ".
            " end as MICROZONA ".
            " , 'FENIX_NAL' as FUENTE ".
            " FROM dbAgendamiento.agn_agendamientos a ".
            " left join agn_subagendas sbag on a.agm_agenda = sbag.sag_id ".
            " left join agn_agendas ag on sbag.sag_agenda = ag.ads_id ".
            " left join agn_departamentos d on a.agm_departamento=d.dep_id ".
            " left join agn_ciudades c on a.agm_ciudad=c.cda_id ".
            " where 1=1 ".
            " and (a.agm_estadototal not in ('Anulado','Cumplido') ".
            " or a.agm_estadototal='') ".
            " and a.agm_fechacita >= CURDATE() ) C1 ";

        $rOcuModulo = $conna->query($sqlOcuModulo);

        if($rOcuModulo->num_rows > 0){
            $iOcM=0;
            $ii=0;
            $data=array();
            $sepp="";
            $fields="";
            //2017-02-03 Mauricio: tener los nombres de los campos en una variable
            $sep="";
            while ($property = mysqli_fetch_field($rOcuModulo)) {
                $fields .=$sep.$property->name;
                $sep=",";
            }
            //$fields .="";

            $subinsert="";
            while($row = $rOcuModulo->fetch_assoc()){
                ++$iOcM;
                $data[]=$row;

                //$subinsert=$sqlinsert;
                $sep="";
                $tmpinsert=" (";
                foreach ($row as $item) {
                    //$subinsert="$subinsert $sep '$item'";
                    $tmpinsert="$tmpinsert  $sep '$item' ";
                    $sep=",";
                }
                $subinsert.="$sepp $tmpinsert) ";

                $ii++;
                $sepp=",";
                if($ii % 1300 == 0){
                    echo "Carga: $ii<br>";
                    $subinsert="insert into go_agen_ocupacionmicrozonas ($fields) values $subinsert";
                    $this->mysqli->query($subinsert);
                    $subinsert="";
                    $sepp="";
                    //echo $subinsert."<br>";

                }
            }


            $smg2=$iOcM." Pedidos Insertados";
            $time_end = microtime (true);
            $time = $time_end - $time_start;
            $this->response ($this->json (array($smg1, $smg2, $time)), 200); // send user details

        }

        $this->response('',403);        // If no records "No Content" status

    }

    /**
     * Funcion para traer el usuario y la fecha de la auditoria de pedidos en O-XXX
     */
    private function buscarPedidoAuditarFenix(){
        if($this->get_request_method() != "GET"){
            $this->response('Metodo no soportado',406);
        }

        $pedido = $this->_request['pedido'];

        $this->dbFenixSTBYConnect();
        $connfstby=$this->connfstby;

        $sqlfenix= " SELECT ".
            " C1.PEDIDO_ID ".
            " , RTRIM(REGEXP_REPLACE((LISTAGG(C1.USUARIO_ID,'-') WITHIN GROUP (ORDER BY C1.USUARIO_ID asc)) ,   ".
            "     '([^-]*)(-\\1)+($|-)', '\\1\\3'),'-') AS USUARIOS ".
            " , RTRIM(REGEXP_REPLACE((LISTAGG(C1.FECHA,',') WITHIN GROUP (ORDER BY C1.USUARIO_ID asc)) ,   ".
            "     '([^,]*)(,\\1)+($|,)', '\\1\\3'),',') AS FECHAS ".
            " , REGEXP_COUNT((RTRIM(REGEXP_REPLACE((LISTAGG(C1.USUARIO_ID,'-') WITHIN GROUP (ORDER BY C1.USUARIO_ID asc)) ,  ".
            "     '([^-]*)(-\\1)+($|-)', '\\1\\3'),'-')),'-')+1 AS CANTIDADUSERS ".
            " FROM ( ".
            " SELECT ".
            " PEDIDO_ID ".
            " , CASE ".
            "   WHEN USUARIO_ID IN ('SYS', 'FENIX') THEN 'AUTOMATICO' ".
            "   ELSE USUARIO_ID ".
            " END AS USUARIO_ID ".
            " , TO_CHAR(FECHA,'RRRR-MM-DD') AS FECHA ".
            " FROM FNX_NOVEDADES_SOLICITUDES ".
            " WHERE PEDIDO_ID='$pedido' ".
            " AND (CONCEPTO_ID_ANTERIOR='PETEC' ".
            " OR CONCEPTO_ID_ANTERIOR='OKRED' ".
            " OR CONCEPTO_ID_ANTERIOR='PEXPQ' ".
            " OR CONCEPTO_ID_ANTERIOR='PRESI' ".
            " OR CONCEPTO_ID_ANTERIOR='APPRV') ".
            " AND CONCEPTO_ID_ACTUAL IN ('PORDE', 'PSERV','PSIEB') ".
            " AND USUARIO_ID NOT IN ('USRFRABTS','ATCFENIX') ) C1 ".
            " GROUP BY C1.PEDIDO_ID ";

        $stid = oci_parse($connfstby, $sqlfenix);

        oci_execute($stid);
        $data=array();

        while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
            $data[]=$row;

        }

        //var_dump($data);
        if(empty($data)){
            $smg1 = "Sin registros";
            $this->response ($this->json (array($smg1,'')), 403); // send user details
        }else{
            $this->response ($this->json ($data), 200); // send user details
        }

    }
    /**
     * Funcin para guardar la gestion de Asignaciones.
     * */
    private function guardarGestionAsignaciones()
    {
        if ($this->get_request_method () != "POST") {
            $this->response ('', 406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr ($usuarioIp);
        $galleta        =   json_decode (stripslashes ($_COOKIE['logedUser']), true);
        $galleta        =   stripslashes ($_COOKIE['logedUser']);
        $galleta        =   json_decode ($galleta);
        $galleta        =   json_decode (json_encode ($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $gestion        =   json_decode (file_get_contents ("php://input"), true);
        $fechaServidor  =   date("Y-m-d H:i:s");
        $usuario        =   $gestion['gestion']['user'];
        $fuente         =   $gestion['gestion']['fuente'];
        $estado         =   $gestion['gestion']['ESTADO_ID'];
        $programacion   =   $gestion['gestion']['horaLlamar'];
        $pedido         =   $gestion['gestion']['pedido'];
        $conceptoId     =   $gestion['gestion']['CONCEPTO_ANTERIOR'];
        $idpedido       =   $gestion['gestion']['ID'];
        $crIncidente    =   $gestion['gestion']['INCIDENTE'];

        $malo           =   false;
        $programado     =   false;
        $cerrar         =   true;
        $guardar        =   false;
        $mysqlerror     =   "";
        $error          =   "";
        $sqlupdate      =   "";
        $columns        =   '';
        $values         =   '';


        /**
        + 2017-04-26: check para evitar que se guarde el registro de Reconfiguracion si el nuevo pedido no esta agendado(solo sistema Fenix)
        + Mauricio.
         */

        $pedidonuevo=$gestion['gestion']['nuevopedido'];
        $gestion['gestion']['fecha_cita']="SIN AGENDA";

        if($conceptoId=="14" && $pedidonuevo!=""){
            $conna=getConnAgendamiento();
            $today=date("Y-m-d");

            $sqlfechacita="SELECT agm_fechacita FROM dbAgendamiento.agn_agendamientos where agm_pedido = '$pedidonuevo' ".
                " and agm_fechacita > '$today' ";

            if ($result2 = $conna->query($sqlfechacita)) {
                if($obj = $result2->fetch_object()){
                    if( $obj->agm_fechacita=="0000-00-00"){
                        $gestion['gestion']['fecha_cita']="SIN AGENDA";
                    }else{
                        $gestion['gestion']['fecha_cita']=$obj->agm_fechacita;
                    }

                    /**
                    [OK]: Tiene una fecha cita futura, se puede guardar el pedido
                     */
                }else{
                    //me debo devolver de aca ya que el pedido no ha sido agendado.....
                    $error="El pedido $pedidonuevo no tiene agenda para futuro. Por favor agendar.";
                    //$this->response ($this->json (array($error, $fuente, $estado, $malo, $programado)), 400);
                    //return;
                }
            }
        }

        $column_names = array('pedido', 'fuente', 'actividad', 'ESTADO_ID', 'OBSERVACIONES_PROCESO', 'estado', 'user','duracion','INCIDENTE','fecha_inicio','fecha_fin','PEDIDO_ID','SUBPEDIDO_ID','SOLICITUD_ID','MUNICIPIO_ID','CONCEPTO_ANTERIOR','idllamada','nuevopedido','motivo_malo','fecha_estado','concepto_final','source','fecha_cita','DEPARTAMENTO','TIPO_TRABAJO','TECNOLOGIA_ID');
        $keys = array_keys($gestion['gestion']);

        if($usuario='undefined' || $usuario=''){$usuario = $usuarioGalleta;}
        if($programacion!=="SIN"){$programado = true;}
        if($estado=='MALO'){$malo = true;}

        if($malo){
            $sqlupdate = "update informe_petec_pendientesm set FECHA_FINAL='$fechaServidor',STATUS='$estado',ASESOR='' WHERE ID=$idpedido";
            $varFeed = "GUARDO PEDIDO MALO";
            $cerrar = false;
        }
        if($programado){

            $sqlupdate="update informe_petec_pendientesm set PROGRAMACION='$programacion', RADICADO_TEMPORAL='NO',ASESOR='', STATUS='PENDI_PETEC' WHERE STATUS in ('PENDI_PETEC','MALO') and PEDIDO_ID='$pedido' ";
            $varFeed = "PROGRAMO PEDIDO";
            $cerrar = false;

        }
        if($cerrar){
            $sqlupdate = "update informe_petec_pendientesm set FECHA_FINAL='$fechaServidor',STATUS='CERRADO_PETEC',ASESOR='' WHERE ID=$idpedido ";
            $varFeed = "GUARDO PEDIDO";

        }

        $rUpdate = $this->mysqli->query($sqlupdate);

        if (!$rUpdate) {
            $mysqlerror = $this->mysqli->error;
            $guardar = false;
        }

        if($fuente==="SIEBEL"){// Si el pedido viene de siebel
            $sqlNca =   " INSERT INTO portalbd.transacciones_nca ( ".
                " OFERTA, ".
                " MUNICIPIO_ID, ".
                " TRANSACCION, ".
                " ESTADO, ".
                " FECHA, ".
                " DURACION, ".
                " INCIDENTE, ".
                " FECHA_INICIO, ".
                " FECHA_FIN, ".
                " ESTADO_FINAL, ".
                " OBSERVACION, ".
                " USUARIO ".
                " ) VALUES (".
                " '".$gestion['gestion']['pedido']."' , ".
                " '".$gestion['gestion']['MUNICIPIO_ID']."'  , ".
                " '".$gestion['gestion']['TRANSACCION']."'   , ".
                " '".$gestion['gestion']['CONCEPTO_ID']."'   , ".
                " '".$gestion['gestion']['FECHA']."'   , ".
                " '".$gestion['gestion']['duracion']."'  , ".
                " '".$gestion['gestion']['INCIDENTE']."'  , ".
                " '".$gestion['gestion']['fecha_inicio']."' , ".
                " '".$gestion['gestion']['fecha_fin']."'  , ".
                " '".$gestion['gestion']['ESTADO_ID']."'   , ".
                " '".$gestion['gestion']['OBSERVACIONES_PROCESO']."'  , ".
                " '".$gestion['gestion']['user']."'  ".
                " ) ";
            //$insertNca = $this->mysqli->query($sqlNca);
            $guardar = true;
        }else{
            if($fuente==='FENIX_NAL'){// Si es fenix, vaya y mire si cambio de concepto
                //Revisamos en fenix si el concepto cambio
                $concepto_final=$this-> buscarConceptoFinalFenix($pedido);
                $gestion['gestion']['concepto_final'] = $concepto_final;
                $guardar = true;

            }else{ // Si no aplica, haga un guardado general.
                $guardar = true;
            }

        }



        if($guardar){//Si fue gestionado, Insertamos gestion en pedidos y mandamos JSON con respuesta.
            foreach($column_names as $desired_key){
                if(!in_array($desired_key, $keys)) {
                    $$desired_key = '';
                }else{
                    $$desired_key = $gestion['gestion'][$desired_key];
                }
                $columns = $columns.$desired_key.',';
                $values = $values."'".$gestion['gestion'][$desired_key]."',";
            }

            $queryGestion = "INSERT INTO pedidos(".trim($columns,',').") VALUES(".trim($values,',').")";

            $insertGestion = $this->mysqli->query($queryGestion);

            //Activiy Feed ------------------------------------------------------------------
            $sqlFeed =  "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuario')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'$estado' ".
                ",'$pedido' ".
                ",'$varFeed' ".
                ",'$conceptoId' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";
            $rFeed = $this->mysqli->query($sqlFeed);
            //------------------------------------------------------------------Activiy Feed



            $this->response ($this->json (array($malo, $programado,$programacion)), 200);
        }else{
            $error = "Error guardando: $mysqlerror";
            $this->response ($this->json (array($error, $fuente, $estado, $malo, $programado)), 403);
        }
    }

    private function buscarConceptoFinalFenix($pedidoBusqueda){

        $this->dbFenixConnect();
        $connf=$this->connf;


        $sqlfenix=" select ".
            " regexp_replace(LISTAGG(nsol.concepto_id_anterior,',') WITHIN GROUP (ORDER BY NULL),'([^,]+)(,\\1)+', '\\1' ) AS CONCEPTO_ID_ANTERIOR_FENIX  ".
            ", regexp_replace(LISTAGG(nsol.concepto_id_actual,',') WITHIN GROUP (ORDER BY NULL),'([^,]+)(,\\1)+', '\\1' ) AS CONCEPTO_FINAL ".
            ",MIN( to_char(nsol.fecha,'RRRR-MM-DD hh24:mi:ss')) as FECHA_FINAL ".
            ", MIN(nsol.usuario_id) as USUARIO_ID ".
            " from fnx_novedades_solicitudes nsol ".
            " where nsol.pedido_id='$pedidoBusqueda' ".
            " and nsol.consecutivo=(select max(a.consecutivo) from fenix.fnx_novedades_solicitudes a ".
            " where nsol.pedido_id=a.pedido_id(+) ".
            " and nsol.subpedido_id=a.subpedido_id(+) ".
            " and nsol.solicitud_id=a.solicitud_id(+)) ";

        $stid = oci_parse($connf, $sqlfenix);
        oci_execute($stid);
        if($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
            $conceptoFinal=$row['CONCEPTO_FINAL'];
            return $conceptoFinal;
        }else{//no cambio de concepto, controlar...

            return "SIN DATOS";
        }
    }

    /**
     * Funcin para listar y buscar pedidos que fueron Auditados
     * */
    private function listarBuscarPedidoAuditoriaAsignaciones()
    {
        if($this->get_request_method() != "GET"){
            $this->response('Metodo no soportado',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr ($usuarioIp);
        $galleta        =   json_decode (stripslashes ($_COOKIE['logedUser']), true);
        $galleta        =   stripslashes ($_COOKIE['logedUser']);
        $galleta        =   json_decode ($galleta);
        $galleta        =   json_decode (json_encode ($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $pedido         =   $this->_request['pedido'];
        $fechaini       =   $this->_request['fechaini'];
        $fechafin       =   $this->_request['fechafin'];
        $paramlst       =   "";
        $today          =   date("Y-m-d");

        if($fechaini=='SIN'){
            $fechaini = date("Y-m-d", strtotime("first day of previous month"));
        }
        if($fechafin=='SIN'){
            $fechafin = $today;
        }
        if($pedido=="TODO" || $pedido=="" || $pedido==null){
            $paramlst = " and FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
        }else{
            $in_stmt = "'".str_replace(",", "','", $pedido)."'";
            $paramlst = " and PEDIDO_ID in (".$in_stmt.") ";
        }

        $sql =  " SELECT ID, ".
            " FECHA_GESTION as FECHA_ESTUDIO ".
            " , PEDIDO_ID ".
            " , TIPO_ELEMENTO_ID ".
            " , USUARIO_ID_GESTION as USUARIO ".
            " , ANALISIS ".
            " , CONCEPTO_ACTUAL as CONCEPTO_AUDITORIA ".
            " , OBSERVACIONES ".
            " , FECHA_FIN AS FECHA_GESTION  ".
            " , USUARIO_ID ".
            " FROM portalbd.gestor_transacciones_oxxx ".
            " WHERE 1=1 ".
            " $paramlst order by ID desc limit 500 ";

        // echo $sql;
        $r = $this->mysqli->query($sql);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $row['OBSERVACIONES']=utf8_encode($row['OBSERVACIONES']);
                $result[] = $row;
            }

            $this->response($this->json(array($result)), 200); // send user details
        }else{
            $error = "No hay datos.";
            $this->response($this->json(array($error)), 403);
        }

    }

    private function csvAuditorias(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido         =   $this->_request['pedido'];
        $fechaini       =   $this->_request['fechaini'];
        $fechafin       =   $this->_request['fechafin'];
        $paramlst       =   "";
        $today          =   date("Y-m-d");

        if($fechaini=='SIN'){
            $fechaini = date("Y-m-d", strtotime("first day of previous month"));
        }
        if($fechafin=='SIN'){
            $fechafin = $today;
        }
        if($pedido=="TODO" || $pedido=="" || $pedido==null){
            $paramlst = " and FECHA_FIN between '$fechaini 00:00:00' and '$fechafin 23:59:59'";
        }else{
            $in_stmt = "'".str_replace(",", "','", $pedido)."'";
            $paramlst = " and PEDIDO_ID in (".$in_stmt.") ";
        }

        $filename="AuditoriasAsignaciones-$usuarioGalleta-$today.csv";

        $sql =  " SELECT  ".
            " FECHA_GESTION as FECHA_ESTUDIO ".
            " , PEDIDO_ID ".
            " , TIPO_ELEMENTO_ID ".
            " , USUARIO_ID_GESTION as USUARIO ".
            " , ANALISIS ".
            " , CONCEPTO_ACTUAL as CONCEPTO_AUDITORIA ".
            " , OBSERVACIONES ".
            " , FECHA_FIN AS FECHA_GESTION  ".
            " , USUARIO_ID  ".
            " FROM portalbd.gestor_transacciones_oxxx ".
            " WHERE 1=1 ".
            " $paramlst order by ID desc limit 500 ";

        $r = $this->mysqli->query($sql);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('FECHA_ESTUDIO','PEDIDO_ID','TIPO_ELEMENTO_ID','USUARIO',' ANALISIS',' CONCEPTO_AUDITORIA',' OBSERVACIONES','USUARIO_ID','FECHA_GESTION'));

            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);

            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO AUDITORIAS' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$usuarioGalleta)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }
    private function idpermisoslst()
    {
        if($this->get_request_method() != "GET"){
            $this->response('Metodo no soportado',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr ($usuarioIp);
        $galleta        =   json_decode (stripslashes ($_COOKIE['logedUser']), true);
        $galleta        =   stripslashes ($_COOKIE['logedUser']);
        $galleta        =   json_decode ($galleta);
        $galleta        =   json_decode (json_encode ($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $today          =   date("Y-m-d");

        $sql =  " SELECT USUARIO_ID FROM portalbd.go_asig_idPermisos where ESTADO=1";

        // echo $sql;
        $r = $this->mysqli->query($sql);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row['USUARIO_ID'];
            }

            $this->response($this->json($result), 200); // send user details
        }else{
            $error = "No hay datos.";
            $this->response($this->json(array($error)), 403);
        }

    }
    private function taskgrupos()
    {
        if($this->get_request_method() != "GET"){
            $this->response('Metodo no soportado',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr ($usuarioIp);
        $galleta        =   json_decode (stripslashes ($_COOKIE['logedUser']), true);
        $galleta        =   stripslashes ($_COOKIE['logedUser']);
        $galleta        =   json_decode ($galleta);
        $galleta        =   json_decode (json_encode ($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $today          =   date("Y-m-d");

        $sql =  " SELECT * FROM portalbd.go_task_grupos";

        // echo $sql;
        $r = $this->mysqli->query($sql);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $row['REPRESENTANTE']=utf8_encode($row['REPRESENTANTE']);
                $result[] = $row;
            }

            $this->response($this->json($result), 200); // send user details
        }else{
            $error = "No hay datos.";
            $this->response($this->json(array($error)), 403);
        }

    }
    private function tasktipos()
    {
        if($this->get_request_method() != "GET"){
            $this->response('Metodo no soportado',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr ($usuarioIp);
        $galleta        =   json_decode (stripslashes ($_COOKIE['logedUser']), true);
        $galleta        =   stripslashes ($_COOKIE['logedUser']);
        $galleta        =   json_decode ($galleta);
        $galleta        =   json_decode (json_encode ($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $today          =   date("Y-m-d");

        $sql =  "SELECT * FROM portalbd.go_task_tipocategoria";

        // echo $sql;
        $r = $this->mysqli->query($sql);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }

            $this->response($this->json($result), 200); // send user details
        }else{
            $error = "No hay datos.";
            $this->response($this->json(array($error)), 403);
        }

    }

    private function taskCrud()
    {
        if($this->get_request_method() != "GET"){
            $this->response('Metodo no soportado',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr ($usuarioIp);
        $galleta        =   json_decode (stripslashes ($_COOKIE['logedUser']), true);
        $galleta        =   stripslashes ($_COOKIE['logedUser']);
        $galleta        =   json_decode ($galleta);
        $galleta        =   json_decode (json_encode ($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $today          =   date("Y-m-d");

        $sqlCerrado =  " SELECT t.*, tp.PIC FROM portalbd.go_task t ".
            " left join portalbd.go_task_profile tp on t.USUARIO_GEST=tp.USUARIO_ID ".
            " where 1=1 ".
            " and t.ESTADO in ('CERRADO') ";

        $rCerrado = $this->mysqli->query($sqlCerrado);

        if($rCerrado->num_rows > 0){
            $cerrados = array();
            while($row = $rCerrado->fetch_assoc()){
                //$row['PIC'] = base64_encode($row['PIC']);
                //$row['PIC'] = 'data:image/jpeg;base64,'.base64_encode( $row['PIC'] );
                $cerrados[] = $row;
            }
        }

        $sql =  " SELECT t.*, tp.PIC FROM portalbd.go_task t ".
            " left join portalbd.go_task_profile tp on t.USUARIO_GEST=tp.USUARIO_ID ".
            " where 1=1 ".
            " and t.ESTADO in ('ACTIVO','PAUSA') ";

        // echo $sql;
        $r = $this->mysqli->query($sql);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                //$row['PIC'] = base64_encode($row['PIC']);
                //$row['PIC'] = 'data:image/jpeg;base64,'.base64_encode( $row['PIC'] );
                $result[] = $row;
            }

            $this->response($this->json(array($result,$cerrados)), 200); // send user details
        }else{
            $error = "No hay datos.";
            $this->response($this->json(array($error)), 403);
        }

    }
    private function taskCrudUser()
    {
        if($this->get_request_method() != "GET"){
            $this->response('Metodo no soportado',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr ($usuarioIp);
        $galleta        =   json_decode (stripslashes ($_COOKIE['logedUser']), true);
        $galleta        =   stripslashes ($_COOKIE['logedUser']);
        $galleta        =   json_decode ($galleta);
        $galleta        =   json_decode (json_encode ($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];
        $today          =   date("Y-m-d");



        $sql =  " SELECT t.*, tp.PIC FROM portalbd.go_task t ".
            " left join portalbd.go_task_profile tp on t.USUARIO_GEST=tp.USUARIO_ID ".
            " where 1=1 ".
            " and t.USUARIO_GEST='$usuarioGalleta' ".
            " and t.ESTADO in ('ACTIVO','PAUSA') ";

        //echo $sql;
        $r = $this->mysqli->query($sql);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                //$row['PIC'] = base64_encode($row['PIC']);
                //$row['PIC'] = 'data:image/jpeg;base64,'.base64_encode( $row['PIC'] );
                $result[] = $row;
            }

            $this->response($this->json($result), 200); // send user details
        }else{
            $error = "No hay datos.";
            $this->response($this->json(array($error)), 204);
        }

    }
    private function updateTaskAdmin(){

        if($this->get_request_method() != "POST"){
            $this->response('',406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $params         =   json_decode(file_get_contents('php://input'),true);
        $idtarea        =   $params['id'];
        $tipotarea      =   $params['tipo'];
        $categoria      =   $params['categoria'];
        $grupo          =   $params['grupo'];
        $representante  =   $params['representante'];
        $estado         =   $params['estado'];
        $usuario_id     =   $params['usuario'];
        $fechamod       =   $params['fecha'];
        $taskisdone     =   $params['taskIsDone'];
        $progresotsk    =   $params['progreso'];
        $today          =   date("Y-m-d H:i:s");

        /*if($taskisdone){
            $progreso=100;
            $estado='CERRADO';
        }else{
            $progreso=$progresotsk;
        } */
        $progreso=$progresotsk;
        $query= " update portalbd.go_task ".
            " set ESTADO='$estado' ".
            " , PROGRESO=$progreso ".
            " , TIPO='$tipotarea' ".
            " , CATEGORIA='$categoria' ".
            " , GRUPO='$grupo' ".
            " , REPRESENTANTE='$representante' ".
            " , FECHA_MODIFICACION='$fechamod' ".
            " , USUARIO_MODIFICACION='$usuario_id' ".
            " where IDTAREA='$idtarea' ";

        $rst = $this->mysqli->query($query);

        if($rst==true){
            $msg="Tarea Actualizada, progreso: $progreso";

            $this->response($this->json(array($msg)), 201);

        }else{
            $msg = "No se pudo actualizar";
            $this->response($this->json(array($msg)), 403);
        }

    }//-----------------------------------------------Fin funcion

    private function putTaskAdmin()
    {
        if ($this->get_request_method () != "POST") {
            $this->response ('', 406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr ($usuarioIp);
        $galleta        =   json_decode (stripslashes ($_COOKIE['logedUser']), true);
        $galleta        =   stripslashes ($_COOKIE['logedUser']);
        $galleta        =   json_decode ($galleta);
        $galleta        =   json_decode (json_encode ($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $newtask        =   json_decode (file_get_contents ("php://input"), true);
        $fechaServidor  =   date("Y-m-d H:i:s");

        $guardar        =   false;
        $mysqlerror     =   "";
        $error          =   "";

        //var_dump($newtask);

        $column_names = array('FECHA_INICIO',
            'USUARIO_CREA',
            'USUARIO_GEST',
            'TIPO',
            'CATEGORIA',
            'GRUPO',
            'REPRESENTANTE',
            'OBSERVACIONES',
            'ESTADO',
            'PROGRESO',
            'PRIORIDAD');

        $keys = array();
        $values = array();
        foreach ($column_names as $column) {
            $value = trim($newtask['newtask'][$column]);
            $value = htmlspecialchars($value);

            $keys[] = "`{$column}`";
            $values[] = "'{$value}'";
        }
        //var_dump($values);
        $queryGestion = " INSERT INTO portalbd.go_task (" . implode(",", $keys) . ") VALUES (" . implode(",", $values ).")";

        //echo $queryGestion;

        $insertGestion = $this->mysqli->query($queryGestion);
        if($insertGestion){
            $msg = "Tarea Creada";
            $guardar = true ;
        }else{
            $mysqlerror = $this->mysqli->error;
            $guardar = false;
        }
        if($guardar){

            $this->response ($this->json (array($msg)), 200);
        }else{
            $error = "Error guardando: $mysqlerror";
            $this->response ($this->json (array($error)), 403);
        }
    }

    /**
     * Retorna los alarmados proactivos en indicadores asignaciones
     */
    private function alarmadosProactivos()
    {
        if ($this->get_request_method () != "GET") {
            $this->response ('Servicio no soportado', 406);
        }

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr ($usuarioIp);
        $galleta        =   json_decode (stripslashes ($_COOKIE['logedUser']), true);
        $galleta        =   stripslashes ($_COOKIE['logedUser']);
        $galleta        =   json_decode ($galleta);
        $galleta        =   json_decode (json_encode ($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido         =   $this->_request['pedido'];
        $fechaServidor  =   date("Y-m-d H:i:s");
        $horaServidor   =   date("H");
        $fecha          =   date("Y-m-d");
        $intervaltime   =   "'$fecha'";
        $mysqlerror     =   "";
        $error          =   "";
        $sqlok          =   false;
        $alarmadosRecu  =   array();
        $alarmados      =   array();
        $alarmadosHist  =   array();

        $sqlAlarmados = " select ".
            "    left(c2.RESPONSABLE,4) as RESPONSABLE ".
            "    , COUNT(*) as CANTIDAD ".
            "    from ( ".
            "    SELECT ".
            "    C1.PEDIDO_ID ".
            "    , case ".
            "        when group_concat(distinct C1.RESPONSABLE order by C1.RESPONSABLE asc) ='ASIGNACIONES,RECONFIGURACION' then 'RECONFIGURACION' ".
            "        when group_concat(distinct C1.RESPONSABLE order by C1.RESPONSABLE asc) ='ACCESO,ASIGNACIONES' then 'ACCESO' ".
            "        when group_concat(distinct C1.RESPONSABLE order by C1.RESPONSABLE asc) ='ASIGNACIONES,OTRO' then 'OTRO' ".
            "        else group_concat(distinct C1.RESPONSABLE order by C1.RESPONSABLE asc)  ".
            "        end AS RESPONSABLE ".
            "    , group_concat(distinct C1.CONCEPTO_ID) AS CONCEPTO_ID ".
            "    , group_concat(distinct C1.ALARMAFECHA) AS ALARMA ".
            "    FROM (SELECT  ".
            "    a.PEDIDO_ID ".
            "    , a.SUBPEDIDO_ID ".
            "    , a.SOLICITUD_ID ".
            "    , a.FECHA_CITA ".
            "    , a.TIPO_ELEMENTO_ID ".
            "    , a.TIPO_SOLICITUD ".
            "    , a.TRABAJOS ".
            "    , a.ESTADO_ID ".
            "    , a.ESTADO_SOLI ".
            "    , a.CONCEPTO_ID ".
            "    , a.DESCRIPCION_CONCEPTO ".
            "    , a.DESCRIPCION_ESTADO ".
            "    , a.UEN_CALCULADA ".
            "    , CASE ".
            "        WHEN a.DEPARTAMENTO='' THEN 'Antioquia' ".
            "        ELSE a.DEPARTAMENTO ".
            "    END AS DEPARTAMENTO ".
            "    , CASE ".
            "        WHEN a.FECHA_CITA=(CURDATE() + INTERVAL 1 DAY) THEN 'MANANA' ".
            "        WHEN a.FECHA_CITA=(CURDATE() + INTERVAL 2 DAY) THEN 'PASADO_MANANA' ".
            "        WHEN a.FECHA_CITA>=(CURDATE() + INTERVAL 3 DAY) THEN 'FUTURA' ".
            "     END AS ALARMAFECHA ".
            "    , CASE ".
            "        WHEN a.CONCEPTO_ID NOT IN ('CUMPL','DEMO','FACTU','ORDEN','PFACT','PEXPQ','PORDE','PSERV','PXSLN','PCTEC','INCON','POPTO','PRACC','PQUET','PRUTA','PCEQU','APPRV') AND a.FECHA_CITA=(CURDATE() + INTERVAL 1 DAY) THEN 'SI' ".
            "        ELSE 'NO' ".
            "    END AS ALARMO_COMP ".
            "    , CASE ".
            "        WHEN a.CONCEPTO_ID IN ('ANCAT','ANDUS','ANFRA','ANFRU','ANINP','ANPUS','ANSPE','ANTNE','ANULA', ".
            "        'ANUOS','ANUPO','ANXSC','APRCT','APROB','AVENC','AXGAR','42','43','46','32','36','37')  THEN 'SI' ".
            "        WHEN a.ESTADO_SOLI='ANULA' THEN 'SI' ".
            "        ELSE 'NO' ".
            "    END AS ANULO_COMP ".
            "    ,  CASE ".
            "        when a.CONCEPTO_ID IN ('PETEC','OKRED','PEOPP','19','O-13','O-15','O-106','PUMED') then 'ASIGNACIONES' ".
            "        when a.CONCEPTO_ID IN ('O-300') then 'ACTIVACION' ".
            "        when a.CONCEPTO_ID IN ('14','99','O-101') then 'RECONFIGURACION' ".
            "        when a.CONCEPTO_ID IN ('AGEN','O-02','O-07','O-08','O-23','O-49','O-50','O-65','O-103','O-AGN','O-40','O-34','AGEND','PPRG','PROG','REAGE') then 'AGENDAMIENTO' ".
            "        when a.CONCEPTO_ID IN ('11','PVENC') then 'BACK' ".
            "        when a.CONCEPTO_ID IN ('2','O-115','O-06') then 'OPERACION CLIENTES' ".
            "        when a.CONCEPTO_ID IN ('PECBA','PLICO','23','24','25','25D','26D','74S','O-85','O-01','O-09') then 'ACCESO' ".
            "        when a.CONCEPTO_ID IN ('PEREP','PECAR','PECSA') then 'CREDITO Y CARTERA' ".
            "        when a.CONCEPTO_ID IN ('82','PEN82','PEFRA') then 'CONTROL FRAUDES' ".
            "        when a.CONCEPTO_ID IN ('47') then 'TI' ".
            "        when a.CONCEPTO_ID in ('42','ANPUS','ANSPE','ANUPO','ANVAL','APRCT','39','34','ANFRU','ANDUS','ANINS','ANCMT','ANFRA') then 'ANULADO CLIENTE' ".
            "        when a.CONCEPTO_ID in ('ANUOS') then 'ANULADO SUSTITUCION' ".
            "        when a.CONCEPTO_ID in ('ANCAT','43','36','32','46','AVENT') then 'ANULADO TECNICO' ".
            "        when a.CONCEPTO_ID in ('AXGAR','AVENC','41','40','44','98','37') then 'ANULADO VENTAS' ".
            "        else 'OTRO' ".
            "    END AS RESPONSABLE ".
            "    FROM scheduling.agendamientoxfenix a ".
            "    where 1=1 ".
            "    and a.pedido_id not like '%pre%' ".
            "    and a.TIPO_ELEMENTO_ID in ('ACCESP','TO','TOIP','INSIP','INSHFC','EQURED','SERHFC') ".
            "    and a.UEN_CALCULADA='HG' ".
            "     ) C1 ".
            "    WHERE 1=1 ".
            "    AND C1.ANULO_COMP='NO' ".
            "    AND C1.ALARMO_COMP='SI' ".
            "    GROUP BY C1.PEDIDO_ID ) c2 ".
            "    where c2.RESPONSABLE in ('ASIGNACIONES','RECONFIGURACION','ACTIVACION') ".
            "    group by c2.RESPONSABLE ";

        $rAlarmados = $this->mysqli->query($sqlAlarmados);

        if($rAlarmados->num_rows > 0){

            while($row = $rAlarmados->fetch_assoc()){
                $alarmados[] = $row;
            }

            $sqlok = true;
        }else{
            $alarmados = array(
                array('RESPONSABLE'=>'ACTI', 'CANTIDAD'=>0),
                array('RESPONSABLE'=>'ASIG', 'CANTIDAD'=>0),
                array('RESPONSABLE'=>'RECO', 'CANTIDAD'=>0)

            );
            $error = "No hay datos.";
            $sqlok = tru;
        }

        if($horaServidor>=16){
            $intervaltime = " DATE_ADD('$fecha', INTERVAL 1 DAY) " ;
            $fecha = date('Y-m-d',strtotime($fecha . "+1 days"));
        }
        $sqlAlarmadosHistorico = "select ".
            " left(c2.RESPONSABLE,4) as RESPONSABLE ".
            " , count(*) as CANTIDAD ".
            " from ( ".
            " SELECT ".
            " C1.FECHA_CITA ".
            " , C1.PEDIDO_ID ".
            " , group_concat(distinct C1.RESPONSABLE) AS RESPONSABLE ".
            " , group_concat(distinct C1.CONCEPTO_ID) AS CONCEPTO_ID ".
            " FROM (SELECT  ".
            " a.PEDIDO_ID ".
            " , a.CONCEPTO_ID ".
            " , a.FECHA_CITA ".
            " , a.TIPO_ELEMENTO_ID ".
            " , a.UEN_CALCULADA ".
            " , a.DEPARTAMENTO ".
            " , ALARMO_COMP ".
            " , a.ANULO_COMP ".
            " , a.RESPONSABLE ".
            " FROM scheduling.historico_alarmados a ".
            " where 1=1 ".
            " and a.pedido_id not like '%pre%' ".
            " and a.TIPO_ELEMENTO_ID in ('ACCESP','TO','TOIP','INSIP','INSHFC') ".
            " and a.UEN_CALCULADA='HG' ".
            " ) C1 ".
            " WHERE 1=1 ".
            " AND C1.ANULO_COMP='NO' ".
            " and C1.FECHA_CITA=$intervaltime ".
            " GROUP BY C1.PEDIDO_ID, C1.FECHA_CITA ) c2 ".
            " where c2.RESPONSABLE in ('ASIGNACIONES', 'RECONFIGURACION','ACTIVACION DESACTIVACION') ".
            " group by c2.RESPONSABLE ";
        //echo $sqlAlarmadosHistorico;
        $rAlarmadosHist = $this->mysqli->query($sqlAlarmadosHistorico);

        if($rAlarmadosHist->num_rows > 0){

            while($row = $rAlarmadosHist->fetch_assoc()){
                $alarmadosHist[] = $row;
            }

            $sqlok = true;
        }else{
            $alarmadosHist = array(
                array('RESPONSABLE'=>'ACTI', 'CANTIDAD'=>0),
                array('RESPONSABLE'=>'ASIG', 'CANTIDAD'=>0),
                array('RESPONSABLE'=>'RECO', 'CANTIDAD'=>0)

            );
            $error = "No hay datos.";
            $sqlok = true;
        }

        $sqlAlarmadosRecuperados = "select ".
            " left(c2.RESPONSABLE,4) as RESPONSABLE ".
            " , count(*) as CANTIDAD ".
            " from ( ".
            " SELECT ".
            " C1.FECHA_CITA ".
            " , C1.PEDIDO_ID ".
            " , group_concat(distinct C1.RESPONSABLE) AS RESPONSABLE ".
            " , group_concat(distinct C1.CONCEPTO_ID) AS CONCEPTO_ID ".
            " FROM (SELECT  ".
            " a.PEDIDO_ID ".
            " , a.CONCEPTO_ID ".
            " , a.FECHA_CITA ".
            " , a.TIPO_ELEMENTO_ID ".
            " , a.UEN_CALCULADA ".
            " , a.DEPARTAMENTO ".
            " , ALARMO_COMP ".
            " , a.ANULO_COMP ".
            " , a.RESPONSABLE ".
            " , a.RECUPERADO ".
            " FROM scheduling.historico_alarmados a ".
            " where 1=1 ".
            " and a.pedido_id not like '%pre%' ".
            " and a.TIPO_ELEMENTO_ID in ('ACCESP','TO','TOIP','INSIP','INSHFC') ".
            " and a.UEN_CALCULADA='HG' ".
            " ) C1 ".
            " WHERE 1=1 ".
            " AND C1.ANULO_COMP='NO' ".
            " and C1.RECUPERADO='SI' ".
            " and C1.FECHA_CITA=current_date() ".
            " GROUP BY C1.PEDIDO_ID, C1.FECHA_CITA ) c2 ".
            " where c2.RESPONSABLE in ('ASIGNACIONES', 'RECONFIGURACION','ACTIVACION DESACTIVACION') ".
            " group by c2.RESPONSABLE ";

        $rAlarmadosRecu = $this->mysqli->query($sqlAlarmadosRecuperados);

        if($rAlarmadosRecu->num_rows > 0){
            while($row = $rAlarmadosRecu->fetch_assoc()){
                $alarmadosRecu[] = $row;
            }

            $sqlok = true;
        }else{
            $alarmadosRecu = array(
                array('RESPONSABLE'=>'ACTI', 'CANTIDAD'=>0),
                array('RESPONSABLE'=>'ASIG', 'CANTIDAD'=>0),
                array('RESPONSABLE'=>'RECO', 'CANTIDAD'=>0)

            );
            $error = "No hay datos.";
            $sqlok = true;
        }


        if($sqlok){
            $msg = "Consultas Realizadas";
            $guardar = true ;
        }else{
            $msg = "Error, algo salio mal";
            $mysqlerror = $this->mysqli->error;
            $guardar = false;
        }

        if($guardar){
            $this->response ($this->json (array($msg,$alarmados,$alarmadosHist,$alarmadosRecu,$fecha)), 200);
        }else{
            $error = "$msg: $mysqlerror";
            $this->response ($this->json (array($error)), 403);
        }
    }

    private function csvAlarmadosProactivos(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido         =   $this->_request['pedido'];
        $fechaini       =   $this->_request['fechaini'];
        $fechafin       =   $this->_request['fechafin'];
        $paramlst       =   "";
        $today          =   date("Y-m-d");


        $filename="AlarmadosProactivos_$usuarioGalleta-$today.csv";

        $sql =  "    SELECT ".
            "    C2.PEDIDO_ID ".
            "    , C2.ESTADO_GESTOR ".
            "    , C2.PROGRAMACION ".
            "    , case when C2.RADICADO_TEMPORAL like '%ARBOL%' then 'ARBOL' else C2.RADICADO_TEMPORAL end as RADICADO_TEMPORAL ".
            "    , C2.OBSERVACIONES ".
            "    , C2.RESPONSABLE ".
            "    , C2.CONCEPTO_ID ".
            "    , C2.TIPO_ELEMENTO_ID ".
            "    , C2.ALARMA ".
            "    , C2.FECHA_CITA ".
            "    FROM ( ".
            "    SELECT ".
            "    C1.PEDIDO_ID ".
            "    , ifnull((SELECT  ".
            "    		case  ".
            "    			when group_concat(distinct b.status order by b.status asc) = 'CERRADO_PETEC,PENDI_PETEC' then 'PENDI_PETEC' ".
            "                when group_concat(distinct b.status order by b.status asc) like  '%MALO%' then 'MALO' ".
            "                else group_concat(distinct b.status order by b.status asc) ".
            "                end as ESTADO ".
            "    	FROM portalbd.informe_petec_pendientesm b  ".
            "        where b.PEDIDO_ID=C1.PEDIDO_ID group by b.PEDIDO_ID),'NO ESTA') as ESTADO_GESTOR ".
            "    , IFNULL((SELECT group_concat(DISTINCT b.PROGRAMACION) AS RA FROM portalbd.informe_petec_pendientesm b ".
            "        where b.PEDIDO_ID=C1.PEDIDO_ID and b.PROGRAMACION!='' group by b.PEDIDO_ID),'Sin') as PROGRAMACION ".
            "    , IFNULL((SELECT group_concat(DISTINCT b.RADICADO_TEMPORAL) AS RA FROM portalbd.informe_petec_pendientesm b  ".
            "        where b.PEDIDO_ID=C1.PEDIDO_ID group by b.PEDIDO_ID),'NO ESTA') as RADICADO_TEMPORAL ".
            "    , ifnull((Select  p.OBSERVACIONES_PROCESO from portalbd.pedidos p  where 1=1  and p.estado_id='MALO'  and p.pedido_id=C1.PEDIDO_ID  order by p.id desc   limit 1 ), 'NO ESTA') as OBSERVACIONES ".
            "    , case ".
            "    	when group_concat(distinct C1.RESPONSABLE order by C1.RESPONSABLE asc) ='ASIGNACIONES,RECONFIGURACION' then 'RECONFIGURACION' ".
            "        when group_concat(distinct C1.RESPONSABLE order by C1.RESPONSABLE asc) ='ACCESO,ASIGNACIONES' then 'ACCESO' ".
            "        when group_concat(distinct C1.RESPONSABLE order by C1.RESPONSABLE asc) ='ASIGNACIONES,OTRO' then 'OTRO' ".
            "        else group_concat(distinct C1.RESPONSABLE order by C1.RESPONSABLE asc)  ".
            "        end AS RESPONSABLE ".
            "    , group_concat(distinct C1.CONCEPTO_ID) AS CONCEPTO_ID ".
            "    , group_concat(distinct C1.TIPO_ELEMENTO_ID) AS TIPO_ELEMENTO_ID ".
            "    , group_concat(distinct C1.ALARMAFECHA) AS ALARMA ".
            "    , max(C1.FECHA_CITA) as FECHA_CITA ".
            "    FROM (SELECT  ".
            "    a.PEDIDO_ID ".
            "    , a.SUBPEDIDO_ID ".
            "    , a.SOLICITUD_ID ".
            "    , a.FECHA_CITA ".
            "    , a.TIPO_ELEMENTO_ID ".
            "    , a.TIPO_SOLICITUD ".
            "    , a.TRABAJOS ".
            "    , a.ESTADO_ID ".
            "    , a.ESTADO_SOLI ".
            "    , a.CONCEPTO_ID ".
            "    , a.DESCRIPCION_CONCEPTO ".
            "    , a.DESCRIPCION_ESTADO ".
            "    , a.UEN_CALCULADA ".
            "    , CASE ".
            "    	WHEN a.DEPARTAMENTO='' THEN 'Antioquia' ".
            "        ELSE a.DEPARTAMENTO ".
            "    END AS DEPARTAMENTO ".
            "    , CASE ".
            "    	WHEN a.FECHA_CITA=(CURDATE() + INTERVAL 1 DAY) THEN 'MANANA' ".
            "        WHEN a.FECHA_CITA=(CURDATE() + INTERVAL 2 DAY) THEN 'PASADO_MANANA' ".
            "        WHEN a.FECHA_CITA>=(CURDATE() + INTERVAL 3 DAY) THEN 'FUTURA' ".
            "     END AS ALARMAFECHA ".
            "    , CASE ".
            "    	WHEN a.CONCEPTO_ID NOT IN ('CUMPL','DEMO','FACTU','ORDEN','PFACT','PEXPQ','PORDE','PSERV','PXSLN','PCTEC','INCON','POPTO','PRACC','PQUET','PRUTA','PCEQU','APPRV') AND a.FECHA_CITA=(CURDATE() + INTERVAL 1 DAY) THEN 'SI' ".
            "        ELSE 'NO' ".
            "    END AS ALARMO_COMP ".
            "    , CASE ".
            "    	WHEN a.CONCEPTO_ID IN ('ANCAT','ANDUS','ANFRA','ANFRU','ANINP','ANPUS','ANSPE','ANTNE','ANULA', ".
            "        'ANUOS','ANUPO','ANXSC','APRCT','APROB','AVENC','AXGAR','42','43','46','32','36','37')  THEN 'SI' ".
            "        WHEN a.ESTADO_SOLI='ANULA' THEN 'SI' ".
            "        ELSE 'NO' ".
            "    END AS ANULO_COMP ".
            "    ,  CASE ".
            "    	when a.CONCEPTO_ID IN ('PETEC','OKRED','PEOPP','19','O-13','O-15','O-106','PUMED') then 'ASIGNACIONES' ".
            "    	when a.CONCEPTO_ID IN ('O-300') then 'ACTIVACION' ".
            "    	when a.CONCEPTO_ID IN ('14','99','O-101') then 'RECONFIGURACION' ".
            "        when a.CONCEPTO_ID IN ('AGEN','O-02','O-07','O-08','O-23','O-49','O-50','O-65','O-103','O-AGN','O-40','O-34','AGEND','PPRG','PROG','REAGE') then 'AGENDAMIENTO' ".
            "        when a.CONCEPTO_ID IN ('11','PVENC') then 'BACK' ".
            "        when a.CONCEPTO_ID IN ('2','O-115','O-06') then 'OPERACION CLIENTES' ".
            "    	when a.CONCEPTO_ID IN ('PECBA','PLICO','23','24','25','25D','25G','26D','74S','O-85','O-01','O-09') then 'ACCESO' ".
            "        when a.CONCEPTO_ID IN ('PEREP','PECAR','PECSA') then 'CREDITO Y CARTERA' ".
            "        when a.CONCEPTO_ID IN ('82','PEN82','PEFRA') then 'CONTROL FRAUDES' ".
            "        when a.CONCEPTO_ID IN ('47') then 'TI' ".
            "        when a.CONCEPTO_ID in ('42','ANPUS','ANSPE','ANUPO','ANVAL','APRCT','39','34','ANFRU','ANDUS','ANINS','ANCMT','ANFRA') then 'ANULADO CLIENTE' ".
            "        when a.CONCEPTO_ID in ('ANUOS') then 'ANULADO SUSTITUCION' ".
            "        when a.CONCEPTO_ID in ('ANCAT','43','36','32','46','AVENT') then 'ANULADO TECNICO' ".
            "        when a.CONCEPTO_ID in ('AXGAR','AVENC','41','40','44','98','37') then 'ANULADO VENTAS' ".
            "        else 'OTRO' ".
            "    END AS RESPONSABLE ".
            "    FROM scheduling.agendamientoxfenix a ".
            "    where 1=1 ".
            "    and a.pedido_id not like '%pre%' ".
            "    and a.TIPO_ELEMENTO_ID in ('ACCESP','TO','TOIP','INSIP','INSHFC','EQURED','SERHFC') ".
            "    and a.UEN_CALCULADA='HG' ".
            "    ORDER BY 1 ASC ) C1 ".
            "    WHERE 1=1 ".
            "    AND C1.ANULO_COMP='NO' ".
            "    AND C1.ALARMO_COMP='SI' ".
            "    GROUP BY C1.PEDIDO_ID ) C2 ".
            "    WHERE C2.RESPONSABLE IN ('ASIGNACIONES','RECONFIGURACION','AYD') ";

        $r = $this->mysqli->query($sql);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('PEDIDO_ID',
                'ESTADO_GESTOR',
                'PROGRAMACION',
                'RADICADO_TEMPORAL',
                'OBSERVACIONES',
                'RESPONSABLE',
                'CONCEPTO_ID',
                'TIPO_ELEMENTO_IT',
                'ALARMA',
                'FECHA_CITA'));

            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);

            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO ALARMADOS PROACTIVOS' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$usuarioGalleta)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }

    private function csvAlarmadosHistorico(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }
        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $pedido         =   $this->_request['pedido'];
        $fechaini       =   $this->_request['fechaini'];
        $fechafin       =   $this->_request['fechafin'];
        $paramlst       =   "";
        $today          =   date("Y-m-d");


        $filename="AlarmadosHistorico_$usuarioGalleta-$today.csv";

        $sql =  "    SELECT  ".
            " C1.FECHA_CITA  ".
            " , C1.PEDIDO_ID  ".
            " , group_concat(distinct C1.RESPONSABLE) AS RESPONSABLE  ".
            " , group_concat(distinct C1.CONCEPTO_ID) AS CONCEPTO_ID  ".
            " FROM (SELECT   ".
            " a.PEDIDO_ID  ".
            " , a.CONCEPTO_ID  ".
            " , a.FECHA_CITA  ".
            " , a.TIPO_ELEMENTO_ID  ".
            " , a.UEN_CALCULADA  ".
            " , a.DEPARTAMENTO  ".
            " , ALARMO_COMP  ".
            " , a.ANULO_COMP  ".
            " , a.RESPONSABLE  ".
            " FROM scheduling.historico_alarmados a  ".
            " where 1=1  ".
            " and a.pedido_id not like '%pre%'  ".
            " and a.TIPO_ELEMENTO_ID in ('ACCESP','TO','TOIP','INSIP','INSHFC')  ".
            " and a.UEN_CALCULADA='HG'  ".
            " ) C1  ".
            " WHERE 1=1  ".
            " AND C1.ANULO_COMP='NO'  ".
            " and C1.FECHA_CITA=current_date()  ".
            " GROUP BY C1.PEDIDO_ID, C1.FECHA_CITA  ";

        $r = $this->mysqli->query($sql);

        if($r->num_rows > 0){
            $result = array();
            $fp = fopen("../tmp/$filename", 'w');
            fputcsv($fp, array('FECHA_CITA',
                'PEDIDO_ID',
                'RESPONSABLE',
                'CONCEPTO_ID'));

            while($row = $r->fetch_assoc()){
                //$result[] = $row;
                fputcsv($fp, $row);
            }
            fclose($fp);

            // SQL Feed----------------------------------
            $sql_log=   "insert into portalbd.activity_feed ( ".
                " USER ".
                ", USER_NAME ".
                ", GRUPO ".
                ", STATUS ".
                ", PEDIDO_OFERTA ".
                ", ACCION ".
                ", CONCEPTO_ID ".
                ", IP_HOST ".
                ", CP_HOST ".
                ") values( ".
                " UPPER('$usuarioGalleta')".
                ", UPPER('$nombreGalleta')".
                ", UPPER('$grupoGalleta')".
                ",'OK' ".
                ",'SIN PEDIDO' ".
                ",'EXPORTO ALARMADOS PROACTIVOS' ".
                ",'ARCHIVO EXPORTADO' ".
                ",'$usuarioIp' ".
                ",'$usuarioPc')";

            $rlog = $this->mysqli->query($sql_log);
            // ---------------------------------- SQL Feed
            $this->response($this->json(array($filename,$usuarioGalleta)), 200); // send user details
        }

        $this->response('',204);        // If no records "No Content" status

    }

    /**
     * Funcion que llama procedimiento en java para extraer pedidos alarmados
     */
    private function runJavaAlarmadosProactivos(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        //$javaexec=shell_exec("/usr/java/java8/bin/java -jar /var/www/html/scheduling/java/agendamiento.jar request=verificarAgendamientos fileConfig=/var/www/html/scheduling/java/fileConfig.xml > /var/www/html/scheduling/java/proceso.log 2>&1");
        //$msg = "Funciono";

        $last_line = system('/usr/java/java8/bin/java -jar /var/www/html/scheduling/java/agendamiento.jar request=verificarAgendamientos fileConfig=/var/www/html/scheduling/java/fileConfig.xml', $retval);
        ($last_line == 0) or die("returned an error: $retval");
        $this->response($this->json(array('Termino:',$last_line,$retval)), 200);
    }

    /**
     *
     * @uses  getLdapUserInfo()
     * getLdapUserInfo, vamos a el servidor de LDPA y buscamos info del usuario
     */
    private function getLdapUserInfo(){
        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }

        $this->dbFenixSTBYConnect();
        $connf=$this->connfstby;

        $usuarioIp      =   $_SERVER['REMOTE_ADDR'];
        $usuarioPc      =   gethostbyaddr($usuarioIp);
        $galleta        =   json_decode(stripslashes($_COOKIE['logedUser']),true);
        $galleta        =   stripslashes($_COOKIE['logedUser']);
        $galleta        =   json_decode($galleta);
        $galleta        =   json_decode(json_encode($galleta), True);
        $usuarioGalleta =   $galleta['login'];
        $nombreGalleta  =   $galleta['name'];
        $grupoGalleta   =   $galleta['GRUPO'];

        $user           =   $this->_request['userID'];
        $userBusqueda   =   $this->_request['userbusqueda'];
        $userConLdap    = null;
        $passConLdap    = null;


        if($user=='' || $user=='undefined'){
            $user = $usuarioGalleta;
        }

        $sqlLdapSesion =  " SELECT * FROM portalbd.go_admin_ldaplogin ";
        $r = $this->mysqli->query($sqlLdapSesion);

        if($r->num_rows > 0){
            while($row = $r->fetch_assoc()){
                $userConLdap = $row['USUARIO_ID'];
                $passConLdap = $row['PASSWORD'];
            }
        }

        $userBusqueda   = str_replace(' ', '', $userBusqueda);
        $userBusqueda   = strtoupper($userBusqueda);

        $ldapserver     =   'net-dc05';
        //$user           =   "MACEVEDG";
        //$user           = "sape";
        $user           = $userConLdap;
        $ldapuser       =   "EPMTELCO\\$user";
        //$ldappass       =   addslashes("switzerland2017++");
        //$ldappass       =   addslashes("n0sun32008*");
        $ldappass       = addslashes("$passConLdap");
        $ldaptree       =   "OU=Usuarios,DC=epmtelco,DC=com,DC=co";
        //$ldaptree       =   "OU=Epm Une,DC=epmtelco,DC=com,DC=co";
        $varuser        =   "(samaccountname=$userBusqueda)";

        //echo  $userBusqueda;

        if($userBusqueda!='UNDEFINED'){

            $ldapconn = ldap_connect($ldapserver) or die("Could not connect to LDAP server.");

            if($ldapconn) {
                // binding to ldap server
                $ldapbind = ldap_bind($ldapconn, $ldapuser, $ldappass) or die ("Error trying to bind: ".ldap_error($ldapconn));
                // verify binding
                $object = new stdClass();
                if ($ldapbind) {

                    $result = ldap_search($ldapconn,$ldaptree, $varuser) or die ("Error in search query: ".ldap_error($ldapconn));
                    $data = ldap_get_entries($ldapconn, $result);
                    $cantdata = ldap_count_entries($ldapconn, $result);

                    if($cantdata>0){

                        for ($i=0; $i<$data["count"]; $i++) {
                            //echo "dn is: ". $data[$i]["dn"] ."<br />";
                            $object->USUARIO_ID = strtoupper($data[$i]["samaccountname"][0]);
                            $object->USUARIO_NOMBRE = $this->clean_chars($this->quitar_tildes(utf8_encode(strtoupper($data[$i]["displayname"][0]))));
                            $object->CARGO = strtoupper($data[$i]["title"][0]);
                            $object->CORREO_USUARIO = strtoupper($data[$i]["mail"][0]);
                            $object->PICTURE = base64_encode($data[$i]["thumbnailphoto"][0]);

                            //var_dump($data[$i]);
                        }

                        $sqlFenix = " SELECT ".
                            " U.REGISTRO AS CEDULA_ID, convert(U.NOMBRE,'US7ASCII') AS NOMBRE".
                            " FROM FNX_USUARIOS U ".
                            " WHERE U.USUARIO_ID='$userBusqueda'";

                        $stid = oci_parse($connf, $sqlFenix);
                        $resultoci= oci_execute($stid);
                        $cedula = "";
                        $nombre = "";
                        while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS) ) {

                            $cedula = $row['CEDULA_ID'];
                            $nombre = $row['NOMBRE'];

                        }
                        $object->CEDULA_ID = $cedula;

                        $this->response($this->json(array($object, $userBusqueda)), 200);



                    }else{
                        $error = "Usuario no encontrado";
                        $this->response($this->json(array($error)), 201);
                    }


                }

            }else {
                $error = "Falló la conexión con LDAP";

                $this->response($this->json(array($error, ldap_error($ldapconn))), 403);
            }



        }else{
            $error = "Ingrese usuario a buscar.";
            $this->response($this->json(array($error)), 403);
        }

    }

    private function conceptoPedidoSiebelFenix($obj){

        $this->dbFenixConnect();
        $connf=$this->connf;

        $sqlfenix=" SELECT ".
            "  C1.PEDIDO_ID AS PEDIDOFNX ".
            "  ,  REGEXP_REPLACE((LISTAGG(C1.CONCEPTO_ID, ',') WITHIN GROUP (ORDER BY C1.CONCEPTO_ID)), '([^,]*)(,\\1)+($|,)', '\\1\\3') AS CONCEPTOS ".
            "  FROM ( ".
            "      SELECT ".
            "  SOL.PEDIDO_ID ".
            "  , SOL.SUBPEDIDO_ID ".
            "  , SOL.CONCEPTO_ID ".
            "  FROM FNX_SOLICITUDES SOL ".
            "  , FNX_PEDIDOS ".
            "  WHERE 1=1 ".
            "          AND SOL.TIPO_ELEMENTO_ID IN ('INSIP','INSHFC','TO','TOIP','ACCESP') ".
            "          and FNX_PEDIDOS.PEDIDO_CRM IN ('$obj') ".
            "          AND SOL.PEDIDO_ID=FNX_PEDIDOS.PEDIDO_ID ) C1 ".
            "  group by C1.PEDIDO_ID ";

        $stid = oci_parse($connf, $sqlfenix);
        oci_execute($stid);
        if($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
            return $row;
        }
        return "NO";
    }

    /**
     * @uses pendientesSiebelFenix()
     * Funcion para buscar pedidos en fenix de siebel
     */
    private function pendientesSiebelFenix(){

        if($this->get_request_method() != "GET"){
            $this->response('',406);
        }


        $this->dbFenixConnect();
        $connf=$this->connf;
        
        $sqlTrunca = " TRUNCATE TABLE portalbd.go_asig_siebelfenix ";
        $rTrunc = $this->mysqli->query($sqlTrunca);

        $sqlGestor= "SELECT DISTINCT PP.PEDIDO_ID AS NUMERO_OFERTA ".
                    " , PP.CONCEPTO_ID as  ESTADO_OFERTA ".
                    " , PP.FECHA_ESTADO ".
                    " , CASE ".
                    "    WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 0 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 2 THEN 'Entre 0-2' ".
                    "    WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 3 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 4 THEN 'Entre 3-4'  ".
                    "    WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 5 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 6 THEN 'Entre 5-6'  ".
                    "    WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 7 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 12 THEN 'Entre 7-12'  ".
                    "    WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 13 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 24 THEN 'Entre 13-24' ".
                    "    WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) >= 25 and HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) <= 48 THEN 'Entre 25-48' ".
                    "    WHEN HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),(PP.FECHA_ESTADO))) > 48 THEN 'Mas de 48' ".
                    " END AS RANGO_PENDIENTE ".
                    " FROM portalbd.informe_petec_pendientesm  PP ".
                    " where PP.FUENTE='SIEBEL' ".
                    " and PP.STATUS in ('PENDI_PETEC','MALO') ".
                    " and PP.CONCEPTO_ID NOT IN ('OT-C08','OT-T01','OT-C11','OT-T04','OT-T05','')";

        $r = $this->mysqli->query($sqlGestor);

        if($r->num_rows > 0){
            while($row = $r->fetch_assoc()){
                $oferta = $row['NUMERO_OFERTA'];

                $objPendiente       =   $row;
                $objFenix           =   $this->buscarPedidoCrmFenix($oferta);

                if($objFenix=='NO'){
                    continue;
                }

                $objPendiente       =   array_merge($objPendiente,$objFenix);
                $columns            =   array_keys($objPendiente);
                $ncols              =   count($columns);

                $sqlinsert = "";
                $sqlinsertm2 = "insert into portalbd.go_asig_siebelfenix ";

                $fields = "";
                $sep = "";
                $values = "";

                for ($i = 0; $i < $ncols; $i++) {

                    $key    =   $columns[$i];
                    $value  =   $objPendiente[$key];
                    $fields =   $fields.$sep.$key;
                    $values =   "$values$sep'$value'";

                    $sep=",";
                    $value = "";
                    $key = "";

                }

                $sep="";

                $sqlinsert = $sqlinsertm2;
                $sqlinsert = "$sqlinsert ($fields) values ($values)";
                $rInsert = $this->mysqli->query($sqlinsert);


            }
            $sqlFinal = " SELECT ".
                        " a.RANGO_PENDIENTE ".
                        " , count(a.NUMERO_OFERTA) as OFERTAS ".
                        " , sum(a.PETEC) as PETEC ".
                        " , sum(a.RECONFIGURACION) as RECONFIGURACION ".
                        " , sum(a.INCONSISTENCIA) as INCONSISTENCIA ".
                        " , sum(a.ACCESO) as ACCESO ".
                        " , sum(a.SIEBEL) as SIEBEL ".
                        " , sum(a.OTRO) as OTRO ".
                        " , max(a.FECHA_CARGA) as FECHA_CARGA ".
                        " FROM portalbd.go_asig_siebelfenix a ".
                        " group by a.RANGO_PENDIENTE ";
            $rFin = $this->mysqli->query($sqlFinal);
            $objAsig = [];
            if($rFin->num_rows > 0){
                while($rowF = $rFin->fetch_assoc()) {

                    $objAsig[] = $rowF;
                }

                $res = "Inserte el chorizo ";
                $this->response($this->json(array($objAsig)), 200);

            }else{
                $res = "Error. No hay datos";
                $this->response($this->json(array($res)), 403);
            }

        }


    }

    private function buscarPedidoCrmFenix($obj){

        $this->dbFenixConnect();
        $connf=$this->connf;

        $sqlfenix=" SELECT P.PEDIDO_CRM AS NUMERO_OFERTA ".
            "  ,P.PEDIDO_ID ".
            "  , NVL((SELECT 1 FROM FNX_SOLICITUDES S  ".
            "    WHERE S.PEDIDO_ID=P.PEDIDO_ID  ".
            "    AND S.TIPO_ELEMENTO_ID IN ('ACCESP','INSIP','INSHFC','TO','TOIP') ".
            "    AND S.CONCEPTO_ID in ('PETEC','21','65','OKRED') ".
            "    GROUP BY S.PEDIDO_ID),0) AS PETEC ".
            "  , NVL((SELECT 1 FROM FNX_SOLICITUDES S  ".
            "    WHERE S.PEDIDO_ID=P.PEDIDO_ID  ".
            "    AND S.TIPO_ELEMENTO_ID IN ('ACCESP','INSIP','INSHFC','TO','TOIP') ".
            "    AND S.CONCEPTO_ID IN ('14','O-101') ".
            "    GROUP BY S.PEDIDO_ID),0) AS RECONFIGURACION ".
            "  , NVL((SELECT 1 FROM FNX_SOLICITUDES S  ".
            "    WHERE S.PEDIDO_ID=P.PEDIDO_ID  ".
            "    AND S.TIPO_ELEMENTO_ID IN ('ACCESP','INSIP','INSHFC','TO','TOIP') ".
            "    AND S.CONCEPTO_ID IN ('99','PUMED') ".
            "    GROUP BY S.PEDIDO_ID),0) AS INCONSISTENCIA ".
            "  , NVL((SELECT 1 FROM FNX_SOLICITUDES S  ".
            "    WHERE S.PEDIDO_ID=P.PEDIDO_ID  ".
            "    AND S.TIPO_ELEMENTO_ID IN ('ACCESP','INSIP','INSHFC','TO','TOIP') ".
            "    AND S.CONCEPTO_ID IN ('22','23','24', '24C', '24E','25', '25E','25C', '25D', '25G', '25i', '25P', '26E', '26D', '74', '74E', '74S', 'PECBA') ".
            "    GROUP BY S.PEDIDO_ID),0) AS ACCESO ".
            "  , NVL((SELECT 1 FROM FNX_SOLICITUDES S  ".
            "    WHERE S.PEDIDO_ID=P.PEDIDO_ID  ".
            "    AND S.TIPO_ELEMENTO_ID IN ('ACCESP','INSIP','INSHFC','TO','TOIP') ".
            "    AND S.CONCEPTO_ID IN ('PRESI','PSIEB') ".
            "    GROUP BY S.PEDIDO_ID),0) AS SIEBEL ".
            "  , NVL((SELECT 1 FROM FNX_SOLICITUDES S  ".
            "    WHERE S.PEDIDO_ID=P.PEDIDO_ID  ".
            "    AND S.TIPO_ELEMENTO_ID IN ('ACCESP','INSIP','INSHFC','TO','TOIP') ".
            "    AND S.CONCEPTO_ID NOT IN ('22','23','24', '24C', '24E','25', '25E','25C', '25D', '25G', '25i' ".
            "    , '25P', '26E', '26D', '74', '74E', '74S', 'PECBA' ".
            "    , 'PETEC','14','O-101','99','PRESI','PSIEB','21','65','OKRED','PUMED') ".
            "    GROUP BY S.PEDIDO_ID),0) AS OTRO ".
            "  FROM FNX_PEDIDOS P ".
            " WHERE P.PEDIDO_CRM='$obj' ";

        $stid = oci_parse($connf, $sqlfenix);
        oci_execute($stid);
        if($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
            return $row;
        }
        return "NO";
    }


    /**
     * @uses objMunicipios()
     */
    private function objMunicipios(){
        if($this->get_request_method() != "GET"){
            $this->response('Metodo no soportado',406);
        }

        $sql =  " SELECT m.ID ".
                " , m.MUNICIPIO_ID ".
                " , m.MUNICIPIO ".
                " , m.DEPARTAMENTO ".
                " , m.REGIONAL ".
                " FROM portalbd.tbl_municipios m ";

        $r = $this->mysqli->query($sql);

        if($r->num_rows > 0){
            $result = array();
            while($row = $r->fetch_assoc()){
                $result[] = $row;
            }

            $this->response($this->json(array($result)), 200); // send user details
        }else{
            $error = "No hay datos.";
            $this->response($this->json(array($error)), 403);
        }

    }

}//cierre de la clase

// Initiiate Library

$api = new API;
$api->processApi();
