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


class wssrpadron {
    const TA 	= "xml/TA-ws_sr_padron_a4.xml";        				# Archivo con el Token y Sign
    const LOG_XMLS = true;                     		# For debugging purposes

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



    /*
    * Constructor
    */
    public function __construct()
    {
        global $conf;

        $this->path = $conf->afipservice->dir_output.'/'.$conf->entity.'/';
        if ($conf->global->AFIPWS_WSAA_MODE == 0) { //MODE 1=produccion 0=Homologacion
            $this->url = 'https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA4?WSDL'; //HOMO


        }else{
            $this->url = $conf->global->AFIPWS_WSSRPADRON_SERVER;
        };



        // seteos en php
        ini_set("soap.wsdl_cache_enabled", "0");

        // SSL stream context to allow AFIP's smaller DH keys (OpenSSL 3.x compatibility)
        $sslContext = stream_context_create(array(
            'ssl' => array(
                'ciphers' => 'DEFAULT:@SECLEVEL=1',
            ),
        ));

        // validar archivos necesarios
//        if (!file_get_contents($this->url, false, $sslContext)) $this->error .= " Failed to open WSDL: ".$this->url; //chequea la url

//		if(!empty($this->error)) {
//			return ($this->error);
//		}



        $this->client = new SoapClient($this->url, array(
                'soap_version' => SOAP_1_1,     //Este WS solo soporta 1.1 //catriel
                'location'     => $this->url,
                'exceptions'   => 1,
                'trace'        => 1,
                'stream_context' => $sslContext)
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
            throw new Exception('WSSRPADRON class. FaultString: ' . $results->faultcode.' '.$results->faultstring);
        }

        if ($method == 'dummy') {return;}

        $XXX=$method.'Result';
        if ($results->$XXX->Errors->Err->Code != 0) {
            $this->error = "Method=$method errcode=".$results->$XXX->Errors->Err->Code." errmsg=".$results->$XXX->Errors->Err->Msg;
        }


        //asigna error a variable
        if ($method == 'FECAESolicitar') {
            if ($results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs->Code){
                $this->ObsCode = $results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs->Code;
                $this->ObsMsg = $results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs->Msg;
            }
        }
        $this->Code = $results->$XXX->Errors->Err->Code;
        $this->Msg = $results->$XXX->Errors->Err->Msg;
        //fin asigna error a variable

        return $results->$XXX->Errors->Err->Code != 0 ? true : false;
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
    * Retorna el datos del contrubuyente.
    */
    public function getPersona($idPersona, $cuitemisor)
    {
        $results = $this->client->getPersona(
            array('sign' => $this->TA->credentials->sign,
                'token' => $this->TA->credentials->token,
                'cuitRepresentada' => floatval($cuitemisor),
                'idPersona' => floatval($idPersona)));

        $e = $this->_checkErrors($results, 'getPersona');

        return $e == false ? $results->personaReturn->persona : false;
    } //end function getPersona


    /*
    * Retorna el estado de servidores.
    */
    public function dummy()
    {
        $results = $this->client->dummy();

        $e = $this->_checkErrors($results, 'dummy');

        return $e == false ? $results->return : false;
    } //end function dummy



} // class

?>
