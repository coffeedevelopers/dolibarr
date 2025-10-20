<?php
class wscdcv1 {
    const TA = "xml/TA-wscdc.xml";
    const LOG_XMLS = true;
    
    public $error = '';
    public $ObsCode = '';
    public $ObsMsg = '';
    public $Code = '';
    public $Msg = '';
    private $client;
    private $TA;
    
    public function __construct() {
        global $conf;
        $this->path = DOL_DOCUMENT_ROOT . "/afipws/";
        
        if ($conf->global->AFIPWS_WSAA_MODE == 0) {
            $this->url = "https://wswhomo.afip.gov.ar/WSCDC/service.asmx?WSDL";
        } else {
            $this->url = $conf->global->AFIPWS_WSCDC_SERVER;
        }
        
        ini_set("soap.wsdl_cache_enabled", "0");
        
        if (!file_get_contents($this->url)) {
            $this->error .= " Failed to open " . $this->url;
        }
        
        if (!empty($this->error)) {
            return $this->error;
        }
        
        $this->client = new SoapClient($this->url, array(
            "soap_version" => SOAP_1_2,
            "location" => $this->url,
            "exceptions" => 0,
            "trace" => 1
        ));
    }
    
    private function _checkErrors($results, $method) {
        if (self::LOG_XMLS) {
            file_put_contents($this->path . "xml/request-" . $method . ".xml", $this->client->__getLastRequest());
            file_put_contents($this->path . "xml/response-" . $method . ".xml", $this->client->__getLastResponse());
        }
        
        if (is_soap_fault($results)) {
            throw new Exception("WSCDC class. FaultString: " . $results->faultcode . " " . $results->faultstring);
        }
        
        if ($method == "ComprobanteDummy") {
            return;
        }
        
        $XXX = $method . "Result";
        
        if ($results->{$XXX}->Errors->Err->Code != 0) {
            $this->error = "Method={$method} errcode=" . $results->{$XXX}->Errors->Err->Code . " errmsg=" . $results->{$XXX}->Errors->Err->Msg;
        }
        
        if ($method == "FECAESolicitar") {
            if ($results->{$XXX}->FeDetResp->FECAEDetResponse->Observaciones->Obs->Code) {
                $this->ObsCode = $results->{$XXX}->FeDetResp->FECAEDetResponse->Observaciones->Obs->Code;
                $this->ObsMsg = $results->{$XXX}->FeDetResp->FECAEDetResponse->Observaciones->Obs->Msg;
            }
        }
        
        $this->Code = $results->{$XXX}->Errors->Err->Code;
        $this->Msg = $results->{$XXX}->Errors->Err->Msg;
        
        return $results->{$XXX}->Errors->Err->Code != 0 ? true : false;
    }
    
    public function openTA() {
        $this->TA = simplexml_load_file($this->path . self::TA);
        return $this->TA == false ? false : true;
    }
    
    public function ComprobanteConstatar($cbte_modo, $cuit_emisor, $pto_vta, $tipo_cbte, $nro_cbte, $cbte_fch, $imp_total, $cod_autorizacion, $doc_tipo_receptor, $doc_nro_receptor, $cuitemisor) {
        $results = $this->client->ComprobanteConstatar(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            ),
            "CmpReq" => array(
                "CbteModo" => $cbte_modo,
                "CuitEmisor" => floatval($cuit_emisor),
                "PtoVta" => $pto_vta,
                "CbteTipo" => $tipo_cbte,
                "CbteNro" => $nro_cbte,
                "CbteFch" => $cbte_fch,
                "ImpTotal" => $imp_total,
                "CodAutorizacion" => $cod_autorizacion,
                "DocTipoReceptor" => $doc_tipo_receptor,
                "DocNroReceptor" => $doc_nro_receptor
            )
        ));
        
        $e = $this->_checkErrors($results, "ComprobanteConstatar");
        return $e == false ? $results->ComprobanteConstatarResult : false;
    }
    
    public function ComprobanteDummy() {
        $results = $this->client->ComprobanteDummy();
        $e = $this->_checkErrors($results, "ComprobanteDummy");
        return $e == false ? $results->ComprobanteDummyResult : false;
    }
    
    public function ComprobantesModalidadConsultar($cuitemisor) {
        $results = $this->client->ComprobantesModalidadConsultar(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => $cuitemisor
            )
        ));
        
        $e = $this->_checkErrors($results, "ComprobantesModalidadConsultar");
        return $e == false ? $results->ComprobantesModalidadConsultarResult : false;
    }
    
    public function ComprobantesTipoConsultar($cuitemisor) {
        $results = $this->client->ComprobantesTipoConsultar(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => $cuitemisor
            )
        ));
        
        $e = $this->_checkErrors($results, "ComprobantesTipoConsultar");
        return $e == false ? $results->ComprobantesTipoConsultarResult : false;
    }
    
    public function DocumentosTipoConsultar($cuitemisor) {
        $results = $this->client->DocumentosTipoConsultar(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => $cuitemisor
            )
        ));
        
        $e = $this->_checkErrors($results, "DocumentosTipoConsultar");
        return $e == false ? $results->DocumentosTipoConsultarResult : false;
    }
    
    public function OpcionalesTipoConsultar($cuitemisor) {
        $results = $this->client->OpcionalesTipoConsultar(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => $cuitemisor
            )
        ));
        
        $e = $this->_checkErrors($results, "OpcionalesTipoConsultar");
        return $e == false ? $results->OpcionalesTipoConsultarResult : false;
    }
}
?>