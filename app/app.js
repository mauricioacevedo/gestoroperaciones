var app = angular.module('myApp', ['base64','ngRoute','ngCookies','ng-fusioncharts','ngAnimate','ui.bootstrap','ui.tinymce','ngSanitize','ui.calendar','angularFileUpload']);
//Los " Myapp " solapas de parámetros a un elemento HTML en el que se ejecutará la aplicación .
//Ahora puede agregar controladores , directivas , filtros y más, para su aplicación AngularJS .
//El módulo ngRoute proporciona enrutamiento y deeplinking Servicios y directivas para aplicaciones angulares .
//El módulo ngCookies proporciona un contenedor conveniente para la lectura y la escritura del navegador cookies.
//FusionCharts suite XT incluye una amplia gama de gráficos, indicadores y mapas que se pueden utilizar para trazar todo tipo de datos estáticos y en tiempo real .

app.directive('popover', function() {
   return function(scope, elem) {
      elem.popover();
   }
});

app.directive('customPopover', function () {
    return {
        restrict: 'A',
        template: '<span>{{label}}</span>',
        link: function (scope, el, attrs) {
            scope.label = attrs.popoverLabel;
            $(el).popover({
				title: function() {
				return  'Observaciones';
				//'<button id="close-popover" data-toggle="clickover" onclick="$(&quot;#cerrar&quot;).popover(&quot;hide&quot;);">X</button>';
				},
                trigger: 'click',
                html: true,
                content: attrs.popoverHtml, 
                placement: attrs.popoverPlacement
            });
        }
    };
});

//---cargar aqrchivo agendamiento---------------------------------
app.service('fileUpload', ['$http','$cookieStore', function ($http,$cookieStore) {
            this.uploadFileToUrl = function(file, uploadUrl){
               var fd = new FormData();
               var user=$cookieStore.get('logedUser').login;               
               file['user']=user+'6666666';
               
		      fd.append('user',user);

               //console.log (file['size']);
               fd.append('fileUpload', file);
                            
               $http.post('services/cargar_datos', fd, {
		  withCredentials: false,
                  transformRequest: angular.identity,
                  headers: {'Content-Type': undefined},
		  params: {'user':user},

		  responseType: "arraybuffer"
               })
                .success(function(){
                alert('El archivo a sido subido correctamente');

               })
               .error(function(){
                alert('Ha habido un error al subir el archivo');

               });
            }
         }]);

app.service('fileUpload2', ['$http', function ($http) {
            this.uploadFileToUrl = function(file, uploadUrl){
               var fd = new FormData();
               fd.append('fileUpload', file);
               $http.post('services/cargar_datosparame', fd, {
                  transformRequest: angular.identity,
                  headers: {'Content-Type': undefined}
               })
               .success(function(){


               })
               .error(function(){
               });
            }
         }]);


 app.directive('fileModel', ['$parse', function ($parse) {
            return {
               restrict: 'A',
               link: function(scope, element, attrs) {
                  var model = $parse(attrs.fileModel);
                  var modelSetter = model.assign;
                  
                  element.bind('change', function(){
                     scope.$apply(function(){
                        modelSetter(scope, element[0].files[0]);
                     });
                  });
               }
            };
         }]);

//---cargar aqrchivo agendamiento-----------------------------------

app.factory("services", ['$http', '$timeout', function($http,$q,$timeout) {
  	 var serviceBase = 'services/'
     var obj = {};

    obj.getCustomers = function(){
        	return $http.get(serviceBase + 'customers');
    	}

	obj.getPedidosUser = function(userID){
		return $http.get(serviceBase + 'pedidosPorUser?userID=' + userID);
	}


//---------------------------------------------------------------------------------------------Agendamiento
    obj.getPedidosUserAdelantarAgenda = function(userID){
                return $http.get(serviceBase + 'pedidosPorUserAdelantarAgenda?userID=' + userID);
        }

      obj.getPedidosUserReagendamiento = function(userID){
                return $http.get(serviceBase + 'pedidosPorUserReagendamiento?userID=' + userID);
        }

       obj.getLightKPISAgendamiento =function(){
                return $http.get(serviceBase + 'lightKPISAgendamiento');
        }

        obj.getPedidosPorPedidoAgendamiento = function(pedido){
                return $http.get(serviceBase + 'pedidosPorPedidoAgendamiento?pedido=' + pedido);
        }

       obj.getListadoPedidosAgendamiento = function(fecha_inicio,fecha_fin,page){
                return $http.get(serviceBase + 'listadoPedidosAgendamiento?fecha_inicio='+fecha_inicio+'&fecha_fin='+fecha_fin+'&page='+page);
        }

   obj.getListadoPendientesAgendamiento = function(fecha_inicio,fecha_fin,page){
                return $http.get(serviceBase + 'listadoPendientesAgendamiento?fecha_inicio='+fecha_inicio+'&fecha_fin='+fecha_fin+'&page='+page);
        }
      obj.getBuscarPedidoAgendamientoRegistro = function(bpedido){
                return $http.get(serviceBase + 'buscarPedidoAgendamientoRegistro?bpedido='+bpedido);
        }

        obj.getBuscarPedidoAgendamientoRegistro1 = function(bpedido,page){
                return $http.get(serviceBase + 'buscarPedidoAgendamientoRegistro1?bpedido='+bpedido+'&page='+page);
        }
       obj.getCsvPendientesAgendamiento = function(login){
                return $http.get(serviceBase + 'csvPendientesAgendamiento?login='+login);
        }

         obj.getCsvPendientesAgendamientoPredictiva = function(login){
                return $http.get(serviceBase + 'csvPendientesAgendamientoPredictiva?login='+login);
        }

	obj.getCsvAGENToday  = function(login){
                return $http.get(serviceBase + 'csvAGENToday?login='+login);
        }


        obj.getCsvPendientesAgenSiete = function(login){
                return $http.get(serviceBase + 'csvPendientesAgenSiete?login='+login);
        }

        obj.getCsvMalos = function(login,concepto){
                return $http.get(serviceBase + 'csvMalos?login='+login+'&concepto='+concepto);
        }

        obj.getCsvMalosAgendamiento = function(login){
                return $http.get(serviceBase + 'csvMalosAgendamiento?login='+login);
        }

          obj.getCsvAgendamiento  = function(login){
                return $http.get(serviceBase + 'csvAgendamiento?login='+login);
        }

    obj.getCsvHistoricosAgendamiento = function(login,fechaIni,fechaFin){
                return $http.get(serviceBase + 'csvHistoricosAgendamiento?login='+login+'&fechaIni='+fechaIni+'&fechaFin='+fechaFin);
        }

         obj.getCsvHistoricosAgendamientoEdatel = function(login,fechaIni,fechaFin){
                return $http.get(serviceBase + 'csvHistoricosAgendamientoEdatel?login='+login+'&fechaIni='+fechaIni+'&fechaFin='+fechaFin);
        }


     obj.getDashboardAgendamiento = function(){
                return $http.get(serviceBase + 'getDashboardAgendamiento');
        }
         obj.getDashboardAgendamientoPresupuestal = function(){
                return $http.get(serviceBase + 'getDashboardAgendamientoPresupuestal');
        }

     obj.demePedidoAgendamiento = function(user,departamento,zona,microzona,proceso,pedido_actual,plaza,username){

		console.log("zona="+zona+", microzona="+microzona+", proceso="+proceso);
		return $http.get(serviceBase+'demePedidoAgendamiento?userID='+user+'&departamento='+departamento+'&pedido_actual='+pedido_actual+'&plaza='+plaza+'&username='+username+'&zona='+zona+'&microzona='+microzona+'&proceso='+proceso);
    	}

    	obj.demePedidoAgendamientomalo = function(user,pedido_actual,plaza,username){

    		return $http.get(serviceBase+'demePedidoAgendamientomalo?userID='+user+'&pedido_actual='+pedido_actual+'&plaza='+plaza+'&username='+username);
    	}

    obj.getDepartamentosPendientesReagendamiento = function(){
		return $http.get(serviceBase + 'getDepartamentosPendientesReagendamiento');
	}
	//pantalla de agendamientos general
        obj.getDepartamentosPendientesReagendamiento = function(proceso){
                return $http.get(serviceBase + 'getDepartamentosPendientesReagendamiento?proceso='+proceso);
        }

         obj.getDepartamentosPendientesReagendamientoproceso = function(proceso){
                return $http.get(serviceBase + 'getDepartamentosPendientesReagendamientoproceso?proceso='+proceso);
        }


    obj.getDepartamentosOcupaagenda = function(){
        return $http.get(serviceBase + 'getDepartamentosOcupaagenda');
    }

     obj.getlistadoAgendamientoAuditoria = function(fecha_inicio,fecha_fin){
                return $http.get(serviceBase + 'listadoAgendamientoAuditoria?fecha_inicio='+fecha_inicio+'&fecha_fin='+fecha_fin);
        }
        obj.getcsvTabla = function(fecha_inicio,fecha_fin){
                return $http.get(serviceBase + 'csvTabla?fecha_inicio='+fecha_inicio+'&fecha_fin='+fecha_fin);
        }

         obj.getPendientesGraficaAgendamiento = function(){
                return $http.get(serviceBase + 'pendientesGraficaAgendamiento');
        }
        obj.getPendientesConceptosReagendamiento  = function(){
                return $http.get(serviceBase + 'pendientesPorConceptoReagendamiento');
        }

         obj.getZonasOcupaagenda = function(dep){
                return $http.get(serviceBase + 'getZonasOcupaagenda?departamento='+dep);
        }

    obj.getDepartamentosAdelantarAgenda = function(){
        return $http.get(serviceBase + 'getDepartamentosAdelantarAgenda');
    }

    obj.getPedidoActualmenteAgendado = function(depa, zona, microzona, fecha, asesor, pedido_actual){
        return $http.get(serviceBase + 'getPedidoActualmenteAgendado?departamento='+depa+'&zona='+zona+'&microzona='+microzona+'&fecha='+fecha+'&asesor='+asesor+'&pedido_actual='+pedido_actual);
    }


	obj.getZonasReagendamiento = function(dep){
                return $http.get(serviceBase + 'getZonasReagendamiento?departamento='+dep);
        }


    obj.getZonasAdelantarAgenda = function(dep){
                return $http.get(serviceBase + 'getZonasAdelantarAgenda?departamento='+dep);
    }

     obj.getMicrozonasReagendamiento = function(zona,depa){
                return $http.get(serviceBase + 'getMicrozonasReagendamiento?departamento='+depa+'&zona='+zona);
    }

    obj.getMicrozonasAdelantarAgenda = function(zona,depa){
                return $http.get(serviceBase + 'getMicrozonasAdelantarAgenda?departamento='+depa+'&zona='+zona);
    }

	obj.getOcupacion = function(fecha, depa, zona){
		return $http.get(serviceBase + 'getOcupacionAgendamiento?fecha='+fecha+'&depa='+depa+'&zona='+zona);
	}
     obj.getcodigo_resultado = function(fecha){
        return $http.get(serviceBase + 'getcodigo_resultado?fecha='+fecha);
    }



    obj.getPedidos_Microzonas = function(fecha){
        return $http.get(serviceBase + 'getPedidos_Microzonas?fecha='+fecha);
    }

     obj.buscarPedidoAgendamiento = function(pedido,pedido_actual,user,username){
                return $http.get(serviceBase + 'buscarPedidoAgendamiento?pedidoID='+pedido+ '&pedido_actual='+pedido_actual+ '&userID='+user+'&username='+username);
     }
      obj.insertPedidoAuditoria = function (pedido) {
                var data= $http.post(serviceBase + 'insertPedidoAuditoria', {"pedido":pedido});
        return data;
        };
     obj.insertPedidoReagendamiento = function (pedido) {
                var data= $http.post(serviceBase + 'insertPedidoReagendamiento', {"pedido":pedido});
                return data;
        };

        obj.insertPedidoAdelantarAgenda = function (pedido) {
                var data= $http.post(serviceBase + 'insertPedidoAdelantarAgenda', {"pedido":pedido});
                return data;
        };
     obj.insertMPedidomalo  = function (pedido) {
                var data= $http.post(serviceBase + 'insertMPedidomalo ', {"pedido":pedido});
                return data;
        };

    //----------prueba
    obj.listar1 = function(){
                return $http.get(serviceBase + 'listadoarchivosdocu1');
        }

     obj.eliminarfile1 = function(file){
                return $http.get(serviceBase + 'eliminarfile1?file='+file);
        }

    //----fin prueba

//-----------------------------------------------------------------------------------------fin_Agendamiento

 //-----------------------------------------------------------------------------------Activacion
       obj.getactividadesUser = function(userID){
                return $http.get(serviceBase + 'actividadesUser?userID=' + userID);
        }

       obj.getListadoActivacion = function(fecha_inicio,fecha_fin,page){
                return $http.get(serviceBase + 'listadoactivacion?fecha_inicio='+fecha_inicio+'&fecha_fin='+fecha_fin+'&page='+page);
        }


         obj.getListadoActivacionTabla = function(fecha_inicio,fecha_fin){
                return $http.get(serviceBase + 'listadoactivaciontabla?fecha_inicio='+fecha_inicio+'&fecha_fin='+fecha_fin);
        }
         obj.getListadoActivacionSiebel = function(fecha_inicio,fecha_fin){
                return $http.get(serviceBase + 'listadoactivacionsiebel?fecha_inicio='+fecha_inicio+'&fecha_fin='+fecha_fin);
        }


      obj.getListadoAlarmasActivacion = function(){
                return $http.get(serviceBase + 'listadoAlarmasActivacion');
        }

        obj.getUsuariosAlarmasActivacion = function(){
                return $http.get(serviceBase + 'usuariosAlarmasActivacion');
        }

    obj.actualizarAlarmaActivacion = function(responOne,responTwo,cola_id){
                return $http.get(serviceBase + 'actualizarAlarmaActivacion?responsable1='+responOne+'&responsable2='+responTwo+'&cola_id='+cola_id);
        }

    obj.insertarAlarmaActivacion = function (nuevaCola) {
        var data= $http.post(serviceBase + 'insertarAlarmaActivacion', {"nuevaCola":nuevaCola});
        return data;
    }


       obj.getListadoTransaccionesActividades = function(userID,fecha_inicio,fecha_fin,page){
                return $http.get(serviceBase + 'listadoTransaccionesActividades?fechaInicio='+fecha_inicio+'&fechaFin='+fecha_fin+'&page='+page+'&userID='+userID);
        }

         obj.getListadoTransaccionesActividades1 = function(userID,fecha_inicio,fecha_fin,page){
                return $http.get(serviceBase + 'listadoTransaccionesActividades1?fechaInicio='+fecha_inicio+'&fechaFin='+fecha_fin+'&page='+page);
        }

     obj.insertTransaccionActividades = function (transaccion) {
                var data= $http.post(serviceBase + 'insertTransaccionActividades', {"transaccion":transaccion});
                return data;
    };

     obj.editTransaccionActividades = function (transaccionA) {
        var data= $http.post(serviceBase + 'editTransaccionActividades', {"transaccionA":transaccionA});
        return data;
    };


     obj.getTransaccionActividades = function (actividadesID) {
        return $http.get(serviceBase + 'getTransaccionActividades?actividadesID='+actividadesID);
    };

       obj.getCsvactividades = function(login,fechaIni,fechaFin){
                return $http.get(serviceBase + 'csvactividades?login='+login+'&fechaIni='+fechaIni+'&fechaFin='+fechaFin);
        }


        obj.getCsvListadoActivacion = function(fechaini,fechafin){
                return $http.get(serviceBase + 'csvListadoActivacion?fechaini='+fechaini+'&fechafin='+fechafin);
        }


        obj.getCsvListadoActivacionnuevo = function(fechaini,fechafin){
                return $http.get(serviceBase + 'csvListadoActivacionnuevos?fechaini='+fechaini+'&fechafin='+fechafin);
        }


	obj.getCsvActivacion  = function(login){
                return $http.get(serviceBase + 'csvActivacion?login='+login);
        }
    obj.getCsvActivacionSiebel  = function(login){
                return $http.get(serviceBase + 'csvActivacionSiebel?login='+login);
        }

    obj.pendientesPorConceptoColaActivacion  = function(){
                return $http.get(serviceBase + 'pendientesPorColaConceptoActivacion');
        }

     obj.getDashboardActivacionMes = function(){
                return $http.get(serviceBase + 'getDashboardActivacionMes');
        }

       obj.getSeguimientoActivacionGraficaAD = function(){
                return $http.get(serviceBase + 'seguimientoactivacionGraficaAD');

        }

        obj.getPendientesGraficaAD = function(){
                return $http.get(serviceBase + 'pendientesGraficaAD');
        }

        obj.getPendientesSiebelGraficaAD = function(){
                return $http.get(serviceBase + 'PendientesSiebelGraficaAD');
        }

//------------------------------------------------------fin_Activacion


//-------------------------------------------------------------------asignaciones

	obj.logVista = function(userID,vista){
                return $http.get(serviceBase + 'logVista?userID=' + userID+'&vista='+vista);
        }

	obj.getLightKPIS =function(){
                return $http.get(serviceBase + 'lightKPIS');
        }

        obj.getLightKPISMaestro =function(){
                return $http.get(serviceBase + 'lightKPISMaestro');
        }


        obj.getPedidosPorPedido = function(pedido){
                return $http.get(serviceBase + 'pedidosPorPedido?pedido=' + pedido);
        }



	   obj.getListadoPedidos = function(fecha_inicio,fecha_fin,page,campo,valorCampo){
                return $http.get(serviceBase + 'listadoPedidos?fecha_inicio='+fecha_inicio+'&fecha_fin='+fecha_fin+'&page='+page+'&campo='+campo+'&valorCampo='+valorCampo);
        }

        obj.getVecinosPagina = function(pagina_inicial, pagina_final){
                return $http.get(serviceBase + 'vecinosPagina?pagina_inicial='+pagina_inicial+'&pagina_final='+pagina_final);
        }
		
		obj.getListadoPedidosReconfiguracion = function(fecha_inicio,fecha_fin,page,campo,valorCampo,userID){
                return $http.get(serviceBase + 'listadoPedidosReconfiguracion?fecha_inicio='+fecha_inicio+'&fecha_fin='+fecha_fin+'&page='+page+'&campo='+campo+'&valorCampo='+valorCampo+'&userID='+userID);
        }


        obj.getListadoPendientes2 = function(fecha_inicio,fecha_fin,concepto,page){
                return $http.get(serviceBase + 'listadoPendientes2?fecha_inicio='+fecha_inicio+'&fecha_fin='+fecha_fin+'&concepto='+concepto+'&page='+page);
        }


        obj.getListadoParametrizados = function(depa, zona){
                return $http.get(serviceBase + 'listadoParametrizadosSiebel?depa='+depa+'&zona='+zona);
        }

     obj.getListadoAdmonTips = function(){
                return $http.get(serviceBase + 'listadoAdmonTips');
        }

    obj.actualizarTablaGraficaCambioNuevoREDCO = function(){
                return $http.get(serviceBase + 'actualizarTablaGraficaCambioNuevoREDCO');
        }

    obj.listar = function(){
                return $http.get(serviceBase + 'listadoarchivosdocu');
        }

    obj.eliminarfile = function(file){
                return $http.get(serviceBase + 'eliminarfile?file='+file);
        }



    obj.getListadoTips = function(){
                return $http.get(serviceBase + 'getListadoTips');
        }

    obj.getTransaccionTip = function (id) {
        return $http.get(serviceBase + 'getTransaccionTip?id='+id);
    };

    obj.demeCapacidadPorDistancia = function (distancia) {
        return $http.get(serviceBase + 'demeCapacidadPorDistancia?distancia='+distancia);
    };

    obj.getVisualizacionTip = function (id) {
        return $http.get(serviceBase + 'getVisualizacionTip?id='+id);
    };

    obj.actualizarTip = function (guardarEdicion) {
        var data= $http.post(serviceBase + 'actualizarTip', {"guardarEdicion":guardarEdicion});
        return data;
    };

    obj.insertarTip = function (nuevoTip) {
        var data= $http.post(serviceBase + 'insertarTip', {"nuevoTip":nuevoTip});
        return data;
    };

    obj.deleteTip = function (id) {
        var data= $http.delete(serviceBase + 'deleteTip?id=' + id);
            return data;
    };


    obj.insertarDatoParametrizacion = function (depa, zona, AM, PM, fechaformato) {
        return $http.get (serviceBase + 'insertarDatoParametrizacion?depa='+depa+'&zona='+zona+'&AM='+AM+'&PM='+PM+'&fechaformato='+fechaformato);
    }

    obj.insertarDatoParametrizacion2 = function (depa, zona, fechaini, fechafin, AM, PM) {
        return $http.get (serviceBase + 'insertarDatoParametrizacion2?depa='+depa+'&zona='+zona+'&fechaini='+fechaini+'&fechafin='+fechafin+'&AM='+AM+'&PM='+PM);
    }
	
	obj.getListadoTransaccionesNCA = function(fecha_inicio,fecha_fin,page){
                return $http.get(serviceBase + 'listadoTransaccionesNCA?fechaInicio='+fecha_inicio+'&fechaFin='+fecha_fin+'&page='+page);
        }



	obj.getListadoUsuarios  = function(){
                return $http.get(serviceBase + 'listadoUsuarios');
        }


	obj.updateParametro  = function(parametro,valor,user){
                return $http.get(serviceBase + 'updateParametro?parametro='+parametro+'&valor='+valor+'&user='+user);
        }

    obj.buscarParametro  = function(parametro){
                return $http.get(serviceBase + 'buscarParametro?parametro='+parametro);
    }


    obj.getFeed  = function(){
                return $http.get(serviceBase + 'getFeed');
        }

    obj.getLoginFeed  = function(){
                return $http.get(serviceBase + 'getLoginFeed');
        }


	obj.insertTransaccionNCA = function (transaccion) {
                var data= $http.post(serviceBase + 'insertTransaccionNCA', {"transaccion":transaccion});
                return data;
    };



    obj.editTransaccionNCA = function (transaccionNCA) {
        var data= $http.post(serviceBase + 'editTransaccionNCA', {"transaccionNCA":transaccionNCA});
        return data;
    };



    obj.getTransaccionNCA = function (ncaID) {
        return $http.get(serviceBase + 'getTransaccionNCA?ncaID='+ncaID);
    };



    
    obj.getListadoConceptos  = function(){
                return $http.get(serviceBase + 'getConceptos');
        }

    obj.getListadoTransaccionesORD = function(fecha_inicio,fecha_fin,page){
                return $http.get(serviceBase + 'listadoTransaccionesORD?fechaInicio='+fecha_inicio+'&fechaFin='+fecha_fin+'&page='+page);
        }


    obj.insertTransaccionORD = function (transaccion) {
                var data= $http.post(serviceBase + 'insertTransaccionORD', {"transaccion":transaccion});
                return data;
    };

    obj.editTransaccionORD = function (transaccionORD) {
        var data= $http.post(serviceBase + 'editTransaccionORD', {"transaccionORD":transaccionORD});
        return data;
    };

    obj.getTransaccionORD = function (ordID) {
        return $http.get(serviceBase + 'getTransaccionORD?ordID='+ordID);
    };
    

	obj.insertUsuario = function (usuario13)  {
		console.log(usuario13);
                var data= $http.post(serviceBase + 'insertUsuario', {"usuario":usuario13});
                return data;
        };

	obj.editUsuario = function (usuario)  {
                console.log(usuario);
                var data= $http.post(serviceBase + 'editUsuario', {"usuario":usuario});
                return data;
        };
        

	obj.getUsuario = function (userID) {
		return $http.get(serviceBase + 'getUsuario?userID='+userID);
	};

        obj.getBuscarPedidoRegistro = function(bpedido,concepto){
                return $http.get(serviceBase + 'buscarPedidoRegistro?bpedido='+bpedido+'&concepto='+concepto);
        }



        obj.getBuscarDatos = function(usuario){
                return $http.get(serviceBase + 'BuscarDatos?usuario='+usuario);
        }


        obj.getCsvNCA = function(login,fechaIni,fechaFin){
                return $http.get(serviceBase + 'csvNCA?login='+login+'&fechaIni='+fechaIni+'&fechaFin='+fechaFin);
        }



	obj.getCsvPendientes = function(login,concepto){
                return $http.get(serviceBase + 'csvPendientes?login='+login+'&concepto='+concepto);
        }
		obj.getCsvPreInstalaciones = function(login){
                return $http.get(serviceBase + 'csvPreInstalaciones?login='+login);
        }
		



	obj.getCsvFenixNal  = function(login){
                return $http.get(serviceBase + 'csvFenixNal?login='+login);
        }

    obj.descargarfile  = function(file){
                return $http.get(serviceBase + 'descargarfile?file='+file);
        }

        obj.getCsvFenixBog  = function(login){
                return $http.get(serviceBase + 'csvFenixBog?login='+login);
        }

		obj.getCsvHistoricos = function(login,fechaIni,fechaFin,campo,valorCampo){
                return $http.get(serviceBase + 'csvHistoricos?login='+login+'&fechaIni='+fechaIni+'&fechaFin='+fechaFin+'&campo='+campo+'&valorCampo='+valorCampo);
        }
		
		obj.getCsvHistoricosReconfiguracion = function(userID,fechaIni,fechaFin,campo,valorCampo){
                return $http.get(serviceBase + 'csvHistoricosReconfiguracion?userID='+userID+'&fechaIni='+fechaIni+'&fechaFin='+fechaFin+'&campo='+campo+'&valorCampo='+valorCampo);
        }



        obj.csvParametrizacionSiebel = function(login){
                return $http.get(serviceBase + 'csvParametrizacionSiebel?login='+login);
        }

        obj.csvParametrizacionMicrozona = function(login, depa, zona){
                return $http.get(serviceBase + 'csvParametrizacionMicrozona?login='+login+'&depa='+depa+'&zona='+zona);
        }

		obj.getCsvGPON = function(olt,tarjeta,puerto,login){
              return $http.get(serviceBase + 'csvGPON?OLT='+olt+'&TARJETA='+tarjeta+'&PUERTO='+puerto+"&login="+login);
        }

	obj.getDashboardAsignaciones = function(){
                return $http.get(serviceBase + 'getDashboardAsignaciones');
        }

        obj.getDashboardPendientes = function(){
                return $http.get(serviceBase + 'getDashboardPendientes');
        }

        obj.getDashboardAsignacionesMes = function(){
                return $http.get(serviceBase + 'getDashboardAsignacionesMes');
        }

        obj.actualizarGraficaCambioNuevoHFC = function(){
                return $http.get(serviceBase + 'actualizarGraficaCambioNuevoHFC');
        }

        obj.actualizarGraficaCambioNuevoREDCO = function(){
                return $http.get(serviceBase + 'actualizarGraficaCambioNuevoREDCO');
        }

        obj.getDashboardAsignacionesMesCobre = function(){
                return $http.get(serviceBase + 'getDashboardAsignacionesMesCobre');
        }

        obj.getDashboardAsignacionesTecnologia = function(){
                return $http.get(serviceBase + 'getDashboardAsignacionesTecnologia');
        }

	obj.getDashboardReconfiguracion = function(){
                return $http.get(serviceBase + 'getDashboardReconfiguracion');
        }

        obj.getDashboardReconfiguracionMes = function(){
                return $http.get(serviceBase + 'getDashboardReconfiguracionMes');
        }



        obj.getPendientesGrafica = function(){
                return $http.get(serviceBase + 'pendientesGrafica');
        }

        obj.getPedidosConAgenda  = function(){
                return $http.get(serviceBase + 'pedidosConAgenda');
        }

	obj.pendientesPorPlaza = function(){
		return $http.get(serviceBase + 'pendientesPorPlaza');
	}


	
	obj.getPendientesIngresosEstudiosGrafica = function(fecha1,fecha2){
                return $http.get(serviceBase + 'ingresosEstudiosGrafica?fechaIni='+fecha1+'&fechaFin='+fecha2);
        }


	obj.getTME  = function(fecha1,fecha2){
                return $http.get(serviceBase + 'calcularDetalleTME?fechaIni='+fecha1+'&fechaFin='+fecha2);
        }
	obj.getProductividadGrupo  = function(fecha1,fecha2){
                return $http.get(serviceBase + 'productividadGrupo?fechaIni='+fecha1+'&fechaFin='+fecha2);
        }
//metodo para obtener departamentos de pantalla de auditoria


obj.getDepartamentosParametrizacionSiebel = function(){
        return $http.get(serviceBase + 'getDepartamentosParametrizacionSiebel');
    }


    obj.getZonasParametrizacionSiebel = function(dep){
                return $http.get(serviceBase + 'getZonasParametrizacionSiebel?departamento='+dep);
    }    



    obj.demePedido = function(user,concepto,pedido_actual,plaza,username,prioridad,fuente){
		var muni=""
		if(concepto=="Bello"){
			muni="&municipio=BELANTCOL";
		}
		return $http.get(serviceBase + 'demePedido?userID='+user+'&concepto='+concepto+'&pedido_actual='+pedido_actual+'&plaza='+plaza+'&username='+username+'&prioridad='+prioridad+'&fuente='+fuente);
    }

        obj.demePedidoReconfiguracion = function(user,concepto,pedido_actual,plaza){

                return $http.get(serviceBase + 'demePedidoReconfiguracion?userID='+user+'&concepto='+concepto+'&pedido_actual='+pedido_actual+'&plaza='+plaza);
        }

	//aca no nos importaria el concepto, sin embargo deberia traerlo para actualizarlo?
        obj.buscarPedido = function(pedido,plaza,pedido_actual,user,username){
                return $http.get(serviceBase + 'buscarPedido?pedidoID='+pedido+ '&plaza='+plaza+ '&pedido_actual='+pedido_actual+ '&userID='+user+'&username='+username);
        }


	obj.buscarPedidoReconfiguracion = function(pedido,plaza,pedido_actual,user,username){
                return $http.get(serviceBase + 'buscarPedidoReconfiguracion?pedidoID='+pedido+ '&plaza='+plaza+ '&pedido_actual='+pedido_actual+ '&userID='+user+'&username='+username);
        }




     
        obj.buscarCmts = function(nnodo){
                return $http.get(serviceBase + 'buscarcmts?nodo_id='+nnodo+'');
        }

	obj.getServicesGPON = function (olt,tarjeta,puerto){
		return $http.get(serviceBase + 'getServicesGPON?OLT='+olt+'&TARJETA='+tarjeta+'&PUERTO='+puerto+' ');
	}

    obj.buscarCapaCobre = function (armario){
        return $http.get(serviceBase + 'buscarCapaCobre?armario='+armario+' ');
    }

        obj.insertPedido = function (pedido) {
                var data= $http.post(serviceBase + 'insertPedido', {"pedido":pedido});
		return data;
        };


        obj.insertPedidoReconfiguracion = function (pedido) {
                var data= $http.post(serviceBase + 'insertPedidoReconfiguracion', {"pedido":pedido});
                return data;
        };




        obj.insertMPedido = function (pedido) {
		//console.log(pedido);
                var data= $http.post(serviceBase + 'insertMPedido', {"pedido":pedido}).then(function (status) {
                        pedido.fecha=status.data['data'];
                        pedido.concepto_final=status.data['msg'];
                return status;
           });
                return data;
        };
      


    	obj.getCustomer = function(customerID){
        	return $http.get(serviceBase + 'customer?id=' + customerID);
    	}

        obj.logout = function (user) {
		var tiempo=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = (date1.getMonth()+1<=9)?'0'+(date1.getMonth()+1):(date1.getMonth()+1);
                var day     = (date1.getDate()<=9)?'0'+date1.getDate():date1.getDate();
                var hour    = (date1.getHours()<=9)?'0'+date1.getHours():date1.getHours();
                var minute  = (date1.getMinutes()<=9)?'0'+date1.getMinutes():date1.getMinutes();
                var seconds = (date1.getSeconds()<=9)?'0'+date1.getSeconds():date1.getSeconds();

                tiempo=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                return $http.post(serviceBase + 'logout', {'user': user,'fecha':tiempo}).then(function (results) {
                return results;
           });
        };

    	obj.insertCustomer = function (customer) {
    		return $http.post(serviceBase + 'insertCustomer', customer).then(function (results) {
        	return results;
    	   });
	};

	obj.updateCustomer = function (id,customer) {
	    	return $http.post(serviceBase + 'updateCustomer', {id:id, customer:customer}).then(function (status) {
	        return status.data;
	    });
	};

	obj.deleteCustomer = function (id) {
	    	return $http.delete(serviceBase + 'deleteCustomer?id=' + id).then(function (status) {
	        return status.data;
	    });
	};
	
	obj.login = function (username,password,tiempo) {
                return $http.post(serviceBase + 'login', {"username":username,"password":password,"fecha":tiempo});
        };
      obj.getcsvDatosAgendamiento= function(fecha,login, depa, zona){
                return $http.get(serviceBase + 'csvDatosAgendamiento?fecha='+ fecha+'&login='+login+'&depa='+depa+'&zona='+zona);
        }
        obj.getcsvPedidosMicrozonas= function(fecha,login){
                return $http.get(serviceBase + 'csvPedidosMicrozonas?fecha='+ fecha+'&login='+login);
        }
         obj.getcsvCodigoResultado= function(fecha,login){
                return $http.get(serviceBase + 'csvCodigoResultado?fecha='+ fecha+'&login='+login);
        }


     obj.getScheduling = function(page){
                return $http.get(serviceBase + 'listadoScheduling?page='+ page);
     }

     obj.getCsvScheduling = function(login){
            return $http.get(serviceBase + 'csvScheduling?login='+login);
     }

     obj.getCsvSchedulingPre = function(login){
            return $http.get(serviceBase + 'csvSchedulingPre?login='+login);
     }

     obj.getCsvSchedulingPedidos = function(login){
            return $http.get(serviceBase + 'csvSchedulingPedidos?login='+login);
     }

	 // Siebel Asignaciones -----------------------------

	 obj.getlistadoOpcionesSiebelAsignaciones= function () {
     	return $http.get(serviceBase + 'listadoOpcionesSiebel');
        };

	obj.getBuscarOfertaSiebelAsignaciones = function(pedido,pedido_actual,user){
        return $http.get(serviceBase + 'buscarOfertaSiebelAsignaciones?pedidoID='+pedido+'&pedido_actual='+pedido_actual+ '&userID='+user);
        }

	 // -----------------------------  Siebel Asignaciones//

	obj.getPedidosGestorUser = function(){
        return $http.get(serviceBase + 'PedidosGestorUser');
        }

//-------------------------------------------------------------------Fin-asignaciones








    	return obj;
}]);




app.controller('DashboardCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {
        var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";

        $rootScope.actualView="dashboard";
        $scope.datosGrafica=[];
        $scope.listado_usuarios_produccion=[];
        $scope.listado_tme=[];
        $scope.lastUpdate="";
        $scope.ordenamientoDemepedido='';
        $scope.ordenamientoDemepedidoUpdate='';

	$scope.asignacioneskpi='0';
	$scope.reconfiguracionkpi='0';
	$scope.agendamientokpi='0';
	$scope.activacionkpi='0';
	$scope.actualizarLightKPISMaestro='';

        $scope.intervalFeed=0;
        $scope.intervalGrafica=0;

        $scope.totalAD="0";

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
            $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

        $scope.doubleDigit= function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
            };

	$scope.intervalLightKPIS = setInterval(function(){
    		$scope.actualizarLightKPIS();
	},60000);


    	$scope.actualizarLightKPIS = function (){
        	services.getLightKPISMaestro().then(function(data){
                	$scope.asignacioneskpi=data.data[0];
		        $scope.reconfiguracionkpi=data.data[1];
		        $scope.agendamientokpi=data.data[2];
		        $scope.activacionkpi=data.data[3];

                	return data.data;
        	});
    	}

	$scope.actualizarLightKPIS();

        $scope.intervalFeed = setInterval(function(){
                $scope.getFeed();
                $scope.getLoginFeed();
           },10000);


        $scope.getFeed = function (){
                services.getFeed().then(function(data){
                        $scope.listado_feed=data.data[0];
                        $scope.total_feed=data.data[1];
                        return data.data;
                });

        }

        $scope.$on(
            "$destroy",
                function( event ) {
                    clearInterval($scope.intervalFeed);
                }
        );



    $scope.getLoginFeed = function (){
        services.getLoginFeed().then(function(data){
                        $scope.login_feed=data.data[0];
                        $scope.total_feed=data.data[1];
                        return data.data;
                });
    }

                $scope.getFeed();
                $scope.getLoginFeed();



        $scope.myDataSourcePendientes = {
                        chart: {
                        startingangle: "120",
                        showlabels: "1",
                        showlegend: "1",
                        enablemultislicing: "0",
                        paletteColors: "#008ee4",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        },
                        data: []

        };

        $scope.actualizarGraficaPendientes   = function (){
                var data1=services.getDashboardPendientes().then(function(data){
                        var categorias=data.data[0];
                        var asignaciones=data.data[1];
                        var reconfiguracion=data.data[2];
                        var agendamiento=data.data[3];
                        var activacion=data.data[4];

                        $scope.myDataSourcePendientes = {

                            chart: {
                                "xAxisName": "Mes",
                                "yAxisName": "Asignaciones",
                                "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                "rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "showValues": "0",
                                "decimals": "1",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14",
                                "toolTipBorderColor": "#FFFFFF",
                                "toolTipBgAlpha": "80",
                            },
                                "categories": [ { "category": categorias } ] ,
    "dataset": [
        {
            "seriesname": "Asignaciones",
            "renderas": "area",
            "data": asignaciones
        },
	        {
            "seriesname": "Reconfiguracion",
            "renderas": "area",
            "data": reconfiguracion
        },
        {
            "seriesname": "Agendamiento",
            "renderas": "area",
            "data": agendamiento
        },
        {
            "seriesname": "Activacion",
            "renderas": "area",
            "data": activacion
        },

    ]
   };
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                        return data.data;
                });

                services.logVista($cookieStore.get('logedUser').login,"Dashboard grafica pendientes");

        };

	$scope.actualizarGraficaPendientes();


        $scope.myDataSourceAsignaciones = {
                        chart: {
                        startingangle: "120",
                        showlabels: "1",
                        showlegend: "1",
                        enablemultislicing: "0",
                        paletteColors: "#008ee4",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        },
                        data: []

        };



        $scope.myDataSourceAsignacionesMes = {
                        chart: {
                        startingangle: "120",
                        showlabels: "1",
                        showlegend: "1",
                        enablemultislicing: "0",
                        paletteColors: "#008ee4",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        },
                        data: []

        };

        $scope.myDataSourceAsignacionesMesCobre = {
                        chart: {
                        startingangle: "120",
                        showlabels: "1",
                        showlegend: "1",
                        enablemultislicing: "0",
                        paletteColors: "#008ee4",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        },
                        data: []

        };

        $scope.myDataSourceAsignacionesMesCobre = {
                        chart: {
                        startingangle: "120",
                        showlabels: "1",
                        showlegend: "1",
                        enablemultislicing: "0",
                        paletteColors: "#008ee4",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        },
                        data: []

        };

        $scope.myDataSourceCambioNuevoHFC = {
                        chart: {
                        startingangle: "120",
                        showlabels: "1",
                        showlegend: "1",
                        enablemultislicing: "0",
                        paletteColors: "#008ee4",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        },
                        data: []

        };

         $scope.myDataSourceCambioNuevoREDCO = {
                        chart: {
                        startingangle: "120",
                        showlabels: "1",
                        showlegend: "1",
                        enablemultislicing: "0",
                        paletteColors: "#008ee4",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        },
                        data: []

        };


$scope.actualizarGraficaCambioNuevoREDCO  = function (){
    var data1=services.actualizarGraficaCambioNuevoREDCO().then(function(data){
             var categorias=data.data[0];
             var manual=data.data[1];
             var auto=data.data[2];
             var manu_cambio=data.data[3];
             var manu_nuevo=data.data[4];
             var auto_nuevo=data.data[5];
             var auto_cambio=data.data[6];

             $scope.meses=data.data[8];

             //var inicial= automatico[0];
             //var finall=automatico[automatico.length-1];
        
            //inicial=inicial['value'];
            //finall=finall['value'];

    $scope.myDataSourceCambioNuevoREDCO = {
                            chart: { "xAxisName": "Mes",
                                //"yAxisName": "Reconfiguracion",
                                "numberScaleValue":".01",
                                //"numberScaleUnit":"%",
                                "paletteColors": "#428bca,#5bc0de,#5cb85c,#f0ad4e",
                                "bgColor": "#ffffff",
                                "rotateValues": "1",
                                "showCanvasBorder": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "legendBorderAlpha": "0",
                                "legendShadow": "0",                   
                                "divLineIsDashed": "1",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "placevaluesInside": "0",
                                "valueFontColor": "#FFFFFF",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "showPercentValues": "1",
                                //"showPercentInTooltip": "0",
                                "decimals": "1",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14",
                                "toolTipBorderColor": "#FFFFFF",
                                "toolTipBgAlpha": "80",
                                "pyaxisname": "Reconfiguracion",
             "syaxisname": "Tiempo Promedio",
                                "setadaptiveymin": "1",
                                "setadaptivesymin": "1",
            },
               "categories": [ { "category": categorias } ] ,
    "dataset": [
        {

            "dataset": 
            [
                {"seriesname": "Cambio Manual",
                    "data": manu_cambio
                },
                {"seriesname": "Nuevo Manual",
                     "data": manu_nuevo
                }
            ]
        },        
        {
            "dataset": 
            [
                {"seriesname": "Cambio Automatico",
                    "data": auto_cambio
                },
                {"seriesname": "Nuevo Automatico",
                     "data": auto_nuevo
                }
            ]
        }   
    ]
};

 var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                        return data.data;
});
                    services.actualizarTablaGraficaCambioNuevoREDCO();
                //services.logVista($cookieStore.get('logedUser').login,"Indicador Asignaciones Mes");
};


    services.actualizarTablaGraficaCambioNuevoREDCO().then(function(data){
            $scope.tabla=data.data[0];
            console.log ($scope.tabla);
                return data.data;
        });


  $scope.actualizarGraficaCambioNuevoHFC  = function (){
    var data1=services.actualizarGraficaCambioNuevoHFC().then(function(data){
             var categorias=data.data[0];
             var manual=data.data[1];
             var auto=data.data[2];
             var manu_cambio=data.data[3];
             var manu_nuevo=data.data[4];
             var auto_nuevo=data.data[5];
             var auto_cambio=data.data[6];

             //var inicial= automatico[0];
             //var finall=automatico[automatico.length-1];
        
            //inicial=inicial['value'];
            //finall=finall['value'];

    $scope.myDataSourceCambioNuevoHFC = {
                            chart: { "xAxisName": "Mes",
                                //"yAxisName": "Reconfiguracion",
                                "numberScaleValue":".01",
                                //"numberScaleUnit":"%",
                                "paletteColors": "#428bca,#5bc0de,#5cb85c,#f0ad4e",
                                "bgColor": "#ffffff",
                                "rotateValues": "1",
                                "showCanvasBorder": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "legendBorderAlpha": "0",
                                "legendShadow": "0",                   
                                "divLineIsDashed": "1",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "placevaluesInside": "0",
                                "valueFontColor": "#FFFFFF",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "showPercentValues": "1",
                                //"showPercentInTooltip": "0",
                                "decimals": "1",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14",
                                "toolTipBorderColor": "#FFFFFF",
                                "toolTipBgAlpha": "80",
                                "pyaxisname": "Reconfiguracion",
             "syaxisname": "Tiempo Promedio",
                                "setadaptiveymin": "1",
                                "setadaptivesymin": "1",
            },
               "categories": [ { "category": categorias } ] ,
    "dataset": [
        {

            "dataset": 
            [
                {"seriesname": "Cambio Manual",
                    "data": manu_cambio
                },
                {"seriesname": "Nuevo Manual",
                     "data": manu_nuevo
                }
            ]
        },        
        {
            "dataset": 
            [
                {"seriesname": "Cambio Automatico",
                    "data": auto_cambio
                },
                {"seriesname": "Nuevo Automatico",
                     "data": auto_nuevo
                }
            ]
        }   
    ]
};

 var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                        return data.data;
});

                //services.logVista($cookieStore.get('logedUser').login,"Indicador Asignaciones Mes");
};


        $scope.actualizarGraficaAsignacionesMes   = function (){
                var data1=services.getDashboardAsignacionesMes().then(function(data){
                        var categorias=data.data[0];
                        var manual=data.data[1];
                        var automatico=data.data[2];
                        var totales=data.data[3];

			var inicial= automatico[0];
			var finall=automatico[automatico.length-1];
		
			inicial=inicial['value'];
			finall=finall['value'];
                        $scope.myDataSourceAsignacionesMes = {

                            chart: {
                                "xAxisName": "Mes",
                                "yAxisName": "Asignaciones",
				         "numberScaleValue":".01",
				                "numberScaleUnit":"%",
                        "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                "rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "showPercentValues": "1",
			        "showPercentInTooltip": "0",
			        "decimals": "1",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14",
                                "toolTipBorderColor": "#FFFFFF",
                                "toolTipBgAlpha": "80",
                            },
                                "categories": [ { "category": categorias } ] ,
       "trendlines": [
        {
            "line": [
                {
                    "startvalue": inicial,
                    "endValue": finall,
                    "color": "#1aaf5d",
                    "valueOnRight": "1",
                    "dashed": "1",
                    "thickness":"4",
                    "alpha": "100",
                    "displayvalue": "Trend"
                }
            ]
        }
    ],
    "dataset": [
        {
            "seriesname": "Manual",
            "data": manual
        },
        {
            "seriesname": "Automatico",
            "renderas": "mscolumn2d",
            "showvalues": "1",
            "theme":"carbon",
            "data": automatico
        },

    ],
};

                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                        return data.data;
                });

                services.logVista($cookieStore.get('logedUser').login,"Indicador Asignaciones Mes");

        };

        $scope.actualizarGraficaAsignacionesMesCobre   = function (){
                var data1=services.getDashboardAsignacionesMesCobre().then(function(data){
                        var categorias=data.data[0];
                        var manual=data.data[1];
                        var automatico=data.data[2];
                        var totales=data.data[3];

                        var inicial= automatico[0];
                        var finall=automatico[automatico.length-1];

                        inicial=inicial['value'];
                        finall=finall['value'];
                        $scope.myDataSourceAsignacionesMesCobre = {

                            chart: {
                                "xAxisName": "Mes",
                                "yAxisName": "Asignaciones",
                                "numberScaleValue":".01",
                                "numberScaleUnit":"%",
                                "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                "rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "showPercentValues": "1",
                                "showPercentInTooltip": "0",
                                "decimals": "1",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14",
                                "toolTipBorderColor": "#FFFFFF",
                                "toolTipBgAlpha": "80",
                            },
                                "categories": [ { "category": categorias } ] ,
       "trendlines": [
        {
            "line": [
                {
                    "startvalue": inicial,
                    "endValue": finall,
                    "color": "#1aaf5d",
                    "valueOnRight": "1",
                    "dashed": "1",
                    "thickness":"4",
                    "alpha": "100",
                    "displayvalue": "Trend"
                }
            ]
        }
    ],
    "dataset": [
        {
            "seriesname": "Manual Cobre",
            "data": manual
        },
        {
            "seriesname": "Automatico Cobre",
            "renderas": "mscolumn2d",
            "showvalues": "1",
            "theme":"carbon",
            "data": automatico
        },

    ],
};

                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                        return data.data;
                });

                services.logVista($cookieStore.get('logedUser').login,"Indicador Asignaciones Mes");

        };



	$scope.actualizarGraficaAsignaciones   = function (){
                var data1=services.getDashboardAsignaciones().then(function(data){
			var categorias=data.data[0];
			var totales=data.data[1];
			var automatico=data.data[2];

			console.log(totales);
                        $scope.myDataSourceAsignaciones = {

                            chart: {
                                "xAxisName": "Fecha",
                                "yAxisName": "Asignaciones",
                                "numberPrefix": "",
                                "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                "rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
				"showPercentValues": "1",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14",
			        "toolTipBorderColor": "#FFFFFF",
			        "toolTipBgAlpha": "80",
                            },

				"categories": [ { "category": categorias } ] ,
    "dataset": [
        {
            "seriesname": "Total Asignaciones",
            "data": totales
        },
        {
            "seriesname": "Automatico",
            "renderas": "area",
            "showvalues": "1",
	    "theme":"carbon",
            "data": automatico
        }
    ]
                        };

                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                        return data.data;
                });

                services.logVista($cookieStore.get('logedUser').login,"Indicador Asignaciones ultimos dias");

        };
$scope.actualizarGraficaAgendamiento   = function (){
                var data1=services.getDashboardAgendamiento().then(function(data){
            var categorias=data.data[0];
            var o_terreno=data.data[1];
            var o_reagendadas=data.data[2];

            console.log(categorias);
                        $scope.myDataSourceAgendamiento = {

                            chart: {
                                "xAxisName": "Fecha",
                                "yAxisName": "Agendamiento",
                                "numberPrefix": "",
                                "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                //"rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "showPercentValues": "1",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14",
                                "toolTipBorderColor": "#FFFFFF",
                                "toolTipBgAlpha": "80",
                            },

                "categories": [ { "category": categorias } ] ,
    "dataset": [
        {
            "seriesname": "ordenes_terreno",
            "data": o_terreno
        },
        {
            "seriesname": "ordenes_reagendas",
            "renderas": "area",
            "showvalues": "1",
        "theme":"carbon",
            "data": o_reagendadas
        }
    ]
                        };

                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                        return data.data;
                });

                services.logVista($cookieStore.get('logedUser').login,"Indicador Agendamientos ultimos dias");

        };

        $scope.myDataSourceAgendamiento = {
                        chart: {
                        startingangle: "120",
                        showlabels: "1",
                        showlegend: "1",
                        enablemultislicing: "0",
                        paletteColors: "#008ee4",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        },
                        data: []

        };
        $scope.actualizarGraficaAgendamientoPresupuestal   = function (){
                var data1=services.getDashboardAgendamientoPresupuestal().then(function(data){
            var categorias=data.data[0];
            var ejecutado=data.data[1];
            var meta=data.data[2];

            console.log(ejecutado);
                        $scope.myDataSourceAgendamientoPresupuestal = {

                            chart: {
                                "xAxisName": "Fecha",
                                "yAxisName": "AgendamientoPresupuestal",
                                "numberPrefix": "$",
                                "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                "rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "showPercentValues": "1",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14",
                                "toolTipBorderColor": "#FFFFFF",
                                "toolTipBgAlpha": "80",
			        "formatNumberScale": "0",
		        	"decimalSeparator": ",",
	        		"thousandSeparator": ".",
				"rotateValues": "1",
                            },

                "categories": [ { "category": categorias } ] ,
    "dataset": [
        {
            "seriesname": "ejecutado",
            "data": ejecutado
        },
        {
            "seriesname": "meta",
            "renderas": "line",
            "showvalues": "0",
	    "rotateValues": "0",
        "theme":"carbon",
            "data": meta
        }
    ]
                        };

                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                        return data.data;
                });

                services.logVista($cookieStore.get('logedUser').login,"Indicador Agendamientos Presupuestal ultimos dias");

        };

        $scope.myDataSourceAgendamientoPresupuestal = {
                        chart: {
                        startingangle: "120",
                        showlabels: "1",
                        showlegend: "1",
                        enablemultislicing: "0",
                        paletteColors: "#008ee4",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        },
                        data: []

        };

        $scope.myDataSourceAsignacionesTecnologia = {
                        chart: {
                        startingangle: "120",
                        showlabels: "1",
                        showlegend: "1",
                        enablemultislicing: "0",
                        paletteColors: "#008ee4",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        },
                        data: []

        };
       $scope.actualizarGraficaAsignacionesTecnologia  = function (){
        var data1=services.getDashboardAsignacionesTecnologia().then(function(data){
                var categorias=data.data[0];
                var redco=data.data[2];
                var hfc=data.data[3];
                var gpon=data.data[4];
                var otra=data.data[5];
                var sin=data.data[6];


               

                $scope.myDataSourceAsignacionesTecnologia = {

                     chart: {
                               "xAxisName": "Mes",
                                //"yAxisName": "Reconfiguracion",
                                "numberScaleValue":".01",
                                //"numberScaleUnit":"%",
                                "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                "rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "showPercentValues": "1",
                                //"showPercentInTooltip": "0",
                                "decimals": "1",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14",
                                "toolTipBorderColor": "#FFFFFF",
                                "toolTipBgAlpha": "80",
                                "pyaxisname": "Reconfiguracion",
             "syaxisname": "Tiempo Promedio",
                                "setadaptiveymin": "1",
                                "setadaptivesymin": "1",
            //  "stack100percent": "1",

                    },
                        "categories": [ { "category": categorias } ] ,
                    "dataset": [
                        {   
                             "seriesname": "hfc",
                            "data": hfc
                        },
                        {
                            "seriesname": "redco",
                            "data": redco
                        },
                        {
                            "seriesname": "gpon",
                            "data": gpon
                        },
                        {
                            "seriesname": "otra",
                            "data": otra
                        },
                         {
                            "seriesname": "sin",
                            "data": sin
                        },

                    ]
                   };
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                        return data.data;
                });

                services.logVista($cookieStore.get('logedUser').login,"Dashboard grafica reconfiguracion mes");

        };

         $scope.myDataSourceReconfiguracion = {
                        chart: {
                        startingangle: "120",
                        showlabels: "1",
                        showlegend: "1",
                        enablemultislicing: "0",
                        paletteColors: "#008ee4",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        },
                        data: []

        };

 

$scope.actualizarGraficaReconfiguracion   = function (){
        var data1=services.getDashboardReconfiguracion().then(function(data){
                var categorias=data.data[0];
                var estudios_manuales=data.data[1];
                var p14=data.data[2];
                var p99=data.data[3];
                
		$scope.myDataSourceReconfiguracion = {

                    chart: {
                        "xAxisName": "Dias",
                        "yAxisName": "Estudios x Reconfiguracion",
                        "bgColor": "#ffffff",
                        "borderAlpha": "20",
                        "canvasBorderAlpha": "0",
                        "usePlotGradientColor": "0",
                        "plotBorderAlpha": "10",
                        "placevaluesInside": "0",
                        "rotatevalues": "0",
                        "valueFontColor": "#0075c2",
                        "showXAxisLine": "1",
                        "xAxisLineColor": "#999999",
                        "divlineColor": "#999999",
                        "divLineDashed": "1",
                        "showAlternateHGridColor": "0",
                        "showValues": "1",
                        "decimals": "1",
                        "subcaptionFontBold": "0",
                        "subcaptionFontSize": "14",
                        "toolTipBorderColor": "#FFFFFF",
                        "toolTipBgAlpha": "80",
			"labelDisplay":"rotate",
			"slantLabels": "1",
			"rotateValues": "1",
                    },
                        "categories": [ { "category": categorias } ] ,
                    "dataset": [
                        {
                            "seriesname": "Estudios manuales",
                            "renderas": "mscolumn2d",
                            "data": estudios_manuales
                        },
                                {
                            "seriesname": "Pedidos en 14",
                            "renderas": "mscolumn2d",
                            "data": p14
                        },
                        {
                            "seriesname": "Pedidos en 99",
                            "renderas": "mscolumn2d",
                            "data": p99
                        },
                    ]
                   };
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                        return data.data;
                });

                services.logVista($cookieStore.get('logedUser').login,"Dashboard grafica reconfiguracion");

        };


        $scope.myDataSourceReconfiguracionMes = {
                        chart: {
                        startingangle: "120",
                        showlabels: "1",
                        showlegend: "1",
                        enablemultislicing: "0",
                        paletteColors: "#008ee4",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        },
                        data: []

        };

$scope.actualizarGraficaReconfiguracionMes   = function (){
        var data1=services.getDashboardReconfiguracionMes().then(function(data){
                var categorias=data.data[0];
                var estudios_manuales=data.data[1];
                var p14=data.data[2];
                var p99=data.data[3];
		var t14=data.data[5];
		var t99=data.data[6];

                $scope.myDataSourceReconfiguracionMes = {

                     chart: {
                                "xAxisName": "Mes",
                                //"yAxisName": "Reconfiguracion",
                                "numberScaleValue":".01",
                                //"numberScaleUnit":"%",
                                "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                "rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "showPercentValues": "1",
                                //"showPercentInTooltip": "0",
                                "decimals": "1",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14",
                                "toolTipBorderColor": "#FFFFFF",
                                "toolTipBgAlpha": "80",
				"pyaxisname": "Reconfiguracion",
        			"syaxisname": "Tiempo Promedio",
				"setadaptiveymin": "1",
			        "setadaptivesymin": "1",
			//	"stack100percent": "1",

                    },
                        "categories": [ { "category": categorias } ] ,
                    "dataset": [
			{
                            "seriesname": "Estudios Manuales",
                            "data": estudios_manuales
                        },
                        {
                            "seriesname": "Pedidos en 14",
                            "data": p14
                        },
                        {
                            "seriesname": "Pedidos en 99",
                            "data": p99
                        },
                    ]
                   };
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                        return data.data;
                });

                services.logVista($cookieStore.get('logedUser').login,"Dashboard grafica reconfiguracion mes");

        };

        $scope.myDataSourceActivacionMes = {
                        chart: {
                        startingangle: "120",
                        showlabels: "1",
                        showlegend: "1",
                        enablemultislicing: "0",
                        paletteColors: "#008ee4",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        },
                        data: []

        };


    $scope.actualizarGraficaActivacionMes   = function (){
        var data1=services.getDashboardActivacionMes().then(function(data){
                //var nombremes=data.data[0];
                //var tmaa=data.data[1];
                //console.log(nombremes);
                //var p14=data.data[2];
                //var p99=data.data[3];

                $scope.myDataSourceActivacionMes = {
                                chart: {
				"xAxisName": "Mes",
                                "yAxisName": "TMA",
                                "numberPrefix": "",
                                "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                "rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "showPercentValues": "1",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14",
                                "toolTipBorderColor": "#FFFFFF",
                                "toolTipBgAlpha": "80",

                        },
                                data: data.data

                        }

                
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                        return data.data;
                });
                //$scope.actualizarGraficaActivacionMes();

                services.logVista($cookieStore.get('logedUser').login,"Dashboard grafica activacion mes");

        };


});

//**********************************Upload*******************************
app.controller('DocuActivacion', function ($scope, $rootScope, $http, $location, $routeParams,$cookies,$cookieStore, services, FileUploader) {
    // Uploader Plugin Code

     var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";

$rootScope.logout = function() {
            services.logout($rootScope.logedUser.login);
            $cookieStore.remove('logedUser');
            $rootScope.logedUser=undefined;
            $scope.pedidos={};
            document.getElementById('logout').className="btn btn-md btn-danger hide";
            var divi=document.getElementById("logoutdiv");
            divi.style.position="absolute";
            divi.style.visibility="hidden";
            $location.path('/');
    };
  

   var uploader = $scope.uploader = new FileUploader({
        url: window.location.protocol + window.location.pathname + 'services/UploadFile1'
    });

                services.listar().then(function(data){
                        $scope.listadodocu=data.data[0];
                        console.log($scope.listadodocu);
                        return data.data;
                });
    // FILTERS

        $scope.eliminarfile = function (file){
                        //console.log(data.data);
                var result = confirm("Esta seguro que desea eliminar el archivo "+file+ "?");
                if (result) {
                                //Logic to delete the item
                    services.eliminarfile(file).then(function(data){
                                if(data.data=='OK'){
                                    document.getElementById("warning").innerHTML="Archivo "+file+" eliminado correctamente.";
                                $scope.error="Archivo "+file+" eliminado correctamente.";
                                }
                               services.listar().then(function(data){
                                    $scope.listadodocu=data.data[0];
                                    //console.log($scope.listadodocu);
                                    return data.data;
                                });
                        });
                };
        };
    //$scope.reload();
    //$interval($scope.reload, 5000);

    uploader.filters.push({
        name: 'extensionFilter',
        fn: function (item, options) {
            var filename = item.name;
            var extension = filename.substring(filename.lastIndexOf('.') + 1).toLowerCase();
            if (extension == "pdf" || extension == "ppt" || extension == "pptx" || extension == "doc" || extension == "docx" || extension == "xlsx" || extension == "xls" ||
                extension == "rtf")
                return true;
            else {
                alert('Formato Invalido. Por favor seleccione un archivo con formato ppt/pptx/pdf/doc/docs/xlsx/xls o rtf');
                return false;
            }
        }
    });

    uploader.filters.push({
        name: 'sizeFilter',
        fn: function (item, options) {
            var fileSize = item.size;
            fileSize = parseInt(fileSize) / (1024 * 1024);
            if (fileSize <= 20)
                return true;
            else {
                alert('El archivo seleccionado excede el límite de tamaño(20 MB). Por favor, seleccione un archivo nuevo y vuelvea a intentarlo .');
                return false;
            }   
        }
    });

    uploader.filters.push({
        name: 'itemResetFilter',
        fn: function (item, options) {
            if (this.queue.length < 5)
                return true;
            else {
                alert('Se ha superado el límite de carga de archivos ');
                return false;
            }
        }
    });
 
    // CALLBACKS
 
    uploader.onWhenAddingFileFailed = function (item, filter, options) {
        console.info('onWhenAddingFileFailed', item, filter, options);
        $scope.error = '';
    };
    /*uploader.onAfterAddingFile = function (fileItem) {
        alert('Archivos listos para la carga .');
    };*/

    uploader.onSuccessItem = function (fileItem, response, status, headers) {
        //$scope.uploader.queue = [];
        //$scope.uploader.progress = 0;
        //console.log(fileItem._file.name);
        services.listar().then(function(data){
                        $scope.listadodocu=data.data[0];
                        console.log($scope.listadodocu);
                        return data.data;
                });
        alert("El archivo "+ fileItem._file.name + " seleccionado se ha cargado correctamente .");
        $scope.error = '';
    };
    uploader.onErrorItem = function (fileItem, response, status, headers) {
        alert('No se pudo cargar el archivo . Por favor, inténtelo de nuevo.');
        $scope.error = '';
    };
    uploader.onCancelItem = function (fileItem, response, status, headers) {
        alert('La subida de archivos ha sido cancelada .');
        $scope.error = '';
    };

    uploader.onAfterAddingAll = function(addedFileItems) {
        console.info('onAfterAddingAll', addedFileItems);
        $scope.error = '';
    };
    uploader.onBeforeUploadItem = function(item) {
        console.info('onBeforeUploadItem', item);
        $scope.error = '';
    };
    uploader.onProgressItem = function(fileItem, progress) {
        //console.info('onProgressItem', fileItem, progress);
        $scope.error = '';
    };
    uploader.onProgressAll = function(progress) {
        //console.info('onProgressAll', progress);
        $scope.error = '';
    };
        
    uploader.onCompleteItem = function(fileItem, response, status, headers) {
        console.info('onCompleteItem', fileItem, response, status, headers);
        $scope.error = '';
    };
    uploader.onCompleteAll = function() {
        console.info('onCompleteAll');
        $scope.error = '';
    };

    console.info('uploader', uploader);
});




//**********************************Upload*******************************


//--------------------------------------------prueba


app.controller('cargar_datos22', function ($scope, $rootScope, $http, $location, $routeParams,$cookies,$cookieStore, services, FileUploader) {
    // Uploader Plugin Code

     var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";


    //console.log ($rootScope.logedUser)
     $scope.usert={};
    $scope.usert.EQUIPO_ID="MANUAL";
    $scope.usert.userID=$scope.userID;
    $scope.usert.ID="";


//console.log($scope.userID);

$rootScope.logout = function() {
            services.logout($rootScope.logedUser.login);
            $cookieStore.remove('logedUser');
            $rootScope.logedUser=undefined;
            $scope.pedidos={};
            document.getElementById('logout').className="btn btn-md btn-danger hide";
            var divi=document.getElementById("logoutdiv");
            divi.style.position="absolute";
            divi.style.visibility="hidden";
            $location.path('/');
    };


   var uploader = $scope.uploader = new FileUploader({
        url: window.location.protocol + window.location.pathname + 'services/UploadFile2'

    });

                services.listar1().then(function(data){
                        $scope.listadodocu1=data.data[0];
                        console.log($scope.listadodocu1);
                        return data.data;
                });
    // FILTERS

        $scope.eliminarfi = function (file){
                        //console.log(data.data);
                var result = confirm("Esta seguro que desea eliminar el archivo "+file+ "?");
                if (result) {
                                //Logic to delete the item
                    services.eliminarfile1(file).then(function(data){
                                if(data.data=='OK'){
                                    document.getElementById("warning").innerHTML="Archivo "+file+" eliminado correctamente.";
                                $scope.error="Archivo "+file+" eliminado correctamente.";
                                }
                               services.listar1().then(function(data){
                                    $scope.listadodocu1=data.data[0];
                                    //console.log($scope.listadodocu);
                                    return data.data;
                                });
                        });
                };
        };
    //$scope.reload();
    //$interval($scope.reload, 5000);

     uploader.filters.push({
        name: 'extensionFilter',
        fn: function (item, options) {
            var filename = item.name;
            var extension = filename.substring(filename.lastIndexOf('.') + 1).toLowerCase();
            if (extension == "xlsx" || extension == "xls")
                return true;
            else {
                alert('Formato Invalido. Por favor seleccione un archivo con formato xlsx/xls');
                return false;
            }
        }
    });

    uploader.filters.push({
        name: 'sizeFilter',
        fn: function (item, options) {
            var fileSize = item.size;
            fileSize = parseInt(fileSize) / (1024 * 1024);
            if (fileSize <= 20)
                return true;
            else {
                alert('El archivo seleccionado excede el límite de tamaño(20 MB). Por favor, seleccione un archivo nuevo y vuelvea a intentarlo .');
                return false;
            }
        }
    });

    uploader.filters.push({
        name: 'itemResetFilter',
        fn: function (item, options) {
            if (this.queue.length < 5)
                return true;
            else {
                alert('Se ha superado el límite de carga de archivos ');
                return false;
            }
        }
    });

    // CALLBACKS

    uploader.onWhenAddingFileFailed = function (item, filter, options) {
        console.info('onWhenAddingFileFailed', item, filter, options);
        $scope.error = '';
    };
    /*uploader.onAfterAddingFile = function (fileItem) {
        alert('Archivos listos para la carga .');
    };*/

    uploader.onSuccessItem = function (fileItem, response, status, headers) {
        //$scope.uploader.queue = [];
        //$scope.uploader.progress = 0;
        //console.log(fileItem._file.name);
        services.listar1().then(function(data){
                        $scope.listadodocu=data.data[0];
                        console.log($scope.listadodocu1);
                        return data.data;
                });
        alert("El archivo "+ fileItem._file.name + " seleccionado se ha cargado correctamente .");
        $scope.error = '';
    };
    uploader.onErrorItem = function (fileItem, response, status, headers) {
        alert('No se pudo cargar el archivo . Por favor, inténtelo de nuevo.');
        $scope.error = '';
    };
    uploader.onCancelItem = function (fileItem, response, status, headers) {
        alert('La subida de archivos ha sido cancelada .');
        $scope.error = '';
    };

    uploader.onAfterAddingAll = function(addedFileItems) {
        console.info('onAfterAddingAll', addedFileItems);
        $scope.error = '';
    };
    uploader.onBeforeUploadItem = function(item) {
        console.info('onBeforeUploadItem', item);
        $scope.error = '';
    };
    uploader.onProgressItem = function(fileItem, progress) {
        //console.info('onProgressItem', fileItem, progress);
        $scope.error = '';
    };
    uploader.onProgressAll = function(progress) {
        //console.info('onProgressAll', progress);
        $scope.error = '';
    };

    uploader.onCompleteItem = function(fileItem, response, status, headers) {
        console.info('onCompleteItem', fileItem, response, status, headers);
        $scope.error = '';
    };
    uploader.onCompleteAll = function() {
        console.info('onCompleteAll');
        $scope.error = '';
    };

    console.info('uploader', uploader);
});




//-----------------------------fin prueba

app.controller('IndicadoresCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore,$http,$base64, services) {
   

	var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";

	$rootScope.actualView="indicadores";
	$scope.datosGrafica=[];
	$scope.listado_usuarios_produccion=[];
	$scope.listado_tme=[];
	$scope.lastUpdate="";
    $scope.ordenamientoDemepedido='';
	$scope.ordenamientoDemepedidoReconfiguracion='';
    $scope.ordenamientoDemepedidoUpdate='';


	$scope.intervalFeed=0;
	$scope.intervalGrafica=0;

	$scope.totalAD="0";

	$rootScope.logout = function() {
        	services.logout($rootScope.logedUser.login);
	        $cookieStore.remove('logedUser');
            $rootScope.logedUser=undefined;
	        $scope.pedidos={};
	        document.getElementById('logout').className="btn btn-md btn-danger hide";
	        var divi=document.getElementById("logoutdiv");
	        divi.style.position="absolute";
	        divi.style.visibility="hidden";
	        $location.path('/');
	};

        $scope.doubleDigit= function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
            };



	/* FUNCION PARA ACTUALIZAR LOS PARAMETROS DEL SISTEMA */
	$scope.updateParametro = function (parametro,valor){

	        services.updateParametro(parametro,valor,$rootScope.logedUser.login).then(function(data){
                                var date1 = new Date();
                                var year    = date1.getFullYear();
                                var month   = $scope.doubleDigit(date1.getMonth()+1);
                                var day     = $scope.doubleDigit(date1.getDate());
                                var hour    = $scope.doubleDigit(date1.getHours());
                                var minute  = $scope.doubleDigit(date1.getMinutes());
                                var seconds = $scope.doubleDigit(date1.getSeconds());
				$scope.ordenamientoDemepedidoUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

			if(parametro=="FECHA_ORDEN_DEMEPEDIDO"){
				$scope.ordenamientoDemepedido=valor;
			}
			if(parametro=="FECHA_ORDEN_DEMEPEDIDO_RECONFIGURACION"){
                                $scope.ordenamientoDemepedidoReconfiguracion=valor;
                        }
						$scope.buscarParametro(parametro);
                        return data.data;
                });

	};


       $scope.buscarParametro = function (parametro){

                services.buscarParametro(parametro).then(function(data){
                    if(parametro=="FECHA_ORDEN_DEMEPEDIDO"){
                        $scope.UsuarioParametro=data.data['USUARIO_ID'];
						$scope.ordenamientoDemepedido=data.data['VALOR'];
						$scope.ordenamientoDemepedidoUpdate=data.data['ULTIMA_ACTUALIZACION'];
                    }
                    if(parametro=="FECHA_ORDEN_DEMEPEDIDO_RECONFIGURACION"){
                        $scope.UsuarioParametroReconfiguracion=data.data['USUARIO_ID'];
						$scope.ordenamientoDemepedidoReconfiguracion=data.data['VALOR'];
						$scope.ordenamientoDemepedidoUpdateReconfiguracion=data.data['ULTIMA_ACTUALIZACION'];
                                }
                        return data.data;
                });

        };

	//para inicializar la variable ordenamientoDemepedido
        services.buscarParametro('FECHA_ORDEN_DEMEPEDIDO').then(function(data){
		
		$scope.ordenamientoDemepedido=data.data['VALOR'];
		$scope.ordenamientoDemepedidoNuevo=data.data['VALOR'];
		$scope.ordenamientoDemepedidoUpdate=data.data['ULTIMA_ACTUALIZACION'];
		$scope.UsuarioParametro=data.data['USUARIO_ID'];
                return data.data;
        });


        services.buscarParametro('FECHA_ORDEN_DEMEPEDIDO_RECONFIGURACION').then(function(data){

                $scope.ordenamientoDemepedidoReconfiguracion=data.data['VALOR'];
                //$scope.ordenamientoDemepedidoNuevo=data.data['VALOR'];
                $scope.ordenamientoDemepedidoUpdateReconfiguracion=data.data['ULTIMA_ACTUALIZACION'];
				$scope.UsuarioParametroReconfiguracion=data.data['USUARIO_ID'];
                return data.data;
        });



	var date1 = new Date();
        var year    = date1.getFullYear();
        var month   = $scope.doubleDigit(date1.getMonth()+1);
        var day     = $scope.doubleDigit(date1.getDate());

        $scope.fechaInicio=year+"-"+month+"-"+day;
        $scope.fechaFin=year+"-"+month+"-"+day;
	
	$scope.intervalGrafica = setInterval(function(){
		$scope.actualizarGrafica();
           },60000);

        $scope.intervalFeed = setInterval(function(){
                $scope.getFeed();
                $scope.getLoginFeed();
           },20000);
	

	$scope.getFeed = function (){
		services.getFeed().then(function(data){
                        $scope.listado_feed=data.data[0];
			$scope.total_feed=data.data[1];
                        return data.data;
                });
		
	}

	$scope.$on(
       		"$destroy",
                        function( event ) {
                            //$timeout.cancel( timer );
                            	//alert('pew! pew!');
				clearInterval($scope.intervalGrafica);
				clearInterval($scope.intervalFeed);
                        }
        );
    


    $scope.getLoginFeed = function (){
        services.getLoginFeed().then(function(data){
                        $scope.login_feed=data.data[0];
                        $scope.total_feed=data.data[1];
                        return data.data;
                });    
    }

	$scope.getFeed();

        $scope.actualizarTME  = function (){
            $scope.refresh='cargando';
		//TOMAR MUESTRA
                var fromDate = document.getElementById("fromDate2").value;
                var toDate = document.getElementById("toDate2").value;

                services.getTME(fromDate,toDate).then(function(data){
                        $scope.listado_tme=data.data;
                        $scope.refresh='';
                        return data.data;

                });
		
		services.logVista($cookieStore.get('logedUser').login,"TME");

        }


	$scope.actualizarProductividadGrupo  = function (){
		$scope.refresh='cargando';

		var fromDate = document.getElementById("fromDate1").value;
                var toDate = document.getElementById("toDate1").value;


		services.getProductividadGrupo(fromDate,toDate).then(function(data){

            $scope.listado_usuarios_produccion=data.data;
			$scope.listado_usuarios_produccion.tservicios = 0; 
			$scope.listado_usuarios_produccion.tpedidos = 0;
            $scope.listado_usuarios_produccion.tbuscado=0;
            $scope.listado_usuarios_produccion.tdemePedido=0;
			$scope.listado_usuarios_produccion.tc15 = 0; 
			$scope.listado_usuarios_produccion.tc99 = 0; 
			$scope.listado_usuarios_produccion.tc14 = 0; 
			$scope.listado_usuarios_produccion.tc2 = 0; 
			$scope.listado_usuarios_produccion.tPORDE = 0; 
			$scope.listado_usuarios_produccion.tOTRO = 0; 
			$scope.listado_usuarios_produccion.ttestudios = 0; 
			$scope.listado_usuarios_produccion.totales = 0;
            $scope.refresh='';
                        return data.data;
                });

		//PARA LLEVAR UN LOG DE QUIENES REFERENCIAN EL INDICADOR...
		
		services.logVista($cookieStore.get('logedUser').login,"ProductividadGrupo");

	}

    //funciones para calcular el acumulado del tiempo

    $scope.timestrToSec = function (timestr) {
        var parts = timestr.split(":");
        return (parts[0] * 3600) +
        (parts[1] * 60) +
        (+parts[2]);
    }

    $scope.pad = function(num) {
        if(num < 10) {
        return "0" + num;
        } else {
        return "" + num;
        }
    }

    $scope.formatTime = function (seconds) {
        if (isNaN(seconds)){
            seconds=0;
        }
        return [$scope.pad(Math.floor(seconds/3600)%60),
        $scope.pad(Math.floor(seconds/60)%60),
        $scope.pad(seconds%60),
        ].join(":");
    }



        $scope.actualizarIngresosEstudiosGrafica  = function (){
		
		var fromDate = document.getElementById("fromDate").value;
		var toDate = document.getElementById("toDate").value;
		var data1=services.getPendientesIngresosEstudiosGrafica(fromDate,toDate).then(function(data){
			//console.log(data.data[0]);
		var cates=[

                { label: "00" },
                { label: "01" },
                { label: "02" },
                { label: "03" },
                { label: "05" },
                { label: "06" },
                { label: "07" },
                { label: "08" },
                { label: "09" },
                { label: "10" },
                { label: "11" },
                { label: "12" },
                { label: "13" },
                { label: "14" },
                { label: "15" },
                { label: "16" },
                { label: "17" },
                { label: "18" },
                { label: "19" },
                { label: "20" },
                { label: "21" },
                { label: "22" },
                { label: "23" }
            ];
		cates.length=data.data[0].length;
                	$scope.myDataSource2 = {
                        	chart: {
				        caption: "Ingresos - Estudios - Pendientes",
				        rotatevalues: "0",
					xaxisname: "Horas",
					palettecolors: "#f8bd19,#008ee4,#33bdda,#e44a00,#6baa01,#583e78",
					yAxisName: "Servicios",
				        placevaluesinside: "1",
				        legendshadow: "0",
				        legendborderalpha: "0",
					linethickness: "6",
				        legendbgcolor: "FFFFFF",
				        showborder: "0",
					bgAlpha: "0",
		                    borderAlpha: "20",
		                    canvasBorderAlpha: "0",
		                    usePlotGradientColor: "0",
		                    plotBorderAlpha: "10",
		                    legendBorderAlpha: "0",
		                    legendShadow: "0",
		                    captionpadding: "20",
					formatNumberScale: "0",
		                    showAxisLines: "1",
		                    axisLineAlpha: "25",
                		    divLineAlpha: "10",
		                    showValues: "0"
                        },
                        	categories: [
        {
            category: cates 
            
        }
    ],
	dataset: [
        {
            seriesname: "Ingresos",
            color: "0000FF",
            showvalues: "1",
            data: data.data[0]
        },
        {
            seriesname: "Estudios",
            color: "00FF00",
            showvalues: "1",
            data: data.data[1]
        },
        {
            seriesname: "Pendientes",
            color: "FF0000",
            showvalues: "1",
            data: data.data[2]
        }
    ],
trendlines: [
        {
	    line: [
                {
                    startvalue: data.data[0][0].value,
                    endValue: data.data[0][data.data[0].length-2].value,
                    color: "#FF0011",
                    valueOnRight: "1",
                    dashed: "1",
                    displayvalue: "Tendencia Ingresos"
                }
            ]
        }
    ]

}
			//console.log(data.data[2][0].value);
                	return data.data;
		});

	};

	$scope.parseInt =  function (numbero){
		return parseInt(numbero);
	};

        $scope.parseFloat =  function (numbero){
                return parseFloat(numbero);
        };

        $scope.roundFloat =  function (numbero){
		var num=parseFloat(numbero).toFixed(2);
                return num;
        };

	$scope.csvFenixNal  = function (){
		var login=$rootScope.logedUser.login;
		services.getCsvFenixNal(login).then(function(data){
			
			//console.log(data.data[0]);
			window.location.href="tmp/"+data.data[0];
                        return data.data;
                });

	};

        $scope.csvFenixBog  = function (){
                var login=$rootScope.logedUser.login;
                services.getCsvFenixBog(login).then(function(data){

                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });

        };

	$scope.csvActivacion  = function (){
                var login=$rootScope.logedUser.login;
                services.getCsvActivacion(login).then(function(data){

                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };


        $scope.actualizarGrafica   = function (){
	//TOMAR MUESTRA
                var data1=services.getPendientesGrafica().then(function(data){
						$scope.pendientesConceptos=data.data;
                        $scope.myDataSource = {

                            chart: {
                               // "caption": "Grafica Pendientes",
                                //"subCaption": "Conceptos",
                                "xAxisName": "Conceptos",
                                "yAxisName": "Serivicios",
                                "numberPrefix": "",
                                "paletteColors": "#0075c2",
                                "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
								"bgColor": "#DDDDDD",
            					"bgAlpha": "50",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                "rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14",
								"labelDisplay": "rotate",
            					"slantLabels": "1"
                            },

                                data: data.data

                        }
			var date1 = new Date();
        	        var year    = date1.getFullYear();
	                var month   = $scope.doubleDigit(date1.getMonth()+1);
	                var day     = $scope.doubleDigit(date1.getDate());
	                var hour    = $scope.doubleDigit(date1.getHours());
        	        var minute  = $scope.doubleDigit(date1.getMinutes());
	                var seconds = $scope.doubleDigit(date1.getSeconds());

	                $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                        return data.data;
                });
		//para actualizar la tabla...
		$scope.actualizarPendientesPorPlaza();

		//$scope.actualizarGraficaAgendamiento();
		
		services.logVista($cookieStore.get('logedUser').login,"Indicadores General");
		
        };

        $scope.actualizarGraficaAD   = function (){
	//TOMAR MUESTRA
                var data1=services.getPendientesGraficaAD().then(function(data){
                        $scope.myDataSourceAD = {
                                chart: {
                                        caption: "Grafica A y D",
                                        subcaption: "Pendientes",
                                        startingangle: "120",
                                        showlabels: "1",
                                        showlegend: "1",
                                        enablemultislicing: "0",
                                        formatNumberScale: "0",
                                        slicingdistance: "15",
                                        showpercentvalues: "0",
                                        showpercentintooltip: "0",
                        },

                                data: data.data[0]

                        }
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
			$scope.totalAD= data.data[1]

                        return data.data;
                });
		$scope.actualizarPendientesPorConceptoColaActivacion();
               
		services.logVista($cookieStore.get('logedUser').login,"Indicadores Activacion y Desactivacion");
           };
        

        $scope.actualizarPendientesPorConceptoColaActivacion  = function (){
                 var data1=services.pendientesPorConceptoColaActivacion().then(function(data){

                        $scope.listado_colas =  data.data[0];
                        $scope.listado_conceptos =  data.data[1];
                        $scope.listado_colas.totales = 0;
                        $scope.listado_colas.total02 = 0;
                        $scope.listado_colas.total34 = 0;
                        $scope.listado_colas.total56 = 0;
		 	$scope.listado_colas.total712 = 0;
                        $scope.listado_colas.total1324 = 0;
                        $scope.listado_colas.total2548 = 0;
                        $scope.listado_colas.totalmas48 = 0;

                        $scope.listado_conceptos.totales = 0;
                        $scope.listado_conceptos.total02 = 0;
                        $scope.listado_conceptos.total34 = 0;
                        $scope.listado_conceptos.total56 = 0;
			$scope.listado_conceptos.total712 = 0;
                        $scope.listado_conceptos.total1324 = 0;
                        $scope.listado_conceptos.total2548 = 0;
                        $scope.listado_conceptos.totalmas48 = 0;

                });

        };




	$scope.actualizarPendientesPorPlaza   = function (){
		 var data1=services.pendientesPorPlaza().then(function(data){

                        $scope.listado_plazas =  data.data[0];
                        $scope.listado_plazas_bogota =  data.data[1];
                        $scope.listado_conceptosas =  data.data[2];
                        $scope.listado_conceptosasn =  angular.copy(data.data[2]);
                        $scope.listado_conceptosin =  data.data[3];
                        $scope.listado_conceptosfc =  data.data[4];

            		$scope.listado_plazas.totales = 0;
            		$scope.listado_plazas.total02 = 0;
            		$scope.listado_plazas.total34 = 0;
            		$scope.listado_plazas.total56 = 0;
            		$scope.listado_plazas.total712 = 0;
                        $scope.listado_plazas.total1324 = 0;
                        $scope.listado_plazas.total2548 = 0;
                        $scope.listado_plazas.totalmas48 = 0;
			

                        $scope.listado_plazas_bogota.totales = 0;
                        $scope.listado_plazas_bogota.total02 = 0;
                        $scope.listado_plazas_bogota.total34 = 0;
                        $scope.listado_plazas_bogota.total56 = 0;
                        $scope.listado_plazas_bogota.total712 = 0;
                        $scope.listado_plazas_bogota.total1324 = 0;
                        $scope.listado_plazas_bogota.total2548 = 0;
                        $scope.listado_plazas_bogota.totalmas48 = 0;

                        $scope.listado_conceptosasn.totales = 0;
                        $scope.listado_conceptosasn.total02 = 0;
                        $scope.listado_conceptosasn.total34 = 0;
                        $scope.listado_conceptosasn.total56 = 0;
                        $scope.listado_conceptosasn.total712 = 0;
                        $scope.listado_conceptosasn.total1324 = 0;
                        $scope.listado_conceptosasn.total2548 = 0;
                        $scope.listado_conceptosasn.totalmas48 = 0;

                         $scope.listado_conceptosas.totales = 0;
                        $scope.listado_conceptosas.total02 = 0;
                        $scope.listado_conceptosas.total34 = 0;
                        $scope.listado_conceptosas.total56 = 0;
                        $scope.listado_conceptosas.total712 = 0;
                        $scope.listado_conceptosas.total1324 = 0;
                        $scope.listado_conceptosas.total2548 = 0;
                        $scope.listado_conceptosas.totalmas48 = 0;

                        $scope.listado_conceptosin.totales = 0;
                        $scope.listado_conceptosin.total02 = 0;
                        $scope.listado_conceptosin.total34 = 0;
                        $scope.listado_conceptosin.total56 = 0;
                        $scope.listado_conceptosin.total712 = 0;
                        $scope.listado_conceptosin.total1324 = 0;
                        $scope.listado_conceptosin.total2548 = 0;
                        $scope.listado_conceptosin.totalmas48 = 0;

                        $scope.listado_conceptosfc.totales = 0;
                        $scope.listado_conceptosfc.total02 = 0;
                        $scope.listado_conceptosfc.total34 = 0;
                        $scope.listado_conceptosfc.total56 = 0;
                        $scope.listado_conceptosfc.total712 = 0;
                        $scope.listado_conceptosfc.total1324 = 0;
                        $scope.listado_conceptosfc.total2548 = 0;
                        $scope.listado_conceptosfc.totalmas48 = 0;

                });

	};

	$scope.myDataSource = {
                        chart: {
                        caption: "Grafica A y D",
                        subcaption: "Pendientes",
                        startingangle: "120",
                        showlabels: "0",
                        showlegend: "1",
                        enablemultislicing: "0",
                        slicingdistance: "15",
			formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        theme: "fint"
                        },
                        data: []

        };


        $scope.myDataSourceAD = {
                        chart: {
                        caption: "Grafica General",
                        subcaption: "Pendientes A y D",
                        startingangle: "120",
                        showlabels: "0",
                        showlegend: "1",
                        enablemultislicing: "0",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        theme: "fint"
                        },
                        data: []

        };


     $scope.myDataSource2 = {
            chart: {
    		                        caption: "Ingresos - Estudios - Pendientes",
                                        rotatevalues: "0",
                                        paletteColors: "#0075c2,#1aaf5d,#ff0011",
                                        xaxisname: "Horas",
                                        yAxisName: "Servicios",
                                        placevaluesinside: "1",
                                        legendshadow: "0",
                                        legendborderalpha: "0",
                                        legendbgcolor: "FFFFFF",
                                        showborder: "0",
                                        bgAlpha: "0",
                                    borderAlpha: "20",
                                    canvasBorderAlpha: "0",
                                    usePlotGradientColor: "0",
                                    plotBorderAlpha: "10",
                                    legendBorderAlpha: "0",
                                    legendShadow: "0",
					formatNumberScale: "0",
                                    captionpadding: "20",
                                    showAxisLines: "1",
                                    axisLineAlpha: "25",
                                    divLineAlpha: "10",
                                    showValues: "0"
	
		},
			categories: [
		        {
		            category: [

		                { label: "00" },
		            ]
		        }
    ],
    dataset: [
        {
            seriesname: "Ingresos",
            color: "F0807F",
            showvalues: "1",
            data: []
        },
        {
            seriesname: "Estudios",
            color: "F1C7D2",
            showvalues: "1",
            data: []
        },
		{
            seriesname: "Pendientes",
            color: "FFC7D2",
            showvalues: "1",
            data: []
        }
    ]
}



//----------Funcion para determinar el color del pendiente --------------------------
var colorDanger="#E83720";
var colorWaring="#E8A820";
var colorWarningTrans="#ffd699";
var colorNormal="#088A08";

$scope.set_color = function (value) {
              if (value > 200) {
                $scope.estilo={
                  "list-style-position":"inside",
                  "border-left": "5px solid "+colorDanger
                    };

                return $scope.estilo;
              }
              else{

                  if(value > 50 && value < 200){

                    $scope.estilo={
                  "list-style-position":"inside",
                  "border-left": "5px solid "+colorWaring
                    };

                  }else{

                     $scope.estilo={
                  "list-style-position":"inside",
                  "border-left": "5px solid "+colorNormal
                    };
                  }

               return $scope.estilo;

              }
            };

$scope.set_color_Cuartil = function (value) {

	//console.log(value);

              if (value >= 4) {
                $scope.estiloCuartil={
                  "list-style-position":"inside",
                  "border-left": "5px solid "+colorDanger
                    };

                return $scope.estiloCuartil;
              }


              if(value >= 3 && value < 4){

                    $scope.estiloCuartil={
                  "list-style-position":"inside",
                  "border-left": "5px solid "+colorWaring

                    };
				  return $scope.estiloCuartil;

                  }

               if(value >= 2 && value < 3){

					$scope.estiloCuartil={
					  "list-style-position":"inside",
					  "border-left": "5px solid "+colorWarningTrans
					};
				   return $scope.estiloCuartil;
                  	}

		if(value >= 1 && value < 2){

					$scope.estiloCuartil={
					  "list-style-position":"inside",
					  "border-left": "5px solid "+colorNormal
					};
				   return $scope.estiloCuartil;
                  	}

};

$scope.datepickerOptions = {
    format: 'yyyy-mm-dd',
    language: 'es',
    autoclose: true,
    weekStart: 0
}

  $scope.status = {
    isFirstOpen: true,
    isFirstDisabled: false
  };


//------PRUEBAS API OPENFIRE -----------------------------


$scope.ChatRoomMates = function() {

    $scope.url = 'http://10.100.82.156:9090/plugins/restapi/v1/chatrooms?type=all';
	$scope.token="ty2p9C36vBqCEU7L";


	var auth = $base64.encode("admin:123456");
    var headers = {"Authorization": "Basic " + auth};

    $http.get($scope.url, {
			headers: headers}).
            then(function successCallback(response) {

                console.log(response);


                }, function errorCallback(response) {

                    console.log(response);

                        })


 };

//------PRUEBAS API OPENFIRE -----------------------------
//$scope.grupo.Cuartil="ASGINACIONES";
$scope.topProductivos = function() {

    services.getPedidosGestorUser().then(
		console.log($scope.grupo);

      function(data){

            $scope.listaProductivos=data.data[0];
		  	//console.log($scope.listaProductivos);

              return data.data;


            }
      , function errorCallback(response,status) {
          //console.log(status);
            $scope.errorDatos="Ops, probelemas";



          }
      );


 };




});


app.controller('UsersCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {
	var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";
        $rootScope.iconcepto="TODO";
	$rootScope.actualView="usuarios";

	$scope.usert={};
	$scope.usert.EQUIPO_ID="MANUAL";
	$scope.usert.ID="";

	$scope.doubleDigit = function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
        };

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

	$scope.saveUsuario = function (usuar){
		var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

		services.insertUsuario(usuar).then(function(data){
			$location.path('/users/');
			$scope.success="Usuario Creado con exito";
                	return data.data;
                });
	};

        $scope.newUsuario = function (usuar){

		$scope.usert={};
        	$scope.usert.EQUIPO_ID="MANUAL";
        	$scope.usert.ID="";

          	$location.path('/users/usuario');
        };


        $scope.editUsuario = function (usuar){

                services.editUsuario(usuar).then(function(data){
                        $location.path('/users/');
                        return data.data;
                });
        };


	$scope.getUsuario = function (userID){
		$scope.usert={};
		
		 services.getUsuario(userID).then(function(data){
			$rootScope.usert=data.data[0];
            $location.path('/users/usuario');
			//$scope.usert=data.data[0];
		 	return data.data;
                });

	};


        $scope.listado_usuarios=[];
	$scope.data = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaIni:"", fechaFin: "" }

        var date1 = new Date();
        var year  = date1.getFullYear();
        var month = $scope.doubleDigit(date1.getMonth()+1);
        var day   = $scope.doubleDigit(date1.getDate());

        var fecha_inicio=year+"-"+month+"-"+day;
        var fecha_fin=year+"-"+month+"-"+day;

        $scope.data.fechaIni=fecha_inicio;
        $scope.data.fechaFin=fecha_fin;

        //services.getListadotransaccionesNCA(fecha_inicio,fecha_fin,$scope.data.currentPage).then(function(data){
	var pathy=$location.path();

        if(pathy=="/users/"){//esto es para controlar que no se vuelva a llamar este listado cuando se usa la vista de edicion-nuevo
		services.getListadoUsuarios().then(function(data){
			console.log(data.data[0]);
                	$scope.listado_usuarios=data.data[0];
                	$scope.data.totalItems=data.data[1];
			$scope.usert={};
                        $scope.usert.EQUIPO_ID="MANUAL";
                        $scope.usert.ID="";
			$scope.usert==undefined;
                	return data.data;
        	});
	}

	if(pathy=="/users/usuario"){
		var date1 = new Date();
                var year    = date1.getFullYear();
               	var month   = $scope.doubleDigit(date1.getMonth()+1);
       	        var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
               	var minute  = $scope.doubleDigit(date1.getMinutes());
       	        var seconds = $scope.doubleDigit(date1.getSeconds());
		$scope.FECHA_INICIO=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
		$scope.usert=$rootScope.usert;
		console.log($scope.usert);
		if($scope.usert==undefined){
			$scope.usert={};
        	        $scope.usert.EQUIPO_ID="MANUAL";
	                $scope.usert.ID="";
		}
		if($scope.usert=={}){
                        $scope.usert={};
                        $scope.usert.EQUIPO_ID="MANUAL";
                        $scope.usert.ID="";
                }
		console.log($scope.usert);
		$rootScope.usert={};
       }

});

app.controller('AlarmasActivacionCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {
    var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";
        $rootScope.iconcepto="TODO";
        $rootScope.actualView="nca";


    $scope.doubleDigit = function (num){
                if(num<0){
                    num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
    };

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

            document.getElementById("newAlarma").style.visibility = "hidden";
            document.getElementById("newAlarma").style.display = "none";

            $scope.listado_alarmas=[];
            $scope.usuariosAlarmas=[];

            services.getListadoAlarmasActivacion().then(function(data){
                    $scope.listado_alarmas=data.data[0];

                    return data.data;
            });

            validaEditar="";
        $rootScope.editAlarma = function(index, responsable1, responsable2) {
                console.log(responsable1,responsable2, index);
                $scope.variableControl=0;
                

            if(validaEditar===""){
                validaEditar= index;
                services.getUsuariosAlarmasActivacion().then(function(data){

                    $scope.usuariosAlarmas=data.data[0];
                    line1 ="";
                    line1 +="<select id='responUno"+index+"'>";
                    for (var i=0; i<$scope.usuariosAlarmas.length; i++){
                        if(responsable1==$scope.usuariosAlarmas[i].USUARIO_ID){
                            line1 += "<option value='"+$scope.usuariosAlarmas[i].USUARIO_ID+"' selected>";
                        }else{
                            line1 += "<option value='"+$scope.usuariosAlarmas[i].USUARIO_ID+"'>";
                        }
                        line1 += $scope.usuariosAlarmas[i].USUARIO_NOMBRE+"</option>";
                    }
                    line1 +="</select>";
                    console.log(line1);
                    document.getElementById("div"+index).innerHTML=line1;
                    $scope.res1=document.getElementById("responUno"+index).selectedIndex;

                    line2 ="";
                    line2 +="<select id='responDos"+index+"'>";
                    for (var j=0; j<$scope.usuariosAlarmas.length; j++){
                        if(responsable2==$scope.usuariosAlarmas[j].USUARIO_ID){
                            line2 += "<option value='"+$scope.usuariosAlarmas[j].USUARIO_ID+"' selected >";
                        }else{
                            line2 += "<option value='"+$scope.usuariosAlarmas[j].USUARIO_ID+"'>";
                        }
                        line2 += $scope.usuariosAlarmas[j].USUARIO_NOMBRE+"</option>";
                    }
                    line2 +="</select>";
                    console.log(line2);
                    document.getElementById("divres"+index).innerHTML=line2;
                    $scope.res2=document.getElementById("responDos"+index).selectedIndex;

                    return data.data;
            });
                document.getElementById("edi"+index).style.visibility = "hidden";
                document.getElementById("edi"+index).style.display = "none";
                document.getElementById("divsave"+index).style.visibility = "visible";
                document.getElementById("divsave"+index).style.display = "inline";
            } else{
                window.alert("YA SE ENCUENTRA EDITANDO UN REGISTRO, NO SE PUEDE MODIFICAR MAS REGISTROS AL MISMO TIEMPO!");
            }
        };

        $rootScope.cancelarAlarma=function(index) {

            

            var selectorOne = document.getElementById("responUno"+index);
            var responOne = selectorOne[selectorOne.selectedIndex].value;
            var selectorTwo = document.getElementById("responDos"+index)
            var responTwo = selectorTwo[selectorTwo.selectedIndex].value;

            selectorOne[$scope.res1].selected=true;
            selectorTwo[$scope.res2].selected=true;

            var responOneT = selectorOne[selectorOne.selectedIndex].text;
            var responTwoT = selectorTwo[selectorTwo.selectedIndex].text;
            
            document.getElementById("edi"+index).style.visibility = "visible";
            document.getElementById("edi"+index).style.display = "inline";
            document.getElementById("divsave"+index).style.visibility = "hidden";
            document.getElementById("divsave"+index).style.display = "none";
            
            
            document.getElementById("div"+index).innerHTML=responOneT;
            document.getElementById("divres"+index).innerHTML=responTwoT;


            validaEditar="";


        }

        $rootScope.guardarAlarma=function(index, cola_id) {
            var selectorOne = document.getElementById("responUno"+index);
            var responOne = selectorOne[selectorOne.selectedIndex].value;
            var selectorTwo = document.getElementById("responDos"+index)
            var responTwo = selectorTwo[selectorTwo.selectedIndex].value;

            var responOneT = selectorOne[selectorOne.selectedIndex].text;
            var responTwoT = selectorTwo[selectorTwo.selectedIndex].text;

            var hoy = new Date();
            var dd = hoy.getDate();
            var mm = hoy.getMonth()+1; //hoy es 0!
            var yyyy = hoy.getFullYear();

            if(dd<10) {
                dd='0'+dd
            } 

            if(mm<10) {
                mm='0'+mm
            } 

            var fecha_act= yyyy+"-"+mm+"-"+dd;

            services.actualizarAlarmaActivacion(responOne,responTwo,cola_id).then(function(data){
                    return data.data;
            });

            document.getElementById("edi"+index).style.visibility = "visible";
            document.getElementById("edi"+index).style.display = "inline";
            document.getElementById("divsave"+index).style.visibility = "hidden";
            document.getElementById("divsave"+index).style.display = "none";
            document.getElementById("div"+index).innerHTML=responOneT;
            document.getElementById("divres"+index).innerHTML=responTwoT;
            document.getElementById("fechaAct"+index).innerHTML=fecha_act;
            validaEditar="";

        }

        $rootScope.nuevaAlarma=function() {
           // $scope.alarmaNueva={};

        services.getUsuariosAlarmasActivacion().then(function(data){
                $scope.usuariosAlarmasNew=data.data[0];
                //$scope.respDosAlarmasNew=data.data[0];
                //console.log($scope.usuariosAlarmasNew);
        });

            document.getElementById("listAlarmas").style.visibility = "hidden";
            document.getElementById("listAlarmas").style.display = "none";
            
            document.getElementById("newAlarma").style.visibility = "visible";
            document.getElementById("newAlarma").style.display = "inline";

        }

        $rootScope.guardarNuevaAlarma=function(nuevaCola) {
            console.log(nuevaCola);
            services.insertarAlarmaActivacion(nuevaCola).then(function(data){
                return data.data;
            });

            services.getListadoAlarmasActivacion().then(function(data){
                    $scope.listado_alarmas=data.data[0];

                    return data.data;
            });

            document.getElementById("newAlarma").style.visibility = "hidden";
            document.getElementById("newAlarma").style.display = "none";
            
            document.getElementById("listAlarmas").style.visibility = "visible";
            document.getElementById("listAlarmas").style.display = "inline";

        }

        $rootScope.cancelarGuardarAlarma=function() {
            
            $scope.alarmaNueva={};

            document.getElementById("newAlarma").style.visibility = "hidden";
            document.getElementById("newAlarma").style.display = "none";
            
            document.getElementById("listAlarmas").style.visibility = "visible";
            document.getElementById("listAlarmas").style.display = "inline";

        }

    });


app.controller('tipsCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {
        
        var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";

    //$scope.edicionTip={};
       //$rootScope.iconcepto="TODO";
        //$rootScope.actualView="nca";
    $scope.doubleDigit = function (num){
                if(num<0){
                    num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
    };

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

      /*      
        $scope.ediTip = function(id){
                $location.path('/admontips/edicionTip/'+id);

        };

        $scope.nuevo = function(){
                $location.path('/admontips/nuevoTip/');
                
        };
            */

            $scope.listadoTips={};
     

                services.getListadoTips().then(function(data){
                        $scope.listadoTips=data.data[0];
                        console.log($scope.listadoTips);
                        return data.data;
                });

        $scope.AbreTips = function(id){
 
        var link = "#/tips/visualizacionTip/"+id;
      window.open(window.location.pathname+ link, "_blank", "toolbar=yes, scrollbars=yes, resizable=yes, top=150, left=300, width=900, height=650");

        };
    


    });


app.controller('unicoTipCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services, transtip) {
        var tipID = ($routeParams.tipID) ? parseInt($routeParams.tipID) : 0;
        var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        $rootScope.actualView="tip";

        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";

    $scope.visualizarTip={};
       //$rootScope.iconcepto="TODO";
        //$rootScope.actualView="nca";
    $scope.doubleDigit = function (num){
                if(num<0){
                    num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
    };

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

                var original = transtip.data;
                original._id = tipID;
                $scope.visualizarTip = angular.copy(original); 
                console.log($scope.visualizarTip);

                line="";
                line+="<div>";
                line+= $scope.visualizarTip.USER_POST;
                line+="</div></br></br></br>";
                console.log(line);
                document.getElementById("poster").innerHTML=line;

               /* document.getElementById("btnNuevo").style.visibility = "hidden";
                document.getElementById("btnNuevo").style.display = "none";
                document.getElementById("btnEditar").style.visibility = "visible";
                document.getElementById("btnEditar").style.display = "inline";

        $scope.editar = function(guardarEdicion){

                //var x = document.getElementById("miAreaTexto").text;
                var x = tinymce.get('miAreaTexto').getContent();
                guardarEdicion.USER_POST=x;
                var datetime = document.getElementById("datetimepicker1").value;
                guardarEdicion.POST_TIME=datetime;
                console.log(guardarEdicion.USER_POST);


                if (guardarEdicion.USUARIO_ID.USUARIO_ID!=undefined ){
                    guardarEdicion.USUARIO_ID=guardarEdicion.USUARIO_ID.USUARIO_ID;
                }

                services.actualizarTip(guardarEdicion).then(function(data){
                    return data.data;
                }); 

                $location.path('/admontips');


        };*/        

    });


app.controller('AdmonTipsCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {
        
        var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";
        $scope.error="";
	$scope.edicionTip={};
       //$rootScope.iconcepto="TODO";
        //$rootScope.actualView="nca";
    $scope.doubleDigit = function (num){
                if(num<0){
                    num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
    };

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

            
        $scope.ediTip = function(id){
                $location.path('/admontips/edicionTip/'+id);

        };

        $scope.eliminarTip = function(id){

            var r = confirm("Realmente desea elimiar el TIP?");
            if (r==true){
                console.log(id);
                services.deleteTip(id).then(function(data){
                    $scope.error=data.data['msg'];
                    return data.data;

                });

                services.getListadoAdmonTips().then(function(data){
                    $scope.listado_admontips=data.data[0];
                    return data.data;
                });
            }

        };

        $scope.nuevo = function(){
                $location.path('/admontips/nuevoTip/');
                
        };

            $scope.listado_admontips={};
     
                services.getListadoAdmonTips().then(function(data){
                        $scope.listado_admontips=data.data[0];
                        return data.data;
                });
            

    });


app.controller('editTipsCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services, transtip) {
        var tipID = ($routeParams.tipID) ? parseInt($routeParams.tipID) : 0;
        var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";

    $scope.edicionTip={};
       //$rootScope.iconcepto="TODO";
        //$rootScope.actualView="nca";
    $scope.doubleDigit = function (num){
                if(num<0){
                    num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
    };

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

                var original = transtip.data;
                original._id = tipID;
                $scope.edicionTip = angular.copy(original); 

                services.getListadoUsuarios().then(function(data){
                    $scope.listado_usuarios=data.data[0];
                    //console.log($scope.listado_usuarios);
                    return data.data;
                });

                document.getElementById("btnNuevo").style.visibility = "hidden";
                document.getElementById("btnNuevo").style.display = "none";
                document.getElementById("btnEditar").style.visibility = "visible";
                document.getElementById("btnEditar").style.display = "inline";

        $scope.editar = function(guardarEdicion){

                //var x = document.getElementById("miAreaTexto").text;
                var x = tinymce.get('miAreaTexto').getContent();
                guardarEdicion.USER_POST=x;
                var datetime = document.getElementById("datetimepicker1").value;
                guardarEdicion.POST_TIME=datetime;
                console.log(guardarEdicion.USER_POST);


                if (guardarEdicion.USUARIO_ID.USUARIO_ID!=undefined ){
                    guardarEdicion.USUARIO_ID=guardarEdicion.USUARIO_ID.USUARIO_ID;
                }

                services.actualizarTip(guardarEdicion).then(function(data){
                    return data.data;
                }); 

                $location.path('/admontips');


        };        

    });

app.controller('nuevoTipsCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {
        var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";

    $scope.edicionTip={};
       //$rootScope.iconcepto="TODO";
        //$rootScope.actualView="nca";
    $scope.doubleDigit = function (num){
                if(num<0){
                    num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
    };

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

                document.getElementById("btnEditar").style.visibility = "hidden";
                document.getElementById("btnEditar").style.display = "none";
                document.getElementById("btnNuevo").style.visibility = "visible";
                document.getElementById("btnNuevo").style.display = "inline";

                services.getListadoUsuarios().then(function(data){
                    $scope.listado_usuarios=data.data[0];
                    //console.log($scope.listado_usuarios);
                    return data.data;
                });


        $scope.insertarTip = function(nuevoTip){

                //var x = document.getElementById("miAreaTexto").text;
                var x = tinymce.get('miAreaTexto').getContent();
                nuevoTip.USER_POST=x;
                var datetime = document.getElementById("datetimepicker1").value;
                nuevoTip.POST_TIME=datetime;


                if (nuevoTip.USUARIO_ID.USUARIO_ID!=undefined ){
                    nuevoTip.USUARIO_ID=nuevoTip.USUARIO_ID.USUARIO_ID;
                }
                console.log(nuevoTip);
                services.insertarTip(nuevoTip).then(function(data){
                    return data.data;
                }); 

                $location.path('/admontips');
        };        

    });

app.controller('NCACtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {
	var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";
        $rootScope.iconcepto="TODO";
	    $rootScope.actualView="nca";
                

	$scope.doubleDigit = function (num){

                if(num<0){
                    num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
    };

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

        $scope.nuevoRegistroNCA = function (){
            $rootScope.transaccion={};
            $rootScope.transaccion.ID='';
            $location.path('/nca/transaccion');
        
        };


        $scope.editTransaccionNCA = function (transaccionNCA){

                if(transaccionNCA.OFERTA==undefined || transaccionNCA.OFERTA=="" ){
                        alert("Oferta sin informacion.");
                        return;
                }

                if(transaccionNCA.MUNICIPIO_ID==undefined || transaccionNCA.MUNICIPIO_ID==""){
                        alert("Municipio sin informacion.");
                        return;
                }

                if(transaccionNCA.TRANSACCION==undefined || transaccionNCA.TRANSACCION==""){
                        alert("Transaccion sin informacion.");
                        return;
                }

                if(transaccionNCA.ESTADO==undefined || transaccionNCA.ESTADO==""){
                        alert("Estado sin informacion.");
                        return;
                }

                if(transaccionNCA.FECHA==undefined || transaccionNCA.FECHA==""){
                        alert("Fecha sin informacion.");
                        return;
                }

                if(transaccionNCA.ESTADO_FINAL==undefined || transaccionNCA.ESTADO_FINAL==""){
                        alert("Estado final sin informacion.");
                        return;
                }
                if(transaccionNCA.OBSERVACION==undefined || transaccionNCA.OBSERVACION==""){
                        alert("Observacion sin informacion.");
                        return;
                }

                services.editTransaccionNCA(transaccionNCA).then(function(data){
                        $location.path('/nca/');
                        return data.data;
                });
        };    

    $scope.getTransaccionNCA = function (ncaID){
        //$scope.transaccion={};
        
         services.getTransaccionNCA(ncaID).then(function(data){
            //console.log(ncaID);
            $rootScope.transaccion=data.data[0];
            //console.log($scope.transaccion);
            //console.log(data);
            $location.path('/nca/transaccion');
            return data.data;
        });

    };


	$scope.saveTransaccion = function (transaccion){
		console.log(transaccion);

                if(transaccion.OFERTA==undefined || transaccion.OFERTA=="" ){
                        alert("Oferta sin informacion.");
                        return;
                }

                if(transaccion.MUNICIPIO_ID==undefined || transaccion.MUNICIPIO_ID==""){
                        alert("Municipio sin informacion.");
                        return;
                }

                if(transaccion.TRANSACCION==undefined || transaccion.TRANSACCION==""){
                        alert("Transaccion sin informacion.");
                        return;
                }

                if(transaccion.ESTADO==undefined || transaccion.ESTADO==""){
                        alert("Estado sin informacion.");
                        return;
                }

                if(transaccion.FECHA==undefined || transaccion.FECHA==""){
                        alert("Fecha sin informacion.");
                        return;
                }

                if(transaccion.ESTADO_FINAL==undefined || transaccion.ESTADO_FINAL==""){
                        alert("Estado final sin informacion.");
                        return;
                }
                if(transaccion.OBSERVACION==undefined || transaccion.OBSERVACION==""){
                        alert("Observacion sin informacion.");
                        return;
                }

		var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

		$scope.transaccion.FECHA_FIN=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
		$scope.transaccion.FECHA_INICIO=$scope.FECHA_INICIO;

		$scope.transaccion.DURACION=$scope.transaccion.FECHA_FIN - $scope.FECHA_INICIO;
		$scope.transaccion.FECHA_INICIO=$scope.FECHA_INICIO;

		//$scope.timeInit=new Date().getTime();
		var df=new Date($scope.transaccion.DURACION);
		$scope.transaccion.DURACION= $scope.doubleDigit(df.getHours()-19)+":"+ $scope.doubleDigit(df.getMinutes())+":"+$scope.doubleDigit(df.getSeconds());
		
		$scope.transaccion.USUARIO=userID;
		$scope.transaccion.USERNAME=$rootScope.logedUser.name;

		services.insertTransaccionNCA($scope.transaccion).then(function(data){
			$location.path('/nca/');
                	return data.data;
                });
	};

        $scope.listado_transacciones=[];
	$scope.data = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaIni:"", fechaFin: "" }

        var date1 = new Date();
        var year  = date1.getFullYear();
        var month = $scope.doubleDigit(date1.getMonth()+1);
        var day   = $scope.doubleDigit(date1.getDate());

        var fecha_inicio=year+"-"+month+"-"+day;
        var fecha_fin=year+"-"+month+"-"+day;

        $scope.data.fechaIni=fecha_inicio;
        $scope.data.fechaFin=fecha_fin;

        //services.getListadotransaccionesNCA(fecha_inicio,fecha_fin,$scope.data.currentPage).then(function(data){
	var pathy=$location.path();

        if(pathy=="/nca/"){//esto es para controlar que no se vuelva a llamar este listado cuando se usa la vista de edicion-nuevo
		services.getListadoTransaccionesNCA(fecha_inicio,fecha_fin,$scope.data.currentPage).then(function(data){
                	$scope.listado_transacciones=data.data[0];
                	$scope.data.totalItems=data.data[1];
                	return data.data;
        	});
	}

	if(pathy=="/nca/transaccion"){
		var date1 = new Date();
                var year    = date1.getFullYear();
               	var month   = $scope.doubleDigit(date1.getMonth()+1);
       	        var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
               	var minute  = $scope.doubleDigit(date1.getMinutes());
       	        var seconds = $scope.doubleDigit(date1.getSeconds());
		$scope.FECHA_INICIO=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
		$scope.transaccion.FECHA=year+"-"+month+"-"+day;
       }

        $scope.pageChanged = function() {
                services.getListadoTransaccionesNCA($scope.data.fechaIni,$scope.data.fechaFin,$scope.data.currentPage).then(function(data){
                        $scope.listado_transacciones=data.data[0];
                        $scope.data.totalItems=data.data[1];
                        return data.data;
                });

        };

        $scope.csvNCA = function (){
                var login=$rootScope.logedUser.login;
                services.getCsvNCA(login,$scope.data.fechaIni,$scope.data.fechaFin).then(function(data){
                        //console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });

        };

	
});

//------------------------actividades de Activacion-----------------------------------------
app.controller('ActividadesCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {
    var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";
        $rootScope.iconcepto="TODO";
        $rootScope.actualView="actividades";
        $scope.actividades=[];

    var actividades=services.getactividadesUser(userID).then(function(data){
            	$scope.actividades=data.data;
            	return data.data;
        });

    $scope.doubleDigit = function (num){

                if(num<0){
                    num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
    };

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

        $scope.nuevoRegistroActivadades = function (){
            $rootScope.transaccion={};
            $rootScope.transaccion.ID='';
            $location.path('/actividades/transaccion');

        
        };


        $scope.editTransaccionActividades = function (transaccionA){
               // console.log(transaccionA.FECHA);
               // if(transaccionA.PEDIDO_ID==undefined || transaccionA.PEDIDO_ID==""){
                 //       alert("Fecha sin informacion.");
                   //     return;
                //}

                if(transaccionA.FECHA==undefined || transaccionA.FECHA==""){
                        alert("Fecha sin informacion.");
                        return;
                }

                if(transaccionA.TIPO_TRABAJO==undefined || transaccionA.TIPO_TRABAJO==""){
                        alert("Tipo Trabajo sin informacion.");
                        return;
                }

                if(transaccionA.APLICACION_ACTIVIDADES==undefined || transaccionA.APLICACION_ACTIVIDADES==""){
                        alert("Aplicacion_actividades sin informacion.");
                        return;
                }

                if(transaccionA.COLA==undefined || transaccionA.COLA==""){
                        alert("Cola sin informacion.");
                        return;
                }

                if(transaccionA.AMANECIERON==undefined || transaccionA.AMANECIERON==""){
                        alert("Amanecieron sin informacion.");
                        return;
                }
                if(transaccionA.GESTIONADO_DIA==undefined || transaccionA.GESTIONADO_DIA==""){
                        alert("Gestionado Dia sin informacion.");
                        return;
                }
                if(transaccionA.QUEDAN_PENDIENTES==undefined || transaccionA.QUEDAN_PENDIENTES==""){
                        alert("Quedan Pendientes sin informacion.");
                        return;
                }
              




                services.editTransaccionActividades(transaccionA).then(function(data){
                        $location.path('/actividades/');
                        return data.data;
                });
        };    

    $scope.getTransaccionActividades = function (actividadesID){
        //$scope.transaccion={};
        
         services.getTransaccionActividades(actividadesID).then(function(data){
            $rootScope.transaccion=data.data[0];
               $rootScope.ttrabajo=  $rootScope.transaccion.TIPO_TRABAJO;
                var TTARREGLO = $rootScope.ttrabajo.split(',');

                $rootScope.TIPO_TRABAJO=TTARREGLO;
               // console.log ( $rootScope.TIPO_TRABAJO);
            $location.path('/actividades/transaccion');
            return data.data;
        });

    };


   $scope.saveTransaccion1 = function (transaccion){
       // console.log(transaccion.TIPO_TRABAJO);

               //  if(transaccion.PEDIDO_ID==undefined || transaccion.PEDIDO_ID==""){
                 //       alert("Fecha sin informacion.");
                   //     return;
            //    }
                if(transaccion.FECHA==undefined || transaccion.FECHA==""){
                        alert("Fecha sin informacion.");
                        return;
                }

                if(transaccion.TIPO_TRABAJO==undefined || transaccion.TIPO_TRABAJO==""){
                        alert("Tipo Trabajo sin informacion.");
                        return;
                }

                if(transaccion.APLICACION_ACTIVIDADES==undefined || transaccion.APLICACION_ACTIVIDADES==""){
                        alert("Aplicacion_actividades sin informacion.");
                        return;
                }

                if(transaccion.COLA==undefined || transaccion.COLA==""){
                        alert("Cola sin informacion.");
                        return;
                }

                if(transaccion.AMANECIERON==undefined || transaccion.AMANECIERON==""){
                        alert("Amanecieron sin informacion.");
                        return;
                }
                if(transaccion.GESTIONADO_DIA==undefined || transaccion.GESTIONADO_DIA==""){
                        alert("Gestionado_Dia sin informacion.");
                        return;
                }
                  if(transaccion.QUEDAN_PENDIENTES==undefined || transaccion.QUEDAN_PENDIENTES==""){
                        alert("Quedan_Pendientes sin informacion.");
                        return;
                }


        var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

        $scope.transaccion.FECHA_FIN=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
        $scope.transaccion.FECHA_INICIO=$scope.FECHA_INICIO;
        
        //$scope.transaccion.DURACION=$scope.transaccion.FECHA_FIN - $scope.FECHA_INICIO;
        $scope.transaccion.FECHA_INICIO=$scope.FECHA_INICIO;
        $scope.transaccion.USUARIO=userID;
        $scope.transaccion.TIPO_TRABAJO=transaccion.TIPO_TRABAJO;
        $scope.transaccion.USERNAME=$rootScope.logedUser.name;

        services.insertTransaccionActividades($scope.transaccion).then(function(data){
            $location.path('/actividades/');
                    return data.data;
                });
    };

$scope.listado_transaccionesActividades=[];
    $scope.data = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaIni:"", fechaFin: "" }

        var date1 = new Date();
        var year  = date1.getFullYear();
        var month = $scope.doubleDigit(date1.getMonth()+1);
        var day   = $scope.doubleDigit(date1.getDate());

        var fecha_inicio=year+"-"+month+"-"+day;
        var fecha_fin=year+"-"+month+"-"+day;
        $scope.data.fechaIni=fecha_inicio;
        $scope.data.fechaFin=fecha_fin;
        //$scope.transaccionA.FECHA=year+"-"+month+"-"+day;



    var pathy=$location.path();

        if(pathy=="/actividades/"){//esto es para controlar que no se vuelva a llamar este listado cuando se usa la vista de edicion-nuevo
        services.getListadoTransaccionesActividades(userID,fecha_inicio,fecha_fin,$scope.data.currentPage).then(function(data){
                    $scope.listado_transaccionesActividades=data.data[0];
                    $scope.data.totalItems1=data.data[1];
                    return data.data;
            });
    }

    $scope.listado_transaccionesActividades1=[];
    $scope.data = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaIni:"", fechaFin: "" }

        var date1 = new Date();
        var year  = date1.getFullYear();
        var month = $scope.doubleDigit(date1.getMonth()+1);
        var day   = $scope.doubleDigit(date1.getDate());

        var fecha_inicio=year+"-"+month+"-"+day;
        var fecha_fin=year+"-"+month+"-"+day;
        $scope.data.fechaIni=fecha_inicio;
        $scope.data.fechaFin=fecha_fin;
        //$scope.transaccionA.FECHA=year+"-"+month+"-"+day;



    var pathy=$location.path();

        if(pathy=="/actividades/"){//esto es para controlar que no se vuelva a llamar este listado cuando se usa la vista de edicion-nuevo
        services.getListadoTransaccionesActividades1(userID,fecha_inicio,fecha_fin,$scope.data.currentPage).then(function(data){
                    $scope.listado_transaccionesActividades1=data.data[0];
                    $scope.data.totalItems=data.data[1];
                    return data.data;
            });
    }

    if(pathy=="/actividades/transaccion"){
        var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());
        $scope.FECHA_INICIO=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
       $scope.transaccion.FECHA=year+"-"+month+"-"+day;


      // console.log($scope.transaccion.FECHA);
       }

        $scope.pageChanged = function() {
                services.getListadoTransaccionesActividades($scope.data.fechaIni,$scope.data.fechaFin,$scope.data.currentPage).then(function(data){
                        $scope.listado_transaccionesActividades=data.data[0];
                        $scope.data.totalItems1=data.data[1];
                        return data.data;
                });

        };

        $scope.buscarDatos  = function (usuario){

                if(usuario.length==0||usuario==''){
                        $scope.calcularPendientes();
                }
                if(usuario.length>=0){
                    services.getBuscarDatos(usuario).then(function(data){
                    $scope.listado_transaccionesActividades1=data.data[0];
                        return data.data;

                    });
                }
        };


        $scope.csvactividades = function (){
                var login=$rootScope.logedUser.login;
                services.getCsvactividades(login,$scope.data.fechaIni,$scope.data.fechaFin).then(function(data){
                        //console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });

        };


    
});


app.controller('RegistrosCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {
	
        var userID=$cookieStore.get('logedUser').login;
	$rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";
	//$rootScope.iconcepto="TODO";
		$scope.checho="-1";

	//alert($routeParams.conceptoid);

        $scope.doubleDigit = function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
        };


	//variables de paginacion
	//$scope.currentPage = 1;
	$scope.data = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaIni:"", fechaFin:"", campo:"TODO", valorCampo:"" }
	//$scope.data1 = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaIni:"", fechaFin: "",concepto: "TODO" }

	if($routeParams.conceptoid == undefined){
		$scope.data1 = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaIni:"", fechaFin: "",concepto: "TODO" }
	}else{
		$scope.data1 = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaIni:"", fechaFin: "" }
	}

	if(!angular.isDefined($scope.currentPage)){
    		$scope.currentPage = 1;
  	} 

	$scope.setPage = function (pageNo) {
    		$scope.data.currentPage = pageNo;
  	};

	$rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };


	$scope.listado_pedidos=[];
        var date1 = new Date();
        var year  = date1.getFullYear();
        var month = $scope.doubleDigit(date1.getMonth()+1);
        var day   = $scope.doubleDigit(date1.getDate());

        var fecha_inicio=year+"-"+month+"-"+day;
		var fecha_fin=year+"-"+month+"-"+day;
	
	$scope.data.fechaIni=fecha_inicio;
	$scope.data1.fechaIni=fecha_inicio;
	
	$scope.data.fechaFin=fecha_fin;
	$scope.data1.fechaFin=fecha_fin;

	$rootScope.actualView="registros";
		
 	services.getListadoPedidos(fecha_inicio,fecha_fin,$scope.data.currentPage).then(function(data){
        	$scope.listado_pedidos=data.data[0];
		$scope.data.totalItems=data.data[1];

                return data.data;
        });


	/*services.getListadoPendientes2(fecha_inicio,fecha_fin,$scope.iconcepto).then(function(data){
                $scope.listado_pendientes=data.data;
                return data.data;
        });
	*/

	$scope.calcularPendientes  = function (sconcept){
		$scope.listado_pendientes=[];
		var date1 = new Date();
	        var year  = date1.getFullYear();
        	var month = date1.getMonth()+1;
	        var day   = date1.getDate();
        	var fecha_inicio=year+"-"+month+"-"+day;
	        var fecha_fin=year+"-"+month+"-"+day;
	        
		services.getListadoPendientes2(fecha_inicio,fecha_fin,sconcept,$scope.data1.currentPage).then(function(data){
	                $scope.listado_pendientes=data.data[0];
			$scope.data1.totalItems=data.data[1];
			$scope.data1.concepto=sconcept;
        	        return data.data;
	        });
	};

	$scope.calcularPendientes($scope.data1.concepto);

        $scope.calcularListado  = function (){
                $scope.listado_pedidos=[];
				//$scope.data.campo="";
				//$scope.data.valorCampo="";
                //var date1 = new Date();
                //var year  = date1.getFullYear();
                //var month = date1.getMonth()+1;
                //var day   = date1.getDate();
                //var fecha_inicio=year+"-"+month+"-"+day;
                //var fecha_fin=year+"-"+month+"-"+day;

                services.getListadoPedidos($scope.data.fechaIni,$scope.data.fechaFin,$scope.data.currentPage,$scope.data.campo,$scope.data.valorCampo).then(function(data){
                        $scope.listado_pedidos=data.data[0];
						$scope.data.totalItems=data.data[1];
                        return data.data;
                });


        };


	$scope.cutString = function(str,howMuch) {
		if(str.length>howMuch){
			return (str.slice(0,howMuch)+".. ");
		}else{
			return str;
		}
	};

	//get another portions of data on page changed
	$scope.pageChanged = function(forma) {
		if(forma=="listadoPedidos"){
			$scope.calcularListado();
		}
		if(forma=="listadoPendientes"){
                        $scope.calcularPendientes($scope.data1.concepto);
                }
	};

        $scope.buscarPedidoRegistro  = function (bpedido){

                if(bpedido.length==0||bpedido==''){
			         $scope.calcularPendientes($scope.data1.concepto);

                }
                if(bpedido.length>=7){
                    services.getBuscarPedidoRegistro(bpedido,$scope.data1.concepto).then(function(data){
                    console.log(data.data[0]);
                    $scope.listado_pendientes=data.data[0];
                        return data.data;
                    });
                }
        }; 

        $scope.csvPendientes  = function (concep){
                var login=$rootScope.logedUser.login;
                services.getCsvPendientes(login,concep).then(function(data){
			console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };
		$scope.csvPreInstalaciones  = function (){
                var login=$rootScope.logedUser.login;
                services.getCsvPreInstalaciones(login).then(function(data){
                        console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };

        $scope.csvMalos  = function (concep){
                var login=$rootScope.logedUser.login;
                services.getCsvMalos(login,concep).then(function(data){
                        console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });

        };



	$scope.csvHistoricos = function (){
                var login=$rootScope.logedUser.login;
                services.getCsvHistoricos(login,$scope.data.fechaIni,$scope.data.fechaFin, $scope.data.campo, $scope.data.valorCampo).then(function(data){
			console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });

        };


	$scope.datepickerOptions = {
	    format: 'yyyy-mm-dd',
	    language: 'es',
	    autoclose: true,
	    weekStart: 0
	}

	if($routeParams.conceptoid != undefined){
		//alert("hola");
		$scope.calcularPendientes($routeParams.conceptoid);
	}

});


app.controller('GeneralCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {

        var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";
        
	$rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

	
});

app.controller('ReconfiguracionCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {

         var userID=$cookieStore.get('logedUser').login;
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";

        $scope.pedidos=[];
        $scope.historico_pedido=[];
        $rootScope.actualView="reconfiguraciones";
        $scope.iconcepto="14";
        $scope.popup='';
        $scope.cargando='';
        $scope.pedidoinfo='Pedido';
		
		$scope.doubleDigit= function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
            };
				
	$scope.prioridad='FECHA_CITA';
	
				var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                	
					$rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };
					
		//variables de paginacion_julian
	//$scope.currentPage = 1;

    //oculta los tips para que no se visualicen al inicio.
        document.getElementById("mostrarTIP").style.visibility = "hidden";
        document.getElementById("mostrarTIP").style.display = "none";
    $scope.listadoTips={};

//trae los tips para visualizarlo    
        services.getListadoTips().then(function(data){
            $scope.listadoTips=data.data[0];
            return data.data;
    });

//funcion que muestra los tip cuando se digita su busqueda.
    $scope.muestraBusquedaTip = function (texto) {

            if(texto.length==0||texto==''){
                document.getElementById("mostrarTIP").style.visibility = "hidden";
                document.getElementById("mostrarTIP").style.display = "none";   

                services.getListadoTips().then(function(data){
                    $scope.listadoTips=data.data[0];
                    return data.data;
                });      
            }
                if(texto.length>=3){
                document.getElementById("mostrarTIP").style.visibility = "visible";
                document.getElementById("mostrarTIP").style.display = "inline";            
            }            
    };



    $scope.AbreTips = function(id){

        $scope.nuevoBuscarTip="";

        document.getElementById("mostrarTIP").style.visibility = "hidden";
        document.getElementById("mostrarTIP").style.display = "none";

        services.getListadoTips().then(function(data){
            $scope.listadoTips=data.data[0];
            return data.data;
        });
 
        var link = "#/tips/visualizacionTip/"+id;
        window.open(window.location.pathname+ link, "_blank", "toolbar=yes, scrollbars=yes, resizable=yes, top=150, left=300, width=900, height=650");

    };


//Funcion para copyclipboard
    $scope.executeCopy= function executeCopy(text){
                var input = document.createElement('textarea');
                document.body.appendChild(input);
                input.value = (text);
                //input.focus();
                input.select();
                document.execCommand('Copy');
                input.remove();
            };


    
	$scope.data = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaIni:"", fechaFin:"", campo:"User", valorCampo: userID }
		$scope.data.fechaIni=year+"-"+month+"-"+day;
		$scope.data.fechaFin=year+"-"+month+"-"+day;
		
		 $scope.calcularListadoReconfiguracion  = function (){
                $scope.listado_pedidos=[];
                services.getListadoPedidosReconfiguracion($scope.data.fechaIni,$scope.data.fechaFin,$scope.data.currentPage,$scope.data.campo,$scope.data.valorCampo,userID).then(function(data){
                        $scope.listado_pedidos=data.data[0];
						$scope.data.totalItems=data.data[1];
                        return data.data;
                });
        };
		
		$scope.calcularListadoReconfiguracion();
		
        //var pedidos=services.getPedidosUser(userID).then(function(data){
          //              $scope.pedidos=data.data;
            //            return data.data;
              //  });
			  
			  //get another portions of data on page changed
	$scope.pageChanged = function(forma) {
		if(forma=="listadoPedidos"){
			$scope.calcularListadoReconfiguracion();
		}
		if(forma=="listadoPendientes"){
                        $scope.calcularPendientes($scope.data1.concepto);
                }
	};			  
		
$scope.popoverboton= function (){
$('#popoverId').popover({
    html: true,
    title: "Observaciones",
    content: function () {
        return $('#popoverContent').html();
    }
});
var isVisible = false;
var clickedAway = false;

$('.popoverThis').popover({
    html: true,
    trigger: "click"
})
$(document).click(  function (e) {
    if (isVisible & clickedAway) {
        $('.popoverThis').popover('hide')
        isVisible = clickedAway = false
    } else {
        clickedAway = true
    }
});
};


        var original = $scope.pedidos;
        $scope.peds={};
        $scope.timeInit=0;
        $rootScope.logedUser=$cookieStore.get('logedUser');

        $scope.pedidos = angular.copy(original);

        $scope.isAuthorized = function(concept){

                if(concept=="PEXPQ") return false;
                if(concept=="PSERV") return false;
                if(concept=="PORDE") return false;
                if(concept=="ORDEN") return false;
                if(concept=="PXSLN") return false;
                if(concept=="POPTO") return false;

                if($scope.busy!="") {
                        return false;

                }
                 return true;
        }
		

		$scope.csvHistoricosReconfiguracion = function (){
                //var login=$rootScope.logedUser.login;
                services.getCsvHistoricosReconfiguracion(userID,$scope.data.fechaIni,$scope.data.fechaFin,$scope.data.campo, $scope.data.valorCampo).then(function(data){
			console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };
		
       $scope.manual = function() {
                $scope.peds={};
		$scope.error="";
                $scope.pedido1="";
                $scope.mpedido={};
                $scope.bpedido='';
                $scope.busy="";
                $scope.historico_pedido=[];
                $scope.mpedido.active=1;
                $scope.mpedido.fuente='FENIX_NAL';
                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        };

        $scope.buscarPedido = function(bpedido,iplaza) {

                $scope.peds={};
                $scope.mpedido={};
                $scope.busy="";
                $scope.error="";
                $scope.pedidoinfo='Pedido';
                //console.log(bpedido);
                var kami=services.buscarPedidoReconfiguracion(bpedido,iplaza,$scope.pedido1,$rootScope.logedUser.login,$rootScope.logedUser.name).then(function(data){
                        $scope.peds = data.data;
                        console.log(data.data);
                        var dat=data.status;
                        if(dat==204){
                                //document.getElementById("warning").innerHTML="No hay Registros";
                                $scope.error="No hay Registros";

                        }else{
                                //document.getElementById("warning").innerHTML="";
                                $scope.pedido1=$scope.peds[0].PEDIDO_ID;
                                $scope.pedidoinfo=$scope.peds[0].PEDIDO_ID;

                                if(($scope.peds[0].STATUS=="PENDI_PETEC" || $scope.peds[0].STATUS=="PENDI_RENUMS")&&$scope.peds[0].ASESOR!=""){
                                        $scope.busy=$scope.peds[0].ASESOR;
                                        $scope.error="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;
                                }
                                $scope.baby($scope.pedido1);
                        }
                        return data.data;
                });
                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        };

        $scope.msavePedido = function() {
                console.log($scope.mpedido);
                $scope.pedido={};
		$scope.error="";
                angular.copy($scope.mpedido,$scope.pedido);

                if($scope.mpedido.pedido==""||$scope.mpedido.pedido=={}||$scope.mpedido.pedido === undefined){
                        alert("Pedido vacio.");
                        return;
                }
                $scope.pedido.user=$rootScope.logedUser.login;
                $scope.pedido.username=$rootScope.logedUser.name;
                $scope.pedido.duracion=new Date().getTime() - $scope.timeInit;
                var df=new Date($scope.pedido.duracion);
                $scope.pedido.duracion= $scope.doubleDigit(df.getHours()-19)+":"+ $scope.doubleDigit(df.getMinutes())+":"+ $scope.doubleDigit(df.getSeconds());
                $scope.pedido.actividad="RECONFIGURACION";
                $scope.pedido.concepto_final=$scope.mpedido.concepto;
                $scope.pedido.fecha_inicio=$scope.fecha_inicio;

                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.pedido.fecha_fin=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                console.log($scope.pedido);
                services.insertMPedido($scope.pedido);
                if($scope.pedidos==""){
                        $scope.pedidos=new Array();
                }
                $scope.pedidos=$scope.pedidos.concat($scope.pedido);
                if($scope.historico_pedido==""){
                        $scope.historico_pedido=new Array();
                }
                console.log($scope.historico_pedido);

                $scope.baby($scope.pedido.pedido);
                $scope.pedido1=$scope.pedido.pedido;

                $scope.timeInit=new Date().getTime();
                date1 = new Date();
                year    = date1.getFullYear();
                month   = $scope.doubleDigit(date1.getMonth()+1);
                day     = $scope.doubleDigit(date1.getDate());
                hour    = $scope.doubleDigit(date1.getHours());
                minute  = $scope.doubleDigit(date1.getMinutes());
                seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                $scope.pedido={};
                $scope.peds={};
                $scope.pedido1="";
                $scope.mpedido={};
                $scope.bpedido='';
                $scope.historico_pedido=[];


                $scope.mpedido.active=1;
                $scope.mpedido.fuente='FENIX_NAL';
                $scope.busy="";
                $scope.mpedido.active=0;
                $scope.pedidoinfo='Pedido';
        };


        $scope.savePedido = function(index) {

                var loader = document.getElementById("class"+index);
                loader.className='glyphicon glyphicon-refresh fa-spin';

                $scope.pedido={};
		$scope.error="";
		console.log($scope.peds[index]);
                angular.copy($scope.peds[index],$scope.pedido);
                console.log($scope.pedido);
                if($scope.pedido.estado===undefined || $scope.pedido.estado==''){
                        alert('Por favor diligenciar todos los campos.');
                        loader.className='';
                        return;
                }
                $scope.pedido.user=$rootScope.logedUser.login;
                $scope.pedido.username=$rootScope.logedUser.name;
                $scope.pedido.duracion=new Date().getTime() - $scope.timeInit;

                $scope.timeInit=new Date().getTime();
                var df=new Date($scope.pedido.duracion);
            $scope.pedido.duracion= $scope.doubleDigit(df.getHours()-19)+":"+ $scope.doubleDigit(df.getMinutes())+":"+$scope.doubleDigit(df.getSeconds());
            $scope.pedido.pedido=$scope.peds[index].PEDIDO_ID+$scope.peds[index].SUBPEDIDO_ID+$scope.peds[index].SOLICITUD_ID;
                $scope.pedido1=$scope.peds[index].PEDIDO_ID;

                $scope.pedido.actividad="RECONFIGURACION";
                $scope.pedido.fuente="FENIX_NAL";

		if($scope.pedido.source==''){
			$scope.pedido.source="AUTO";
		}

                $scope.pedido.fecha_inicio=$scope.fecha_inicio;

                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.pedido.fecha_fin=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

                var dat= new Date();

                services.insertPedidoReconfiguracion($scope.pedido).then(function (status) {
						$scope.calcularListadoReconfiguracion();
                        $scope.pedido.fecha=status.data['data'];
                        $scope.pedido.concepto_final=status.data['msg'];
                        if($scope.pedido.concepto_final=="El pedido NO ha cambiado de concepto en Fenix!!!"){
                                alert($scope.pedido.concepto_final);
                                $scope.pedido.fecha="";
                                $scope.pedido.concepto_final="";
                        }else{
                                $scope.historico_pedido=$scope.historico_pedido.concat(angular.copy($scope.pedido));
                                $scope.peds.splice(index,1);
                                 if($scope.pedidos==""){
                                        $scope.pedidos=new Array();
                                }
                                $scope.pedidos=$scope.pedidos.concat($scope.pedido);
                                if($scope.historico_pedido==""){
                                        $scope.historico_pedido=new Array();
                                }
                                        $scope.pedido=[];
                                        $scope.busy="";
                                        $scope.timeInit=new Date().getTime();
                                        date1 = new Date();
                                        year    = date1.getFullYear();
                                        month   = $scope.doubleDigit(date1.getMonth()+1);
                                        day     = $scope.doubleDigit(date1.getDate());
                                        hour    = $scope.doubleDigit(date1.getHours());
                                        minute  = $scope.doubleDigit(date1.getMinutes());
                                        seconds = $scope.doubleDigit(date1.getSeconds());

                                        $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                                        $scope.popup='';
                                    
                        }
                    loader.className='';
                    $scope.pedidoinfo='Pedido';					
			   return status;
		   });
   };
		
        $scope.baby = function(pedido) {
                console.log(pedido);
                services.getPedidosPorPedido(pedido).then(function(data){
                      console.log(data.data);
                      $scope.historico_pedido=data.data;
                      return data.data;
                 });
        };

        $scope.start = function(pedido) {
                var pedido1='';
                $scope.popup='';
		$scope.error="";
                if(JSON.stringify($scope.peds) !=='{}' && $scope.peds.length>0){
                         pedido1=$scope.peds[0].PEDIDO_ID;
                }
                $scope.peds={};
                $scope.mpedido={};
                $scope.bpedido='';
                $scope.busy="";
                $scope.pedido1=pedido1;

                var demePedidoButton=document.getElementById("iniciar");
                demePedidoButton.setAttribute("disabled","disabled");
                demePedidoButton.className = "btn btn-success disabled";

	
		if($scope.prioridad=='FECHA_CITA'){
			$scope.prioridad='FECHA_CITA';
		}else{
			$scope.prioridad='FECHA_CITA';
		}

                var kami=services.demePedido($rootScope.logedUser.login,$scope.iconcepto,$scope.pedido1,$scope.iplaza,$rootScope.logedUser.name,$scope.prioridad).then(function(data){
                        $scope.peds = data.data;
                        console.log(data.data);
                        if(data.data==''){
                                document.getElementById("warning").innerHTML="No hay Registros";
                                $scope.error="No hay Registros";
                        }else{
                                document.getElementById("warning").innerHTML="";
                                $scope.pedido1=$scope.peds[0].PEDIDO_ID;
                                $scope.pedidoinfo=$scope.peds[0].PEDIDO_ID;

				console.log("esta es la info que trae el deme pedido");
				console.log($scope.peds[0].ASESOR);
				if($scope.peds[0].ASESOR!=""&&$scope.peds[0].ASESOR!=undefined){
                                        $scope.busy=$scope.peds[0].ASESOR;
                                        $scope.error="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;
                                }

                                $scope.baby($scope.pedido1);
                        }
                        var demePedidoButton=document.getElementById("iniciar");
                        demePedidoButton.removeAttribute("disabled");
                        demePedidoButton.className = "btn btn-success";
                        return data.data;
                });

               $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        };

$scope.intervalLightKPIS = setInterval(function(){
                $scope.actualizarLightKPIS();
           },60000);

        $scope.actualizarLightKPIS = function (){
                services.getLightKPIS().then(function(data){
            $rootScope.oldlightkpi=$rootScope.lightkpi;
                        $rootScope.lightkpi=data.data[0];


            if($rootScope.oldlightkpi==""||$rootScope.oldlightkpi==undefined){
                $rootScope.oldlightkpi=$rootScope.lightkpi;
            }

            //console.log($rootScope.lightkpi);
            //
            var arrayLength = $rootScope.lightkpi.length;
            var arrayLength2 = $rootScope.oldlightkpi.length;
            

            var negocioAsingaciones="<table class='table small table-striped table-hover table-bordered table-condensed'>"+
                                "<thead>"+
                                        "<th>Concepto</th>"+
                                        "<th>Cantidad</th>"+
                                "</thead>"+
                                "<tbody>";
            var negocioReconfiguracion="<table class='table small table-striped table-hover table-bordered table-condensed'>"+
                                "<thead>"+
                                        "<th>Concepto</th>"+
                                        "<th>Cantidad</th>"+
                                "</thead>"+
                                "<tbody>";

            var negocioOtros="<table class='table small table-striped table-hover table-bordered table-condensed'>"+
                                "<thead>"+
                                        "<th>Concepto</th>"+
                                        "<th>Cantidad</th>"+
                                "</thead>"+
                                "<tbody>";


            $rootScope.totalNegocioAsignacionesOld=$rootScope.totalNegocioAsignaciones;
            $rootScope.totalNegocioReconfiguracionOld=$rootScope.totalNegocioReconfiguracion;
            $rootScope.totalNegocioOtrosOld=$rootScope.totalNegocioOtros;


            $rootScope.totalNegocioAsignaciones=0;
            $rootScope.totalNegocioReconfiguracion=0;
            $rootScope.totalNegocioOtros=0;


            for (var i = 0; i < arrayLength; i++) {
                var counter=$rootScope.lightkpi[i].COUNTER;
                var concepto_id=$rootScope.lightkpi[i].CONCEPTO_ID;

                if(concepto_id=='PETEC'||concepto_id=='OKRED'||concepto_id=='PETEC-BOG'||concepto_id=='PEOPP'||concepto_id=='19'||concepto_id=='O-13'||concepto_id=='O-15'||concepto_id=='O-106'){
                    negocioAsingaciones+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"<font color='DarkGray'><strong><i>&nbsp;&nbsp; Servicios</strong></i></font></td></tr>";
                    $rootScope.totalNegocioAsignaciones=parseInt($rootScope.totalNegocioAsignaciones)+parseInt(counter);
                }else if(concepto_id=='14'||concepto_id=='99'||concepto_id=='92'){
                        negocioReconfiguracion+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"<font color='DarkGray'><strong><i>&nbsp;&nbsp; Pedidos</strong></i></font></td></tr>";
                    $rootScope.totalNegocioReconfiguracion=parseInt($rootScope.totalNegocioReconfiguracion)+parseInt(counter);
                                }else if(concepto_id=='O-101'){
                                    negocioReconfiguracion+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"<font color='DarkGray'><strong><i>&nbsp;&nbsp; Servicios</strong></i></font></td></tr>";
                                    $rootScope.totalNegocioReconfiguracion=parseInt($rootScope.totalNegocioReconfiguracion)+parseInt(counter);
                                    }else{
                                       negocioOtros+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"<font color='DarkGray'><strong><i>&nbsp;&nbsp; Servicios</strong></i></font></td></tr>";
                                       $rootScope.totalNegocioOtros=parseInt($rootScope.totalNegocioOtros)+parseInt(counter);
                                    }
            }
            
            $rootScope.nasignacionesstyle={};
            $rootScope.nreconfiguracionstyle={};
            $rootScope.notrosstyle={};


            if($rootScope.totalNegocioAsignaciones>$rootScope.totalNegocioAsignacionesOld){
                            $rootScope.nasignacionesstyle.ICON="fa fa-arrow-circle-up fa-2x";
                                $rootScope.nasignacionesstyle.STYLE="red";
                        }else if($rootScope.totalNegocioAsignaciones<$rootScope.totalNegocioAsignacionesOld){
                                $rootScope.nasignacionesstyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.nasignacionesstyle.STYLE="green";
                        }else {
                                $rootScope.nasignacionesstyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.nasignacionesstyle.STYLE="gray";
                        }

                        if($rootScope.totalNegocioReconfiguracion>$rootScope.totalNegocioReconfiguracionOld){
                                $rootScope.nreconfiguracionstyle.ICON="fa fa-arrow-circle-up fa-2x";
                                $rootScope.nreconfiguracionstyle.STYLE="red";
                        }else if($rootScope.totalNegocioReconfiguracion<$rootScope.totalNegocioReconfiguracionOld){
                                $rootScope.nreconfiguracionstyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.nreconfiguracionstyle.STYLE="green";
                        }else {
                                $rootScope.nreconfiguracionstyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.nreconfiguracionstyle.STYLE="gray";
                        }


                        if($rootScope.totalNegocioOtros>$rootScope.totalNegocioOtrosOld){
                                $rootScope.notrosstyle.ICON="fa fa-arrow-circle-up fa-2x";
                                $rootScope.notrosstyle.STYLE="red";
                        }else if($rootScope.totalNegocioOtros<$rootScope.totalNegocioOtrosOld){
                                $rootScope.notrosstyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.notrosstyle.STYLE="green";
                        }else {
                                $rootScope.notrosstyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.notrosstyle.STYLE="gray";
                        }


            document.getElementById("nasignaciones").innerHTML=negocioAsingaciones+"</tbody></table>";
            document.getElementById("nreconfiguracion").innerHTML=negocioReconfiguracion+"</tbody></table>";
            document.getElementById("notros").innerHTML=negocioOtros+"</tbody></table>";

                        return data.data;
                });
        }

        $scope.$on(
                "$destroy",
                        function( event ) {
                            $timeout.cancel(timer);
                            clearInterval($scope.intervalLightKPIS);
          });


$scope.actualizarLightKPIS();


});




app.controller('AsignacionesCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, $timeout, services) {

    //var userID = ($routeParams.userID) ? parseInt($routeParams.userID) : 0;
    //
    //alert('entro al controlador');
	var userID=$cookieStore.get('logedUser').login;
	document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";
    $scope.pedidos=[];
    $scope.pedidosUnicos='';
	$scope.historico_pedido=[];
	$rootScope.actualView="asignaciones";
	$scope.iconcepto="PETEC";
	$scope.popup='';
	$scope.intervalLightKPIS='';
    $scope.pedidoinfo='Pedido';

	var pedidos=services.getPedidosUser(userID).then(function(data){
            $scope.pedidos=data.data[0];
            $scope.pedidosUnicos=data.data[1];
            return data.data;
                });

      	var original = $scope.pedidos;
        var originalUnico=$scope.pedidosUnicos;
	$scope.peds={};
	$scope.timeInit=0;
	$rootScope.logedUser=$cookieStore.get('logedUser');
	
    $scope.pedidos = angular.copy(original);
	$scope.error="";


//oculta los tips para que no se visualicen al inicio.
        document.getElementById("mostrarTIP").style.visibility = "hidden";
        document.getElementById("mostrarTIP").style.display = "none";
    $scope.listadoTips={};

//trae los tips para visualizarlo    
        services.getListadoTips().then(function(data){
            $scope.listadoTips=data.data[0];
            return data.data;
    });

//funcion que muestra los tip cuando se digita su busqueda.
    $scope.muestraBusquedaTip = function (texto) {

            if(texto.length==0||texto==''){
                document.getElementById("mostrarTIP").style.visibility = "hidden";
                document.getElementById("mostrarTIP").style.display = "none";   

                services.getListadoTips().then(function(data){
                    $scope.listadoTips=data.data[0];
                    return data.data;
                });      
            }
                if(texto.length>=3){
                document.getElementById("mostrarTIP").style.visibility = "visible";
                document.getElementById("mostrarTIP").style.display = "inline";            
            }            
    };



    $scope.AbreTips = function(id){

        $scope.nuevoBuscarTip="";

        document.getElementById("mostrarTIP").style.visibility = "hidden";
        document.getElementById("mostrarTIP").style.display = "none";

        services.getListadoTips().then(function(data){
            $scope.listadoTips=data.data[0];
            return data.data;
        });
 
        var link = "#/tips/visualizacionTip/"+id;
        window.open(window.location.pathname+ link, "_blank", "toolbar=yes, scrollbars=yes, resizable=yes, top=150, left=300, width=900, height=650");

    };

    $scope.pedunicos=function(){
        var pedunic=services.getPedidosUser(userID).then(function(data){
            $scope.pedidosUnicos=data.data[1];
            return data.data;
                });};


    $scope.AbreVecinos = function(pagina){

        //$scope.nuevoBuscarTip="";

        //document.getElementById("mostrarTIP").style.visibility = "hidden";
        //document.getElementById("mostrarTIP").style.display = "none";
 
         var link = "#/vecinos/"+pagina;
        window.open(window.location.pathname+ link, "_blank", "toolbar=yes, scrollbars=yes, resizable=yes, top=50, left=70, right=100, width=1200, height=600");

    };

//Funcion para copyclipboard
    $scope.executeCopy= function executeCopy(text){
                var input = document.createElement('textarea');
                document.body.appendChild(input);
                input.value = (text);
                //input.focus();
                input.select();
                document.execCommand('Copy');
                input.remove();
            };

	$scope.isAuthorized = function(concept){

        var CargoID = parseInt($rootScope.logedUser.CARGO_ID);

        if(CargoID>5){

            if(concept=="PEXPQ") return false;
            if(concept=="PSERV") return false;
            if(concept=="ORDEN") return false;
            if(concept=="PXSLN") return false;
            if(concept=="PFACT") return false;
            if(concept=="CUMPL") return false;
            if(concept=="PORDE") return false;
            if(concept=="FACTU") return false;

        }



        /* codigo viejo

		if(concept=="PEXPQ") return false;
		if(concept=="PSERV") return false;
		if(concept=="ORDEN" && CargoID > 5) return false;
		if(concept=="PXSLN") return false;
        if(concept=="PFACT") return false;
        if(concept=="CUMPL") return false;
        //alert("concepto:"+concept+ " cargo: "+CargoID );
        if(concept=="PORDE" && CargoID > 5) return false; */
                
		//para controlar campos cuando el pedido esta ocupado por alguien mas....
		if($scope.busy!="") {
			//alert($scope.busy);
			return false;
			
		}
                 return true;
        }

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
		clearInterval($scope.intervalLightKPIS);
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };


       // Disque Light KPI --------------------------------------------------------------

	$scope.intervalLightKPIS = setInterval(function(){
                $scope.actualizarLightKPIS();
           },60000);

        $scope.actualizarLightKPIS = function (){
                services.getLightKPIS().then(function(data){
			$rootScope.oldlightkpi=$rootScope.lightkpi;
                        $rootScope.lightkpi=data.data[0];


			if($rootScope.oldlightkpi==""||$rootScope.oldlightkpi==undefined){
				$rootScope.oldlightkpi=$rootScope.lightkpi;
			}

			//console.log($rootScope.lightkpi);
			//
			var arrayLength = $rootScope.lightkpi.length;
			var arrayLength2 = $rootScope.oldlightkpi.length;
			

			var negocioAsingaciones="<table class='table small table-striped table-hover table-bordered table-condensed'>"+
                                "<thead>"+
                                        "<th>Concepto</th>"+
                                        "<th>Cantidad</th>"+
                                "</thead>"+
                                "<tbody>";
			var negocioReconfiguracion="<table class='table small table-striped table-hover table-bordered table-condensed'>"+
                                "<thead>"+
                                        "<th>Concepto</th>"+
                                        "<th>Cantidad</th>"+
                                "</thead>"+
                                "<tbody>";

			var negocioOtros="<table class='table small table-striped table-hover table-bordered table-condensed'>"+
                                "<thead>"+
                                        "<th>Concepto</th>"+
                                        "<th>Cantidad</th>"+
                                "</thead>"+
                                "<tbody>";


			$rootScope.totalNegocioAsignacionesOld=$rootScope.totalNegocioAsignaciones;
			$rootScope.totalNegocioReconfiguracionOld=$rootScope.totalNegocioReconfiguracion;
			$rootScope.totalNegocioOtrosOld=$rootScope.totalNegocioOtros;


			$rootScope.totalNegocioAsignaciones=0;
			$rootScope.totalNegocioReconfiguracion=0;
			$rootScope.totalNegocioOtros=0;


			for (var i = 0; i < arrayLength; i++) {
				var counter=$rootScope.lightkpi[i].COUNTER;
				var concepto_id=$rootScope.lightkpi[i].CONCEPTO_ID;

				if(concepto_id=='PETEC'||concepto_id=='OKRED'||concepto_id=='PETEC-BOG'||concepto_id=='PEOPP'||concepto_id=='19'||concepto_id=='O-13'||concepto_id=='O-15'||concepto_id=='O-106'||concepto_id=='PUMED'||concepto_id=='COBERTURA'||concepto_id=='CONSTRUCCION'||concepto_id=='DISENO'||concepto_id=='DISPONIBILIDAD'){
					negocioAsingaciones+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"<font color='DarkGray'><strong><i>&nbsp;&nbsp; Servicios</strong></i></font></td></tr>";
					$rootScope.totalNegocioAsignaciones=parseInt($rootScope.totalNegocioAsignaciones)+parseInt(counter);
				}else if(concepto_id=='14'||concepto_id=='99' ||concepto_id=='92'){
                        negocioReconfiguracion+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"<font color='DarkGray'><strong><i>&nbsp;&nbsp; Pedidos</strong></i></font></td></tr>";
					$rootScope.totalNegocioReconfiguracion=parseInt($rootScope.totalNegocioReconfiguracion)+parseInt(counter);
                                }else if(concepto_id=='O-101'){
                                    negocioReconfiguracion+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"<font color='DarkGray'><strong><i>&nbsp;&nbsp; Servicios</strong></i></font></td></tr>";
                                    $rootScope.totalNegocioReconfiguracion=parseInt($rootScope.totalNegocioReconfiguracion)+parseInt(counter);
                                    }else{
					                   negocioOtros+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"<font color='DarkGray'><strong><i>&nbsp;&nbsp; Servicios</strong></i></font></td></tr>";
					                   $rootScope.totalNegocioOtros=parseInt($rootScope.totalNegocioOtros)+parseInt(counter);
				                    }
			}
			
			$rootScope.nasignacionesstyle={};
			$rootScope.nreconfiguracionstyle={};
			$rootScope.notrosstyle={};


			if($rootScope.totalNegocioAsignaciones>$rootScope.totalNegocioAsignacionesOld){
                        	$rootScope.nasignacionesstyle.ICON="fa fa-arrow-circle-up fa-2x";
                             	$rootScope.nasignacionesstyle.STYLE="red";
                        }else if($rootScope.totalNegocioAsignaciones<$rootScope.totalNegocioAsignacionesOld){
                                $rootScope.nasignacionesstyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.nasignacionesstyle.STYLE="green";
                        }else {
                                $rootScope.nasignacionesstyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.nasignacionesstyle.STYLE="gray";
                        }

                        if($rootScope.totalNegocioReconfiguracion>$rootScope.totalNegocioReconfiguracionOld){
                                $rootScope.nreconfiguracionstyle.ICON="fa fa-arrow-circle-up fa-2x";
                                $rootScope.nreconfiguracionstyle.STYLE="red";
                        }else if($rootScope.totalNegocioReconfiguracion<$rootScope.totalNegocioReconfiguracionOld){
                                $rootScope.nreconfiguracionstyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.nreconfiguracionstyle.STYLE="green";
                        }else {
                                $rootScope.nreconfiguracionstyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.nreconfiguracionstyle.STYLE="gray";
                        }


                        if($rootScope.totalNegocioOtros>$rootScope.totalNegocioOtrosOld){
                                $rootScope.notrosstyle.ICON="fa fa-arrow-circle-up fa-2x";
                                $rootScope.notrosstyle.STYLE="red";
                        }else if($rootScope.totalNegocioOtros<$rootScope.totalNegocioOtrosOld){
                                $rootScope.notrosstyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.notrosstyle.STYLE="green";
                        }else {
                                $rootScope.notrosstyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.notrosstyle.STYLE="gray";
                        }


			document.getElementById("nasignaciones").innerHTML=negocioAsingaciones+"</tbody></table>";
			document.getElementById("nreconfiguracion").innerHTML=negocioReconfiguracion+"</tbody></table>";
			document.getElementById("notros").innerHTML=negocioOtros+"</tbody></table>";

                        return data.data;
                });
        }

        $scope.$on(
                "$destroy",
                        function( event ) {
                            $timeout.cancel($scope.intervalLightKPIS);
                            clearInterval($scope.intervalLightKPIS);
          });

	// --------------------------------------------------------------Disque Light KPI


	$scope.manual = function() {
		$scope.peds={};
		$scope.error="";
		$scope.pedido1="";
		$scope.mpedido={};
		$scope.bpedido='';
		$scope.busy="";
		$scope.historico_pedido=[];
		$scope.mpedido.active=1;
		$scope.mpedido.fuente='FENIX_NAL';
                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

	};

                 $scope.validaMunicipio = function(index) {
                            $scope.MUNICIPIO = $scope.peds[index].MUNICIPIO_ID;
                            buscar = /ANTCOL/;
                            var rta = buscar.test($scope.peds[index].MUNICIPIO_ID);
                            return rta;
                }

        $scope.buscarPedido = function(bpedido,iplaza) {
        	$scope.error="";
                $scope.peds={};
                $scope.mpedido={};
                $scope.busy="";
                $scope.error="";
                $scope.pedidoinfo='Pedido';
            
                //$scope.pedidoinfo='';
                var kami=services.buscarPedido(bpedido,iplaza,$scope.pedido1,$rootScope.logedUser.login,$rootScope.logedUser.name).then(function(data){
                        $scope.peds = data.data;         
			           console.log(data.status);
			var dat=data.status;
			//alert("'"+data.status+"'");
                        if(dat==204){
                                document.getElementById("warning").innerHTML="No hay Registros. Intente Cambiando de Plaza";
				$scope.error="No hay Registros. Intente Cambiando de Plaza";
                        }else{

                                if($scope.peds[0]=="PEDIDO_OCUPADO"){
                                        $scope.error="El pedido: "+$scope.peds[2] + " está ocupado por:" +$scope.peds[1] ;
										$scope.pedidoinfo='Pedido';
										$scope.peds=[];
                                        return;

                                }


                                document.getElementById("warning").innerHTML="";
                                $scope.pedido1=$scope.peds[0].PEDIDO_ID;
                                $scope.pedidoinfo=$scope.peds[0].PEDIDO_ID;
                                //$scope.pedidoinfo=$scope.peds[0].PEDIDO_ID;

					//alert("El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR);
				/*if($scope.peds[0].STATUS=="PENDI_PETEC"&&$scope.peds[0].ASESOR!=""){
					$scope.busy=$scope.peds[0].ASESOR;
					//alert("El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR);
					$scope.error="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;
				}*/

                        /*   console.log("este es el municipo: " + $scope.peds[0].MUNICIPIO_ID);
                            $scope.MUNICIPIO = $scope.peds[0].MUNICIPIO_ID;
                            buscar = /ANTCOL/;
                            $scope.validaMunicipio = buscar.test($scope.peds[0].MUNICIPIO_ID);
                            console.log("este es el municipo abreviado: " + $scope.validaMunicipio);
                                $scope.baby($scope.pedido1);*/
                        }   

                        //$scope.MUNICIPIO = $scope.peds[0].MUNICIPIO_ID;
                          //  buscar = /ANTCOL/;
                            //$scope.validaMunicipio = buscar.test($scope.peds[0].MUNICIPIO_ID);
                            //console.log("esta es la validacion " + $scope.validaMunicipio);
                            //$rootScope.pagina_servicio_vecinos = $scope.peds[0].PAGINA_SERVICIO;
                            //console.log("esto es lo que retorna" + $scope.validaMunicipio + " y la pagina " + $scope.peds[0].PAGINA_SERVICIO);

                        return data.data;
                });
                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        };

	
	$scope.msavePedido = function() {
        
        var loader = document.getElementById("mloader");
        mloader.className='glyphicon glyphicon-refresh fa-spin';

		console.log($scope.mpedido);
		$scope.pedido={};
		$scope.error="";
		angular.copy($scope.mpedido,$scope.pedido);
		//alert($scope.mpedido.pedido);
		if($scope.mpedido.pedido==""||$scope.mpedido.pedido=={}||$scope.mpedido.pedido === undefined){
			alert("Pedido vacio.");
            mloader.className='';
			return;
		}
		$scope.pedido.user=$rootScope.logedUser.login;
		$scope.pedido.username=$rootScope.logedUser.name;
                $scope.pedido.duracion=new Date().getTime() - $scope.timeInit;
		var df=new Date($scope.pedido.duracion);
		$scope.pedido.duracion= $scope.doubleDigit(df.getHours()-19)+":"+ $scope.doubleDigit(df.getMinutes())+":"+ $scope.doubleDigit(df.getSeconds());
		$scope.pedido.actividad="ESTUDIO";
		$scope.pedido.concepto_final=$scope.mpedido.concepto;
		$scope.pedido.fecha_inicio=$scope.fecha_inicio;

                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.pedido.fecha_fin=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
		console.log($scope.pedido);
		services.insertMPedido($scope.pedido);
                if($scope.pedidos==""){
                        $scope.pedidos=new Array();
                }
                $scope.pedidos=$scope.pedidos.concat($scope.pedido);
                if($scope.historico_pedido==""){
                        $scope.historico_pedido=new Array();
                }
		console.log($scope.historico_pedido);

		$scope.baby($scope.pedido.pedido);
		$scope.pedido1=$scope.pedido.pedido;
		
                $scope.timeInit=new Date().getTime();
                date1 = new Date();
                year    = date1.getFullYear();
                month   = $scope.doubleDigit(date1.getMonth()+1);
                day     = $scope.doubleDigit(date1.getDate());
                hour    = $scope.doubleDigit(date1.getHours());
                minute  = $scope.doubleDigit(date1.getMinutes());
                seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

		$scope.pedido={};
	        $scope.peds={};
                $scope.pedido1="";
                $scope.mpedido={};
                $scope.bpedido='';
                $scope.historico_pedido=[];
                $scope.mpedido.active=1;
                $scope.mpedido.fuente='FENIX_NAL';
		$scope.busy="";
		$scope.mpedido.active=0;
        $scope.pedidoinfo='Pedido';
        mloader.className='';
	};

    $scope.savePedido = function(index) {

        var loader = document.getElementById("class"+index);
        loader.className='glyphicon glyphicon-refresh fa-spin';

		$scope.pedido={};
		
		$scope.error="";

		//$scope.pedido=$scope.peds[index];
		angular.copy($scope.peds[index],$scope.pedido);
		console.log($scope.pedido);
		//if($scope.pedido.estado===undefined||$scope.pedido.accion===undefined){
		if($scope.pedido.estado===undefined){
			alert('Por favor diligenciar todos los campos.');
			return;
		}
		//console.log($scope.pedido);
		$scope.pedido.user=$rootScope.logedUser.login;
		$scope.pedido.username=$rootScope.logedUser.name;
		$scope.pedido.duracion=new Date().getTime() - $scope.timeInit;

		$scope.timeInit=new Date().getTime();
		var df=new Date($scope.pedido.duracion);
		$scope.pedido.duracion= $scope.doubleDigit(df.getHours()-19)+":"+ $scope.doubleDigit(df.getMinutes())+":"+ $scope.doubleDigit(df.getSeconds());
		$scope.pedido.pedido=$scope.peds[index].PEDIDO_ID+$scope.peds[index].SUBPEDIDO_ID+$scope.peds[index].SOLICITUD_ID;
		$scope.pedido1=$scope.peds[index].PEDIDO_ID;//esta variable es para saber cual es el pedido actual en el sistema, esto con el fin de liberarlo cuando se quiera trabajar otro pedido
		//pedido.pedido_id=
		//pedido.estado=$scope.peds[index].estado;
		//pedido.observacion=$scope.peds[index].observacion;
		$scope.pedido.actividad="ESTUDIO";
		$scope.pedido.fuente=$scope.peds[index].FUENTE;
		$scope.pedido.fecha_inicio=$scope.fecha_inicio;

          	var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

		$scope.pedido.fecha_fin=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;	

  		var dat= new Date();
		//$scope.pedido.statusfinal="hoho";
		services.insertPedido($scope.pedido).then(function (status) {
                        $scope.pedido.fecha=status.data['data'];
                        $scope.pedido.concepto_final=status.data['msg'];
                        $scope.pedido.con_fenix=status.data['con_fenix'];   
                        

                       /* if($scope.pedido.concepto_final=="El pedido bloqueado por Usuario por mas de una hora, fue liberado por el sistema, usuario no podra gestionarlo hasta despues de una hora!!!"){
                            $scope.error=$scope.pedido.concepto_final;
                                //$scope.peds.splice(index,1);
                                $scope.peds=[];
                                $scope.pedido={};
                                $scope.pedidos="";
                        }*/

                        if($scope.pedido.concepto_final=="El pedido NO ha cambiado de concepto en Fenix!!!" || $scope.pedido.concepto_final=="ERROR!"){
                                alert($scope.pedido.concepto_final);
                                

                                $scope.pedido.fecha="";
                                $scope.pedido.concepto_final="";
                        }else{

                            if($scope.pedido.concepto_final=="El pedido bloqueado por Usuario por mas de una hora, fue liberado por el sistema, usuario no podra gestionarlo hasta despues de una hora!!!"){
                            $scope.error=$scope.pedido.concepto_final;
                                //$scope.peds.splice(index,1);
                                $scope.peds=[];
                                $scope.pedido={};
                                $scope.pedidos="";
                        } else{
                                $scope.historico_pedido=$scope.historico_pedido.concat(angular.copy($scope.pedido));
                                $scope.peds.splice(index,1);
                                 if($scope.pedidos==""){
                                        $scope.pedidos=new Array();
                                }

                                $scope.pedido.concepto_final=$scope.pedido.con_fenix;
                                $scope.pedidos=$scope.pedidos.concat($scope.pedido);
                                if($scope.historico_pedido==""){
                                        $scope.historico_pedido=new Array();
                                }
                                        $scope.pedido=[];
                                        $scope.busy="";
                                        $scope.timeInit=new Date().getTime();
                                        date1 = new Date();
                                        year    = date1.getFullYear();
                                        month   = $scope.doubleDigit(date1.getMonth()+1);
                                        day     = $scope.doubleDigit(date1.getDate());
                                        hour    = $scope.doubleDigit(date1.getHours());
                                        minute  = $scope.doubleDigit(date1.getMinutes());
                                        seconds = $scope.doubleDigit(date1.getSeconds());

                                        $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                                        $scope.popup='';
                            }

                        }
                loader.className='';
                if($scope.peds.length==0){ 
                    $scope.pedidoinfo='Pedido';
                }
               
                return status;
           });

    };

	$scope.baby = function(pedido) {
		console.log(pedido);
		services.getPedidosPorPedido(pedido).then(function(data){
                      console.log(data.data);
                      $scope.historico_pedido=data.data;
                      return data.data;
                 });		
	};

	$scope.start = function(pedido) {
                var pedido1='';
		$scope.popup='';
		$scope.error="";
        
		if(JSON.stringify($scope.peds) !=='{}' && $scope.peds.length>0){
			//alert($scope.peds[0].PEDIDO_ID);
			 pedido1=$scope.peds[0].PEDIDO_ID;
             
		}
		$scope.peds={};
		$scope.mpedido={};
		$scope.bpedido='';
		$scope.busy="";
		$scope.pedido1=pedido1;


		$scope.error="";

		var demePedidoButton=document.getElementById("iniciar");
		demePedidoButton.setAttribute("disabled","disabled");
		demePedidoButton.className = "btn btn-success disabled";

		var kami=services.demePedido($rootScope.logedUser.login,$scope.iconcepto,$scope.pedido1,$scope.iplaza,$rootScope.logedUser.name,'').then(function(data){
        		$scope.peds = data.data;
                //console.log("este es el municipio" + $scope.peds[0].MUNICIPIO_ID);
                //$scope.MUNICIPIO = $scope.peds[0].MUNICIPIO_ID;
                //buscar = /ANTCOL/;
                //$scope.validaMunicipio = buscar.test($scope.MUNICIPIO);
               //console.log("esta es la validacion " + $scope.validaMunicipio);
            //$rootScope.pagina_servicio_vecinos = $scope.peds[0].PAGINA_SERVICIO;
            //console.log("esto es lo que retorna" + $rootScope.pagina_servicio_vecinos);
			if(data.data==''){
				document.getElementById("warning").innerHTML="No hay Registros. Intente Cambiando de plaza.";
				$scope.error="No hay Registros. Intente Cambiando de plaza.";
			}else{
				document.getElementById("warning").innerHTML="";
				$scope.pedido1=$scope.peds[0].PEDIDO_ID;
                                $scope.pedidoinfo=$scope.peds[0].PEDIDO_ID;



                                if($scope.peds[0].STATUS=="PENDI_PETEC"&&$scope.peds[0].ASESOR!=""){
                                        $scope.busy=$scope.peds[0].ASESOR;
					$scope.error="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;
                                        	//alert("El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR);
						//$scope.popup='done';
					//}
                                }

				$scope.baby($scope.pedido1);
                //console.log("este es el municipio" + $scope.peds[0].MUNICIPIO_ID);
                /*$scope.MUNICIPIO = $scope.peds[0].MUNICIPIO_ID;
                buscar = /ANTCOL/;
                $scope.validaMunicipio = buscar.test($scope.MUNICIPIO);*/
                //console.log("esta es la validacion " + $scope.validaMunicipio);
                //$rootScope.pagina_servicio_vecinos = $scope.peds[0].PAGINA_SERVICIO;
                //console.log("esto es lo que retorna" + $scope.validaMunicipio + " y la pagina " + $scope.peds[0].PAGINA_SERVICIO);
			}
	                var demePedidoButton=document.getElementById("iniciar");
	                demePedidoButton.removeAttribute("disabled");
			demePedidoButton.className = "btn btn-success";
			return data.data;
    		});
		$scope.timeInit=new Date().getTime();
		var date1 = new Date();
		var year    = date1.getFullYear();
		var month   = $scope.doubleDigit(date1.getMonth()+1);
		var day     = $scope.doubleDigit(date1.getDate());
		var hour    = $scope.doubleDigit(date1.getHours());
		var minute  = $scope.doubleDigit(date1.getMinutes());
		var seconds = $scope.doubleDigit(date1.getSeconds());

		$scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
    	};


	$scope.actualizarLightKPIS();
	
	$scope.doubleDigit= function (num){
		
		if(num<0){
			num=0;
		}
		
	        if(num<=9){
        	    return "0"+num;
	        }
        	return num;
	    };

});


app.controller('listCtrl', function ($scope, services) {
    services.getCustomers().then(function(data){
        $scope.customers = data.data;
	console.log($scope.customers);
    });
});


app.controller('TabController', function ($scope) {

	$scope.tab = 1;

    	$scope.setTab = function(newTab){
      		$scope.tab = newTab;
    	};

    	$scope.isSet = function(tabNum){
      		return $scope.tab === tabNum;
    	};
});


app.controller('login', function ($scope,$route, $rootScope, $location, $routeParams,$cookies,$cookieStore,services) {


	if($cookieStore.get('logedUser')!=undefined){
		//hay alguien logeado
		var id_user=$cookieStore.get('logedUser').id;
		document.getElementById('logout').className="btn btn-md btn-danger";
                var divi=document.getElementById("logoutdiv");
		divi.style.visibility="visible";
		divi.style.position="relative";
		
		if($cookieStore.get('logedUser').GRUPO=='ASIGNACIONES'){
                        $location.path('/asignacion/');
                }else if($cookieStore.get('logedUser').GRUPO=='AGENDAMIENTO'){
                        $location.path('/agendamiento/reagendamiento');
                }else if($cookieStore.get('logedUser').GRUPO=='ACTIVACION'){
                        $location.path('/alarmas/');
                }else if($cookieStore.get('logedUser').GRUPO=='SUPER'){
                        $location.path('/dashboard/');
		}else if($cookieStore.get('logedUser').GRUPO=='B2B'){
                        $location.path('/b2b/');
                }else if($cookieStore.get('logedUser').GRUPO=='RECONFIGURACION'){
                        $location.path('/reconfiguracion/');
		}else{
                        $location.path('/general/'+id_user);
                }
		//$location.path('/asignacion/'+id_user);
	}

        $scope.doubleDigit = function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
        };

	$scope.myInterval = 5000;
  	$scope.noWrapSlides = false;
  	var slides = $scope.slides = [];
  	$scope.addSlide = function(counter) {
    		var newWidth = 640 + slides.length + 1;
    		slides.push({
      			image: './images/reglas/' + counter + '.jpg',
      			//text: ['More','Extra','Lots of','Surplus'][slides.length % 4] + ' ' +
        		//['Cats', 'Kittys', 'Felines', 'Cutes'][slides.length % 4]
    		});
  	};
  
	//for (var i=0; i<4; i++) {
    	$scope.addSlide('1');
        $scope.addSlide('2');
        $scope.addSlide('3');
        $scope.addSlide('4');
        $scope.addSlide('5');
        $scope.addSlide('6');
        $scope.addSlide('7');
        $scope.addSlide('8');
        $scope.addSlide('9');
        $scope.addSlide('10');
        $scope.addSlide('11');
        $scope.addSlide('12');
        $scope.addSlide('13');
    	$scope.addSlide('14');
  	//}



	document.getElementById('logout').className="btn btn-md btn-danger hide";
	var divi=document.getElementById("logoutdiv");
	divi.style.position="absolute";
	divi.style.visibility="hidden";
	
	$rootScope.actualView="";
	
      	$scope.login = function() {

		/*
		var response = grecaptcha.getResponse();

		if(response.length == 0){
		    //reCaptcha not verified
		    	alert("Por favor verificar captcha!");
			return;
		 }else{
			//it can continue normally...
		}	
		*/

        $location.path('/');
	var success = function (data) {
		//console.log("DATOS DE LOGIN: ");
		//console.log(data);

        $scope.loginexito=true;
		var id_user=data['id'];
		$rootScope.logedUser=data;
		$cookieStore.put('logedUser', data);

		document.getElementById('logout').className="btn btn-md btn-danger";
	 	var divi=document.getElementById("logoutdiv");
		divi.style.visibility="visible";
		divi.style.position="relative";
		//alert(data.GRUPO);
		
                if($cookieStore.get('logedUser').GRUPO=='ASIGNACIONES'){
                        $location.path('/asignacion/');
                }else if($cookieStore.get('logedUser').GRUPO=='AGENDAMIENTO'){
                        $location.path('/agendamiento/reagendamiento');
                }else if($cookieStore.get('logedUser').GRUPO=='ACTIVACION'){
                        $location.path('/alarmas/');
                }else if($cookieStore.get('logedUser').GRUPO=='SUPER'){
                        $location.path('/dashboard/');
                }else if($cookieStore.get('logedUser').GRUPO=='B2B'){
                        $location.path('/b2b/');
                }else if($cookieStore.get('logedUser').GRUPO=='RECONFIGURACION'){
                        $location.path('/reconfiguracion/');
                }else{
                        $location.path('/general/'+id_user);
                }

/*

		if(data.GRUPO=='ASIGNACIONES'||data.GRUPO=='INCONSISTENCIAS'){
			$location.path('/asignacion/'+id_user);
		}else if(data.GRUPO=='RECONFIGURACION'){
                        $location.path('/reconfiguracion/');
			$route.reload();
                }else if(data.GRUPO=='FACTIBILIDADES'){
                        $location.path('/gpon/');
                        $route.reload();
                } else {
			//$location.path('/asignacion/'+id_user);
			$location.path('/general/');
                        $route.reload();

		}

*/		

		/*
		if(data.GRUPO!='ASIGNACIONES'){
			
			$location.path('/general/');
			
		}else{
          		$location.path('/asignacion/'+id_user);
			$route.reload();
			//$window.location.href='/asignacion/'+id_user;
		}*/

      	};


	var error = function () {
          // TODO: apply user notification here..
          $scope.error="Usuario o contraseña invalido..";
      	};



	$rootScope.logout = function() {
	        services.logout($rootScope.logedUser.login);
	        $cookieStore.remove('logedUser');
            $rootScope.logedUser=undefined;
	        $scope.pedidos={};
	        document.getElementById('logout').className="btn btn-md btn-danger hide";
	        var divi=document.getElementById("logoutdiv");
	        divi.style.position="absolute";
	        divi.style.visibility="hidden";
        	$location.path('/');
	};

               var tiempo=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                tiempo=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
	
	services.login($scope.lform.username,$scope.lform.password,tiempo).success(success).error(error);
      };
});


app.controller('editCtrl', function ($scope, $rootScope, $location, $routeParams, services, customer) {
    var customerID = ($routeParams.customerID) ? parseInt($routeParams.customerID) : 0;
    $rootScope.title = (customerID > 0) ? 'Edit Customer' : 'Add Customer';
    $scope.buttonText = (customerID > 0) ? 'Update Customer' : 'Add New Customer';
      var original = customer.data;
      original._id = customerID;
      $scope.customer = angular.copy(original);
      $scope.customer._id = customerID;

      $scope.isClean = function() {
        return angular.equals(original, $scope.customer);
      }

      $scope.deleteCustomer = function(customer) {
        $location.path('/customers');
        if(confirm("Are you sure to delete customer number: "+$scope.customer._id)==true)
        services.deleteCustomer(customer.customerNumber);
      };

      $scope.saveCustomer = function(customer) {
        $location.path('/customers');
        if (customerID <= 0) {
            services.insertCustomer(customer);
        }
        else {
            services.updateCustomer(customerID, customer);
service.PROGRAMADO        }
    };
});


app.controller('SchedulingCtrl',function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {
	
	var userID=$cookieStore.get('logedUser').login;
	$rootScope.logedUser=$cookieStore.get('logedUser');
	document.getElementById('logout').className="btn btn-md btn-danger";
    var divi=document.getElementById("logoutdiv");
    divi.style.visibility="visible";
    divi.style.position="relative";
	
    $scope.data = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0}
    $scope.listaScheduling=[];

    $rootScope.actualView="Alarmados";

    $rootScope.logout = function() {
        services.logout($rootScope.logedUser.login);
        $cookieStore.remove('logedUser');
        $rootScope.logedUser=undefined;
        $scope.pedidos={};
        document.getElementById('logout').className="btn btn-md btn-danger hide";
        var divi=document.getElementById("logoutdiv");
        divi.style.position="absolute";
        divi.style.visibility="hidden";
        $location.path('/');
     }; 


    if(!angular.isDefined($scope.currentPage)){
            $scope.currentPage = 1;
    };

    services.getScheduling($scope.data.currentPage).then(function(data){
        $scope.listaScheduling = data.data[0];
        $scope.totalScheduling = data.data[1];
        $scope.totalSchedulingPre = data.data[2];
        $scope.totalSchedulingPedidos = data.data[3];
        $scope.data.totalItems= data.data[1];
        return data.data;
});

    $scope.setPage = function (pageNo) {
            $scope.data.currentPage = pageNo;
    };

    $scope.pageChanged = function(forma) {
        if(forma=="listaRegistros"){
            $scope.calcularListado();
        }
    };

    $scope.calcularListado  = function (){
        $scope.listaScheduling=[];

            services.getScheduling($scope.data.currentPage).then(function(data){
                $scope.listaScheduling = data.data[0];
                $scope.totalScheduling = data.data[1];
	        $scope.totalSchedulingPre = data.data[2];
        	$scope.totalSchedulingPedidos = data.data[3];
                $scope.data.totalItems= data.data[1];
                       return data.data;
               });

        };

    $scope.csvScheduling = function (){
    var login=$rootScope.logedUser.login;
    services.getCsvScheduling(login).then(function(data){
            window.location.href="tmp/"+data.data[0];
            return data.data;
    });

    };

    $scope.csvSchedulingPre = function (){
    var login=$rootScope.logedUser.login;
    services.getCsvSchedulingPre(login).then(function(data){
            window.location.href="tmp/"+data.data[0];
            return data.data;
    });

    };

    $scope.csvSchedulingPedidos = function (){
    var login=$rootScope.logedUser.login;
    services.getCsvSchedulingPedidos(login).then(function(data){
            window.location.href="tmp/"+data.data[0];
            return data.data;
    });

    };
                 
});


app.controller('OcupacionAgendamientoCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {

        var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";
		$scope.departamentos={};
        $scope.zonas={};
        $scope.depa='';
        $scope.zona='';


        $scope.doubleDigit = function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
        };
     $scope.parseInt =  function (numbero){
	
	var num = parseInt(numbero) || 0;
        return num;
    };

 $scope.$watch("getOcupacionAgendamiento()", function(newValue, oldValue) {
    if (newValue === oldValue) {
      return;
    }
 
    alert("0");
  });

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

        var date1 = new Date();
	   date1.setDate(date1.getDate() + 1);
        var year  = date1.getFullYear();
        var month = $scope.doubleDigit(date1.getMonth()+1);
        var day   = $scope.doubleDigit(date1.getDate());

        var fecha_inicio=year+"-"+month+"-"+day;

        $scope.fechaAgendamiento=fecha_inicio;
        
        $scope.listado_cuposxagendas=[];

        $rootScope.actualView="ocupacion-agendamiento";



        services.getOcupacion(fecha_inicio, $scope.depa, $scope.zona).then(function(data){
            //console.log('Este es el objeto cupos por agenda' + $scope.listado_cuposxagendas);
            $scope.listado_cuposxagendas=data.data[0];
            //console.log($scope.listado_cuposxagendas);
            $scope.listado_cuposxagendas.MIGRACION = 0;
            $scope.listado_cuposxagendas.VIP = 0; 
            $scope.listado_cuposxagendas.BRONZE = 0; 
            $scope.listado_cuposxagendas.GPON = 0;  
            $scope.listado_cuposxagendas.tPARAM_AM = 0; 
            $scope.listado_cuposxagendas.tDISP_AM = 0;
            $scope.listado_cuposxagendas.tPARAM_PM=0;
            $scope.listado_cuposxagendas.tPARAM_PM=0;
            $scope.listado_cuposxagendas.tDISP_PM = 0; 
            $scope.listado_cuposxagendas.tPARAM_HF = 0; 
            $scope.listado_cuposxagendas.tDISP_HF = 0; 
            $scope.listado_cuposxagendas.tTOTAL_DISP= 0;            
            $scope.listado_cuposxagendas.totales = 0;
            $scope.refresh='';
                return data.data;
        });


        $scope.getOcupacionfecha  = function (fecha, depa, zona){
                depa='';
                zona='';
            console.log('departamento fecha: ' + depa + ' y la zona fecha: ' + zona );
        services.getOcupacion(fecha, depa, zona).then(function(data){
                    $scope.listado_cuposxagendas=data.data[0];
                    $scope.departamento='';
                    $scope.zona='';
                    return data.data;
            });
        };


        $scope.getOcupacion  = function (fecha, depa, zona){
            console.log('departamento: ' + depa + ' y la zona: ' + zona );
        services.getOcupacion(fecha, depa, zona).then(function(data){
                    $scope.listado_cuposxagendas=data.data[0];
                    return data.data;
            });
        };


       $scope.csvDatosAgendamiento = function (fecha, depa, zona){
                var login=$rootScope.logedUser.login;
                console.log('departamento: ' + depa + ' y la zona: ' + zona );
                services.getcsvDatosAgendamiento(fecha,login, depa, zona).then(function(data){
                        console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };  
                 
                      
            //PARA LLEVAR UN LOG DE QUIENES REFERENCIAN EL INDICADOR...
        
        services.logVista($cookieStore.get('logedUser').login,"Ocupacion");


    $scope.getDepartamentos = function() {
        $scope.depa='';
        services.getDepartamentosOcupaagenda().then(function(data){
            $scope.departamentos=data.data[0];
            //console.log($scope.departamentos);

            return data.data;
                });
    };

    $scope.getDepartamentos();

    $scope.getZonas = function(depa){
        $scope.zona='';
        //console.log('Depa es' + depa);
        services.getZonasOcupaagenda(depa).then(function(data){
        $scope.zonas=data.data[0];
        console.log($scope.zonas);
        return data.data;
    });
    };

});

app.controller('cargar_datosCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services, fileUpload){

        var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";
        $rootScope.iconcepto="TODO";
    $rootScope.actualView="usuarios";

    //console.log ($rootScope.logedUser)
    $scope.usert={};
    $scope.usert.EQUIPO_ID="MANUAL";
    $scope.usert.ID="";



    $scope.doubleDigit = function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
        };

            $scope.uploadFile = function(){
                $scope.user=$rootScope.logedUser.login;

               var file = $scope.myFile;
               console.log('file is');
               console.dir(file);


               var uploadUrl = 'services/cargar_datos';
              // console.log ($scope.user);
               fileUpload.uploadFileToUrl(file, uploadUrl, $scope.user);

            };
    });

app.controller('Pedidos_MicrozonasCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {

        var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";
        $scope.depa='';
        $scope.zona='';
        
        $scope.doubleDigit = function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
        };
     $scope.parseInt =  function (numbero){
    
    var num = parseInt(numbero) || 0;
        return num;
    };
 $scope.$watch("getPedidos_Microzonas()", function(newValue, oldValue) {
    if (newValue === oldValue) {
      return;
    }
 
    alert("0");
  });



        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

        var date1 = new Date();
    date1.setDate(date1.getDate() + 1);
        var year  = date1.getFullYear();
        var month = $scope.doubleDigit(date1.getMonth()+1);
        var day   = $scope.doubleDigit(date1.getDate());

        var fecha_inicio=year+"-"+month+"-"+day;

        $scope.fechaAgendamiento=fecha_inicio;
    
        $scope.listado_cuposxagendas=[];

        $rootScope.actualView="ocupacion-agendamiento";

        services.getOcupacion(fecha_inicio, $scope.depa, $scope.zona).then(function(data){
            $scope.listado_cuposxagendas=data.data[0];
            $scope.listado_cuposxagendas.tDISP_AM = 0;
            $scope.listado_cuposxagendas.tDISP_PM = 0; 
            $scope.listado_cuposxagendas.tDISP_HF = 0; 
            $scope.listado_cuposxagendas.tTOTAL_DISP= 0;            
            $scope.listado_cuposxagendas.totales = 0;
            $scope.refresh='';



                return data.data;
        });


        $scope.getOcupacion  = function (fecha){

        services.getOcupacion(fecha).then(function(data){
                    $scope.listado_cuposxagendas=data.data[0];
                    return data.data;
            });
        };

       $scope.csvPedidosMicrozonas = function (fecha){
                var login=$rootScope.logedUser.login;
                services.getcsvPedidosMicrozonas(fecha,login).then(function(data){
                        console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };
      
       
            
                      
            //PARA LLEVAR UN LOG DE QUIENES REFERENCIAN EL INDICADOR...
        
        services.logVista($cookieStore.get('logedUser').login,"Ocupacion");

    


});


app.controller('ParametrizacionSiebel', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore,$compile,$timeout,services,uiCalendarConfig,fileUpload2) {
        $scope.tab = 2;
        $scope.actual = "";
        $scope.alertOnEventClick = "false";
        var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";
        $scope.AM = "";
        $scope.PM = "";
        $scope.events = [];
        $scope.departamentos={};
        $scope.zonas={};

        $scope.doubleDigit = function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
        };
     $scope.parseInt =  function (numbero){
    
    var num = parseInt(numbero) || 0;
        return num;
    };
 
        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };
    var date = new Date();
    var d = date.getUTCDate();
    var m = date.getUTCMonth();
    var y = date.getUTCFullYear();
    
  /*  $scope.changeTo = 'Hungarian';
    /* event source that pulls from google.com */
   /*  $scope.eventSource = {
            url: "http://www.google.com/calendar/feeds/usa__en%40holiday.calendar.google.com/public/basic",
            className: 'gcal-event',           // an option!
            currentTimezone: 'America/Chicago' // an option!
    };
    /* event source that contains custom events on the scope */
     /*  $scope.events = [
                          //{title: $scope.AM,start: $scope.actual,end: $scope.actual},
                       //  {title: 'prueba15615616a1sdad',start: $scope.actual,end: $scope.actual},
                       //  {id: 999,title: 'Repeating Event',start: new Date(y, m, d - 3, 16, 0),allDay: false},
                      // {id: 999,title: 'Repeating Event',start: new Date(y, m, d + 4, 16, 0),allDay: false},
                      //    {title: 'Birthday Party',start: new Date(y, m, d + 1, 19, 0),end: new Date(y, m, d + 1, 22, 30),allDay: false},
                      //   {title: 'Click for Google',start: new Date(y, m, 28),end: new Date(y, m, 29),url: 'http://google.com/'}
                        ];

    /* event source that calls a function on every view switch */
    $scope.eventsF = function (start, end, timezone, callback) {
      var s = new Date(start).getTime() / 1000;
      var e = new Date(end).getTime() / 1000;
      var m = new Date(start).getMonth();
      var events = [{title: 'Feed Me ' + m,start: s + (50000),end: s + (100000),allDay: false, className: ['customFeed']}];
      callback(events);
    };

    /* $scope.calEventsExt = {
       color: '#f00',
      textColor: 'yellow',
       events: [ 
         {type:'party',title: 'Lunch',start: new Date(y, m, d, 12, 0),end: new Date(y, m, d, 14, 0),allDay: false},
        {type:'party',title: 'Lunch 2',start: new Date(y, m, d, 12, 0),end: new Date(y, m, d, 14, 0),allDay: false},
       {type:'party',title: 'Click for Google',start: new Date(y, m, 28),end: new Date(y, m, 29),url: 'http://google.com/'}
      ]
    };*/

    $scope.getDepartamentos = function() {
        $scope.departamentos1=[];
        services.getDepartamentosParametrizacionSiebel().then(function(data){
            $scope.departamentos1=data.data;
            //console.log($scope.departamentos);
            return data.data;
                });
    };

    $scope.getDepartamentos();

    $scope.getZonas = function(depa) {
                //console.log(depa);
                $scope.zonas=[];
               services.getZonasParametrizacionSiebel(depa).then(function(data){
                        $scope.zonas=data.data;
                        //console.log($scope.zonas);
                       //$scope.events=[];
                        return data.data;
                }); 
        };


   $scope.calcularPara = function (depa, zona){
        $scope.listado_parametrizados=[];
        $scope.departamentos = depa;
        $scope.zona1 = zona;
        console.log("esta es la lista" + depa + "-" + zona);
        services.getListadoParametrizados(depa, zona).then(function(data){
        $scope.listado_parametrizados=data.data;
		//console.log("esta es la lista" + $scope.events);
		//console.log($scope.events);
		var len=$scope.listado_parametrizados.length;
		for(var i=0;i<len;i++){
			//console.log($scope.listado_parametrizados[i]);
			var obj=$scope.listado_parametrizados[i];
			//var total=" { title:'AM: "+obj.AM+" ' , start: '"+obj.FECHA_LARGA+"' , end: '"+obj.FECHA_LARGA+"', allDay: allDay}  ";
            $scope.lastUpdate = new Date(obj.FECHA);
                        //var fecha = new Date();
                        var year    = $scope.lastUpdate.getUTCFullYear();
                        var month   = $scope.doubleDigit($scope.lastUpdate.getUTCMonth()+1);
                        var day     = $scope.doubleDigit($scope.lastUpdate.getUTCDate());
                        $scope.lastUpdate=year+"/"+month+"/"+day;
           
           console.log('date: '+$scope.lastUpdate);
			//
			//
			//var total2={title: 'AM: '+obj.AM, start:obj.FECHA_LARGA+'T01:32:21.196Z', end:obj.FECHA+'T01:32:21.196Z', allDay: allDay};
            //var total2={title: 'AM: '+obj.AM, start:obj.FECHA_LARGA, end:obj.FECHA_LARGA, allDay:true};

        
			//$scope.events.push(total2);

			$scope.events.push({
                         title:'AM: '+ obj.AM
			             ,start: new Date($scope.lastUpdate)
			             ,end: new Date($scope.lastUpdate)
                         //,allDay: allDay
                         });
            $scope.events.push({
                         title:'PM: '+ obj.PM
                        ,start: new Date($scope.lastUpdate)
                        ,end: new Date($scope.lastUpdate)
                        //,allDay: allDay
                         });

			//console.log(total);

		}

		//console.log($scope.events); 
                //$scope.events.push({
                        // title: 'esto es lo de adentro',start:$scope.listado_parametrizados.fecha2 ,end: $scope.listado_parametrizados.fecha2
                        //});
                    /*if($scope.listado_parametrizados === undefined){
                        $scope.listado_parametrizados = [];
                         } else {
                          var n = $scope.listado_parametrizados.length;
                            }
                 console.log("esta es la lista: " + $scope.listado_parametrizados);
*/
                /*for (var i = 0; i < n; i++) {
                        $scope.lista = $scope.listado_parametrizados[i];
                        console.log("esta es la fecha: " + $scope.lista.FECHA_LARGA);
                        console.log("esta es la am: " + $scope.lista.AM);
                        console.log("esta es la pm: " + $scope.lista.PM);
                         console.log($scope.listado_parametrizados);
                          var ficha = 'start:' + $scope.lista.FECHA_LARGA;
                           var focha = 'end:' + $scope.lista.FECHA_LARGA;
                        $scope.events.push({
                         title:'AM: ' + $scope.lista.AM,ficha
                         });
                        $scope.events.push({
                         title:'PM: ' + $scope.lista.PM,focha//start:$scope.lista.FECHA_LARGA ,end: $scope.lista.FECHA_LARGA''
                         });

                };*/
                //$pruebacambio();
        });
            
    };
                    $scope.setTab = function(newTab){
                        $scope.tab = newTab;
                        if($scope.tab == 2){
                            $scope.lastUpdate = new Date();
                            var year    = $scope.lastUpdate.getUTCFullYear();
                            var month   = $scope.doubleDigit($scope.lastUpdate.getUTCMonth()+1);
                            var day     = $scope.doubleDigit($scope.lastUpdate.getUTCDate());
                            $scope.lastUpdate=year+"/"+month+"/"+day;
                        }
                    };
                    $scope.isSet = function(tabNum){
                        return $scope.tab === tabNum;
                    };


    /* alert on eventClick */
    $scope.alertOnEventClick = function( date, jsEvent, view){
        $scope.alertMessage = (date.title + ' was clicked ');
    };
    /* alert on Drop */
     $scope.alertOnDrop = function(event, delta, revertFunc, jsEvent, ui, view){
                //console.log('esta funcionando ');
       $scope.alertMessage = ('Event Droped to make dayDelta ' + delta);
    };
    /* alert on Resize */
    $scope.alertOnResize = function(event, delta, revertFunc, jsEvent, ui, view ){
       $scope.alertMessage = ('Event Resized to make dayDelta ' + delta);
    };
    /* add and removes an event source of choice */
    $scope.addRemoveEventSource = function(sources,source) {
      var canAdd = 0;
      angular.forEach(sources,function(value, key){
        if(sources[key] === source){
          sources.splice(key,1);
          canAdd = 1;
        }
      });
      if(canAdd === 0){
        sources.push(source);
      }
    };
    /* add custom event*/

$rootScope.guardaPara=function(depa, zona, AM, PM) {
            //console.log(depa, zona, AM, PM, $scope.lastUpdate);
            services.insertarDatoParametrizacion(depa, zona, AM, PM, $scope.lastUpdate).then(function(data){
                //console.log(data.data);
                //$scope.events=[];
                $scope.remove();
                $scope.calcularPara(depa, zona);
                //return data.data;
            });
        };

       $rootScope.guardaPara2=function(depa, zona, fechaini, fechafin, AM, PM) {
            //console.log("esto es lo que le mande= " + depa, zona, fechaini, fechafin, AM, PM);
            if(fechaini==fechafin){
                alert('Para editar o ingresar un solo día favor hacerlo por el INGRESO POR DÍA.');               
            }else{
            services.insertarDatoParametrizacion2(depa, zona, fechaini, fechafin, AM, PM).then(function(data){
                //console.log(depa, zona);
            //console.log("esto devuelve el API" + data.data);
              $scope.remove();
              $scope.calcularPara(depa, zona);
                //return data.data;
            });
            }
        };


        $pruebacambio = function(view, element){
            //console.log("desde la vista: " + $scope.DEPA + $scope.ZONA);
                $scope.calcularPara($scope.departamentos, $scope.zona1);

        };


        $scope.uploadFile = function(depa, zona){
               var file = $scope.myFile;
               $scope.name = '';
               console.log('file is ');
               console.dir(file);
            var uploadUrl = 'services/cargar_datosparame';
              fileUpload2.uploadFileToUrl(file, uploadUrl);
               if(file == undefined){
                alert('Recuerde seleccionar el archivo a cargar');
               }else{
                alert('Archivo  ' + file.name + '  cargado correctamente');
                }   
            $scope.remove();
            $scope.calcularPara(depa, zona);
            $scope.getDepartamentos();
            };

$scope.csvParametrizacionSiebel = function (){
                var login=$rootScope.logedUser.login;
                services.csvParametrizacionSiebel(login).then(function(data){
            //console.log("este es el boton" + data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });

};
$scope.csvParametrizacionMicrozona = function (depa, zona){
                //console.log("este es el boton" + zona + depa);
                var login=$rootScope.logedUser.login;
                if(depa == undefined || zona == undefined){
                    alert('Por favor ingresar Departamento y Zona a exportar.');
                  }else{
                       services.csvParametrizacionMicrozona(login,depa,zona).then(function(data){
                        if(data.data[0] == "Null"){
                            zona === undefined;
                            alert('No hay datos para la microzona ' + zona);
                            zona === undefined;
                        }   else { 
                                window.location.href="tmp/"+data.data[0];
                                return data.data;
                            }
                   });
                }
};
///***************************************Esta es la parte que carga en el calendario cuando se ingresan datos en cada jornada
   //$scope.guardaPara = function(AM, PM) {
        //console.log('-'+AM+PM);
     // $scope.events.push({
       // title: 'AM:'+ AM,
        //start: $scope.actual,
        //end: $scope.actual
      //});
        //$scope.events.push({
        //title: 'PM:'+ PM,
        //start: $scope.actual,
        //end: $scope.actual   
      //});
    //};

 ///**************************************Esta es la parte que carga en el calendario cuando se ingresan datos en cada jornada   
    /* remove event */
    $scope.remove = function(index) {
    var len=$scope.events.length;
    //console.log("entre al remove: " + len + "events es = " + $scope.events);
    $scope.events.splice(index,len);
    };
    /* Change View */
    $scope.changeView = function(view,calendar) {
      uiCalendarConfig.calendars[calendar].fullCalendar('changeView',view);
    };

    /* Change View */
    $scope.renderCalender = function(calendar) {

      if(uiCalendarConfig.calendars[calendar]){
        uiCalendarConfig.calendars[calendar].fullCalendar('render');
      }
    };

    $scope.alertOnEventClick = function( date, jsEvent, view){
                $scope.lastUpdate = new Date(date);
                        //var fecha = new Date();
                        var year    = $scope.lastUpdate.getUTCFullYear();
                        var month   = $scope.doubleDigit($scope.lastUpdate.getUTCMonth()+1);
                        var day     = $scope.doubleDigit($scope.lastUpdate.getUTCDate());
                        $scope.lastUpdate=year+"/"+month+"/"+day;
	       //console.log('date: esta es la fecha '+$scope.actual);
        //$scope.alertMessage = (date.title + ' was clicked ');
                /*var x = document.getElementById('myDIV');
                    if (x.style.display === 'none') {
                        x.style.display = 'block';
                    } */
                    var len=$scope.events.length;

                    for(var i=0;i<len;i++){
                        var obj = $scope.events[i];
                        var year    = obj.start.getUTCFullYear();
                        var month   = $scope.doubleDigit(obj.start.getUTCMonth()+1);
                        var day     = $scope.doubleDigit(obj.start.getUTCDate());
                        $scope.actual=year+"/"+month+"/"+day;
                        var obj2 = $scope.events[i+1];
                         if($scope.lastUpdate == $scope.actual) {
                            //console.log("encontre la fecha " + obj.title + "-" + obj2.title)
                                i=len;
                                //var str = "PM: 3";
                                //var res = obj.title.slice(4,5);
                                if ($scope.tab === 1){
                                $scope.AM1 = obj.title.slice(4,8);
                                $scope.PM1 = obj2.title.slice(4,8);
                                console.log("encontre la fecha " + $scope.AM + "-" + $scope.PM)
                            }else{
                                $scope.AM = obj.title.slice(4,8);
                                $scope.PM = obj2.title.slice(4,8);
                                console.log("encontre la fecha 1" + $scope.AM1 + "-" + $scope.PM1)
                            }
                                //$scope.PM = obj.PM;      
                            }
                            else{
                                $scope.AM = "";
                                $scope.PM = "";
                                $scope.AM1 = "";
                                $scope.PM1 = "";
                            }
                    //var total=" { title:'AM: "+obj.AM+" ' , start: '"+obj.FECHA_LARGA+"' , end: '"+obj.FECHA_LARGA+"', allDay: allDay}  ";
                    //console.log("la fecha es " + $scope.lastUpdate + "la otra es " + $scope.actual)
                    }
                    //console.log("encontre la fecha 1 " + date)
                    //console.log("la fecha es " + new Date($scope.lastUpdate) + "la otra es " + obj.start)
                    //console.log($scope.events);
                   // console.log($scope.events)
                //document.getElementById("AM").value = $scope.AM;
                //document.getElementById("PM").value = $scope.PM;
                //$scope.AM = "";
                //$scope.PM = "";
    };

     /* Render Tooltip */
    $scope.eventRender = function( event, element, view ) { 
        //alert("prueba alart");
        element.attr({'tooltip': event.title,
                     'tooltip-append-to-body': true});
        $compile(element)($scope);
    };
    /* config object */
    $scope.uiConfig = {
      calendar:{
        height: 450,
        width: 500,
        editable: true,
        selectable: true,
	    //stick: true,
        header:{
          left: 'today',
          center: 'title',
          right: 'prev,next',
        },
	    dayClick : $scope.alertOnEventClick,
        eventClick: $scope.alertOnEventClick,
        eventDrop: $scope.alertOnDrop,
        eventResize: $scope.alertOnResize,
        eventRender: $scope.eventRender, 
        viewRender: $pruebacambio
      }
    };

     /*$scope.changeLang = function() {
      if($scope.changeTo === 'Hungarian'){
        $scope.uiConfig.calendar.dayNames = ["Vasárnap", "Hétfő", "Kedd", "Szerda", "Csütörtök", "Péntek", "Szombat"];
        $scope.uiConfig.calendar.dayNamesShort = ["Vas", "Hét", "Kedd", "Sze", "Csüt", "Pén", "Szo"];
        $scope.changeTo= 'English';
      } else {
        $scope.uiConfig.calendar.dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        $scope.uiConfig.calendar.dayNamesShort = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
        $scope.changeTo = 'Hungarian';
      }
    };
    /* event sources array*/
    $scope.eventSources = [$scope.events, $scope.eventSource, $scope.eventsF];
    $scope.eventSources2 = [$scope.calEventsExt, $scope.eventsF, $scope.events];

            //PARA LLEVAR UN LOG DE QUIENES REFERENCIAN EL INDICADOR...
        
        services.logVista($cookieStore.get('logedUser').login,"ParametrizacionSiebel");

});







app.controller('RegistrosAgendamientoCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services) {

        var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";

        $scope.doubleDigit = function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
                }
                return num;
        };

        $scope.data = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaIni:"", fechaFin: "" }

        /*if($routeParams.conceptoid == undefined){
                $scope.data1 = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaIni:"", fechaFin: "",concepto: "TODO" }
        }else{
                $scope.data1 = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaIni:"", fechaFin: "" }
        }*/

	$scope.data1 = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaIni:"", fechaFin: "" }

        if(!angular.isDefined($scope.currentPage)){
                $scope.currentPage = 1;
        }

        $scope.setPage = function (pageNo) {
                $scope.data.currentPage = pageNo;
        };

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };

        $scope.listado_pedidos=[];
        var date1 = new Date();
        var year  = date1.getFullYear();
        var month = $scope.doubleDigit(date1.getMonth()+1);
        var day   = $scope.doubleDigit(date1.getDate());

        var fecha_inicio=year+"-"+month+"-"+day;
        var fecha_fin=year+"-"+month+"-"+day;

        $scope.data.fechaIni=fecha_inicio;
        $scope.data1.fechaIni=fecha_inicio;

        $scope.data.fechaFin=fecha_fin;
        $scope.data1.fechaFin=fecha_fin;

        $rootScope.actualView="registros-agendamiento";

        services.getListadoPedidosAgendamiento(fecha_inicio,fecha_fin,$scope.data.currentPage).then(function(data){
                $scope.listado_pedidos=data.data[0];
                $scope.data.totalItems=data.data[1];
				$scope.data.campo,$scope.data.valorCampo
                return data.data;
        });
         

        $scope.calcularPendientes  = function (){
                $scope.listado_pendientes=[];
                var date1 = new Date();
                var year  = date1.getFullYear();
                var month = date1.getMonth()+1;
                var day   = date1.getDate();
                var fecha_inicio=year+"-"+month+"-"+day;
                var fecha_fin=year+"-"+month+"-"+day;

                services.getListadoPendientesAgendamiento(fecha_inicio,fecha_fin,$scope.data1.currentPage).then(function(data){
                        //console.log($scope.data.totalItems2);
                        $scope.listado_pendientes=data.data[0];
                        $scope.data1.totalItems=data.data[1];
                        $scope.data.totalItems2=data.data[2];
                        //$scope.data1.concepto=sconcept;
                        return data.data;
                });
        };

       

        $scope.calcularPendientes();

        $scope.calcularListado  = function (){

                services.getListadoPedidosAgendamiento($scope.data.fechaIni,$scope.data.fechaFin,$scope.data.currentPage).then(function(data){
                        $scope.listado_pedidos=data.data[0];
                        $scope.data.totalItems=data.data[1];
                        return data.data;
                });


        };

         
       
       

        $scope.calcularPendientes();

    
        $scope.cutString = function(str,howMuch) {
                if(str.length>howMuch){
                        return (str.slice(0,howMuch)+".. ");
                }else{
                        return str;
                }
        };

        $scope.pageChanged = function(forma) {
                if(forma=="listadoPedidos"){
                        $scope.calcularListado();
                }
                if(forma=="listadoPendientes"){
                        $scope.calcularPendientes();
                }
        };

        $scope.buscarPedidoRegistro  = function (bpedido){

                if(bpedido.length==0||bpedido==''){
                        $scope.calcularPendientes();
                }
                if(bpedido.length>=7){
                    services.getBuscarPedidoAgendamientoRegistro(bpedido).then(function(data){
                    console.log(data.data[0]);
                    $scope.listado_pendientes=data.data[0];
                    $scope.data.totalItems=data.data[1];
                        return data.data;
                    });
                }
        };


        $scope.buscarPedidoRegistro1  = function (bpedido){

                if(bpedido.length==0||bpedido==''){
                        $scope.calcularPendientes();
                }
                if(bpedido.length>=0){
                    services.getBuscarPedidoAgendamientoRegistro1(bpedido,$scope.data.currentPage).then(function(data){
                     $scope.listado_pedidos=data.data[0];
                     $scope.data.totalItems1=data.data[1];
                        return data.data;

                    });
                }
        };

        $scope.csvPendientesAgendamiento  = function (concep){
                var login=$rootScope.logedUser.login;
                services.getCsvPendientesAgendamiento(login).then(function(data){
                        console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };

         $scope.csvPendientesAgendamientoPredictiva  = function (concep){
                var login=$rootScope.logedUser.login;
                services.getCsvPendientesAgendamientoPredictiva(login).then(function(data){
                        console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };

        $scope.csvPendientesAgenSiete  = function (concep){
                var login=$rootScope.logedUser.login;
                services.getCsvPendientesAgenSiete(login).then(function(data){
                        console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };

        $scope.csvMalosAgendamiento  = function (){
                var login=$rootScope.logedUser.login;
                services.getCsvMalosAgendamiento(login).then(function(data){
                        console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });

        };
  
        $scope.csvHistoricos = function (){
                var login=$rootScope.logedUser.login;
                services.getCsvHistoricosAgendamiento(login,$scope.data.fechaIni,$scope.data.fechaFin).then(function(data){
                        console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });

        };


         $scope.csvHistoricosEdatel = function (){
                var login=$rootScope.logedUser.login;
                services.getCsvHistoricosAgendamientoEdatel(login,$scope.data.fechaIni,$scope.data.fechaFin).then(function(data){
                        console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });

        };

        $scope.csvAGENToday = function (){
                var login=$rootScope.logedUser.login;
                services.getCsvAGENToday().then(function(data){
                        console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });

        };

	

        $scope.datepickerOptions = {
            format: 'yyyy-mm-dd',
            language: 'es',
            autoclose: true,
            weekStart: 0
        }

        if($routeParams.conceptoid != undefined){
                $scope.calcularPendientes($routeParams.conceptoid);
        }

});




app.controller('AgendamientoAdelantarCtrl',function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services,$compile) {

        var userID=$cookieStore.get('logedUser').login;
        $rootScope.logedUser=$cookieStore.get('logedUser');
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";
        $scope.lastUpdate="";


        $scope.peds={};

        $scope.intervalLightKPIS='';

        $scope.pedidos=[];
        $rootScope.actualView="reagendamiento";
        $scope.popup='';
        $scope.pedido={};
        $scope.error="";
        $scope.historico_pedido=[];
        $scope.departamentos=[];
        $scope.zonas={};
        $scope.microzonas={};
        $scope.departamento="";


        $scope.ordenamientoDemepedido='';
        $scope.ordenamientoDemepedidoUpdate='';

        $scope.ordenamientoDemepedidoNuevo='';


        services.getDepartamentosAdelantarAgenda().then(function(data){
                        $scope.departamentos={};
                        $scope.departamentos=data.data;
                        console.log($scope.departamentos);

                        return data.data;
                });


	var pedidos=services.getPedidosUserAdelantarAgenda(userID).then(function(data){
                        $scope.pedidos=data.data;
                        return data.data;
                   });

    	$rootScope.logout = function() {
        	services.logout($rootScope.logedUser.login);
	        $cookieStore.remove('logedUser');
	        $rootScope.logedUser=undefined;
	        document.getElementById('logout').className="btn btn-md btn-danger hide";
	        var divi=document.getElementById("logoutdiv");
	        divi.style.position="absolute";
	        divi.style.visibility="hidden";
	        $location.path('/');
	};
      
       // $scope.getDepartamentos();

        $scope.getZonas = function(depa) {
                console.log(depa);
                $scope.zonas={};
                $scope.microzonas={};
               
               services.getZonasAdelantarAgenda(depa).then(function(data){
                        $scope.zonas=data.data;
                        console.log($scope.zonas);
                        return data.data;
                }); 
        };


        $scope.getMicrozonas = function(zona,depa){
                console.log(zona,depa);
                $scope.microzonas={};

                services.getMicrozonasAdelantarAgenda(zona,depa).then(function(data){
                        $scope.microzonas=data.data;
                        console.log($scope.microzonas);
                        return data.data;
                });
        };


        $scope.start = function(depa,zona,microzona,fecha) {
            $scope.refresh='cargando';
            console.log(depa, zona, microzona, fecha);
            $scope.pedido_actual=0;

            services.getPedidoActualmenteAgendado(depa,zona,microzona,fecha,$rootScope.logedUser.login, $scope.pedido_actual).then(function(data){

                    $scope.peds = data.data[0];
                    $scope.pedido1=data.data[1];

                    console.log(data.data);

                    if(data.data==''||data.data=='No hay registros!'){
                        document.getElementById("warning").innerHTML="No hay Registros";
                        $scope.error="No hay Registros";
                    }else{
                                document.getElementById("warning").innerHTML="";
                                $scope.pedido1=$scope.peds[0].PEDIDO_ID;
                                $scope.baby($scope.pedido1);

                                if($scope.peds[0].STATUS=="ADEN_AGEN"&&$scope.peds[0].ASESOR!=""){
                                        $scope.busy=$scope.peds[0].ASESOR;
                                        $scope.error="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;
                                }

                        }

                    $scope.baby($scope.pedido1);

                    $scope.refresh='';
                    $scope.peds[0].FECHA_INICIO=new Date().getTime();
                    console.log($scope.peds[0]);
                    return data.data;


            });

            $scope.timeInit=new Date().getTime();
            
            

       };


        $scope.doubleDigit= function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
               }
            return num;
        };


        $scope.baby = function(pedido) {
                services.getPedidosPorPedidoAgendamiento(pedido).then(function(data){
                      $scope.historico_pedido=data.data;
                      return data.data;
                 });
        };


        $scope.buscarPedidoAgendamiento = function(bpedido) {
                $scope.error="";
                $scope.peds={};
                $scope.mpedido={};
                $scope.busy="";
                $scope.error="";
                var kami=services.buscarPedidoAgendamiento(bpedido,$scope.pedido1,$rootScope.logedUser.login,$rootScope.logedUser.name).then(function(data){
                        $scope.peds = data.data;
                        console.log($scope.peds);
                        var dat=data.status;

                        if(dat==204){
                                document.getElementById("warning").innerHTML="No hay Registros";
                                $scope.error="No hay Registros";
                                $scope.historico_pedido={};
                        }else{
                                document.getElementById("warning").innerHTML="";
                                $scope.pedido1=$scope.peds[0].PEDIDO_ID;
                                $scope.baby($scope.pedido1);

                                if($scope.peds[0].STATUS=="PENDI_AGEN"&&$scope.peds[0].ASESOR!=""){
                                        $scope.busy=$scope.peds[0].ASESOR;
                                        $scope.error="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;
                                }

                        }


                        var demePedidoButton=document.getElementById("iniciar");
                        demePedidoButton.removeAttribute("disabled");
                        demePedidoButton.className = "btn btn-success";
                        return data.data;
                });
                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        };





        $rootScope.abreZona = function(departamento){
            var n = $rootScope.lightkpi.length;

            var zonasTabla="<table class='table small table-striped table-hover table-bordered table-condensed'>";
            zonasTabla+= "<thead><th class='text-center'>Zona</th><th class='text-center'>Cantidad</th></thead><tbody>"

            for (var i = 0; i < n; i++) {
                if(departamento===$rootScope.lightkpi[i].DEPARTAMENTO){
                    zonasTabla+="<tr><td>"+$rootScope.lightkpi[i].SUBZONA_ID+"</td><td>"+$rootScope.lightkpi[i].COUNTER+"</td></tr>";
                }
            }
            zonasTabla+="</tbody></table>";

                $rootScope.selected_option = zonasTabla;
        };

    $scope.intervalLightKPIS = setInterval(function(){
                $scope.actualizarLightKPIS();
           },60000);

        $scope.actualizarLightKPIS = function (){
                services.getLightKPISAgendamiento().then(function(data){
                        $rootScope.oldlightkpi=$rootScope.lightkpi;
                        $rootScope.lightkpi=data.data[0];

                        if($rootScope.oldlightkpi==""||$rootScope.oldlightkpi==undefined){
                                $rootScope.oldlightkpi=$rootScope.lightkpi;
                        }

                        var arrayLength = $rootScope.lightkpi.length;
                        var arrayLength2 = $rootScope.oldlightkpi.length;

                        $rootScope.totalNegocioAgendamientoOld=$rootScope.totalNegocioAgendamiento;
                        $rootScope.totalNegocioAgendamiento=0;
            $rootScope.totalesDepartamento=[];
            var obj={};
            obj.COUNTER=0;
            obj.DEPARTAMENTO='';
            var deparProvisional='';
            var totalDepa=0;
            var nvec=0;

            $rootScope.totalesDepartamento[0]=obj;

                for (var i = 0; i < arrayLength; i++) {

                    var counter=$rootScope.lightkpi[i].COUNTER;
                    var depa=$rootScope.lightkpi[i].DEPARTAMENTO;

                    if(depa===deparProvisional){

                        obj.COUNTER=parseInt($rootScope.totalesDepartamento[nvec].COUNTER)+parseInt(counter);
                        $rootScope.totalesDepartamento[nvec]=obj;
                    }
                    else{
                        nvec++;
                        obj=$rootScope.totalesDepartamento[nvec];

                        if(obj===undefined){
                            obj={};
                        }

                        obj.DEPARTAMENTO=depa;
                        obj.COUNTER=counter;

                        $rootScope.totalesDepartamento[nvec]=obj;
                        deparProvisional=depa;
                    }

                        $rootScope.totalNegocioAgendamiento=parseInt($rootScope.totalNegocioAgendamiento)+parseInt(counter);
                }

                        //console.log($rootScope.totalesDepartamento);

                        $rootScope.nagendamientostyle={};

                        if($rootScope.totalNegocioAgendamiento>$rootScope.totalNegocioAgendamientoOld){
                                $rootScope.nagendamientostyle.ICON="fa fa-arrow-circle-up fa-2x";
                                $rootScope.nagendamientostyle.STYLE="red";
                        }else if($rootScope.totalNegocioAgendamiento<$rootScope.totalNegocioAgendamientoOld){
                                $rootScope.nagendamientostyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.nagendamientostyle.STYLE="green";
                        }else {
                                $rootScope.nagendamientostyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.nagendamientostyle.STYLE="gray";
                        }

                        return data.data;
                });
        };

$scope.actualizarLightKPIS();
        $scope.$on(
                "$destroy",
                        function( event ) {
                            $timeout.cancel(timer);
                            clearInterval($scope.intervalLightKPIS);
          });


$scope.savePedido = function(index) {

        var loader = document.getElementById("class"+index);
        loader.className='glyphicon glyphicon-refresh fa-spin';
    
        $scope.pedido={};

        console.log("Un objeto: "+angular.copy($scope.peds[index],$scope.pedido));

        if($scope.pedido==undefined||$scope.pedido==''||$scope.pedido.length==0){
                  console.log("estoy malo: "+$scope.pedido+"----"+JSON.stringify($scope.peds[index]));
        console.log(Object.prototype.toString.call($scope.peds[index])+" == "+Object.prototype.toString.call($scope.pedido));
        }else{
                  console.log("tengo datos: "+$scope.pedido);
        }



        if($scope.pedido.NOVEDAD===undefined){
            alert('Por favor diligenciar todos los campos.');
            return;
        }

        $scope.pedido.ASESOR=$rootScope.logedUser.login;
        $scope.pedido.ASESORNAME=$rootScope.logedUser.name;
        $scope.pedido.DURACION=new Date().getTime() - $scope.timeInit;
        //$scope.pedido.DEPARTAMENTO=$scope.departamento.DEPARTAMENT;

    $scope.pedido.PROGRAMACION=document.getElementById('programacion').value;

   if($scope.pedido.NOVEDAD!='AGENDADO'&&$scope.pedido.NOVEDAD!='YA ESTA AGENDADO'&&$scope.pedido.NOVEDAD!='AGENDADO MANUAL' &&$scope.pedido.NOVEDAD!='AGENDADO_FUTURO' &&$scope.pedido.NOVEDAD!='YA ESTA AGENDADO-USUARIO' && $scope.pedido.NOVEDAD!='ERROR SIEBEL 8.1'  ){
        $scope.pedido.FECHA_CITA_REAGENDA='';
        $scope.pedido.JORNADA_CITA='';
    }else{
        $scope.pedido.PROGRAMACION='';
    }


    if($scope.pedido.NOVEDAD!='AGENDADO'&&$scope.pedido.NOVEDAD!='YA ESTA AGENDADO' &&$scope.pedido.NOVEDAD!='AGENDADO_FUTURO' &&$scope.pedido.NOVEDAD!='YA ESTA AGENDADO-USUARIO'){
        if($scope.pedido.PROGRAMACION===undefined||$scope.pedido.PROGRAMACION==''){
            alert('Por favor incluir la informacion para nuevo contacto.');
            return;

        }
        //PARA HACER QUE HAYA UN REINTENTO DEL PEDIDO!!
        if($scope.pedido.NOVEDAD=='CLIENTE NO CONTACTADO'||$scope.pedido.NOVEDAD=='NO PUEDE ATENDER LLAMADA'){
            var date1 = new Date();

            date1.setHours(date1.getHours()+2);

                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.pedido.PROGRAMACION=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
        }
    
    }else{
        console.log($scope.pedido.FECHA_CITA_REAGENDA+"||||||"+$scope.pedido.JORNADA_CITA);
        if($scope.pedido.FECHA_CITA_REAGENDA==''|| $scope.pedido.FECHA_CITA_REAGENDA===undefined){
                        alert('Por favor incluir la fecha de la reagenda..');
                        return;
        }

                if($scope.pedido.JORNADA_CITA==''|| $scope.pedido.JORNADA_CITA===undefined){
                        alert('Por favor incluir la fecha de la reagenda..');
                        return;
                }

        if($scope.pedido.NOVEDAD=='YA ESTA AGENDADO'){
            $scope.pedido.PROGRAMACION=$scope.pedido.FECHA_CITA_REAGENDA+" 14:00:00";
        }


    }

        $scope.timeInit=new Date().getTime();
        var df=new Date($scope.pedido.DURACION);
        $scope.pedido.DURACION= 
        $scope.doubleDigit(df.getHours()-19)+":"+ $scope.doubleDigit(df.getMinutes())+":"+ $scope.doubleDigit(df.getSeconds());
        //$scope.pedido.pedido=$scope.peds[index].PEDIDO_ID;
        $scope.pedido1=$scope.peds[index].PEDIDO_ID;

        $scope.pedido.ACTIVIDAD_GESTOR="ADELANTAR_AGENDA";
        $scope.pedido.FUENTE=$scope.peds[index].FUENTE;
        $scope.pedido.FECHA_INICIO=$scope.fecha_inicio;
        $scope.pedido.TIEMPO_TOTAL=$scope.pedido.TIEMPO_TOTAL+" DIAS";

        var date1 = new Date();
        var year    = date1.getFullYear();
        var month   = $scope.doubleDigit(date1.getMonth()+1);
        var day     = $scope.doubleDigit(date1.getDate());
        var hour    = $scope.doubleDigit(date1.getHours());
        var minute  = $scope.doubleDigit(date1.getMinutes());
        var seconds = $scope.doubleDigit(date1.getSeconds());

        $scope.pedido.FECHA_FIN=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        var dat= new Date();

    //console.log($scope.pedido);
    $scope.pedido.fecha='';
    $scope.pedido.concepto_final='';
        services.insertPedidoAdelantarAgenda($scope.pedido).then(function (status) {
            $scope.pedido.fecha=status.data['data'];
            $scope.pedido.concepto_final=status.data['msg'];

            if($scope.pedido.concepto_final=="El pedido NO ha cambiado de concepto en Fenix!!!" || $scope.pedido.concepto_final=="ERROR!"){
                    alert($scope.pedido.concepto_final);


                    $scope.pedido.fecha="";
                    $scope.pedido.concepto_final="";
            }else{

                if($scope.pedido.concepto_final=="El pedido bloqueado por Usuario por mas de una hora, fue liberado por el sistema, usuario no podra gestionarlo hasta despues de una hora!!!"){
                $scope.error=$scope.pedido.concepto_final;

                    $scope.peds=[];
                    $scope.pedido={};
                    $scope.pedidos="";
            } else{
            console.log("EL PEDIDO QUEDO ASI: ");
            console.log($scope.pedido);
                    $scope.historico_pedido=$scope.historico_pedido.concat(angular.copy($scope.pedido));
                    $scope.peds.splice(index,1);
                     if($scope.pedidos==""){
                            $scope.pedidos=new Array();
                    }
                    $scope.pedidos=$scope.pedidos.concat($scope.pedido);
                    if($scope.historico_pedido==""){
                            $scope.historico_pedido=new Array();
                    }
                            $scope.pedido=[];
                            $scope.busy="";
                            $scope.timeInit=new Date().getTime();
                            date1 = new Date();
                            year    = date1.getFullYear();
                            month   = $scope.doubleDigit(date1.getMonth()+1);
                            day     = $scope.doubleDigit(date1.getDate());
                            hour    = $scope.doubleDigit(date1.getHours());
                            minute  = $scope.doubleDigit(date1.getMinutes());
                            seconds = $scope.doubleDigit(date1.getSeconds());

                            $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                            $scope.popup='';
                }

            }
            loader.className='';
            return status;
        });

    };//FIN SAVEPEDIDO
 

});

app.controller('AgendamientoCtrl',function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore,$timeout, services,$compile) {
    
    	var userID=$cookieStore.get('logedUser').login;
    	$rootScope.logedUser=$cookieStore.get('logedUser');
    	document.getElementById('logout').className="btn btn-md btn-danger";
    	var divi=document.getElementById("logoutdiv");
    	divi.style.visibility="visible";
    	divi.style.position="relative";
    	$scope.lastUpdate="";

   
    	$scope.peds={};

	$scope.intervalLightKPIS='';

        $scope.pedidos=[];
        $rootScope.actualView="reagendamiento";
        $scope.popup='';
        $scope.pedido={};
        $scope.error="";
        $scope.historico_pedido=[];
	$scope.departamentos=[];
	$scope.zonas={};
	$scope.microzonas={};
	$scope.proceso="TODO";
	$scope.departamento="";
    $scope.zona={};
    $scope.zona.SUBZONA_ID='';
    $scope.TODAY_TRIES="";

	$scope.ordenamientoDemepedido='';
	$scope.ordenamientoDemepedidoUpdate='';
	$scope.ordenamientoDemepedidoNuevo=''; 


	var pedidos=services.getPedidosUserReagendamiento(userID).then(function(data){
            	$scope.pedidos=data.data;
            	return data.data;
        });

 
    $rootScope.logout = function() {
        services.logout($rootScope.logedUser.login);
        $cookieStore.remove('logedUser');
        $rootScope.logedUser=undefined;
        document.getElementById('logout').className="btn btn-md btn-danger hide";
        var divi=document.getElementById("logoutdiv");
        divi.style.position="absolute";
        divi.style.visibility="hidden";
        $location.path('/');
     }; 

        $scope.csvAgendamiento  = function (){
                var login=$rootScope.logedUser.login;
                services.getCsvAgendamiento(login).then(function(data){
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };


//	$scope.calcularnovedad = function() {

		

//if($scope.proceso=='REPARACION'){
 //   $scope.novedades=[];

//}else($scope.proceso=='INSTALACION') 


//	};

        $scope.getDepartamentos = function() {

        $scope.departamentos={};
        $scope.microzonas={};
            services.getDepartamentosPendientesReagendamiento($scope.proceso).then(function(data){
            $scope.departamentos=data.data;
            //console.log($scope.proceso);

                        return data.data;
                });
    };

	//$scope.getDepartamentos();
   


        $scope.getZonas = function(depa) {
              //  console.log(depa);
		if(depa=="EDATEL"||depa.DEPARTAMENT=="EDATEL"){
			$scope.departamento={};
			$scope.departamento.DEPARTAMENT= "EDATEL";
			//console.log($scope.departamento.DEPARTAMENT);
			return;
		}

                $scope.zonas={};
		      $scope.microzonas={};
                services.getZonasReagendamiento(depa.DEPARTAMENT).then(function(data){
                        $scope.zonas=data.data;

                        return data.data;
                });
        };

       

         $scope.getZonasEdatel = function() {
                //console.log(depa);

                $scope.zonasedatel={};
              $scope.microzonas={};
                services.getZonasReagendamiento("EDATEL").then(function(data){
                        $scope.zonasedatel=data.data;

                        return data.data;
                });
        };

 $scope.getZonasEdatel();
	$scope.getMicrozonas = function(zona,depa){
		$scope.microzonas={};
		
                services.getMicrozonasReagendamiento(zona.SUBZONA_ID,depa.DEPARTAMENT).then(function(data){
                        $scope.microzonas=data.data;
			             console.log($scope.microzonas);
                        return data.data;
                });
		
	};




$scope.popoverboton= function (){
$('#popoverId').popover({
    html: true,
    title: "Observaciones",
    content: function () {
        return $('#popoverContent').html();
    }
});
var isVisible = false;
var clickedAway = false;

$('.popoverThis').popover({
    html: true,
    trigger: 'click'
})
$(document).click(  function (e) {
    if (isVisible & clickedAway) {
        $('.popoverThis').popover('hide')
        isVisible = clickedAway = false
    } else {
        clickedAway = true
    }
});
};


        $scope.doubleDigit= function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
               }
            return num;
        };

/* FUNCION PARA ACTUALIZAR LOS PARAMETROS DEL SISTEMA */
$scope.updateParametro = function (parametro,valor){

        services.updateParametro(parametro,valor,$rootScope.logedUser.login).then(function(data){
                if(parametro=="FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO"|| parametro=="FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO_REPA"|| parametro=="FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO_EDATEL"){
                        $scope.ordenamientoDemepedido=valor;
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.ordenamientoDemepedidoUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                        console.log($scope.ordenamientoDemepedido);
                }
                return data.data;
        });

	if(parametro=="FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO"){
		valor=$scope.prioridadDemepedidoNuevo;
		parametro='PRIORIDAD_DEMEPEDIDO_AGENDAMIENTO';
		services.updateParametro(parametro,valor,$rootScope.logedUser.login).then(function(data){
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.ordenamientoDemepedidoUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

		});
	}

        if(parametro=="FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO_REPA"){
                valor=$scope.prioridadDemepedidoNuevoR;
                parametro='PRIORIDAD_DEMEPEDIDO_AGENDAMIENTO_REPA';
                services.updateParametro(parametro,valor,$rootScope.logedUser.login).then(function(data){
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.ordenamientoDemepedidoUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                       //console.log($scope.prioridadDemepedidoNuevoR); 

                });
        }

       if(parametro=="FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO_EDATEL"){
                valor=$scope.prioridadDemepedidoNuevoED;
                parametro='PRIORIDAD_DEMEPEDIDO_AGENDAMIENTO_EDATEL';
                services.updateParametro(parametro,valor,$rootScope.logedUser.login).then(function(data){
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.ordenamientoDemepedidoUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                        //console.log($scope.prioridadDemepedidoNuevoED);
                });
        } 



};


$scope.buscarParametro = function (parametro){

        services.buscarParametro(parametro).then(function(data){
                return data.data;
        });

};


services.buscarParametro('FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO').then(function(data){

        $scope.ordenamientoDemepedido=data.data['VALOR'];
        $scope.ordenamientoDemepedidoNuevo=data.data['VALOR'];
        $scope.ordenamientoDemepedidoUpdate=data.data['ULTIMA_ACTUALIZACION'];
        return data.data;
});


services.buscarParametro('PRIORIDAD_DEMEPEDIDO_AGENDAMIENTO').then(function(data){

        $scope.prioridadDemepedidoNuevo=data.data['VALOR'];
        $scope.ordenamientoDemepedidoUpdate=data.data['ULTIMA_ACTUALIZACION'];
        return data.data;
});


services.buscarParametro('FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO_REPA').then(function(data){

        $scope.ordenamientoDemepedidoR=data.data['VALOR'];
        $scope.ordenamientoDemepedidoNuevoR=data.data['VALOR'];
        $scope.ordenamientoDemepedidoUpdate=data.data['ULTIMA_ACTUALIZACION'];
        //console.log($scope.ordenamientoDemepedidoUpdate);
        return data.data;
});


services.buscarParametro('PRIORIDAD_DEMEPEDIDO_AGENDAMIENTO_REPA').then(function(data){

        $scope.prioridadDemepedidoNuevoR=data.data['VALOR'];
        $scope.ordenamientoDemepedidoUpdate=data.data['ULTIMA_ACTUALIZACION'];
        return data.data;
});

services.buscarParametro('FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO_EDATEL').then(function(data){

        $scope.ordenamientoDemepedidoED=data.data['VALOR'];
        $scope.ordenamientoDemepedidoNuevoED=data.data['VALOR'];
        $scope.ordenamientoDemepedidoUpdate=data.data['ULTIMA_ACTUALIZACION'];
         //console.log($scope.ordenamientoDemepedidoUpdate);

        return data.data;
});


services.buscarParametro('PRIORIDAD_DEMEPEDIDO_AGENDAMIENTO_EDATEL').then(function(data){

        $scope.prioridadDemepedidoNuevoED=data.data['VALOR'];
        $scope.ordenamientoDemepedidoUpdate=data.data['ULTIMA_ACTUALIZACION'];
        //console.log($scope.prioridadDemepedidoNuevoED);
        return data.data;
});


        $scope.parseInt =  function (numbero){
        return parseInt(numbero);
        };

        $scope.parseFloat =  function (numbero){
                return parseFloat(numbero);
        };

        $scope.roundFloat =  function (numbero){
        var num=parseFloat(numbero).toFixed(2);
                return num;
        };

	$scope.baby = function(pedido) {
        	services.getPedidosPorPedidoAgendamiento(pedido).then(function(data){
                      $scope.historico_pedido=data.data;
			//console.log($scope.historico_pedido);
                      return data.data;
                 });
    	};

        $scope.buscarPedidoAgendamiento = function(bpedido) {
                $scope.error="";
                $scope.peds={};
                $scope.mpedido={};
                $scope.busy="";
                $scope.error="";
                $scope.TODAY_TRIES="";
                var kami=services.buscarPedidoAgendamiento(bpedido,$scope.pedido1,$rootScope.logedUser.login,$scope.proceso,$scope.TODAY_TRIES,$rootScope.logedUser.name).then(function(data){
                        $scope.peds = data.data;
                        
                        var dat=data.status;
                        
                        if(dat==204){
                                document.getElementById("warning").innerHTML="No hay Registros";
                                $scope.error="No hay Registros";
                                $scope.historico_pedido={};
                        }else{
                                document.getElementById("warning").innerHTML="";
                                $scope.pedido1=$scope.peds[0].PEDIDO_ID;
                                $scope.baby($scope.pedido1);



                                if($scope.peds[0].STATUS=="PENDI_AGEN"&&$scope.peds[0].ASESOR!=""){
                                        $scope.busy=$scope.peds[0].ASESOR;
                                        $scope.error="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;
                                }
                              $scope.proceso=$scope.peds[0].PROCESO;
                              $scope.TODAY_TRIES=$scope.peds[0].TODAY_TRIES;

                              


                                }
                        
            		
			

			var demePedidoButton=document.getElementById("iniciar");
                        demePedidoButton.removeAttribute("disabled");
                        demePedidoButton.className = "btn btn-success";
                        return data.data;
                });
                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;


        };


        $scope.buscarPedidoAgendamientoAuditoria = function(bpedido) {
                $scope.error="";
                $scope.peds={};
                $scope.mpedido={};
                $scope.busy="";
                $scope.error="";
                var kami=services.buscarPedidoAgendamiento(bpedido,$scope.pedido1,$rootScope.logedUser.logine,$scope.proceso,$rootScope.logedUser.name).then(function(data){
                        $scope.peds = data.data;
                    
                        var dat=data.status;

                        if(dat==204){
                                document.getElementById("warning").innerHTML="No hay Registros";
                                $scope.error="No hay Registros";
                        }else{
                                document.getElementById("warning").innerHTML="";
                                $scope.pedido1=$scope.peds[0].PEDIDO_ID;

                                if($scope.peds[0].STATUS=="PENDI_AGEN"&&$scope.peds[0].ASESOR!=""){
                                        $scope.busy=$scope.peds[0].ASESOR;
                                        $scope.error="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;
                                }

                        }
                        var demePedidoButton=document.getElementById("iniciar");
                        demePedidoButton.removeAttribute("disabled");
                        demePedidoButton.className = "btn btn-success";
                        return data.data;
                });


                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        };

        $rootScope.abreZona = function(departamento){
            var n = $rootScope.lightkpi.length;

            var zonasTabla="<table class='table small table-striped table-hover table-bordered table-condensed'>";
            zonasTabla+= "<thead><th class='text-center'>Zona</th><th class='text-center'>Cantidad</th></thead><tbody>"

            for (var i = 0; i < n; i++) {
                if(departamento===$rootScope.lightkpi[i].DEPARTAMENTO){
                    zonasTabla+="<tr><td>"+$rootScope.lightkpi[i].SUBZONA_ID+"</td><td>"+$rootScope.lightkpi[i].COUNTER+"</td></tr>";
                }
            }
            zonasTabla+="</tbody></table>";

		$rootScope.selected_option = zonasTabla;
        };



	//kpis
    $scope.intervalLightKPIS = setInterval(function(){
                $scope.actualizarLightKPIS();
           },60000);

        $scope.actualizarLightKPIS = function (){
                services.getLightKPISAgendamiento().then(function(data){
                        $rootScope.oldlightkpi=$rootScope.lightkpi;
                        $rootScope.lightkpi=data.data[0];

                        if($rootScope.oldlightkpi==""||$rootScope.oldlightkpi==undefined){
                                $rootScope.oldlightkpi=$rootScope.lightkpi;
                        }

                        var arrayLength = $rootScope.lightkpi.length;
                        var arrayLength2 = $rootScope.oldlightkpi.length;

			$rootScope.totalNegocioAgendamientoOld=$rootScope.totalNegocioAgendamiento;
			$rootScope.totalNegocioAgendamiento=0;
            $rootScope.totalesDepartamento=[];
            var obj={};
            obj.COUNTER=0;
            obj.DEPARTAMENTO='';
            var deparProvisional='';
            var totalDepa=0;
            var nvec=0;

            $rootScope.totalesDepartamento[0]=obj;

                for (var i = 0; i < arrayLength; i++) {

                    var counter=$rootScope.lightkpi[i].COUNTER;
                    var depa=$rootScope.lightkpi[i].DEPARTAMENTO;

                    if(depa===deparProvisional){

                        obj.COUNTER=parseInt($rootScope.totalesDepartamento[nvec].COUNTER)+parseInt(counter);
                        $rootScope.totalesDepartamento[nvec]=obj;
                    }
                    else{
                        nvec++;
                        obj=$rootScope.totalesDepartamento[nvec];

                        if(obj===undefined){
                            obj={};
                        }

                        obj.DEPARTAMENTO=depa;
                        obj.COUNTER=counter;

                        $rootScope.totalesDepartamento[nvec]=obj;
                        deparProvisional=depa;
                    }


		    	$rootScope.totalNegocioAgendamiento=parseInt($rootScope.totalNegocioAgendamiento)+parseInt(counter);
                }

                    	
                           
		 	$rootScope.nagendamientostyle={};

		 	if($rootScope.totalNegocioAgendamiento>$rootScope.totalNegocioAgendamientoOld){
                                $rootScope.nagendamientostyle.ICON="fa fa-arrow-circle-up fa-2x";
                                $rootScope.nagendamientostyle.STYLE="red";
                        }else if($rootScope.totalNegocioAgendamiento<$rootScope.totalNegocioAgendamientoOld){
                                $rootScope.nagendamientostyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.nagendamientostyle.STYLE="green";
                        }else {
                                $rootScope.nagendamientostyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.nagendamientostyle.STYLE="gray";
                        }

			return data.data;
		});
	};

$scope.actualizarLightKPIS();
        $scope.$on(
                "$destroy",
                        function( event ) {
                            $timeout.cancel($scope.intervalLightKPIS);
                            clearInterval($scope.intervalLightKPIS);
          });

////////////////////////////////////////////////////////////////
	

$scope.start = function(pedido) {
                var pedido1='';
                $scope.popup='';
                $scope.error="";
                if(JSON.stringify($scope.peds) !=='{}' && $scope.peds.length>0){
                         pedido1=$scope.peds[0].PEDIDO_ID;
                }
                $scope.peds={};
                $scope.bpedido='';
                $scope.busy="";
                $scope.pedido1=pedido1;

                $scope.error="";

              

		if($scope.departamento == undefined||$scope.departamento==''||$scope.departamento.DEPARTAMENT == undefined || $scope.departamento.DEPARTAMENT==''){
			console.log($scope.departamento);
			alert("Seleccione un departamento.");
			return;
		}


                var demePedidoButton=document.getElementById("iniciar");
                demePedidoButton.setAttribute("disabled","disabled");
                demePedidoButton.className = "btn btn-success disabled";

		if($scope.microzona == undefined || $scope.microzona==''){
			$scope.microzona='';
		}

    



                var kami=services.demePedidoAgendamiento($rootScope.logedUser.login,$scope.departamento.DEPARTAMENT,$scope.zona.SUBZONA_ID,$scope.microzona,$scope.proceso,$scope.TODAY_TRIES,$scope.pedido1,$scope.iplaza,$rootScope.logedUser.name).then(function(data){
                        
                        
                        if(data.data==''||data.data=='No hay registros!'){
                                document.getElementById("warning").innerHTML="No hay Registros";
                                $scope.error="No hay Registros";


                        }else{
                            $scope.peds = data.data;
                                document.getElementById("warning").innerHTML="";
                                $scope.pedido1=$scope.peds[0].PEDIDO_ID;
                                $scope.TODAY_TRIES=$scope.peds[0].TODAY_TRIES;

                                if($scope.peds[0].STATUS=="PENDI_AGEN"&&$scope.peds[0].ASESOR!=""){
                                        $scope.busy=$scope.peds[0].ASESOR;
                                        $scope.error="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;

                                }
				$scope.baby($scope.pedido1);
                        }

                        var demePedidoButton=document.getElementById("iniciar");
                        demePedidoButton.removeAttribute("disabled");
                        demePedidoButton.className = "btn btn-success";
                        return data.data;
                });
                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        };


/////////////////////////////////////////////////////////////////
        $scope.actualizarGraficaAgendamiento   = function (){

                var data1=services.getPendientesGraficaAgendamiento().then(function(data){
                        $scope.myDataSourceAgendamiento = {

                            chart: {
                                "caption": "Grafica Agendamiento",
                                "subCaption": "Conceptos Pendientes",
                                "xAxisName": "Conceptos",
                                "yAxisName": "Pendientes",
                                "numberPrefix": "",
                                "paletteColors": "#0075c2",
                                "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                "rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14"
                            },

                                data: data.data[0]

                        }
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                        $scope.totalAGEN= data.data[1];

                        return data.data;
                });

                var data2=services.getPendientesConceptosReagendamiento().then(function(data){

                        $scope.tbl_fechacita =  data.data[0];

                        $scope.tbl_fechacita.totales = 0;
                        $scope.tbl_fechacita.total02 = 0;
                        $scope.tbl_fechacita.total34 = 0;
                        $scope.tbl_fechacita.total56 = 0;
                        $scope.tbl_fechacita.total712 = 0;
                        $scope.tbl_fechacita.total1324 = 0;
                        $scope.tbl_fechacita.total2548 = 0;
                        $scope.tbl_fechacita.totalmas48 = 0;

                        return data.data;

                });


            var data3=services.getPedidosConAgenda().then(function(data){

                        $scope.tbl_pedAgenda =  data.data[0];

                        $scope.tbl_pedAgenda.totales = 0;
                        $scope.tbl_pedAgenda.total02 = 0;
                        $scope.tbl_pedAgenda.total34 = 0;
                        $scope.tbl_pedAgenda.total56 = 0;
                        $scope.tbl_pedAgenda.total712 = 0;

                        $scope.totalPed= data.data[1];

                        return data.data;

                });


                services.logVista($cookieStore.get('logedUser').login,"Indicadores Agendamiento");
           };


        $scope.myDataSourceAgendamiento = {
                        chart: {
                        caption: "Grafica Agendamiento",
                        subcaption: "Conceptos Pendientes Agendamiento",
                        startingangle: "120",
                        showlabels: "0",
                        showlegend: "1",
                        enablemultislicing: "0",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        theme: "fint"
                        },
                        data: []

        };


   $scope.savePedido = function(index) {
    console.log ($scope.pedido);

        var loader = document.getElementById("class"+index);
        loader.className='glyphicon glyphicon-refresh fa-spin';
	
	$scope.pedido={};

        console.log("Un objeto: "+angular.copy($scope.peds[index],$scope.pedido));

        if($scope.pedido==undefined||$scope.pedido==''||$scope.pedido.length==0){
                  console.log("estoy malo: "+$scope.pedido+"----"+JSON.stringify($scope.peds[index]));
		console.log(Object.prototype.toString.call($scope.peds[index])+" == "+Object.prototype.toString.call($scope.pedido));
        }else{
                  console.log($scope.pedido);
        }

	       

        if($scope.pedido.NOVEDAD===undefined){
            alert('Por favor diligenciar la NOVEDAD.');
            return;
        }

      $scope.pedido.ASESOR=$rootScope.logedUser.login;
        $scope.pedido.ASESORNAME=$rootScope.logedUser.name;
        $scope.pedido.DURACION=new Date().getTime() - $scope.timeInit;
       $scope.pedido.DEPARTAMENTO=$scope.departamento.DEPARTAMENT;
      
      
    if(document.getElementById('programacion')==null){
        $scope.pedido.PROGRAMACION="";

        console.log($scope.pedido.PROGRAMACION);
    }else{
        $scope.pedido.PROGRAMACION=document.getElementById('programacion').value;
        
    }
    


    if($scope.pedido.NOVEDAD!='AGENDADO'&&$scope.pedido.NOVEDAD!='YA ESTA AGENDADO'&&$scope.pedido.NOVEDAD!='AGENDADO MANUAL' &&$scope.pedido.NOVEDAD!='AGENDADO_FUTURO' &&$scope.pedido.NOVEDAD!='YA ESTA AGENDADO-USUARIO'  ){
        $scope.pedido.FECHA_CITA_REAGENDA='';
        $scope.pedido.JORNADA_CITA='';

    }else{
        $scope.pedido.PROGRAMACION='';
    }

    if($scope.pedido.NOVEDAD!='LLAMAR FUTURO'){
        
        //PARA HACER QUE HAYA UN REINTENTO DEL PEDIDO!!
        if($scope.pedido.NOVEDAD=='CLIENTE NO CONTACTADO'||$scope.pedido.NOVEDAD=='NO PUEDE ATENDER LLAMADA'){
            var date1 = new Date();

            date1.setHours(date1.getHours()+6);

                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                //$scope.pedido.PROGRAMACION=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                $scope.pedido.PROGRAMACION='';

        }
    }

    if($scope.pedido.NOVEDAD=='AGENDADO' || $scope.pedido.NOVEDAD=='PENDIENTE POR OTRO CONCEPTO' || $scope.pedido.NOVEDAD=='NO DESEA EL SERVICIO' || $scope.pedido.NOVEDAD=='YA ESTA CUMPLIDO' ){
        console.log($scope.pedido.NOVEDAD)
        var regexp = /^([0-9]{2,20})$/;

        if(regexp.test($scope.pedido.IDLLAMADA)==false || $scope.pedido.IDLLAMADA==undefined ){
            alert('id llamada esta mal gestionado y/o esta vacio');
            
            return;

        }
    }



        $scope.timeInit=new Date().getTime();
        var df=new Date($scope.pedido.DURACION);
        $scope.pedido.DURACION= 
        $scope.doubleDigit(df.getHours()-19)+":"+ $scope.doubleDigit(df.getMinutes())+":"+ $scope.doubleDigit(df.getSeconds());
        $scope.pedido.pedido=$scope.peds[index].PEDIDO_ID;
        $scope.pedido1=$scope.peds[index].PEDIDO_ID;

        $scope.pedido.ACTIVIDAD_GESTOR="REAGENDAMIENTO";
        $scope.pedido.FUENTE=$scope.peds[index].FUENTE;
        $scope.pedido.FECHA_INICIO=$scope.fecha_inicio;
        $scope.pedido.TIEMPO_TOTAL=$scope.pedido.TIEMPO_TOTAL+" DIAS";

        var date1 = new Date();
        var year    = date1.getFullYear();
        var month   = $scope.doubleDigit(date1.getMonth()+1);
        var day     = $scope.doubleDigit(date1.getDate());
        var hour    = $scope.doubleDigit(date1.getHours());
        var minute  = $scope.doubleDigit(date1.getMinutes());
        var seconds = $scope.doubleDigit(date1.getSeconds());

        $scope.pedido.FECHA_FIN=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        var dat= new Date();

	
	$scope.pedido.fecha='';
	$scope.pedido.concepto_final='';

    //console.log($scope.pedido.SUBZONA_ID);

        services.insertPedidoReagendamiento($scope.pedido).then(function (status) {
            $scope.pedido.fecha=status.data['data'];
            $scope.pedido.concepto_final=status.data['msg'];

            if($scope.pedido.concepto_final=="El pedido NO ha cambiado de concepto en Fenix!!!" || $scope.pedido.concepto_final=="ERROR!"){
                    alert($scope.pedido.concepto_final);


                    $scope.pedido.fecha="";
                    $scope.pedido.concepto_final="";
            }else{

                if($scope.pedido.concepto_final=="El pedido bloqueado por Usuario por mas de una hora, fue liberado por el sistema, usuario no podra gestionarlo hasta despues de una hora!!!"){
                $scope.error=$scope.pedido.concepto_final;

                    $scope.peds=[];
                    $scope.pedido={};
                    $scope.pedidos="";
            } else{
		     console.log("EL PEDIDO QUEDO ASI: ");
             console.log($scope.pedido);
                    $scope.historico_pedido=$scope.historico_pedido.concat(angular.copy($scope.pedido));
                    $scope.peds.splice(index,1);
                     if($scope.pedidos==""){
                            $scope.pedidos=new Array();
                    }
                    $scope.pedidos=$scope.pedidos.concat($scope.pedido);
                    if($scope.historico_pedido==""){
                            $scope.historico_pedido=new Array();
                    }
                            $scope.pedido=[];
                            $scope.busy="";
                            $scope.timeInit=new Date().getTime();
                            date1 = new Date();
                            year    = date1.getFullYear();
                            month   = $scope.doubleDigit(date1.getMonth()+1);
                            day     = $scope.doubleDigit(date1.getDate());
                            hour    = $scope.doubleDigit(date1.getHours());
                            minute  = $scope.doubleDigit(date1.getMinutes());
                            seconds = $scope.doubleDigit(date1.getSeconds());

                            $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                            $scope.popup='';
                }

            }
            loader.className='';
            return status;
        });

    };//FIN SAVEPEDIDO

  var colorDanger="#E83720";
    var colorWaring="#E8A820";
    var colorNormal="#088A08";



    $scope.set_color = function (service) {


        if (service.PROCESO=='INSTALACION' && service.TODAY_TRIES >= 5) {
            $scope.error='Recuerde agendar o en su defecto anular el pedido';
            //alert('pedido especial');

                $scope.estilo={
                 "color" : "white",
                    "background-color" : "red",
                    "font-size" : "10px",
                    "padding" : "2px"
                    };

                return $scope.estilo;
              }
              else{

                if(service.PROCESO=='REPARACION' && service.TODAY_TRIES >= 3){
                $scope.error='Recuerde agendar o en su defecto cerrar y crear SS';

                    $scope.estilo={
                  "color" : "white",
                    "background-color" : "red",
                    "font-size" : "10px",
                    "padding" : "2px"
                    };

                  }else{

                     $scope.estilo={
                  "list-style-position":"inside",
                  "border-left": "5px solid "+colorNormal
                    };
                  }

               return $scope.estilo;

              }
            };

});

app.controller('AuditoriaCtrl',function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services,$compile) {
    
      var userID=$cookieStore.get('logedUser').login;
      $rootScope.logedUser=$cookieStore.get('logedUser');
      document.getElementById('logout').className="btn btn-md btn-danger";
      var divi=document.getElementById("logoutdiv");
      divi.style.visibility="visible";
      divi.style.position="relative";
      $scope.lastUpdate="";

   
      $scope.peds={};

   $scope.intervalLightKPIS='';

        $scope.pedidos=[];
        $rootScope.actualView="reagendamiento";
        $scope.popup='';
        $scope.pedido={};
        $scope.error="";
        $scope.historico_pedido=[];
   $scope.departamentos=[];
   $scope.zonas={};
   $scope.microzonas={};
   $scope.departamento="";


   $scope.ordenamientoDemepedido='';
   $scope.ordenamientoDemepedidoUpdate='';
   $scope.ordenamientoDemepedidoNuevo=''; 


   var pedidos=services.getPedidosUserReagendamiento(userID).then(function(data){
               $scope.pedidos=data.data;
               return data.data;
        });

 
    $rootScope.logout = function() {
        services.logout($rootScope.logedUser.login);
        $cookieStore.remove('logedUser');
        $rootScope.logedUser=undefined;
        document.getElementById('logout').className="btn btn-md btn-danger hide";
        var divi=document.getElementById("logoutdiv");
        divi.style.position="absolute";
        divi.style.visibility="hidden";
        $location.path('/');
     }; 

      

   $scope.getDepartamentos = function() {
      $scope.departamentos={};
      $scope.microzonas={};
                services.getDepartamentosPendientesReagendamiento().then(function(data){
         $scope.departamentos=data.data;
         console.log($scope.departamentos);

                        return data.data;
                });
   };

   $scope.getDepartamentos();


        $scope.getZonas = function(depa) {
                console.log(depa);
                $scope.zonas={};
            $scope.microzonas={};
                services.getZonasReagendamiento(depa.DEPARTAMENT).then(function(data){
                        $scope.zonas=data.data;

                        return data.data;
                });
        };


   $scope.getMicrozonas = function(zona,depa){
      $scope.microzonas={};
      
                services.getMicrozonasReagendamiento(zona.SUBZONA_ID,depa.DEPARTAMENT).then(function(data){
                        $scope.microzonas=data.data;
         console.log($scope.microzonas);
                        return data.data;
                });
      
   };


$scope.popoverboton= function (){
$('#popoverId').popover({
    html: true,
    title: "Observaciones",
    content: function () {
        return $('#popoverContent').html();
    }
});
var isVisible = false;
var clickedAway = false;

$('.popoverThis').popover({
    html: true,
    trigger: 'click'
})
$(document).click(  function (e) {
    if (isVisible & clickedAway) {
        $('.popoverThis').popover('hide')
        isVisible = clickedAway = false
    } else {
        clickedAway = true
    }
});
};


        $scope.doubleDigit= function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
               }
            return num;
        };

/* FUNCION PARA ACTUALIZAR LOS PARAMETROS DEL SISTEMA */
$scope.updateParametro = function (parametro,valor){

        services.updateParametro(parametro,valor,$rootScope.logedUser.login).then(function(data){
                if(parametro=="FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO"){
                        $scope.ordenamientoDemepedido=valor;
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.ordenamientoDemepedidoUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                }
                return data.data;
        });

   if(parametro=="FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO"){
      valor=$scope.prioridadDemepedidoNuevo;
      parametro='PRIORIDAD_DEMEPEDIDO_AGENDAMIENTO';
      services.updateParametro(parametro,valor,$rootScope.logedUser.login).then(function(data){
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.ordenamientoDemepedidoUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

      });
   }

};


$scope.buscarParametro = function (parametro){

        services.buscarParametro(parametro).then(function(data){
                return data.data;
        });

};


services.buscarParametro('FECHA_ORDEN_DEMEPEDIDO_AGENDAMIENTO').then(function(data){

        $scope.ordenamientoDemepedido=data.data['VALOR'];
        $scope.ordenamientoDemepedidoNuevo=data.data['VALOR'];
        $scope.ordenamientoDemepedidoUpdate=data.data['ULTIMA_ACTUALIZACION'];
        return data.data;
});


services.buscarParametro('PRIORIDAD_DEMEPEDIDO_AGENDAMIENTO').then(function(data){

        $scope.prioridadDemepedidoNuevo=data.data['VALOR'];
        $scope.ordenamientoDemepedidoUpdate=data.data['ULTIMA_ACTUALIZACION'];
        return data.data;
});




        $scope.parseInt =  function (numbero){
        return parseInt(numbero);
        };

        $scope.parseFloat =  function (numbero){
                return parseFloat(numbero);
        };

        $scope.roundFloat =  function (numbero){
        var num=parseFloat(numbero).toFixed(2);
                return num;
        };

   $scope.baby = function(pedido) {
         services.getPedidosPorPedidoAgendamiento(pedido).then(function(data){
                      $scope.historico_pedido=data.data;
         //console.log($scope.historico_pedido);
                      return data.data;
                 });
      };

       $scope.manual = function() {
        $scope.peds={};
        $scope.error="";
        $scope.pedido1="";
        $scope.mpedido={};
        $scope.bpedido='';
        $scope.busy="";
        $scope.historico_pedido=[];
        $scope.mpedido.active=1;
        //$scope.mpedido.PROGRAMACION="";

                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                $scope.fecha_fin=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

    };


      
      $scope.msavePedidomalo = function() {//validacion datos para ingreso manual
                
                $scope.pedido={};
             $scope.error="";
                angular.copy($scope.mpedido,$scope.pedido);

                if($scope.mpedido.PEDIDO_ID==""||$scope.mpedido.PEDIDO_ID=={}||$scope.mpedido.PEDIDO_ID === undefined){
                        alert("Pedido vacio.");
                        return;
                }
                 if($scope.pedido.NOVEDAD===undefined){
            alert('Por favor diligenciar la NOVEDAD.');
            return;
        }

        $scope.pedido.ASESOR=$rootScope.logedUser.login;
        $scope.pedido.ASESORNAME=$rootScope.logedUser.name;
        $scope.pedido.DURACION=new Date().getTime() - $scope.timeInit;
         

        $scope.pedido.ACTIVIDAD_GESTOR="REAGENDAMIENTO";
        $scope.pedido.FUENTE=$scope.mpedido.FUENTE;
        $scope.pedido.OBSERVACION_GESTOR=$scope.mpedido.OBSERVACION_GESTOR;
        $scope.pedido.proceso=$scope.mpedido.proceso;
        //$scope.pedido.PROGRAMACION=$scope.mpedido.PROGRAMACION;
               // $scope.pedido.ACTIVIDADES="INSTA";  

                if(document.getElementById('programacion')==null){
        $scope.pedido.PROGRAMACION="";

        console.log($scope.pedido.PROGRAMACION);
    }else{
        $scope.pedido.PROGRAMACION=document.getElementById('programacion').value;
        
    }
    
    //console.log($scope.pedido.ACTIVIDAD_GESTOR);

    if($scope.pedido.NOVEDAD!='AGENDADO'&&$scope.pedido.NOVEDAD!='YA ESTA AGENDADO'&&$scope.pedido.NOVEDAD!='AGENDADO MANUAL' &&$scope.pedido.NOVEDAD!='AGENDADO_FUTURO' &&$scope.pedido.NOVEDAD!='YA ESTA AGENDADO-USUARIO'  ){
        $scope.pedido.FECHA_CITA_REAGENDA='';
        $scope.pedido.JORNADA_CITA='';
   
        //console.log($scope.pedido.PROGRAMACION);


    }else{
        $scope.pedido.PROGRAMACION='';
    }


                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                
                console.log($scope.pedido);
                console.log($scope.mpedido);
                
                

                 services.insertMPedidomalo($scope.pedido).then(function (status) {
                
                if($scope.pedidos==""){
                    $scope.pedidos=new Array();
                }
                $scope.pedidos=$scope.pedidos.concat($scope.pedido);
                if($scope.historico_pedido==""){
                       $scope.historico_pedido=new Array();
                }

                $scope.baby($scope.pedido.PEDIDO_ID);
                $scope.pedido1=$scope.pedido.PEDIDO_ID;

                $scope.timeInit=new Date().getTime();
                date1 = new Date();
                year    = date1.getFullYear();
                month   = $scope.doubleDigit(date1.getMonth()+1);
                day     = $scope.doubleDigit(date1.getDate());
                hour    = $scope.doubleDigit(date1.getHours());
                minute  = $scope.doubleDigit(date1.getMinutes());
                seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
               

                $scope.pedido={};
                $scope.peds={};
                $scope.pedido1="";
                $scope.mpedido={};
                $scope.bpedido='';
                $scope.proceso='';
                $scope.historico_pedido=[];

                 $scope.mpedido.active=1;
                $scope.busy="";
                $scope.mpedido.active=0;
                $scope.pedidoinfo='Pedido';
            }); 
        };

      
        $scope.buscarPedidoAgendamiento = function(bpedido) {
                $scope.error="";
                $scope.peds={};
                $scope.mpedido={};
                $scope.busy="";
                $scope.error="";
                var kami=services.buscarPedidoAgendamiento(bpedido,$scope.pedido1,$rootScope.logedUser.login,$rootScope.logedUser.name).then(function(data){
                        $scope.peds = data.data;
                        console.log($scope.peds);
                        var dat=data.status;
                        
                        if(dat==204){
                                document.getElementById("warning").innerHTML="No hay Registros";
                                $scope.error="No hay Registros";
                                $scope.historico_pedido={};
                        }else{
                                document.getElementById("warning").innerHTML="";
                                $scope.pedido1=$scope.peds[0].PEDIDO_ID;
                                $scope.baby($scope.pedido1);

                                if($scope.peds[0].STATUS=="PENDI_AGEN"&&$scope.peds[0].ASESOR!=""){
                                        $scope.busy=$scope.peds[0].ASESOR;
                                        $scope.error="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;
                                }
                                
                        }
            

         var demePedidoButton=document.getElementById("iniciar");
                        demePedidoButton.removeAttribute("disabled");
                        demePedidoButton.className = "btn btn-danger";
                        return data.data;
                });
                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        };


        $scope.buscarPedidoAgendamientoAuditoria = function(bpedido) {
                $scope.error="";
                $scope.peds={};
                $scope.mpedido={};
                $scope.busy="";
                $scope.error="";
                var kami=services.buscarPedidoAgendamiento(bpedido,$scope.pedido1,$rootScope.logedUser.login,$rootScope.logedUser.name).then(function(data){
                        $scope.peds = data.data;
                        console.log($scope.peds);
                        var dat=data.status;

                        if(dat==204){
                                document.getElementById("warning").innerHTML="No hay Registros";
                                $scope.error="No hay Registros";
                        }else{
                                document.getElementById("warning").innerHTML="";
                                $scope.pedido1=$scope.peds[0].PEDIDO_ID;

                                if($scope.peds[0].STATUS=="PENDI_AGEN"&&$scope.peds[0].ASESOR!=""){
                                        $scope.busy=$scope.peds[0].ASESOR;
                                        $scope.error="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;
                                }

                        }
                        var demePedidoButton=document.getElementById("iniciar");
                        demePedidoButton.removeAttribute("disabled");
                        demePedidoButton.className = "btn btn-danger";
                        return data.data;
                });
                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        };

        $rootScope.abreZona = function(departamento){
            var n = $rootScope.lightkpi.length;

            var zonasTabla="<table class='table small table-striped table-hover table-bordered table-condensed'>";
            zonasTabla+= "<thead><th class='text-center'>Zona</th><th class='text-center'>Cantidad</th></thead><tbody>"

            for (var i = 0; i < n; i++) {
                if(departamento===$rootScope.lightkpi[i].DEPARTAMENTO){
                    zonasTabla+="<tr><td>"+$rootScope.lightkpi[i].SUBZONA_ID+"</td><td>"+$rootScope.lightkpi[i].COUNTER+"</td></tr>";
                }
            }
            zonasTabla+="</tbody></table>";

      $rootScope.selected_option = zonasTabla;
        };



   //kpis
    $scope.intervalLightKPIS = setInterval(function(){
                $scope.actualizarLightKPIS();
           },60000);

        $scope.actualizarLightKPIS = function (){
                services.getLightKPISAgendamiento().then(function(data){
                        $rootScope.oldlightkpi=$rootScope.lightkpi;
                        $rootScope.lightkpi=data.data[0];

                        if($rootScope.oldlightkpi==""||$rootScope.oldlightkpi==undefined){
                                $rootScope.oldlightkpi=$rootScope.lightkpi;
                        }

                        var arrayLength = $rootScope.lightkpi.length;
                        var arrayLength2 = $rootScope.oldlightkpi.length;

         $rootScope.totalNegocioAgendamientoOld=$rootScope.totalNegocioAgendamiento;
         $rootScope.totalNegocioAgendamiento=0;
            $rootScope.totalesDepartamento=[];
            var obj={};
            obj.COUNTER=0;
            obj.DEPARTAMENTO='';
            var deparProvisional='';
            var totalDepa=0;
            var nvec=0;

            $rootScope.totalesDepartamento[0]=obj;

                for (var i = 0; i < arrayLength; i++) {

                    var counter=$rootScope.lightkpi[i].COUNTER;
                    var depa=$rootScope.lightkpi[i].DEPARTAMENTO;

                    if(depa===deparProvisional){

                        obj.COUNTER=parseInt($rootScope.totalesDepartamento[nvec].COUNTER)+parseInt(counter);
                        $rootScope.totalesDepartamento[nvec]=obj;
                    }
                    else{
                        nvec++;
                        obj=$rootScope.totalesDepartamento[nvec];

                        if(obj===undefined){
                            obj={};
                        }

                        obj.DEPARTAMENTO=depa;
                        obj.COUNTER=counter;

                        $rootScope.totalesDepartamento[nvec]=obj;
                        deparProvisional=depa;
                    }


            $rootScope.totalNegocioAgendamiento=parseInt($rootScope.totalNegocioAgendamiento)+parseInt(counter);
                }

                     console.log($rootScope.totalesDepartamento);
                           
         $rootScope.nagendamientostyle={};

         if($rootScope.totalNegocioAgendamiento>$rootScope.totalNegocioAgendamientoOld){
                                $rootScope.nagendamientostyle.ICON="fa fa-arrow-circle-up fa-2x";
                                $rootScope.nagendamientostyle.STYLE="red";
                        }else if($rootScope.totalNegocioAgendamiento<$rootScope.totalNegocioAgendamientoOld){
                                $rootScope.nagendamientostyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.nagendamientostyle.STYLE="green";
                        }else {
                                $rootScope.nagendamientostyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.nagendamientostyle.STYLE="gray";
                        }

         return data.data;
      });
   };

$scope.actualizarLightKPIS();
        $scope.$on(
                "$destroy",
                        function( event ) {
                            $timeout.cancel(timer);
                            clearInterval($scope.intervalLightKPIS);
          });

////////////////////////////////////////////////////////////////
   

$scope.start = function(pedido) {
                var pedido1='';
                $scope.popup='';
                $scope.error="";
                if(JSON.stringify($scope.peds) !=='{}' && $scope.peds.length>0){
                         pedido1=$scope.peds[0].PEDIDO_ID;
                }
                $scope.peds={};
                $scope.bpedido='';
                $scope.busy="";
                $scope.pedido1=pedido1;

                $scope.error="";

                var demePedidoButton=document.getElementById("iniciar");
                demePedidoButton.setAttribute("disabled","disabled");
                demePedidoButton.className = "btn btn-danger disabled";

                

                var kami=services.demePedidoAgendamientomalo($rootScope.logedUser.login,$scope.pedido1,$scope.iplaza,$rootScope.logedUser.name,'').then(function(data){
                        $scope.peds = data.data;
                        console.log(data.data);
                        if(data.data==''||data.data=='No hay registros!'){
                                document.getElementById("warning").innerHTML="No hay Registros";
                                $scope.error="No hay Registros";
                        }else{
                                document.getElementById("warning").innerHTML="";
                                $scope.pedido1=$scope.peds[0].PEDIDO_ID;

                                if($scope.peds[0].STATUS=="MALO"&&$scope.peds[0].ASESOR!=""){
                                        $scope.busy=$scope.peds[0].ASESOR;
                                        $scope.error="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;
                                }
            $scope.baby($scope.pedido1);
                        }

                        var demePedidoButton=document.getElementById("iniciar");
                        demePedidoButton.removeAttribute("disabled");
                        demePedidoButton.className = "btn btn-danger";
                        return data.data;
                });
                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
        };


/////////////////////////////////////////////////////////////////

        
  

       
   $scope.saveAuditoria = function(index) {

        var loader = document.getElementById("class"+index);
        loader.className='glyphicon glyphicon-refresh fa-spin';
   
   $scope.pedido={};

        console.log("Un objeto: "+angular.copy($scope.peds[index],$scope.pedido));

        if($scope.pedido==undefined||$scope.pedido==''||$scope.pedido.length==0){
                  console.log("estoy malo: "+$scope.pedido+"----"+JSON.stringify($scope.peds[index]));
      console.log(Object.prototype.toString.call($scope.peds[index])+" == "+Object.prototype.toString.call($scope.pedido));
        }else{
                  console.log("tengo datos: "+$scope.pedido);
        }

   console.log($scope.pedido);

        if($scope.pedido.NOVEDAD===undefined){
            alert('Por favor diligenciar la NOVEDAD.');
            return;
        }

        $scope.pedido.ASESOR=$rootScope.logedUser.login;
        $scope.pedido.ASESORNAME=$rootScope.logedUser.name;
        $scope.pedido.DURACION=new Date().getTime() - $scope.timeInit;
   $scope.pedido.DEPARTAMENTO=$scope.departamento.DEPARTAMENT;

   $scope.pedido.PROGRAMACION=document.getElementById('programacion').value;


      //PARA HACER QUE HAYA UN REINTENTO DEL PEDIDO!!
      if($scope.pedido.NOVEDAD=='CLIENTE NO CONTACTADO'||$scope.pedido.NOVEDAD=='NO PUEDE ATENDER LLAMADA'){
         var date1 = new Date();

         date1.setHours(date1.getHours()+6);

            var year    = date1.getFullYear();
            var month   = $scope.doubleDigit(date1.getMonth()+1);
            var day     = $scope.doubleDigit(date1.getDate());
            var hour    = $scope.doubleDigit(date1.getHours());
            var minute  = $scope.doubleDigit(date1.getMinutes());
            var seconds = $scope.doubleDigit(date1.getSeconds());

              $scope.pedido.PROGRAMACION=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
      }
   
   

               

      if($scope.pedido.NOVEDAD=='YA ESTA AGENDADO'){
         $scope.pedido.PROGRAMACION=$scope.pedido.FECHA_CITA_REAGENDA+" 14:00:00";
      }


   

        $scope.timeInit=new Date().getTime();
        var df=new Date($scope.pedido.DURACION);
        $scope.pedido.DURACION= 
        $scope.doubleDigit(df.getHours()-19)+":"+ $scope.doubleDigit(df.getMinutes())+":"+ $scope.doubleDigit(df.getSeconds());
        //$scope.pedido.pedido=$scope.peds[index].PEDIDO_ID;
        $scope.pedido1=$scope.peds[index].PEDIDO_ID;

        $scope.pedido.ACTIVIDAD_GESTOR="REAGENDAMIENTO";
        $scope.pedido.FUENTE=$scope.peds[index].FUENTE;
        $scope.pedido.FECHA_INICIO=$scope.fecha_inicio;
        $scope.pedido.TIEMPO_TOTAL=$scope.pedido.TIEMPO_TOTAL+" DIAS";

        var date1 = new Date();
        var year    = date1.getFullYear();
        var month   = $scope.doubleDigit(date1.getMonth()+1);
        var day     = $scope.doubleDigit(date1.getDate());
        var hour    = $scope.doubleDigit(date1.getHours());
        var minute  = $scope.doubleDigit(date1.getMinutes());
        var seconds = $scope.doubleDigit(date1.getSeconds());

        $scope.pedido.FECHA_FIN=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        var dat= new Date();

   //console.log($scope.pedido);
   $scope.pedido.fecha='';
   $scope.pedido.concepto_final='';
        services.insertPedidoAuditoria($scope.pedido).then(function (status) {
            $scope.pedido.fecha=status.data['data'];
            $scope.pedido.concepto_final=status.data['msg'];

            if($scope.pedido.concepto_final=="El pedido NO ha cambiado de concepto en Fenix!!!" || $scope.pedido.concepto_final=="ERROR!"){
                    alert($scope.pedido.concepto_final);


                    $scope.pedido.fecha="";
                    $scope.pedido.concepto_final="";
            }else{

                if($scope.pedido.concepto_final=="El pedido bloqueado por Usuario por mas de una hora, fue liberado por el sistema, usuario no podra gestionarlo hasta despues de una hora!!!"){
                $scope.error=$scope.pedido.concepto_final;

                    $scope.peds=[];
                    $scope.pedido={};
                    $scope.pedidos="";
            } else{
          console.log("EL PEDIDO QUEDO ASI: ");
         console.log($scope.pedido);
                    $scope.historico_pedido=$scope.historico_pedido.concat(angular.copy($scope.pedido));
                    $scope.peds.splice(index,1);
                     if($scope.pedidos==""){
                            $scope.pedidos=new Array();
                    }
                    $scope.pedidos=$scope.pedidos.concat($scope.pedido);
                    if($scope.historico_pedido==""){
                            $scope.historico_pedido=new Array();
                    }
                            $scope.pedido=[];
                            $scope.busy="";
                            $scope.timeInit=new Date().getTime();
                            date1 = new Date();
                            year    = date1.getFullYear();
                            month   = $scope.doubleDigit(date1.getMonth()+1);
                            day     = $scope.doubleDigit(date1.getDate());
                            hour    = $scope.doubleDigit(date1.getHours());
                            minute  = $scope.doubleDigit(date1.getMinutes());
                            seconds = $scope.doubleDigit(date1.getSeconds());

                            $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                            $scope.popup='';
                }

            }
            loader.className='';
            return status;
        });

    };//FIN SAVEPEDIDO


                 
});


app.controller('Tabla_agendamientoCtrl',function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, services,$compile) {

      var userID=$cookieStore.get('logedUser').login;
      $rootScope.logedUser=$cookieStore.get('logedUser');
      document.getElementById('logout').className="btn btn-md btn-danger";
      var divi=document.getElementById("logoutdiv");
      divi.style.visibility="visible";
      divi.style.position="relative";
      $scope.lastUpdate="";

   
      $scope.peds={};

   $scope.intervalLightKPIS='';

        $scope.pedidos=[];
        $rootScope.actualView="reagendamiento";
        $scope.popup='';
        $scope.pedido={};
        $scope.error="";
        $scope.historico_pedido=[];
   $scope.departamentos=[];
   $scope.zonas={};
   $scope.microzonas={};
   $scope.departamento="";


   $scope.ordenamientoDemepedido='';
   $scope.ordenamientoDemepedidoUpdate='';
   $scope.ordenamientoDemepedidoNuevo=''; 


  
   
$scope.doubleDigit= function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
               }
            return num;
        };




        $scope.parseInt =  function (numbero){
        return parseInt(numbero);
        };

        $scope.parseFloat =  function (numbero){
                return parseFloat(numbero);
        };

        $scope.roundFloat =  function (numbero){
        var num=parseFloat(numbero).toFixed(2);
                return num;
        };

       
 

/////////////////////////////////////////////////////////////////
        
        $scope.actualizarlistaauditoria=function(fecha_inicio,fecha_fin){
         var audi=services.getlistadoAgendamientoAuditoria(fecha_inicio,fecha_fin).then(function(data){                
                         $scope.tbl_listado =  data.data[0];
                         $scope.tbl_listado.ANTIOQUIA = 0; 
                        $scope.tbl_listado.ATLANTICO = 0;
                        $scope.tbl_listado.BOGOTACOBRE=0;
                        $scope.tbl_listado.BOGOTA=0;
                        $scope.tbl_listado.BOLIVAR = 0; 
                        $scope.tbl_listado.BOYACA = 0; 
                        $scope.tbl_listado.CALDAS = 0; 
                        $scope.tbl_listado.CAQUETA= 0;
                        $scope.tbl_listado.CESAR= 0;     
                        $scope.tbl_listado.CUNDINAMARCA= 0;       
                        $scope.tbl_listado.DTH= 0;       
                        $scope.tbl_listado.HUILA= 0;       
                        $scope.tbl_listado.MAGDALENA= 0;       
                        $scope.tbl_listado.META= 0;       
                        $scope.tbl_listado.NORTE_DE_SANTANDER= 0;       
                        $scope.tbl_listado.QUINDIO= 0;     
                        $scope.tbl_listado.SANTANDER= 0;  
                        $scope.tbl_listado.SUCRE= 0;  
                        $scope.tbl_listado.TOLIMA= 0;  
                        $scope.tbl_listado.VALLE= 0;  
                        $scope.tbl_listado.VALLE_DEL_CAUCA= 0;  
                        $scope.tbl_listado.VACIOS= 0;        
                        $scope.tbl_listado.REGISTROS = 0;
                        $scope.refresh='';
                         $scope.length = $scope.tbl_listado.length;
                        return data.data;

                });


                
        };
        $scope.actualizarlistaauditoria();
        
        //var pedidos=services.getPedidosUser(userID).then(function(data){
          //              $scope.pedidos=data.data;
            //            return data.data;
              //  });
              
              //get another portions of data on page changed
    $scope.pageChanged = function(forma) {
        if(forma=="tbl_listado"){
            $scope.actualizarlistaauditoria();
        }
        if(forma=="tbl_listado"){
                        $scope.calcularPendientes($scope.data1.concepto);
                }
    };            
        

        
            $scope.csvTabla = function (fecha_inicio,fecha_fin){
                var login=$rootScope.logedUser.login;
                services.getcsvTabla(fecha_inicio,fecha_fin).then(function(data){
            console.log(data.data[0]);
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });

        };

      

            


  

        var date1 = new Date();
        var year    = date1.getFullYear();
        var month   = $scope.doubleDigit(date1.getMonth()+1);
        var day     = $scope.doubleDigit(date1.getDate());

       $scope.fecha_inicio=year+"-"+month+"-"+day;
      $scope.fecha_fin=year+"-"+month+"-"+day;

        var dat= new Date();


    
                 
});



app.controller('ActivacionCtrl',function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore,$timeout, services) {
    
      var userID=$cookieStore.get('logedUser').login;
    $rootScope.logedUser=$cookieStore.get('logedUser');
    document.getElementById('logout').className="btn btn-md btn-danger";
    var divi=document.getElementById("logoutdiv");
    divi.style.visibility="visible";
    divi.style.position="relative";
    $scope.lastUpdate="";

     $scope.doubleDigit= function (num){

                if(num<0){
                        num=0;
                }

                if(num<=9){
                    return "0"+num;
               }
            return num;
        };


    $scope.data = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaini:"", fechafin: "" };

    $scope.data1 = { maxSize: 5, currentPage: 1, numPerPage: 100, totalItems: 0, fechaini:"", fechafin: "" } ;

    var date1 = new Date();
    var year  = date1.getFullYear();
    var month =  $scope.doubleDigit(date1.getMonth()+1);
    var day   =  $scope.doubleDigit(date1.getDate());
    var fechaini=year+"-"+month+"-"+day;
    var fechafin=year+"-"+month+"-"+day;
    

    $scope.data.fechaini=fechaini;
    $scope.data1.fechaini=fechaini;

    $scope.data.fechafin=fechafin;

    

    if(!angular.isDefined($scope.currentPage)){
                $scope.currentPage = 1;
        }


    $scope.setPage = function (pageNo) {
            $scope.data.currentPage = pageNo;
    };

    $rootScope.logout = function() {
        services.logout($rootScope.logedUser.login);
        $cookieStore.remove('logedUser');
        $rootScope.logedUser=undefined;
        $scope.pedidos={};
        document.getElementById('logout').className="btn btn-md btn-danger hide";
        var divi=document.getElementById("logoutdiv");
        divi.style.position="absolute";
        divi.style.visibility="hidden";
        $location.path('/');

     };

     $scope.listadoactivacion=[];

        $scope.data1.fechafin=fechafin;

        $rootScope.actualView="activacion";

        $scope.parseInt =  function (numbero){
        return parseInt(numbero);
        };

        $scope.parseFloat =  function (numbero){
                return parseFloat(numbero);
        };

        $scope.roundFloat =  function (numbero){
        var num=parseFloat(numbero).toFixed(2);
                return num;
        };

       

       $scope.listadoactivacion1  = function (){ 

                services.getListadoActivacion($scope.data.fechaini,$scope.data.fechafin,$scope.data.currentPage).then(function(data){

                   // console.log(data);
                       $scope.listadoactivacion=data.data[0];
                       $scope.data.totalItems=data.data[1];

                    return data.data;
               });


        };



      $scope.listadoactivacion1();
        

    $scope.pageChanged = function(forma) {

            $scope.listadoactivacion1();
   

               // console.log($scope.data.concepto);
    };            

  $scope.csvActivacion  = function (){
                var login=$rootScope.logedUser.login;
                services.getCsvActivacion(login).then(function(data){
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };

     $scope.csvActivacionSiebel  = function (){
                var login=$rootScope.logedUser.login;
                services.getCsvActivacionSiebel(login).then(function(data){
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };

        $scope.csvListadoActivacion  = function (fechaini,fechafin){
                var login=$rootScope.logedUser.login;
                services.getCsvListadoActivacion($scope.data.fechaini,$scope.data.fechafin,login).then(function(data){
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };

        $scope.csvListadoActivacionnuevos  = function (fechaini,fechafin){
                var login=$rootScope.logedUser.login;
                services.getCsvListadoActivacionnuevo($scope.data.fechaini,$scope.data.fechafin,login).then(function(data){
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
        };
    

       $scope.actualizarGraficaAD   = function (){
    //TOMAR MUESTRA
                var data1=services.getPendientesGraficaAD().then(function(data){

                        $scope.myDataSourceAD = {
                             /*   chart: {
                                        caption: "Grafica A y D",
                                        subcaption: "Pendientes",
                                        startingangle: "120",
                                        showlabels: "1",
                                        showlegend: "1",
                                        enablemultislicing: "0",
                                        formatNumberScale: "0",
                                        slicingdistance: "15",
                                        showpercentvalues: "0",
                                        showpercentintooltip: "0",
                        }, */

                           chart: {
                                "caption": "Grafica Activación / Desactivación",
                                "subCaption": "Pendientes",
                                "xAxisName": "Colas",
                                "yAxisName": "Pendientes",
                                "numberPrefix": "",
                                "paletteColors": "#0075c2",
                                "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                "rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14"
                            },
                                data: data.data[0]

                        }
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                        $scope.totalAD= data.data[1]

                        return data.data;
                });
        $scope.actualizarPendientesPorConceptoColaActivacion();
               
        services.logVista($cookieStore.get('logedUser').login,"Indicadores Activacion y Desactivacion");
           };
        

        $scope.actualizarPendientesPorConceptoColaActivacion  = function (){
                 var data1=services.pendientesPorConceptoColaActivacion().then(function(data){

                        $scope.listado_colas =  data.data[0];
                        $scope.listado_conceptos =  data.data[1];
                        $scope.listado_colas.totales = 0;
                        $scope.listado_colas.total02 = 0;
                        $scope.listado_colas.total34 = 0;
                        $scope.listado_colas.total56 = 0;
                        $scope.listado_colas.total712 = 0;
                        $scope.listado_colas.total1324 = 0;
                        $scope.listado_colas.total2548 = 0;
                        $scope.listado_colas.totalmas48 = 0;

                        $scope.listado_conceptos.totales = 0;
                        $scope.listado_conceptos.total02 = 0;
                        $scope.listado_conceptos.total34 = 0;
                        $scope.listado_conceptos.total56 = 0;
                        $scope.listado_conceptos.total712 = 0;
                        $scope.listado_conceptos.total1324 = 0;
                        $scope.listado_conceptos.total2548 = 0;
                        $scope.listado_conceptos.totalmas48 = 0;

                });

        };

        $scope.myDataSourceAD = {
                        chart: {
                        caption: "Grafica General",
                        subcaption: "Pendientes A y D",
                        startingangle: "120",
                        showlabels: "0",
                        showlegend: "1",
                        enablemultislicing: "0",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        theme: "fint"
                        },
                        data: []

        };


          $scope.actualizarseguimientoGraficaAD   = function (){
    //TOMAR MUESTRA
                var data1=services.getSeguimientoActivacionGraficaAD().then(function(data){

                        $scope.myDataSourceAD = {
                        

                            chart: {
                                "caption": "Pedidos Cumplidos / Activación",
                                "xAxisName": "Colas",
                                "yAxisName": "Pedidos",
                                "numberPrefix": "",
                                "paletteColors": "#0075c2",
                                "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                "rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14"
                            },



                                data: data.data[0]

                        }
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                        $scope.totalAD= data.data[1]

                        return data.data;
                });
               
      
           };
        

          $scope.listadoactivacion2  = function (){ 

                services.getListadoActivacionTabla($scope.data.fechaini,$scope.data.fechafin,$scope.data.currentPage).then(function(data){

                   // console.log(data);
                       $scope.listadoactivaciontabla=data.data[1];
                       $scope.listadoactivaciontabla.totales = 0;
                       
                       //$scope.data.totalItems=data.data[1];

                    return data.data;
               });


        };


        $scope.myDataSourceAD = {
                        chart: {
                        caption: "Grafica General",
                        subcaption: "Pendientes A y D",
                        startingangle: "120",
                        showlabels: "0",
                        showlegend: "1",
                        enablemultislicing: "0",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        theme: "fint"
                        },
                        data: []

        };//--------------------------------

    $scope.actualizarGraficaADS   = function (){
    //TOMAR MUESTRA
                var data1=services.getPendientesSiebelGraficaAD().then(function(data){

                        $scope.myDataSourceAD = {

                           chart: {
                                "caption": "Grafica Activación / Siebel",
                                "subCaption": "Pendientes",
                                "xAxisName": "Pedidos Por Producto",
                                "yAxisName": "Pendientes",
                                "numberPrefix": "",
                                "paletteColors": "#0075c2",
                                "bgColor": "#ffffff",
                                "borderAlpha": "20",
                                "canvasBorderAlpha": "0",
                                "usePlotGradientColor": "0",
                                "plotBorderAlpha": "10",
                                "placevaluesInside": "0",
                                "rotatevalues": "0",
                                "valueFontColor": "#0075c2",
                                "showXAxisLine": "1",
                                "xAxisLineColor": "#999999",
                                "divlineColor": "#999999",
                                "divLineDashed": "1",
                                "showAlternateHGridColor": "0",
                                "subcaptionFontBold": "0",
                                "subcaptionFontSize": "14"
                            },
                                data: data.data[0]

                        }
                        var date1 = new Date();
                        var year    = date1.getFullYear();
                        var month   = $scope.doubleDigit(date1.getMonth()+1);
                        var day     = $scope.doubleDigit(date1.getDate());
                        var hour    = $scope.doubleDigit(date1.getHours());
                        var minute  = $scope.doubleDigit(date1.getMinutes());
                        var seconds = $scope.doubleDigit(date1.getSeconds());

                        $scope.lastUpdate=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                        $scope.totalAD= data.data[1]

                        return data.data;
             });

    };

     $scope.listadoactivacion3  = function (){

                services.getListadoActivacionSiebel($scope.data.fechaini,$scope.data.fechafin,$scope.data.currentPage).then(function(data){

                   // console.log(data);
                       $scope.listadoactivacionsiebel=data.data[1];
                       $scope.listadoactivacionsiebel.totales = 0;

                       //$scope.data.totalItems=data.data[1];

                    return data.data;
               });


        };


        $scope.myDataSourceAD = {
                        chart: {
                        caption: "Grafica General",
                        subcaption: "Pendientes A y D",
                        startingangle: "120",
                        showlabels: "0",
                        showlegend: "1",
                        enablemultislicing: "0",
                        slicingdistance: "15",
                        formatNumberScale: "0",
                        showpercentvalues: "1",
                        showpercentintooltip: "0",
                        plottooltext: "Age group : $label Total visit : $datavalue",
                        theme: "fint"
                        },
                        data: []

        };
                 
});



app.controller('PordenesCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, $timeout, services) {
    

        var userID=$cookieStore.get('logedUser').login;
        document.getElementById('logout').className="btn btn-md btn-danger";
        var divi=document.getElementById("logoutdiv");
        divi.style.visibility="visible";
        divi.style.position="relative";


        $scope.pedidos=[];
        $scope.historico_pedido=[];
        $scope.nombreUsuario="";
        $scope.listado_usuarios=[];
        $scope.verificarMalo=0;
        $scope.intervalLightKPIS='';
        $scope.pedidoinfo='Pedido';
        $timeout='';


        //$rootScope.actualView="reconfiguraciones";
        //$scope.iconcepto="14";
        $scope.popup='';
        $scope.cargando='';
        var pedidos=services.getPedidosUser(userID).then(function(data){
                        $scope.pedidos=data.data;
                        return data.data;
                });
        var original = $scope.pedidos;
        $scope.peds={};
        $scope.timeInit=0;
        $rootScope.logedUser=$cookieStore.get('logedUser');

        $scope.pedidos = angular.copy(original);


        $scope.intervalLightKPIS = setInterval(function(){
            $scope.actualizarLightKPIS();
        },60000);

//Comienza codigo de TIPS
//oculta los tips para que no se visualicen al inicio.
        document.getElementById("mostrarTIP").style.visibility = "hidden";
        document.getElementById("mostrarTIP").style.display = "none";
    $scope.listadoTips={};

//trae los tips para visualizarlo    
        services.getListadoTips().then(function(data){
            $scope.listadoTips=data.data[0];
            return data.data;
    });

//funcion que muestra los tip cuando se digita su busqueda.
    $scope.muestraBusquedaTip = function (texto) {

            if(texto.length==0||texto==''){
                document.getElementById("mostrarTIP").style.visibility = "hidden";
                document.getElementById("mostrarTIP").style.display = "none";   

                services.getListadoTips().then(function(data){
                    $scope.listadoTips=data.data[0];
                    return data.data;
                });      
            }
                if(texto.length>=3){
                document.getElementById("mostrarTIP").style.visibility = "visible";
                document.getElementById("mostrarTIP").style.display = "inline";            
            }            
    };



    $scope.AbreTips = function(id){

        $scope.nuevoBuscarTip="";

        document.getElementById("mostrarTIP").style.visibility = "hidden";
        document.getElementById("mostrarTIP").style.display = "none";

        services.getListadoTips().then(function(data){
            $scope.listadoTips=data.data[0];
            return data.data;
        });
 
        var link = "#/tips/visualizacionTip/"+id;
        window.open(window.location.pathname+ link, "_blank", "toolbar=yes, scrollbars=yes, resizable=yes, top=150, left=300, width=900, height=650");

    }; //Termina Código de TIPS

//Funcion para copyclipboard
    $scope.executeCopy= function executeCopy(text)
            {
                var input = document.createElement('textarea');
                document.body.appendChild(input);
                input.value = (text);
                //input.focus();
                input.select();
                document.execCommand('Copy');
                input.remove();
            };


    $scope.actualizarLightKPIS = function (){
                services.getLightKPIS().then(function(data){
            $rootScope.oldlightkpi=$rootScope.lightkpi;
                        $rootScope.lightkpi=data.data[0];


            if($rootScope.oldlightkpi==""||$rootScope.oldlightkpi==undefined){
                $rootScope.oldlightkpi=$rootScope.lightkpi;
            }

            //console.log($rootScope.lightkpi);
            //
            var arrayLength = $rootScope.lightkpi.length;
            var arrayLength2 = $rootScope.oldlightkpi.length;
            

            var negocioAsingaciones="<table class='table small table-striped table-hover table-bordered table-condensed'>"+
                                "<thead>"+
                                        "<th>Concepto</th>"+
                                        "<th>Cantidad</th>"+
                                "</thead>"+
                                "<tbody>";
            var negocioReconfiguracion="<table class='table small table-striped table-hover table-bordered table-condensed'>"+
                                "<thead>"+
                                        "<th>Concepto</th>"+
                                        "<th>Cantidad</th>"+
                                "</thead>"+
                                "<tbody>";

            var negocioOtros="<table class='table small table-striped table-hover table-bordered table-condensed'>"+
                                "<thead>"+
                                        "<th>Concepto</th>"+
                                        "<th>Cantidad</th>"+
                                "</thead>"+
                                "<tbody>";


            $rootScope.totalNegocioAsignacionesOld=$rootScope.totalNegocioAsignaciones;
            $rootScope.totalNegocioReconfiguracionOld=$rootScope.totalNegocioReconfiguracion;
            $rootScope.totalNegocioOtrosOld=$rootScope.totalNegocioOtros;


            $rootScope.totalNegocioAsignaciones=0;
            $rootScope.totalNegocioReconfiguracion=0;
            $rootScope.totalNegocioOtros=0;


            for (var i = 0; i < arrayLength; i++) {
                var counter=$rootScope.lightkpi[i].COUNTER;
                var concepto_id=$rootScope.lightkpi[i].CONCEPTO_ID;

                if(concepto_id=='PETEC'||concepto_id=='OKRED'||concepto_id=='PEOPP'||concepto_id=='19'||concepto_id=='O-13'||concepto_id=='O-15'||concepto_id=='O-106'){
                    negocioAsingaciones+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"</td></tr>";
                    $rootScope.totalNegocioAsignaciones=parseInt($rootScope.totalNegocioAsignaciones)+parseInt(counter);
                }else if(concepto_id=='14'||concepto_id=='99'||concepto_id=='O-101'){
                        negocioReconfiguracion+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"</td></tr>";
                    $rootScope.totalNegocioReconfiguracion=parseInt($rootScope.totalNegocioReconfiguracion)+parseInt(counter);
                                }else{
                    negocioOtros+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"</td></tr>";
                    $rootScope.totalNegocioOtros=parseInt($rootScope.totalNegocioOtros)+parseInt(counter);
                }
            }
            
            $rootScope.nasignacionesstyle={};
            $rootScope.nreconfiguracionstyle={};
            $rootScope.notrosstyle={};


            if($rootScope.totalNegocioAsignaciones>$rootScope.totalNegocioAsignacionesOld){
                            $rootScope.nasignacionesstyle.ICON="fa fa-arrow-circle-up fa-2x";
                                $rootScope.nasignacionesstyle.STYLE="red";
                        }else if($rootScope.totalNegocioAsignaciones<$rootScope.totalNegocioAsignacionesOld){
                                $rootScope.nasignacionesstyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.nasignacionesstyle.STYLE="green";
                        }else {
                                $rootScope.nasignacionesstyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.nasignacionesstyle.STYLE="gray";
                        }

                        if($rootScope.totalNegocioReconfiguracion>$rootScope.totalNegocioReconfiguracionOld){
                                $rootScope.nreconfiguracionstyle.ICON="fa fa-arrow-circle-up fa-2x";
                                $rootScope.nreconfiguracionstyle.STYLE="red";
                        }else if($rootScope.totalNegocioReconfiguracion<$rootScope.totalNegocioReconfiguracionOld){
                                $rootScope.nreconfiguracionstyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.nreconfiguracionstyle.STYLE="green";
                        }else {
                                $rootScope.nreconfiguracionstyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.nreconfiguracionstyle.STYLE="gray";
                        }


                        if($rootScope.totalNegocioOtros>$rootScope.totalNegocioOtrosOld){
                                $rootScope.notrosstyle.ICON="fa fa-arrow-circle-up fa-2x";
                                $rootScope.notrosstyle.STYLE="red";
                        }else if($rootScope.totalNegocioOtros<$rootScope.totalNegocioOtrosOld){
                                $rootScope.notrosstyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.notrosstyle.STYLE="green";
                        }else {
                                $rootScope.notrosstyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.notrosstyle.STYLE="gray";
                        }


            document.getElementById("nasignaciones").innerHTML=negocioAsingaciones+"</tbody></table>";
            document.getElementById("nreconfiguracion").innerHTML=negocioReconfiguracion+"</tbody></table>";
            document.getElementById("notros").innerHTML=negocioOtros+"</tbody></table>";

                        return data.data;
            });
    }

    $scope.$on(
        "$destroy",
        function(event){
            //$timeout.cancel(timer);
            clearInterval($scope.intervalLightKPIS);
    }); 


    $scope.savePedido = function(index,transaccion) {

        if($scope.transaccion===undefined){
            if($scope.verificarMalo==0){
            alert('Por favor diligenciar todos los campos de auditoria.');
            return;
            }
            else{
                $scope.transaccion={};
            }
        }

        //$scope.transaccion={};
        var loader = document.getElementById("class");
        loader.className='glyphicon glyphicon-refresh fa-spin';

        $scope.transaccion.USUARIO_ID=$rootScope.logedUser.login;
        $scope.transaccion.USERNAME=$rootScope.logedUser.name;
        $scope.transaccion.TIPO_ELEMENTO_ID="";
        $scope.transaccion.CONCEPTO_FINAL="";

        $scope.error="";
       
        for(i=0;i<index;i++){
            $scope.pedido={};

            angular.copy($scope.peds[i],$scope.pedido);

            console.log($scope.pedido);

            var verificaConcepto = $scope.isAuthorized($scope.pedido.CONCEPTO_ID);
            console.log(verificaConcepto);
            
            if($scope.pedido.estado===undefined){
                alert('Por favor diligenciar todos los campos.');
                return;
            }

            $scope.pedido.user=$rootScope.logedUser.login;
            $scope.pedido.username=$rootScope.logedUser.name;
            $scope.pedido.duracion=new Date().getTime() - $scope.timeInit;

            $scope.timeInit=new Date().getTime();
            var df=new Date($scope.pedido.duracion);
            $scope.pedido.duracion= $scope.doubleDigit(df.getHours()-19)+":"+ $scope.doubleDigit(df.getMinutes())+":"+ $scope.doubleDigit(df.getSeconds());

            $scope.pedido.pedido=$scope.pedido.PEDIDO_ID+$scope.pedido.SUBPEDIDO_ID+$scope.pedido.SOLICITUD_ID;
            $scope.pedido1=$scope.peds[i].PEDIDO_ID;//esta variable es para saber cual es el pedido actual en el sistema, esto con el fin de liberarlo cuando se quiera trabajar otro pedido
            
            $scope.pedido.actividad="ESTUDIO";
            $scope.pedido.fuente=$scope.pedido.FUENTE;
            $scope.pedido.fecha_inicio=$scope.fecha_inicio;
            if ($scope.transaccion.TIPO_ELEMENTO_ID!=""){
                $scope.transaccion.TIPO_ELEMENTO_ID=$scope.transaccion.TIPO_ELEMENTO_ID+"-"+$scope.pedido.TIPO_ELEMENTO_ID;    
            }
            else{
                $scope.transaccion.TIPO_ELEMENTO_ID=$scope.pedido.TIPO_ELEMENTO_ID;
            }
            if ($scope.transaccion.CONCEPTO_FINAL!=""){
                $scope.transaccion.CONCEPTO_FINAL=$scope.transaccion.CONCEPTO_FINAL+"-"+$scope.pedido.estado;    
            }
            else{
                $scope.transaccion.CONCEPTO_FINAL=$scope.pedido.estado;
            }
            $scope.transaccion.PEDIDO_ID=$scope.pedido.PEDIDO_ID;
            $scope.transaccion.CONCEPTO_ACTUAL=$scope.pedido.CONCEPTO_ID;
            //$scope.transaccion.CONCEPTO_FINAL=

                var date1 = new Date();
                    var year    = date1.getFullYear();
                    var month   = $scope.doubleDigit(date1.getMonth()+1);
                    var day     = $scope.doubleDigit(date1.getDate());
                    var hour    = $scope.doubleDigit(date1.getHours());
                    var minute  = $scope.doubleDigit(date1.getMinutes());
                    var seconds = $scope.doubleDigit(date1.getSeconds());

            $scope.pedido.fecha_fin=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds; 

            var dat= new Date();
                
        //$scope.pedido.statusfinal="hoho";
        //console.log($scope.pedido); 

       services.insertPedido($scope.pedido).then(function (status) {

                        
                        //$scope.pedido=$scope.pedidoSeguro;
                        //angular.copy($scope.pedidoSeguro,$scope.pedido);
                        //console.log($scope.pedido);
                        $scope.pedido.fecha=status.data['data'];
		   				$scope.pedidoinfo='Pedido';
                        //$scope.pedido.concepto_final=status.data['msg'];
		   				$scope.pedido.concepto_final="Sin validacion";
                       if($scope.pedido.concepto_final=="El pedido bloqueado por Usuario por mas de dos hora, fue liberado por el sistema, usuario no podra gestionarlo hasta despues de una hora!!!"){
                            $scope.error=$scope.pedido.concepto_final;
                                $scope.pedido={};
                                //$scope.pedidos="";
                        }
                       if($scope.pedido.concepto_final=="El pedido NO ha cambiado de concepto en Fenix!!!" || $scope.pedido.concepto_final=="ERROR!"){
                                alert($scope.pedido.concepto_final);
                                $scope.pedido.fecha="";
                                $scope.pedido.concepto_final="";
                        }else{

                            if($scope.pedido.concepto_final=="El pedido bloqueado por Usuario por mas de dos hora, fue liberado por el sistema, usuario no podra gestionarlo hasta despues de una hora!!!"){
                            $scope.error=$scope.pedido.concepto_final;
                                $scope.pedido={};
                                //$scope.pedidos="";
                            }else{
                                    $scope.historico_pedido=$scope.historico_pedido.concat(angular.copy($scope.pedido));
                                    if($scope.historico_pedido==""){
                                            $scope.historico_pedido=new Array();
                                    }
                                            $scope.pedido={};
                                            $scope.busy="";
                                            $scope.timeInit=new Date().getTime();
                                            date1 = new Date();
                                            year    = date1.getFullYear();
                                            month   = $scope.doubleDigit(date1.getMonth()+1);
                                            day     = $scope.doubleDigit(date1.getDate());
                                            hour    = $scope.doubleDigit(date1.getHours());
                                            minute  = $scope.doubleDigit(date1.getMinutes());
                                            seconds = $scope.doubleDigit(date1.getSeconds());

                                            $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                                            $scope.popup='';
                            }     
                        }
                        loader.className='';
                        
                        if($scope.peds.length==0){ 
                            $scope.pedidoinfo='Pedido';
                           }

                        var pedidosP=services.getPedidosUser(userID).then(function(data){
                                $scope.pedidos=data.data;
                                return data.data;
                        });
    
           }); 

        } // termina el ciclo for.


        if($scope.verificarMalo==0){
                $scope.transaccion.FECHA_INICIO=$scope.fecha_inicio;
                $scope.transaccion.FECHA_FIN=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
                var e = document.getElementById("dropUserID");
                $scope.transaccion.USUARIO_ID_GESTION = e.options[e.selectedIndex].text;
                $scope.transaccion.USUARIO_NOMBRE= document.getElementById("textNombre").value;
                if($scope.transaccion.ANALISIS=="RED MAL ASIGNADA"||$scope.transaccion.ANALISIS=="RENUMERACION MALA ASIGNACION"||$scope.transaccion.ANALISIS=="APROBO ALTO RIESGO"){
                $scope.transaccion.PUNTAJE=-1;
                }
                else{
                $scope.transaccion.PUNTAJE=0;   
                }

                    services.insertTransaccionORD($scope.transaccion).then(function (status) {
                       $scope.transaccion.fecha=status.data['data'];
                       $scope.transaccion.mensaje_final=status.data['msg'];
                       $scope.pedido={};

                    });
        }
        $scope.peds={};

        loader.className='';
        return status;  
            
    }


    $scope.buscarPedido = function(bpedido,iplaza) {
        	$scope.error="";
                $scope.peds={};
                $scope.mpedido={};
                $scope.busy="";
                $scope.error="";
                $scope.pedidoinfo='Pedido';

                //$scope.pedidoinfo='';
                var kami=services.buscarPedido(bpedido,iplaza,$scope.pedido1,$rootScope.logedUser.login,$rootScope.logedUser.name).then(function(data){
                        $scope.peds = data.data;         
			           console.log(data.status);
			var dat=data.status;
			//alert("'"+data.status+"'");
                        if(dat==204){
                                document.getElementById("warning").innerHTML="No hay Registros. Intente Cambiando de Plaza";
				$scope.error="No hay Registros. Intente Cambiando de Plaza";
                        }else{

                                if($scope.peds[0]=="PEDIDO_OCUPADO"){
                                        $scope.error="El pedido: "+$scope.peds[2] + " está ocupado por:" +$scope.peds[1] ;
										$scope.pedidoinfo='Pedido';
										$scope.peds=[];
                                        return;

                                }


                                document.getElementById("warning").innerHTML="";
                                $scope.pedido1=$scope.peds[0].PEDIDO_ID;
                                $scope.pedidoinfo=$scope.peds[0].PEDIDO_ID;
                                //$scope.pedidoinfo=$scope.peds[0].PEDIDO_ID;

					//alert("El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR);
				/*if($scope.peds[0].STATUS=="PENDI_PETEC"&&$scope.peds[0].ASESOR!=""){
					$scope.busy=$scope.peds[0].ASESOR;
					//alert("El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR);
					$scope.error="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;
				}*/

                        /*   console.log("este es el municipo: " + $scope.peds[0].MUNICIPIO_ID);
                            $scope.MUNICIPIO = $scope.peds[0].MUNICIPIO_ID;
                            buscar = /ANTCOL/;
                            $scope.validaMunicipio = buscar.test($scope.peds[0].MUNICIPIO_ID);
                            console.log("este es el municipo abreviado: " + $scope.validaMunicipio);
                                $scope.baby($scope.pedido1);*/
                        }

                        //$scope.MUNICIPIO = $scope.peds[0].MUNICIPIO_ID;
                          //  buscar = /ANTCOL/;
                            //$scope.validaMunicipio = buscar.test($scope.peds[0].MUNICIPIO_ID);
                            //console.log("esta es la validacion " + $scope.validaMunicipio);
                            //$rootScope.pagina_servicio_vecinos = $scope.peds[0].PAGINA_SERVICIO;
                            //console.log("esto es lo que retorna" + $scope.validaMunicipio + " y la pagina " + $scope.peds[0].PAGINA_SERVICIO);

                        return data.data;
                });
                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

		var pathy=$location.path();
                if(pathy=="/asignacion_ordenes/"){//esto es para controlar que no se vuelva a llamar este listado cuando se usa la vista de edicion-nuevo
                            services.getListadoUsuarios().then(function(data){
                            $scope.listado_usuarios=data.data[0];
                            return data.data;
                    });
                }

        };

$scope.isAuthorized = function(concept){

        var CargoID = parseInt($rootScope.logedUser.CARGO_ID);

        if(CargoID>5){

            if(concept=="PEXPQ") return false;
            if(concept=="PSERV") return false;
            if(concept=="ORDEN") return false;
            if(concept=="PXSLN") return false;
            if(concept=="PFACT") return false;
            if(concept=="CUMPL") return false;
            if(concept=="PORDE") return false;
            if(concept=="FACTU") return false;

        }



        /* codigo viejo

		if(concept=="PEXPQ") return false;
		if(concept=="PSERV") return false;
		if(concept=="ORDEN" && CargoID > 5) return false;
		if(concept=="PXSLN") return false;
        if(concept=="PFACT") return false;
        if(concept=="CUMPL") return false;
        //alert("concepto:"+concept+ " cargo: "+CargoID );
        if(concept=="PORDE" && CargoID > 5) return false; */

		//para controlar campos cuando el pedido esta ocupado por alguien mas....
		if($scope.busy!="") {
			//alert($scope.busy);
			return false;

		}
                 return true;
        }

        $rootScope.logout = function() {
                services.logout($rootScope.logedUser.login);
                $cookieStore.remove('logedUser');
                $rootScope.logedUser=undefined;
                $scope.pedidos={};
                clearInterval($scope.intervalLightKPIS);
                document.getElementById('logout').className="btn btn-md btn-danger hide";
                var divi=document.getElementById("logoutdiv");
                divi.style.position="absolute";
                divi.style.visibility="hidden";
                $location.path('/');
        };


    $scope.start = function(pedido) {
                var pedido1='';
        $scope.popup='';
        $scope.error="";

        if(JSON.stringify($scope.peds) !=='{}' && $scope.peds.length>0){
            //alert($scope.peds[0].PEDIDO_ID);
             pedido1=$scope.peds[0].PEDIDO_ID;
        }
        $scope.peds={};
        $scope.mpedido={};
        $scope.bpedido='';
        $scope.busy="";
        $scope.pedido1=pedido1;


        $scope.error="";

        var demePedidoButton=document.getElementById("iniciar");
        demePedidoButton.setAttribute("disabled","disabled");
        demePedidoButton.className = "btn btn-success disabled";


        var kami=services.demePedido($rootScope.logedUser.login,$scope.iconcepto,$scope.pedido1,$scope.iplaza,$rootScope.logedUser.name,'').then(function(data){
                $scope.peds = data.data;
            if(data.data==''){
                document.getElementById("warning").innerHTML="No hay Registros";
                $scope.error="No hay Registros";
            }else{
                document.getElementById("warning").innerHTML="";
                $scope.pedido1=$scope.peds[0].PEDIDO_ID;
                $scope.pedidoinfo=$scope.peds[0].PEDIDO_ID;

                                if($scope.peds[0].STATUS=="PENDI_PETEC"&&$scope.peds[0].ASESOR!=""){
                                        $scope.busy=$scope.peds[0].ASESOR;
                    $scope.error="El pedido esta ocupado por "+$scope.peds[0].ASESOR;
                                            //alert("El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR);
                        //$scope.popup='done';
                    //}
                                }

                $scope.baby($scope.pedido1);
            }
                    var demePedidoButton=document.getElementById("iniciar");
                    demePedidoButton.removeAttribute("disabled");
            demePedidoButton.className = "btn btn-success";
            return data.data;
            });
        //console.log("el pedido: "+$scope.pedido1);
        //console.log($scope.historico_pedidos);
        $scope.timeInit=new Date().getTime();
        var date1 = new Date();
        var year    = date1.getFullYear();
        var month   = $scope.doubleDigit(date1.getMonth()+1);
        var day     = $scope.doubleDigit(date1.getDate());
        var hour    = $scope.doubleDigit(date1.getHours());
        var minute  = $scope.doubleDigit(date1.getMinutes());
        var seconds = $scope.doubleDigit(date1.getSeconds());

        $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
        
        //alert($scope.fecha_inicio);

        var pathy=$location.path();

      if(pathy=="/asignacion_ordenes/"){//esto es para controlar que no se vuelva a llamar este listado cuando se usa la vista de edicion-nuevo
        services.getListadoUsuarios().then(function(data){
                    $scope.listado_usuarios=data.data[0];
                    return data.data;
            });
        };

    };

	$scope.manual = function() {
		$scope.peds={};
		$scope.error="";
		$scope.pedido1="";
		$scope.mpedido={};
		$scope.bpedido='';
		$scope.busy="";
		$scope.historico_pedido=[];
		$scope.mpedido.active=1;
		$scope.mpedido.fuente='FENIX_NAL';
		//$scope.pedidoinfo='Manual';
        $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

	};

    $scope.getNombre = function() {
       
        var e = document.getElementById("dropUserID");
        var value = e.options[e.selectedIndex].value;

        var nombre=document.getElementById('textNombre');
        nombre.value=value;
 
    };

        $scope.MaloVisible = function(index){

            var valor = new Array(index);

            for (i=0;i<index;i++){
                var eleme= document.getElementById("estadoGestion"+i);
                valor[i] = eleme.options[eleme.selectedIndex].value;      
                
            }   
        
            for(i=0;i<index;i++){
                if(valor[i]=="MALO"){
                    document.getElementById("motivoMalo").style.display = "block";
                    document.getElementById("motivoMalo").removeAttribute("style");
                    document.getElementById("theadAudi").style.visibility = "hidden";
                    document.getElementById("controlesAuditoria").style.display = "none";
                    var dropAnal = document.getElementById("dropAnalisis");
                    dropAnal.selectedIndex=-1;
                    var dropuser = document.getElementById("dropUserID");
                    dropuser.selectedIndex=-1;
                    var textNombre = document.getElementById("textNombre");
                    textNombre.value="";
                    var date = document.getElementById("toDate");
                    date.value="";
                    var textObser = document.getElementById("txtObservacion");
                    textObser.value="";
                    $scope.verificarMalo=1;
                    break;
                }
                else{
                    document.getElementById("motivoMalo").style.display = "none";
                    document.getElementById("theadAudi").style.visibility = "visible";
                    document.getElementById("controlesAuditoria").style.display = "block";
                    var dropMotivo = document.getElementById("dropMotivo"+i);
                    dropMotivo.selectedIndex=-1;
                    $scope.verificarMalo=0;
                    for(j=0;j<index;j++){
                        if(valor[j]=="MALO"){
                        $scope.verificarMalo=1;
                        }
                    }

                }
            }
     
        };


    $scope.MaloInvisibleCarga = function() {

    document.getElementById("motivoMalo").style.display = "none";
    document.getElementById("theadAudi").style.visibility = "visible";
    document.getElementById("controlesAuditoria").style.display = "block";

    };

    $scope.baby = function(pedido) {
        services.getPedidosPorPedido(pedido).then(function(data){
                      $scope.historico_pedido=data.data;
                      return data.data;
                 });        
    };


    $scope.doubleDigit= function (num){
        
        if(num<0){
            num=0;
        }
        
            if(num<=9){
                return "0"+num;
            }
            return num;
    };



});

//app.controller('mymodalcontroller', function ($scope,services)


app.controller('gponcontroller', function ($scope,$route, $rootScope, $location, $routeParams,$cookies,$cookieStore,services)
{
    $scope.header = 'Buscador GPON';
    $scope.footer = 'Gerencia Alistamiento';
    $scope.nods =[];
    $scope.nodshfc =[];
    $scope.resultado=[];
    $rootScope.actualView="gpon";


    var userID=$cookieStore.get('logedUser').login;
    document.getElementById('logout').className="btn btn-md btn-danger";
    var divi=document.getElementById("logoutdiv");
    divi.style.visibility="visible";
    divi.style.position="relative";
    $rootScope.logedUser=$cookieStore.get('logedUser');

    $rootScope.logout = function() {
        services.logout($rootScope.logedUser.login);
        $cookieStore.remove('logedUser');
        $rootScope.logedUser=undefined;
        $scope.pedidos={};
        document.getElementById('logout').className="btn btn-md btn-danger hide";
        var divi=document.getElementById("logoutdiv");
        divi.style.position="absolute";
        divi.style.visibility="hidden";
        $location.path('/');
     };




    $scope.myRightButton = function (bool) {
            alert('!!! first function call!');
        };

 
	var exporte=document.getElementById("exportar_gpon");
	exporte.setAttribute("disabled","disabled");
	exporte.className = "btn btn-success disabled";
 

    $scope.doubleDigit= function (num){

        if(num<0){
            num=0;
        }

            if(num<=9){
                return "0"+num;
            }
            return num;
        };

    $scope.closeToMe= function(po) {
        $scope.nods =[];
    }

	$scope.csvGPON = function(olt,tarjeta,puerto){
		var login=$cookieStore.get('logedUser').login;
                services.getCsvGPON(olt,tarjeta,puerto,login).then(function(data){
                        window.location.href="tmp/"+data.data[0];
                        return data.data;
                });
	}

    $scope.buscarGPON = function(olt,tarjeta,puerto) {

	var hora=new Date().getHours();

	/*if(hora==12||hora==13||hora==5||hora==6){
		alert("Fenix Stby esta abajo!!");
		return;
	}*/

        $scope.error="";
        var kami=services.getServicesGPON(olt,tarjeta,puerto).then(function(data){

                        $scope.resultado = data.data;
			console.log("info: '"+$scope.resultado+"'");
			console.log(data.data);
                        if($scope.resultado==''){
				$scope.error="Este nodo no existe.";
				var exporte=document.getElementById("exportar_gpon");
			        exporte.setAttribute("disabled","disabled");
			        exporte.className = "btn btn-success disabled";
			
                                //document.getElementById("warning").innerHTML="Este nodo no existe.";
                        }else{
				$scope.error=undefined;
                                //document.getElementById("warning").innerHTML="";
				var exporte=document.getElementById("exportar_gpon");
			        exporte.removeAttribute("disabled","disabled");
			        exporte.className = "btn btn-success btn-sm";

                        }
                        return data.data;
                });

                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        };

});



app.controller('vecinoscontroller', function ($scope,$route, $rootScope, $location, $routeParams,$cookies,$cookieStore,services)
{
 $scope.pagina = "";
 $scope.pagina_inicial = "";
 $scope.pagina_final = "";
   //$scope.pagina=$location.url(); 
    $scope.pagina = $routeParams.pagina_servicio_vecinos;
    $scope.pagina_inicial = $scope.pagina.slice(0,11) + '0000000';
    $scope.pagina_final = $scope.pagina.slice(0,11) + '9999999';
    //console.log('esta es la pag: ' + $scope.pagina);
    //console.log('esta es la pagInicial: ' + $scope.pagina_inicial);
    //console.log('esta es la pagFinal: ' + $scope.pagina_final);
    $rootScope.actualView="vecinos";



    var userID=$cookieStore.get('logedUser').login;
    document.getElementById('logout').className="btn btn-md btn-danger";
    var divi=document.getElementById("logoutdiv");
    divi.style.visibility="visible";
    divi.style.position="relative";
    $rootScope.logedUser=$cookieStore.get('logedUser');

    $rootScope.logout = function() {
        services.logout($rootScope.logedUser.login);
        $cookieStore.remove('logedUser');
        $rootScope.logedUser=undefined;
        $scope.pedidos={};
        document.getElementById('logout').className="btn btn-md btn-danger hide";
        var divi=document.getElementById("logoutdiv");
        divi.style.position="absolute";
        divi.style.visibility="hidden";
        $location.path('/');
     };


    $scope.doubleDigit= function (num){

        if(num<0){
            num=0;
        }

            if(num<=9){
                return "0"+num;
            }
            return num;
        };

    //$scope.vecinos_pagina = [];
    services.getVecinosPagina($scope.pagina_inicial, $scope.pagina_final).then(function(data){
        //$scope.vecinos_pagina=data.data[0];
        var prueba = data.data;
        $scope.total = prueba[0];
        $scope.vecinos_pagina=prueba[1];
        console.log('total:' + $scope.total );
        return data.data;
        });

                $scope.search = {};
             $scope.getKeysOfCollection = function(obj) {
                obj = angular.copy(obj);
                if (!obj) {
                  return [];
                }
                return Object.keys(obj);
              }


    $scope.pageChanged = function(forma) {
            $scope.vecinos_pagina();
    };
  
});



app.controller('distanciacontroller', function ($scope,$route, $rootScope, $location, $routeParams,$cookies,$cookieStore,services)
{
    $scope.header = 'Calculo distancia Cobre';
    $scope.footer = 'Gerencia Alistamiento';
    $scope.nods =[];
    $scope.nodshfc =[];

    $scope.distancia="";
    $scope.capacidad="";


    $scope.myRightButton = function (bool) {
            alert('!!! first function call!');
     };

    $scope.doubleDigit= function (num){

        if(num<0){
            num=0;
        }

            if(num<=9){
                return "0"+num;
            }
            return num;
        };



    $scope.closeToMe= function(po) {
        $scope.nods =[];
    }

    $scope.demeCapacidad = function(distancia) {

            $scope.error="";
            var kami=services.demeCapacidadPorDistancia(distancia).then(function(data){
            $scope.capacidad=data.data[1];
                        return data.data;
                });

        
    };

        $scope.buscarCapacidadCobre = function(armario) {
            $scope.search = {};
            $scope.error="";
        var kami=services.buscarCapaCobre(armario).then(function(data){
                        $scope.resultado = data.data;
            //console.log("info: '"+$scope.resultado+"'");
            console.log(data.data);
                        if($scope.resultado==''){
                $scope.error="Armario "+armario+" no existe.";   
                 $scope.resultado=undefined;             
                                //document.getElementById("warning").innerHTML="Este nodo no existe.";
                        }else{
                            $scope.error=undefined;
                       }
                        return data.data;
                });
        };
                $scope.search = {};
             $scope.getKeysOfCollection = function(obj) {
                obj = angular.copy(obj);
                if (!obj) {
                  return [];
                }
                return Object.keys(obj);
              }


});

// Controlador para Gestion En Siebel -----------------------------------------------
app.controller('siebelAsignacionesCtrl', function ($scope, $rootScope, $location, $routeParams,$cookies,$cookieStore, $timeout, services) {



	// Basura del logueo ---------------------------------
		$rootScope.logedUser=$cookieStore.get('logedUser');
		var userID=$cookieStore.get('logedUser').login;
			document.getElementById('logout').className="btn btn-md btn-danger";
			var divi=document.getElementById("logoutdiv");
				divi.style.visibility="visible";
				divi.style.position="relative";


		$rootScope.logout = function() {
					services.logout(userID);
					$cookieStore.remove('logedUser');
					$rootScope.logedUser=undefined;
					$scope.pedidos={};
					clearInterval($scope.intervalLightKPIS);
					document.getElementById('logout').className="btn btn-md btn-danger hide";
					var divi=document.getElementById("logoutdiv");
					divi.style.position="absolute";
					divi.style.visibility="hidden";
					$location.path('/');
			};



	//  ---------------------------------Basura del logueo

	// Inicio de Variables ---------------------------------
		$scope.pedidos=[];
		$scope.pedidosUnicos='';
		$scope.historico_pedido=[];
		$rootScope.actualView="tx/siebel_asignaciones";
		$scope.iconcepto="COBERTURA";
		$scope.popup='';
		$scope.intervalLightKPIS='';
		$scope.pedidoinfo='';
		$scope.errorDatos=null;
		$scope.accRdy=false;
		$scope.fecha_inicio=null;
		$scope.fecha_fin=null;

		var pedidos=services.getPedidosUser(userID).then(function(data){
				$scope.pedidos=data.data[0];
				$scope.pedidosUnicos=data.data[1];
				return data.data;
					});

		 var original = $scope.pedidos;
		 var originalUnico=$scope.pedidosUnicos;

		$scope.peds={};
		$scope.timeInit=0;
		$scope.pedidos = angular.copy(original);

		$scope.pedidoIsActive=false;

	// ---------------------------------Inicio de Variables


// Disque Light KPI --------------------------------------------------------------

	$scope.intervalLightKPIS = setInterval(function(){
                $scope.actualizarLightKPIS();
           },60000);

        $scope.actualizarLightKPIS = function (){
                services.getLightKPIS().then(function(data){
			$rootScope.oldlightkpi=$rootScope.lightkpi;
                        $rootScope.lightkpi=data.data[0];


			if($rootScope.oldlightkpi==""||$rootScope.oldlightkpi==undefined){
				$rootScope.oldlightkpi=$rootScope.lightkpi;
			}

			//console.log($rootScope.lightkpi);
			//
			var arrayLength = $rootScope.lightkpi.length;
			var arrayLength2 = $rootScope.oldlightkpi.length;


			var negocioAsingaciones="<table class='table small table-striped table-hover table-bordered table-condensed'>"+
                                "<thead>"+
                                        "<th>Concepto</th>"+
                                        "<th>Cantidad</th>"+
                                "</thead>"+
                                "<tbody>";
			var negocioReconfiguracion="<table class='table small table-striped table-hover table-bordered table-condensed'>"+
                                "<thead>"+
                                        "<th>Concepto</th>"+
                                        "<th>Cantidad</th>"+
                                "</thead>"+
                                "<tbody>";

			var negocioOtros="<table class='table small table-striped table-hover table-bordered table-condensed'>"+
                                "<thead>"+
                                        "<th>Concepto</th>"+
                                        "<th>Cantidad</th>"+
                                "</thead>"+
                                "<tbody>";


			$rootScope.totalNegocioAsignacionesOld=$rootScope.totalNegocioAsignaciones;
			$rootScope.totalNegocioReconfiguracionOld=$rootScope.totalNegocioReconfiguracion;
			$rootScope.totalNegocioOtrosOld=$rootScope.totalNegocioOtros;


			$rootScope.totalNegocioAsignaciones=0;
			$rootScope.totalNegocioReconfiguracion=0;
			$rootScope.totalNegocioOtros=0;


			for (var i = 0; i < arrayLength; i++) {
				var counter=$rootScope.lightkpi[i].COUNTER;
				var concepto_id=$rootScope.lightkpi[i].CONCEPTO_ID;

				if(concepto_id=='PETEC'||concepto_id=='OKRED'||concepto_id=='PETEC-BOG'||concepto_id=='PEOPP'||concepto_id=='19'||concepto_id=='O-13'||concepto_id=='O-15'||concepto_id=='O-106'||concepto_id=='PUMED'||concepto_id=='COBERTURA'||concepto_id=='CONSTRUCCION'||concepto_id=='DISENO'||concepto_id=='DISPONIBILIDAD'){
					negocioAsingaciones+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"<font color='DarkGray'><strong><i>&nbsp;&nbsp; Servicios</strong></i></font></td></tr>";
					$rootScope.totalNegocioAsignaciones=parseInt($rootScope.totalNegocioAsignaciones)+parseInt(counter);
				}else if(concepto_id=='14'||concepto_id=='99' ||concepto_id=='92'){
                        negocioReconfiguracion+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"<font color='DarkGray'><strong><i>&nbsp;&nbsp; Pedidos</strong></i></font></td></tr>";
					$rootScope.totalNegocioReconfiguracion=parseInt($rootScope.totalNegocioReconfiguracion)+parseInt(counter);
                                }else if(concepto_id=='O-101'){
                                    negocioReconfiguracion+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"<font color='DarkGray'><strong><i>&nbsp;&nbsp; Servicios</strong></i></font></td></tr>";
                                    $rootScope.totalNegocioReconfiguracion=parseInt($rootScope.totalNegocioReconfiguracion)+parseInt(counter);
                                    }else{
					                   negocioOtros+="<tr><td><a href='./#/registros/"+concepto_id+"'>"+concepto_id+"</a></td><td>"+counter+"<font color='DarkGray'><strong><i>&nbsp;&nbsp; Servicios</strong></i></font></td></tr>";
					                   $rootScope.totalNegocioOtros=parseInt($rootScope.totalNegocioOtros)+parseInt(counter);
				                    }
			}

			$rootScope.nasignacionesstyle={};
			$rootScope.nreconfiguracionstyle={};
			$rootScope.notrosstyle={};


			if($rootScope.totalNegocioAsignaciones>$rootScope.totalNegocioAsignacionesOld){
                        	$rootScope.nasignacionesstyle.ICON="fa fa-arrow-circle-up fa-2x";
                             	$rootScope.nasignacionesstyle.STYLE="red";
                        }else if($rootScope.totalNegocioAsignaciones<$rootScope.totalNegocioAsignacionesOld){
                                $rootScope.nasignacionesstyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.nasignacionesstyle.STYLE="green";
                        }else {
                                $rootScope.nasignacionesstyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.nasignacionesstyle.STYLE="gray";
                        }

                        if($rootScope.totalNegocioReconfiguracion>$rootScope.totalNegocioReconfiguracionOld){
                                $rootScope.nreconfiguracionstyle.ICON="fa fa-arrow-circle-up fa-2x";
                                $rootScope.nreconfiguracionstyle.STYLE="red";
                        }else if($rootScope.totalNegocioReconfiguracion<$rootScope.totalNegocioReconfiguracionOld){
                                $rootScope.nreconfiguracionstyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.nreconfiguracionstyle.STYLE="green";
                        }else {
                                $rootScope.nreconfiguracionstyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.nreconfiguracionstyle.STYLE="gray";
                        }


                        if($rootScope.totalNegocioOtros>$rootScope.totalNegocioOtrosOld){
                                $rootScope.notrosstyle.ICON="fa fa-arrow-circle-up fa-2x";
                                $rootScope.notrosstyle.STYLE="red";
                        }else if($rootScope.totalNegocioOtros<$rootScope.totalNegocioOtrosOld){
                                $rootScope.notrosstyle.ICON="fa fa-arrow-circle-down fa-2x";
                                $rootScope.notrosstyle.STYLE="green";
                        }else {
                                $rootScope.notrosstyle.ICON="fa fa-minus-circle fa-2x";
                                $rootScope.notrosstyle.STYLE="gray";
                        }


			document.getElementById("nasignaciones").innerHTML=negocioAsingaciones+"</tbody></table>";
			document.getElementById("nreconfiguracion").innerHTML=negocioReconfiguracion+"</tbody></table>";
			document.getElementById("notros").innerHTML=negocioOtros+"</tbody></table>";

                        return data.data;
                });
        }

        $scope.$on(
                "$destroy",
                        function( event ) {
                            $timeout.cancel($scope.intervalLightKPIS);
                            clearInterval($scope.intervalLightKPIS);
          });

	// --------------------------------------------------------------Disque Light KPI



	// DemePedido --------------------------------------------------------------
	$scope.baby = function(pedido) {
		//console.log(pedido);
		services.getPedidosPorPedido(pedido).then(function(data){
                     // console.log(data.data);
                      $scope.historico_pedido=data.data;
                      return data.data;
                 });
	};

	$scope.start = function(pedido) {

		var pedido1='';
		$scope.popup='';
		$scope.errorDatos=null;
		$scope.InfoPedido=[];
		$scope.fecha_inicio=null;
		$scope.accRdy=false;
		$scope.InfoGestion={};
		$scope.InfoPedido.INCIDENTE='NO';
		$scope.pedidoIsGuardado=false;

		if(JSON.stringify($scope.peds) !=='{}' && $scope.peds.length>0){
			//alert($scope.peds[0].PEDIDO_ID);
			 pedido1=$scope.peds[0].PEDIDO_ID;

		}
		$scope.peds={};
		$scope.mpedido={};
		$scope.bpedido='';
		$scope.busy="";
		$scope.pedido1=pedido1;
		$scope.error="";
		$scope.iplaza='TODOS';
		$scope.fuente="SIEBEL";

		var demePedidoButton=document.getElementById("iniciar");
			demePedidoButton.setAttribute("disabled","disabled");
			demePedidoButton.className = "btn btn-success btn-DemePedido-xs disabled";

		var kami=services.demePedido($rootScope.logedUser.login,$scope.iconcepto,$scope.pedido1,$scope.iplaza,$rootScope.logedUser.name,'',$scope.fuente).then(function(data){

			$scope.peds = data.data;

			console.log($scope.peds);

			if(data.data==''){

				document.getElementById("warning").innerHTML="No hay Registros. Intente Cambiando de Estado.";
				$scope.errorDatos="No hay Registros. Intente Cambiando de Estado.";
			}else{

				document.getElementById("warning").innerHTML="";
				$scope.pedido1=$scope.peds[0].PEDIDO_ID;
                $scope.pedidoinfo=$scope.peds[0].PEDIDO_ID;
				$scope.pedidoIsActive=true;
				$scope.errorDatos=null;
				$scope.fecha_inicio=$rootScope.fechaProceso();

					if($scope.peds[0].STATUS=="PENDI_PETEC"&&$scope.peds[0].ASESOR!=""){
                            $scope.busy=$scope.peds[0].ASESOR;
							$scope.errorDatos="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.peds[0].ASESOR;

                                }

				$scope.baby($scope.pedido1);

			}
	      		var demePedidoButton=document.getElementById("iniciar");
	                demePedidoButton.removeAttribute("disabled");
					demePedidoButton.className = "btn btn-success btn-DemePedido-xs";
					return data.data;
    		});

    	};

	// -------------------------------------------------------------- DemePedido

	// BuscarPedido ---------------------------------------------------------------

	$scope.buscarPedido = function(buscar,pedidoinfo) {

			var pedido1='';
			$scope.popup='';
			$scope.errorDatos=null;
			$scope.InfoPedido=[];
			$scope.fecha_inicio=null;
			$scope.accRdy=false;
			$scope.InfoGestion={};
			$scope.InfoPedido.INCIDENTE='NO';
			$scope.pedidoIsGuardado=false;

			$scope.pedidoActual=pedidoinfo;

			$scope.buscar=buscar;



          var kami=services.getBuscarOfertaSiebelAsignaciones(buscar,$scope.pedidoActual,$rootScope.logedUser.login).then(

			  function(data){

				 if(data.data==''){
						$scope.errorDatos="No hay Registros. Intente con otra oferta";
					 	$scope.peds={};
						$scope.mpedido={};
						$scope.busy="";
						$scope.pedidoIsActive=false;
					}else{

						$scope.peds = data.data[1];
				  	   	$scope.ocupado=data.data[0];
						$scope.pedido1=$scope.peds[0].PEDIDO_ID;
				  	   	$scope.pedidoinfo=$scope.peds[0].PEDIDO_ID;

						var dat=data.status;
						//alert("'"+data.status+"'");
							if(dat==204){
							   document.getElementById("warning").innerHTML="No hay Registros. Intente Cambiando de Estado";
								$scope.errorDatos="No hay Registros. Intente Cambiando de Estado";
								$scope.peds={};
								$scope.mpedido={};
								$scope.busy="";
								$scope.pedidoIsActive=false;

							}else{

								   if($scope.ocupado==true){
													$scope.busy=$scope.peds[0].ASESOR;
													$scope.errorDatos="El pedido "+$scope.pedido1+" esta ocupado por "+$scope.busy;
													return;

									 }
											$scope.errorDatos=null;
											$scope.pedidoIsActive=true;
											$scope.fecha_inicio=$rootScope.fechaProceso();


									return data.data;
							}
					}
			  });


        };











	// --------------------------------------------------------------- BuscarPedido

	// GuardarPedido --------------------------------------------------------------

	$scope.listarEstados=function(){

			services.getlistadoOpcionesSiebelAsignaciones().then(

			  function(data){

				  $scope.Observaciones=data.data[0];
				  $scope.Estados=data.data[1];
				  $scope.listadoOpcionesSiebel=data.data[2];

				  return data.data;


					}
			  , function errorCallback(response,status) {
				  //console.log(status);
					$scope.errorDatos="Sin Procesos";

				  }
			  );
		};

	$scope.listarEstados();

	$scope.onChangeAccion=function(){
      $scope.accRdy=true;
 };

	$scope.guardarPedido=function(InfoPedido,gestion,status){

		$scope.fecha_fin=$rootScope.fechaProceso();
		$scope.stautsGo=status[0].STATUS;

		//console.log($scope.stautsGo);

		$scope.InfoGestion={
			ID:gestion.ID,
			OFERTA:gestion.PEDIDO_ID,
			MUNICIPIO_ID:gestion.MUNICIPIO_ID,
			TRANSACCION:gestion.DESC_TIPO_TRABAJO,
			ESTADO:gestion.CONCEPTO_ID,
			FECHA:gestion.FECHA_ESTADO,
			DURACION:null,
			INCIDENTE:InfoPedido.INCIDENTE,
			FECHA_INICIO:$scope.fecha_inicio,
			FECHA_FIN:$scope.fecha_fin,
			ESTADO_FINAL:InfoPedido.ESTADO_PROCESO,
			OBSERVACION:InfoPedido.OBSERVACIONES_PROCESO,
			USUARIO:$rootScope.logedUser.login,
			STATUS:$scope.stautsGo
			}

		//console.log($scope.InfoGestion);


		services.insertTransaccionNCA($scope.InfoGestion).then(

			  function(data){

				  $scope.pedidoIsGuardado = true;
				  $scope.errorDatos = null;
				  $scope.InfoPedido = [];
				  $scope.fecha_inicio = null;
				  $scope.fecha_fin = null;
				  $scope.accRdy = false;
				  $scope.InfoGestion = {};
				  $scope.pedidoOcupado = false
				  $scope.pedidoIsActive = false
				  $scope.peds = {};
				  $scope.mpedido = {};
				  $scope.bpedido = '';
				  $scope.busy = "";
				  $scope.error = "";
				  $scope.iplaza = 'TODOS';
				  $scope.fuente = "SIEBEL";
				  $scope.buscar = null;
				  return data.data;


					}
			  , function errorCallback(response,status) {
				  //console.log(status);
					$scope.errorDatos="No se pudo guardar";

				  }
			  );







	};


	// -------------------------------------------------------------- GuardarPedido







$scope.doubleDigit= function (num){

		if(num<0){
			num=0;
		}

	        if(num<=9){
        	    return "0"+num;
	        }
        	return num;
	    };


});
// -----------------------------------------------Controlador para Gestion En Siebel

app.controller('mymodalcontroller', function ($scope,$route, $rootScope, $location, $routeParams,$cookies,$cookieStore,services)
{
    $scope.header = 'Buscador Nodos CMTS';
    $scope.footer = 'Gerencia Alistamiento';
    $scope.nods =[];
    $scope.nodshfc =[];
    $scope.myRightButton = function (bool) {
            alert('!!! first function call!');
        };

	$scope.distancia='';
	$scope.resultado='';

    $scope.doubleDigit= function (num){
        
        if(num<0){
            num=0;
        }
        
            if(num<=9){
                return "0"+num;
            }
            return num;
        };

    $scope.closeToMe= function(po) {
        $scope.nods =[];
    }

    $scope.buscarCmts = function(nnodo) {
        
        $scope.error="";
        var kami=services.buscarCmts(nnodo,$scope.nodo_id).then(function(data){
                        $scope.nods = data.data[1];
                        $scope.nodshfc = data.data[0];
                        if(data.data==''){
                                document.getElementById("warning").innerHTML="Este nodo no existe.";
                        }else{
                                document.getElementById("warning").innerHTML="";
                        }
                        return data.data;
                });

                $scope.timeInit=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = $scope.doubleDigit(date1.getMonth()+1);
                var day     = $scope.doubleDigit(date1.getDate());
                var hour    = $scope.doubleDigit(date1.getHours());
                var minute  = $scope.doubleDigit(date1.getMinutes());
                var seconds = $scope.doubleDigit(date1.getSeconds());

                $scope.fecha_inicio=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;

        };
 
});


app.directive('modal', function () {
    return {
        restrict: 'EA',
        scope: {
            title1: '=modalTitle',
            header: '=modalHeader',
            body: '=modalBody',
            footer: '=modalFooter',
            callbackbuttonleft: '&ngClickLeftButton',
            callbackbuttonright: '&ngClickRightButton',
            handler: '=lolo'
        },
        templateUrl: 'partials/partialmodal.html',
        transclude: true,
        controller: function ($scope) {
            $scope.handler = 'pop'; 
        }
    };
});

 app.directive("digitalClock",function($timeout,dateFilter){
   return function(scope,element,attrs) {
     
     element.addClass('alert alert-info text-center clock');
     
     scope.updateClock = function(){
       $timeout(function(){
         element.text(dateFilter(new Date(), 'HH:mm:ss')); 
         scope.updateClock();
       },1000);
     };
     
     scope.updateClock();

   };
 });
 
 //Directiva para Mostrar un gif mientras carga el http request
 app.directive('loading',   ['$http' ,function ($http)
    {
        return {
            restrict: 'A',
            link: function (scope, elm, attrs)
            {
                scope.isLoading = function () {
                    return $http.pendingRequests.length > 0;
                };

                scope.$watch(scope.isLoading, function (v)
                {
                    if(v){
                        elm.show();
                    }else{
                        elm.hide();
                    }
                });
            }
        };

    }]);

app.directive('popover', function() {
   return function(scope, elem) {
      elem.popover();
   }
});



app.directive('tooltip', function() {
   return function(scope, elem) {
      elem.tooltip();
   }
});

// Filtros ------------------------------------------

app.filter('timeago', function() {
        return function(input, p_allowFuture) {

            var substitute = function (stringOrFunction, number, strings) {
                    var string = angular.isFunction(stringOrFunction) ? stringOrFunction(number, dateDifference) : stringOrFunction;
                    var value = (strings.numbers && strings.numbers[number]) || number;
                    return string.replace(/%d/i, value);
                },
                nowTime = (new Date()).getTime(),
                date = (new Date(input)).getTime(),
                //refreshMillis= 6e4, //A minute
                allowFuture = p_allowFuture || false,
                strings= {
                    prefixAgo: 'hace',
                    prefixFromNow: '',
                    suffixAgo: "",
                    suffixFromNow: "ahora",
                    seconds: "unos segs",
                    minute: "un min",
                    minutes: "%d mins",
                    hour: "una hora",
                    hours: " %d horas",
                    day: "un dia",
                    days: "%d dias",
                    month: "un mes",
                    months: "%d meses",
                    year: "un ano",
                    years: "%d anos"
                },
                dateDifference = nowTime - date,
                words,
                seconds = Math.abs(dateDifference) / 1000,
                minutes = seconds / 60,
                hours = minutes / 60,
                days = hours / 24,
                years = days / 365,
                separator = strings.wordSeparator === undefined ?  " " : strings.wordSeparator,


                prefix = strings.prefixAgo,
                suffix = strings.suffixAgo;

            if (allowFuture) {
                if (dateDifference < 0) {
                    prefix = strings.prefixFromNow;
                    suffix = strings.suffixFromNow;
                }
            }

            words = seconds < 45 && substitute(strings.seconds, Math.round(seconds), strings) ||
            seconds < 90 && substitute(strings.minute, 1, strings) ||
            minutes < 45 && substitute(strings.minutes, Math.round(minutes), strings) ||
            minutes < 90 && substitute(strings.hour, 1, strings) ||
            hours < 24 && substitute(strings.hours, Math.round(hours), strings) ||
            hours < 42 && substitute(strings.day, 1, strings) ||
            days < 30 && substitute(strings.days, Math.round(days), strings) ||
            days < 45 && substitute(strings.month, 1, strings) ||
            days < 365 && substitute(strings.months, Math.round(days / 30), strings) ||
            years < 1.5 && substitute(strings.year, 1, strings) ||
            substitute(strings.years, Math.round(years), strings);
      //console.log(prefix+words+suffix+separator);
      prefix.replace(/ /g, '')
      words.replace(/ /g, '')
      suffix.replace(/ /g, '')
      return (prefix+' '+words+' '+suffix+' '+separator);

        };
    });

app.filter('sumarNum', function() {
        return function(data, key) {
            if (typeof(data) === 'undefined' || typeof(key) === 'undefined') {
                return 0;
            }

            var sum = 0;
            angular.forEach(data, function(obj, objKey){
                sum+= parseFloat(obj[key]);
            });

            return sum;

        };
});

//  ------------------------------------------ Filtros


app.config( [
    '$compileProvider',
    function( $compileProvider )
    {   
        $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|sip|file):/);
        // Angular before v1.2 uses $compileProvider.urlSanitizationWhitelist(...)
    }
]);

app.config(['$routeProvider',
  function($routeProvider) {
    $routeProvider.
      when('/customers', {
        title: 'Customers',
        templateUrl: 'partials/customers.html',
        controller: 'listCtrl'
      })

	// ASIGNACIONES ------------------------------------------
      .when('/edit-customer/:customerID', {
        title: 'Edit Customers',
        templateUrl: 'partials/edit-customer.html',
        controller: 'editCtrl',
        resolve: {
          customer: function(services, $route){
            var customerID = $route.current.params.customerID;
            return services.getCustomer(customerID);
          }
        }
      })
      .when('/asignacion/:userID', {
        title: 'Asignacion',
        templateUrl: 'partials/asignacion.html',
        controller: 'AsignacionesCtrl',
      })
	.when('/asignacion/', {
         title: 'Asignacion',
         templateUrl: 'partials/asignacion.html',
         controller: 'AsignacionesCtrl'
      })

	.when('/reconfiguracion/', {
         title: 'Reconfiguracion',
         templateUrl: 'partials/reconfiguracion.html',
         controller: 'ReconfiguracionCtrl'
      })

	.when('/nca/', {
         title: 'NCA',
         templateUrl: 'partials/nca.html',
         controller: 'NCACtrl'
      })

	 .when('/nca/transaccion', {
         title: 'NCA',
         templateUrl: 'partials/transaccion-nca.html',
         controller: 'NCACtrl'
      })


	  .when('/admontips/nuevoTip', {
         title: 'Nuevo Tip',
         templateUrl: 'partials/editTips.html',
         controller: 'nuevoTipsCtrl'
      })

      .when('/tips/', {
         title: 'TIPS',
         templateUrl: 'partials/tips.html',
         controller: 'tipsCtrl',
         reloadOnSearch: false
      })

      .when('/tips/visualizacionTip/:tipID', {
         title: 'Tip',
         templateUrl: 'partials/soloTip.html',
         controller: 'unicoTipCtrl',
         resolve: {
            transtip: function(services, $route){
                var tipID = $route.current.params.tipID;
                return services.getVisualizacionTip(tipID);
            }
        }
    })

	  .when('/admontips/', {
         title: 'Administración Tips',
         templateUrl: 'partials/admontips.html',
         controller: 'AdmonTipsCtrl'
      })

      .when('/admontips/edicionTip/:tipID', {
         title: 'Edición Tips',
         templateUrl: 'partials/editTips.html',
         controller: 'editTipsCtrl',
         resolve: {
            transtip: function(services, $route){
                var tipID = $route.current.params.tipID;
                return services.getTransaccionTip(tipID);
            }
        }
    })

	.when('/registros/', {
		 title: 'Registros',
		 templateUrl: 'partials/registros.html',
		 controller: 'RegistrosCtrl'
	})

	.when('/registros/:conceptoid', {
	 title: 'Registros',
	 templateUrl: 'partials/registros.html',
	 controller: 'RegistrosCtrl'
	})


	 .when('/asignacion_ordenes/', {
		 title: 'Ordenes',
		 templateUrl: 'partials/asignacion_ordenes.html',
		 controller: 'PordenesCtrl'
	  })

		.when('/ord/', {
		 title: 'ORD',
		 templateUrl: 'partials/oxxx.html',
		 controller: 'PordenesCtrl'
	  })

		.when('/ord/ordtransaccion', {
		 title: 'ORD',
		 templateUrl: 'partials/transacciones_oxxx.html',
		 controller: 'PordenesCtrl'
	  })

		.when('/tx/siebel_asignaciones', {
		 title: 'DemePedido Siebel Asignaciones',
		 templateUrl: 'partials/transacciones/siebel_asignaciones.html?n=1',
		 controller: 'siebelAsignacionesCtrl'
	  })


	//  ------------------------------------------ASIGNACIONES

     .when('/actividades/', {
         title: 'Actividades',
         templateUrl: 'partials/actividades.html',
         controller: 'ActividadesCtrl'
      })


      .when('/actividades/transaccion', {
         title: 'Actividades',
         templateUrl: 'partials/transaccion-activadades.html',
         controller: 'ActividadesCtrl'
      })

      .when('/alarmas/', {
         title: 'Alarmas Activacon',
         templateUrl: 'partials/alarmas.html',
         controller: 'AlarmasActivacionCtrl'
      })


   // ADMINISTRACION ------------------------------------------


        .when('/users/', {
         title: 'Gestion Usuarios',
         templateUrl: 'partials/users.html',
         controller: 'UsersCtrl'
      })

        .when('/users/usuario', {
         title: 'SignUP',
         templateUrl: 'partials/singup.html',
         controller: 'UsersCtrl'
      })

        .when('/indicadores/', {
         title: 'Indicadores',
         templateUrl: 'partials/indicadores.html',
         controller: 'IndicadoresCtrl'
      })


        .when('/dashboard/', {
         title: 'Dashboard',
         templateUrl: 'partials/dashboard.html',
         controller: 'DashboardCtrl'
      })

	//  ------------------------------------------ADMINISTRACION

        .when('/registros-agendamiento/', {
         title: 'Registros',
         templateUrl: 'partials/registros-reagendamiento.html',
         controller: 'RegistrosAgendamientoCtrl'
      })


        .when('/cupos-agendamiento/', {
         title: 'Ocupacion',
         templateUrl: 'partials/ocupacion-agendamiento.html',
         controller: 'OcupacionAgendamientoCtrl'
      })        

        .when('/parametrizacion-siebel/', {
         title: 'parametrizacion',
         templateUrl: 'partials/parametrizacion-siebel.html',
         controller: 'ParametrizacionSiebel'
      })

        .when('/docuactivacion/', {
         title: 'documentacionactivacion',
         templateUrl: 'partials/docuactivacion.html',
         controller: 'DocuActivacion'
      })
    //----prueba
     .when('/cargar_datos22/', {
         title: 'cargar_datos22',
         templateUrl: 'partials/cargar_datos22.html',
         controller: 'cargar_datos22'
      })
//----------fin prueba

        .when('/Pedidos_Microzonas/', {
         title: 'Pedidos_Microzonas',
         templateUrl: 'partials/Pedidos_Microzonas.html',
         controller: 'Pedidos_MicrozonasCtrl'
      })



	.when('/general/', {
         title: 'General',
         templateUrl: 'partials/general.html',
         controller: 'generalCtrl'
      })
      .when('/', {
        title: 'Login',
        templateUrl: 'partials/login.html',
        controller: 'login'
      })

	// HERRAMIENTAS ------------------------------------------

    .when('/cmts/', {
        title: 'Cmts',
        templateUrl: 'partials/buscarcmts.html',
        controller: 'mymodalcontroller',
	reloadOnSearch: false
      })
    .when('/distancia/', {
        title: 'Distancia',
        templateUrl: 'partials/calculo-distancia.html',
        controller: 'distanciacontroller',
        reloadOnSearch: false
      })

    .when('/gpon/', {
        title: 'GPON',
        templateUrl: 'partials/buscar_gpon.html',
        controller: 'gponcontroller',
        reloadOnSearch: false
      })

	.when('/vecinos/:pagina_servicio_vecinos', {
			title: 'vecinos',
			templateUrl: 'partials/buscarvecinos.html',
			controller: 'vecinoscontroller',
			reloadOnSearch: false
		  })

// ------------------------------------------HERRAMIENTAS

	     .when('/codigo_resultado/', {
         title: 'codigo_resultado',
         templateUrl: 'partials/codigo_resultado.html',
         controller: 'cargar_datosCtrl'
      })

    .when('/scheduling/', {
         title: 'Alarmados Proactivos',
         templateUrl: 'partials/scheduling.html',
         controller: 'SchedulingCtrl'
      })

     .when('/agendamiento_auditoria/', {
         title: 'Conceptos Agendamiento Auditoria',
         templateUrl: 'partials/agendamiento_auditoria.html',
         controller: 'AuditoriaCtrl'
      })
      .when('/tabla_agendamiento/', {
         title: 'Tabla Agendamiento',
         templateUrl: 'partials/tabla_agendamiento.html',
         controller: 'Tabla_agendamientoCtrl'
      })

      .when('/agendamiento/', {
         title: 'Conceptos Agendamiento',
         templateUrl: 'partials/agendamiento.html',
         controller: 'AgendamientoCtrl'
      })

	.when('/agendamiento/reagendamiento', {
         title: 'Pantalla de Reagendamiento',
         templateUrl: 'partials/reagendamiento.html',
         controller: 'AgendamientoCtrl'
      })

        .when('/agendamiento/edatel', {
         title: 'Pantalla de Agendamiento Edatel',
         templateUrl: 'partials/edatel.html',
         controller: 'AgendamientoCtrl'
      })


        .when('/agendamiento/adelantaragenda', {
         title: 'Pantalla Adelantar agenda',
         templateUrl: 'partials/adelantaragenda.html',
         controller: 'AgendamientoAdelantarCtrl'
      })

        .when('/auditoria', {
         title: 'Pantalla de Auditoria',
         templateUrl: 'partials/auditoria.html',
         controller: 'AuditoriaCtrl'
      })

        .when('/agendamiento/auditoria', {
         title: 'Pantalla de Reagendamiento-Auditoria',
         templateUrl: 'partials/auditoria-agendamiento.html',
         controller: 'AgendamientoCtrl'
      })


    .when('/activacion/', {
         title: 'Indicadores Activación',
         templateUrl: 'partials/activacion.html',
         controller: 'ActivacionCtrl'
    })



        .when('/b2b/', {
         title: 'b2b',
         templateUrl: 'partials/registros_b2b.html',
         controller: 'RegistrosAgendamientoCtrl'
      })

      .otherwise({
        redirectTo: '/'
      });
}]);

app.run(['$rootScope', 'services', function($rootScope, services){


      $rootScope.cargos=[
        {CARGO_ID:'1', VALOR:'SUPERVISOR'},
        {CARGO_ID:'2', VALOR:'GESTOR TECNICO'},
        {CARGO_ID:'3', VALOR:'GESTOR DE LA INFORMACION'},
        {CARGO_ID:'4', VALOR:'AUXILIAR DE PROYECTOS'},
        {CARGO_ID:'5', VALOR:'SOPORTE NIVEL IV'},
        {CARGO_ID:'6', VALOR:'SOPORTE NIVEL III'},
        {CARGO_ID:'7', VALOR:'SOPORTE NIVEL II'},
        {CARGO_ID:'8', VALOR:'SOPORTE NIVEL I'},
        {CARGO_ID:'9', VALOR:'PRACTICANTE'},
        {CARGO_ID:'10', VALOR:'AUXILIAR UNE'},
        {CARGO_ID:'11', VALOR:'ASISTENTE COMERCIAL'}
    ];

      $rootScope.grupos = {
      "type": "select",
      "name": "grupos",
      "value": ["SUPER","ACTIVACION", "ASIGNACIONES", "AGENDAMIENTO", "RECONFIGURACION","INCONSISTENCIAS"],
      "values": ["SUPER","ACTIVACION", "ASIGNACIONES", "AGENDAMIENTO", "RECONFIGURACION","INCONSISTENCIAS"]
  };

      $rootScope.funciones = {
      "type": "select",
      "name": "funciones",
      "value": ["ASIGNADOR","APOYO", "OTRO", "R14", "SISTEMA"],
      "values": ["ASIGNADOR","APOYO", "OTRO", "R99", "SISTEMA"]
  };

      $rootScope.interventores = {
      "type": "select",
      "name": "interventores",
      "value": ["GIOVANI DE JESUS RODRIGUEZ PEREZ","JUAN FERNANDO MUÑOZ ZAPATA", "MONICA TATIANA HUERTAS GIRALDO", "NORBEY ANDRES MIRA DUQUE", "OTRO"],
      "values": ["GIOVANI DE JESUS RODRIGUEZ PEREZ","JUAN FERNANDO MUÑOZ ZAPATA", "MONICA TATIANA HUERTAS GIRALDO", "NORBEY ANDRES MIRA DUQUE", "OTRO"]
  };


	$rootScope.listadoConceptos=[
        {CONCEPTO_ID:'TODO', VALOR:'TODO'},
        {CONCEPTO_ID:'PETEC', VALOR:'PETEC'},
		{CONCEPTO_ID:'PEOPP', VALOR:'PEOPP'},
		{CONCEPTO_ID:'PUMED', VALOR:'PUMED'},
        {CONCEPTO_ID:'14', VALOR:'14'},
        {CONCEPTO_ID:'15', VALOR:'15'},
		{CONCEPTO_ID:'19', VALOR:'19'},
		{CONCEPTO_ID:'21', VALOR:'21'},
		{CONCEPTO_ID:'92', VALOR:'92'},
		{CONCEPTO_ID:'99', VALOR:'99'},
		{CONCEPTO_ID:'O-13', VALOR:'O-13'},
		{CONCEPTO_ID:'O-15', VALOR:'O-15'},
		{CONCEPTO_ID:'O-106', VALOR:'O-106'},
		{CONCEPTO_ID:'O-101', VALOR:'O-101'},
		{CONCEPTO_ID:'O-15', VALOR:'O-15'},
		{CONCEPTO_ID:'COBERTURA', VALOR:'COBERTURA'},
        {CONCEPTO_ID:'CONSTRUCCION', VALOR:'CONSTRUCCION'},
        {CONCEPTO_ID:'DISENO', VALOR:'DISENO'},
        {CONCEPTO_ID:'DISPONIBILIDAD', VALOR:'DISPONIBILIDAD'}
    ];

	// Listados Siebel -------------------------------------------

		$rootScope.estadosSiebel=[
			{ESTADO_ID:'COBERTURA', VALOR:'COBERTURA'},
			{ESTADO_ID:'CONSTRUCCION', VALOR:'CONSTRUCCION'},
			{ESTADO_ID:'DISENO', VALOR:'DISENO'},
			{ESTADO_ID:'DISPONIBILIDAD', VALOR:'DISPONIBILIDAD'}
    	];

	// ------------------------------------------- Listados Siebel

}]);

//Variables Globales
app.run(function($rootScope, services) {

    $rootScope.daysInMonth = function(year, month) {
        return new Date(year, month+1,0).getDate();
    }

    $rootScope.fechaProceso = function() {
        var tiempo=new Date().getTime();
                var date1 = new Date();
                var year    = date1.getFullYear();
                var month   = (date1.getMonth()+1<=9)?'0'+(date1.getMonth()+1):(date1.getMonth()+1);
                var day     = (date1.getDate()<=9)?'0'+date1.getDate():date1.getDate();
                var hour    = (date1.getHours()<=9)?'0'+date1.getHours():date1.getHours();
                var minute  = (date1.getMinutes()<=9)?'0'+date1.getMinutes():date1.getMinutes();
                var seconds = (date1.getSeconds()<=9)?'0'+date1.getSeconds():date1.getSeconds();

                tiempo=year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
        return tiempo;
    }

    $rootScope.executeCopy= function executeCopy(text){
                var input = document.createElement('textarea');
                document.body.appendChild(input);
                input.value = (text);
                //input.focus();
                input.select();
                document.execCommand('Copy');
                input.remove();
            };

});

app.run(['$location', '$rootScope', function($location, $rootScope) {

    $rootScope.$on('$routeChangeSuccess', function (event, current, previous) {
        console.log( current );
        $rootScope.title = current.$$route.title;
    });


}]);

