<?php
/*
 * Argentina Electronic Invoice module for Dolibarr
 * 2015 Pablo <pablin.php@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


//include_once DOL_DOCUMENT_ROOT . '/afipservice/class/wsaa_db.class.php';


class wsfev1 {
	//const CUIT 	= 20267565393;                 		# CUIT del emisor de las facturas. Solo numeros sin comillas.
  	const TA 	= "xml/TA-wsfe.xml";        				# Archivo con el Token y Sign
	//https://wswhomo.afip.gov.ar/wsfev1/service.asmx // Funciones
	//https://wswhomo.afip.gov.ar/wsfev1/service.asmx?WSDL // para obtener WSDL
	//const WSDL = "wsfev1.wsdl";                   	# The WSDL corresponding to WSFEV1
	const LOG_XMLS = true;                     		# For debugging purposes
	//const WSFEURL = "https://wswhomo.afip.gov.ar/wsfev1/service.asmx"; // homologacion wsfev1 (testing)
	//const WSFEURL = "?????????/wsfev1/service.asmx"; // produccion


	/*
	* manejo de errores
	*/
	public $error = '';
	public $ObsCode = '';
	public $ObsMsg = '';
	public $Code = '';
	public $Msg = '';
	/**
	* Cliente SOAP
	*/
	private $client;

	/*
	* objeto que va a contener el xml de TA
	*/
	private $TA;
    //var $wsaadb;


	/*
	* Constructor
	*/
	public function __construct()
	{
		global $conf;

		$wsaaMode = 0;
		if (function_exists('getDolGlobalInt')) {
			$wsaaMode = (int) getDolGlobalInt('AFIPSERVICE_WSAA_MODE');
		} elseif (isset($conf->global->AFIPSERVICE_WSAA_MODE)) {
			$wsaaMode = (int) $conf->global->AFIPSERVICE_WSAA_MODE;
		}

		$wsfeServer = '';
		if (function_exists('getDolGlobalString')) {
			$wsfeServer = (string) getDolGlobalString('AFIPSERVICE_WSFE_SERVER');
		} elseif (!empty($conf->global->AFIPSERVICE_WSFE_SERVER)) {
			$wsfeServer = (string) $conf->global->AFIPSERVICE_WSFE_SERVER;
		}

		//$this->path = DOL_DOCUMENT_ROOT.'/afipservice/';
        $this->path = $conf->afipservice->dir_output.'/'.$conf->entity.'/';
		if ($wsaaMode == 0) { //MODE 1=produccion 0=Homologacion
            $this->url = 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx?WSDL'; //HOMO

        }else{
			$this->url = $wsfeServer;
        };



	// seteos en php
        ini_set("soap.wsdl_cache_enabled", "0");

    // validar archivos necesarios
        if (!file_get_contents($this->url)) $this->error .= " Failed to open WSDL: ".$this->url; //chequea la url

		if(!empty($this->error)) {
			return ($this->error);
		}



    $this->client = new SoapClient($this->url, array(
				'soap_version' => SOAP_1_2,
				'location'     => $this->url,
				'exceptions'   => 1,
				'trace'        => 1)
    );


	}



	/*
	* Chequea los errores en la operacion, si encuentra algun error falta lanza una exepcion
	* si encuentra un error no fatal, loguea lo que paso en $this->error
	*/
	private function _checkErrors($results, $method)
	{
		if (self::LOG_XMLS){
			file_put_contents($this->path."xml/request-".$method.".xml",$this->client->__getLastRequest());
			file_put_contents($this->path."xml/response-".$method.".xml",$this->client->__getLastResponse());
		}

		if (is_soap_fault($results)) {
			throw new Exception('WSFE class. FaultString: ' . $results->faultcode.' '.$results->faultstring);
		}

		if ($method == 'FEDummy') {return;}

		$XXX=$method.'Result';
		if ($results->$XXX->Errors->Err->Code != 0) {
			$this->error = "Method=$method errcode=".$results->$XXX->Errors->Err->Code." errmsg=".$results->$XXX->Errors->Err->Msg;
		}


		//asigna error a variable
		if ($method == 'FECAESolicitar') {
			if ($results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs->Code) {
				$this->ObsCode = $results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs->Code;
				$this->ObsMsg = $results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs->Msg;

			}
		}
		//if ($results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs[0]->Code){
			//	$this->ObsCode = $results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs[0]->Code;
			//	$this->ObsMsg = $results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs[0]->Msg;
			//}
		//	}
		$this->Code = $results->$XXX->Errors->Err->Code;
		$this->Msg = $results->$XXX->Errors->Err->Msg;
		//fin asigna error a variable

		if(is_array($results->$XXX->Errors->Err)){ //?CAMBIO TOMAS (Por version) -> Agregado del if para evitar nulos
			return count($results->$XXX->Errors->Err) != 0 ? true : false;
		} else {
			return false;
		}
	}



	/**
	* Abre el archivo de TA xml,
	* si hay algun problema devuelve false
	*/
	public function openTA()
	{
	$this->TA = simplexml_load_file($this->path.self::TA);

	return $this->TA == false ? false : true;
	}

	/*
	* Retorna el ultimo número autorizado.
	*/
	public function FECompUltimoAutorizado($ptovta, $tipo_cbte, $cuitemisor)
	{
	$results = $this->client->FECompUltimoAutorizado(
		array('Auth'=>array('Token' => $this->TA->credentials->token,
							'Sign' => $this->TA->credentials->sign,
							'Cuit' => floatval($cuitemisor)),
			'PtoVta' => $ptovta,
			'CbteTipo' => $tipo_cbte));

    $e = $this->_checkErrors($results, 'FECompUltimoAutorizado');

    return $e == false ? $results->FECompUltimoAutorizadoResult->CbteNro : false;
	} //end function FECompUltimoAutorizado


	/*
	* Retorna el estado de servidores.
	*/
	public function FEDummy()
	{
		$results = $this->client->FEDummy();

		$e = $this->_checkErrors($results, 'FEDummy');

		return $e == false ? $results->FEDummyResult : false;
	} //end function FEDummy


	/*
	* Retorna el ultimo comprobante autorizado para el tipo de comprobante /cuit / punto de venta ingresado.
	*/
	public function recuperaLastCMP ($ptovta, $tipo_cbte, $cuitemisor)
	{
	$results = $this->client->FERecuperaLastCMPRequest(
		array('argAuth' =>  array('Token' => $this->TA->credentials->token,
								'Sign' => $this->TA->credentials->sign,
								'cuit' => floatval($cuitemisor)),
			'argTCMP' => array('PtoVta' => $ptovta,
								'TipoCbte' => $tipo_cbte)));
	$e = $this->_checkErrors($results, 'FERecuperaLastCMPRequest');

	return $e == false ? $results->FERecuperaLastCMPRequestResult->cbte_nro : false;
	} //end function recuperaLastCMP

/*
* Retorna comprobantes autorizados.
*/
	public function FECompConsultar ($tipo_cbte, $cbte_nro, $ptovta, $cuitemisor)
	{
		$results = $this->client->FECompConsultar(
			array(
				'Auth' =>
					array( 'Token' => $this->TA->credentials->token,
						'Sign' => $this->TA->credentials->sign,
						'Cuit' => floatval($cuitemisor)),
				'FeCompConsReq' =>
					array(
						'PtoVta' => $ptovta,
						'CbteTipo' => $tipo_cbte,
						'CbteNro' => $cbte_nro)));
		$e = $this->_checkErrors($results, 'FeCompConsReq');

		return $e == false ? $results->FECompConsultarResult: false;
	} //end function FECompConsultar



	/*
	* Solicitud CAE y fecha de vencimiento
	*/
	public function FECAESolicitar($cbte, $ptovta, $regfe, $regfeasoc, $regfetrib, $regfeiva, $cuitemisor, $fcerechazada = false)
	{

	global $conf;
	if (!$conf->global->AFIP_NOT_SEND_COND_IVA_RECEPTOR) { //?CAMBIO TOMAS (CAMBIO TEMPORAL PARA PRODUCCION)
		$params = array(
			'Auth' =>
			array( 'Token' => $this->TA->credentials->token,
				'Sign' => $this->TA->credentials->sign,
				'Cuit' => floatval($cuitemisor)),
			'FeCAEReq' =>
			array( 'FeCabReq' =>
				array( 'CantReg' => 1,
					'PtoVta' => $ptovta,
					'CbteTipo' => $regfe['CbteTipo'] ),
				'FeDetReq' =>
				array( 'FECAEDetRequest' =>
					array( 'Concepto' => $regfe['Concepto'],
						'DocTipo' => $regfe['DocTipo'],
						'DocNro' => $regfe['DocNro'],
						'CbteDesde' => $cbte,
						'CbteHasta' => $cbte,
						'CbteFch' => $regfe['CbteFch'],
						'ImpNeto' => $regfe['ImpNeto'],
						'ImpTotConc' => $regfe['ImpTotConc'],
						'ImpIVA' => $regfe['ImpIVA'],
						'ImpTrib' => $regfe['ImpTrib'],
						'ImpOpEx' => $regfe['ImpOpEx'],
						'ImpTotal' => $regfe['ImpTotal'],
						'FchServDesde' => $regfe['FchServDesde'], //null
						'FchServHasta' => $regfe['FchServHasta'], //null
						'FchVtoPago' => $regfe['FchVtoPago'], //null
						'MonId' => $regfe['MonId'], //PES
						'MonCotiz' => $regfe['MonCotiz'], //1
						'CondicionIVAReceptorId' => $regfe['CondicionIVA'],//?CAMBIO TOMAS
						'Tributos' => $regfetrib,
						'Iva' => $regfeiva
					),
				),
			),
		);
	} else {
		$params = array(
			'Auth' =>
			array( 'Token' => $this->TA->credentials->token,
				'Sign' => $this->TA->credentials->sign,
				'Cuit' => floatval($cuitemisor)),
			'FeCAEReq' =>
			array( 'FeCabReq' =>
				array( 'CantReg' => 1,
					'PtoVta' => $ptovta,
					'CbteTipo' => $regfe['CbteTipo'] ),
				'FeDetReq' =>
				array( 'FECAEDetRequest' =>
					array( 'Concepto' => $regfe['Concepto'],
						'DocTipo' => $regfe['DocTipo'],
						'DocNro' => $regfe['DocNro'],
						'CbteDesde' => $cbte,
						'CbteHasta' => $cbte,
						'CbteFch' => $regfe['CbteFch'],
						'ImpNeto' => $regfe['ImpNeto'],
						'ImpTotConc' => $regfe['ImpTotConc'],
						'ImpIVA' => $regfe['ImpIVA'],
						'ImpTrib' => $regfe['ImpTrib'],
						'ImpOpEx' => $regfe['ImpOpEx'],
						'ImpTotal' => $regfe['ImpTotal'],
						'FchServDesde' => $regfe['FchServDesde'], //null
						'FchServHasta' => $regfe['FchServHasta'], //null
						'FchVtoPago' => $regfe['FchVtoPago'], //null
						'MonId' => $regfe['MonId'], //PES
						'MonCotiz' => $regfe['MonCotiz'], //1
						'Tributos' => $regfetrib,
						'Iva' => $regfeiva
					),
				),
			),
		);
	}

    // Agrego Opcionales para FCEA (CBU)
	if($regfe['CbteTipo']==201){
	    $cbu = $conf->global->MAIN_INFO_TVAINTRA;
	    $regfeopcional = array();
		$regfeopcional['Opcional'][]= array(
				'Id'=> '2101',
		        'Valor'=> $cbu
		);
		$regfeopcional['Opcional'][]= array(
		        'Id'=> '27',
		        'Valor'=> "SCA" // Error de AFIP 10216: SCA o ADC
		);
		//$opcionales['Opcionales'] = $regfeopcional;
		//array_push($params['FeCAEReq']['FeDetReq']['FECAEDetRequest']['Opcionales'],$regfeopcional);
		//var_dump($regfeopcional);
		$params['FeCAEReq']['FeDetReq']['FECAEDetRequest']['Opcionales']=$regfeopcional;
	}


	if ($regfe['CbteTipo']==203 OR $regfe['CbteTipo']==202 ){

	    if(!$fcerechazada) {$rechazada = 'N';} else {$rechazada='S';}

	    $regfeopcional['Opcional'][]= array(
	        'Id'=> '22',
	        'Valor'=> $rechazada
	    );
	    $params['FeCAEReq']['FeDetReq']['FECAEDetRequest']['Opcionales']= $regfeopcional;
	    $params['FeCAEReq']['FeDetReq']['FECAEDetRequest']['CbtesAsoc']= $regfeasoc;
	    unset($params['FeCAEReq']['FeDetReq']['FECAEDetRequest']['FchVtoPago']);
	}

	if ($regfe['CbteTipo']==3 OR $regfe['CbteTipo']==2 OR $regfe['CbteTipo']==8 OR $regfe['CbteTipo']==7 ){
	    $params['FeCAEReq']['FeDetReq']['FECAEDetRequest']['CbtesAsoc']= $regfeasoc;
	}


	$results = $this->client->FECAESolicitar($params);

    $e = $this->_checkErrors($results, 'FECAESolicitar');


	return $e == false ? $results->FECAESolicitarResult: false;
	} //end function FECAESolicitar

	 /*
    * Recuperador de condicion de iva del receptor
	(FEParamGetCondicionIvaReceptor)
    */
    public function FEParamGetCondicionIvaReceptor($cuitreceptor)//?CAMBIO TOMAS
    {
        $results = $this->client->FEParamGetCondicionIvaReceptor(
            array('Auth'=>array('Token' => $this->TA->credentials->token,
                'Sign' => $this->TA->credentials->sign,
                'Cuit' => floatval($cuitreceptor)),
                // 'ClaseCmp' => $ClaseCmp,
                ));

        $e = $this->_checkErrors($results, 'FEParamGetCondicionIvaReceptor');

        return $e == false ? $results->FEParamGetCondicionIvaReceptorResult->ResultGet->CondicionIvaReceptor : false;
    } //end function FEParamGetCondicionIvaReceptor

	/*
	* Recuperador de cotizacion de moneda
	(FEParamGetCotizacion)
    */
    public function FEParamGetCotizacion($cuitemisor, $monid)//?CAMBIO TOMAS
    {
        $results = $this->client->FEParamGetCotizacion(
            array('Auth'=>array('Token' => $this->TA->credentials->token,
                'Sign' => $this->TA->credentials->sign,
                'Cuit' => floatval($cuitemisor)),
				'MonId' => $monid
                ));

        $e = $this->_checkErrors($results, 'FEParamGetCotizacion');

        return $e == false ? $results->FEParamGetCotizacionResult->ResultGet->MonCotiz : false;
    } //end function FEParamGetCotizacion

    /*
    * Recuperador de valores referenciales de códigos de Tipos de Tributos
(FEParamGetTiposTributos)
    */
    public function FEParamGetTiposTributos($cuitemisor)
    {
        $results = $this->client->FEParamGetTiposTributos(
            array('Auth'=>array('Token' => $this->TA->credentials->token,
                'Sign' => $this->TA->credentials->sign,
                'Cuit' => floatval($cuitemisor)),
                ));

        $e = $this->_checkErrors($results, 'FEParamGetTiposTributos');

        return $e == false ? $results->FEParamGetTiposTributosResult->ResultGet->TributoTipo : false;
    } //end function FEParamGetTiposTributos



    /*
    * Recupera el listado de puntos de venta registrados y su estado
(FEParamGetPtosVenta)
    */
    public function FEParamGetPtosVenta($cuitemisor)
    {
        $results = $this->client->FEParamGetPtosVenta(
            array('Auth'=>array('Token' => $this->TA->credentials->token,
                'Sign' => $this->TA->credentials->sign,
                'Cuit' => floatval($cuitemisor)),
            ));

        $e = $this->_checkErrors($results, 'FEParamGetPtosVenta');

        return $e == false ? $results->FEParamGetPtosVentaResult->ResultGet->PtoVenta : false;
    } //end function FEParamGetTiposTributos


    /*
     * Recuperador de valores referenciales de códigos de Tipos de Tributos
     (FEParamGetTiposTributos)
     */
    public function FEconsultarMontoObligadoRecepcion($cuitemisor)
    {
        $results = $this->client->FEParamGetTiposTributos(
            array('Auth'=>array('Token' => $this->TA->credentials->token,
                'Sign' => $this->TA->credentials->sign,
                'Cuit' => floatval($cuitemisor)),
            ));

        $e = $this->_checkErrors($results, 'FEParamGetTiposTributos');

        return $e == false ? $results->FEParamGetTiposTributosResult->ResultGet->TributoTipo : false;
    } //end function FEParamGetTiposTributos




} // class

?>
