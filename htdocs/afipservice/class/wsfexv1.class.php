<?php
class wsfexv1 {
    const TA = "xml/TA-wsfex.xml";
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
        $this->path = DOL_DOCUMENT_ROOT . "/afipservice/";

        if ($conf->global->AFIPWS_WSAA_MODE == 0) {
            $this->url = "https://wswhomo.afip.gov.ar/wsfexv1/service.asmx?WSDL";
        } else {
            $this->url = $conf->global->AFIPWS_WSFEX_SERVER;
        }

        ini_set("soap.wsdl_cache_enabled", "0");

        // SSL stream context to allow AFIP's smaller DH keys (OpenSSL 3.x compatibility)
        $sslContext = stream_context_create(array(
            'ssl' => array(
                'ciphers' => 'DEFAULT:@SECLEVEL=1',
            ),
        ));

        if (!file_get_contents($this->url, false, $sslContext)) {
            $this->error .= " Failed to open WSDL: " . $this->url;
        }

        if (!empty($this->error)) {
            return $this->error;
        }

        $this->client = new SoapClient($this->url, array(
            "soap_version" => SOAP_1_2,
            "location" => $this->url,
            "exceptions" => 1,
            "trace" => 1,
            "stream_context" => $sslContext
        ));
    }

    private function _checkErrors($results, $method) {
        if (self::LOG_XMLS) {
            file_put_contents($this->path . "xml/request-" . $method . ".xml", $this->client->__getLastRequest());
            file_put_contents($this->path . "xml/response-" . $method . ".xml", $this->client->__getLastResponse());
        }

        if (is_soap_fault($results)) {
            throw new Exception("WSFE class. FaultString: " . $results->faultcode . " " . $results->faultstring);
        }

        if ($method == "FEDummy") {
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

    public function FEXGetCMP($tipo_cbte, $cbte_nro, $ptovta, $cuitemisor) {
        $results = $this->client->FEXGetCMP(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            ),
            "Cmp" => array(
                "Cbte_Tipo" => $tipo_cbte,
                "Punto_vta" => $ptovta,
                "Cbte_nro" => $cbte_nro
            )
        ));

        $e = $this->_checkErrors($results, "FEXGetCMP");
        return $e == false ? $results->FEXGetCMPResult : false;
    }

    public function FEXGetLast_ID($cuitemisor) {
        $results = $this->client->FEXGetLast_ID(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            )
        ));

        $e = $this->_checkErrors($results, "FEXGetLast_ID");
        return $e == false ? $results->FEXGetLast_IDResult->CbteNro : false;
    }

    public function FEXGetLast_CMP($ptovta, $tipo_cbte, $cuitemisor) {
        $results = $this->client->FEXGetLast_CMP(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            ),
            "Pto_venta" => $ptovta,
            "Cbte_Tipo" => $tipo_cbte
        ));

        $e = $this->_checkErrors($results, "FEXGetLast_CMP");
        return $e == false ? $results->FEXGetLast_CMPResult->CbteNro : false;
    }

    public function FEXGetPARAM_MON($cuitemisor) {
        $results = $this->client->FEXGetPARAM_MON(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            )
        ));

        $e = $this->_checkErrors($results, "FEXGetPARAM_MON");
        return $e == false ? $results->FEXGetPARAM_MONResult->CbteNro : false;
    }

    public function FEXGetPARAM_Cbte_Tipo($cuitemisor) {
        $results = $this->client->FEXGetPARAM_Cbte_Tipo(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            )
        ));

        $e = $this->_checkErrors($results, "FEXGetPARAM_Cbte_Tipo");
        return $e == false ? $results->FEXGetPARAM_Cbte_TipoResult->CbteNro : false;
    }

    public function FEXGetPARAM_Tipo_Expo($cuitemisor) {
        $results = $this->client->FEXGetPARAM_Tipo_Expo(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            )
        ));

        $e = $this->_checkErrors($results, "FEXGetPARAM_Tipo_Expo");
        return $e == false ? $results->FEXGetPARAM_Tipo_ExpoResult->CbteNro : false;
    }

    public function FEXGetPARAM_Umed($cuitemisor) {
        $results = $this->client->FEXGetPARAM_Umed(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            )
        ));

        $e = $this->_checkErrors($results, "FEXGetPARAM_Umed");
        return $e == false ? $results->FEXGetPARAM_UmedResult->CbteNro : false;
    }

    public function FEXGetPARAM_Idiomas($cuitemisor) {
        $results = $this->client->FEXGetPARAM_Idiomas(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            )
        ));

        $e = $this->_checkErrors($results, "FEXGetPARAM_Idiomas");
        return $e == false ? $results->FEXGetPARAM_IdiomasResult->CbteNro : false;
    }

    public function FEXGetPARAM_DST_pais($cuitemisor) {
        $results = $this->client->FEXGetPARAM_DST_pais(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            )
        ));

        $e = $this->_checkErrors($results, "FEXGetPARAM_DST_pais");
        return $e == false ? $results->FEXGetPARAM_DST_paisResult->CbteNro : false;
    }

    public function FEXGetPARAM_Incoterms($cuitemisor) {
        $results = $this->client->FEXGetPARAM_Incoterms(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            )
        ));

        $e = $this->_checkErrors($results, "FEXGetPARAM_Incoterms");
        return $e == false ? $results->FEXGetPARAM_IncotermsResult->CbteNro : false;
    }

    public function FEXGetPARAM_DST_CUIT($cuitemisor) {
        $results = $this->client->FEXGetPARAM_DST_CUIT(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            )
        ));

        $e = $this->_checkErrors($results, "FEXGetPARAM_DST_CUIT");
        return $e == false ? $results->FEXGetPARAM_DST_CUITResult->CbteNro : false;
    }

    public function FEXGetPARAM_Ctz($idmoneda, $cuitemisor) {
        $results = $this->client->FEXGetPARAM_Ctz(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            ),
            array("Mon_id" => $idmoneda)
        ));

        $e = $this->_checkErrors($results, "FEXGetPARAM_Ctz");
        return $e == false ? $results->FEXGetPARAM_DST_CUITResult->CbteNro : false;
    }

    public function FEXGetPARAM_PtoVenta($cuitemisor) {
        $results = $this->client->FEXGetPARAM_PtoVenta(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            )
        ));

        $e = $this->_checkErrors($results, "FEXGetPARAM_PtoVenta");
        return $e == false ? $results->FEXGetPARAM_PtoVentaResult->CbteNro : false;
    }

    public function FEXGetPARAM_Opcionales($cuitemisor) {
        $results = $this->client->FEXGetPARAM_Opcionales(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            )
        ));

        $e = $this->_checkErrors($results, "FEXGetPARAM_Opcionales");
        return $e == false ? $results->FEXGetPARAM_OpcionalesResult->CbteNro : false;
    }

    public function FEXCheck_Permiso($idpermiso, $dstmerc, $cuitemisor) {
        $results = $this->client->FEXCheck_Permiso(array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            ),
            array("ID_Permiso" => $idpermiso),
            array("Dst_merc" => $dstmerc)
        ));

        $e = $this->_checkErrors($results, "FEXCheck_Permiso");
        return $e == false ? $results->FEXCheck_PermisoResult->CbteNro : false;
    }

    public function FEXDummy() {
        $results = $this->client->FEXDummy();
        $e = $this->_checkErrors($results, "FEDummy");
        return $e == false ? $results->FEXDummyResult : false;
    }

    public function FEXAuthorize($ptovta, $regfe, $iterms, $cmps_asoc, $permisos, $opcionales, $cuitemisor) {
        $params = array(
            "Auth" => array(
                "Token" => $this->TA->credentials->token,
                "Sign" => $this->TA->credentials->sign,
                "Cuit" => floatval($cuitemisor)
            ),
            "Cmp" => array(
                "Id" => '',
                "Fecha_cbte" => '',
                "Cbte_Tipo" => '',
                "Punto_vta" => $ptovta,
                "Cbte_nro" => '',
                "Tipo_expo" => '',
                "Permiso_existente" => '',
                "Dst_cmp" => '',
                "Cliente" => '',
                "Cuit_pais_cliente" => '',
                "Domicilio_cliente" => '',
                "Id_impositivo" => '',
                "Moneda_Id" => '',
                "Moneda_ctz" => '',
                "Obs_comerciales" => '',
                "Imp_total" => " ",
                "Obs" => '',
                "Forma_pago" => '',
                "Incoterms" => '',
                "Incoterms_Ds" => '',
                "Idioma_cbte" => '',
                "Cmps_asoc" => $cmps_asoc,
                "Permisos" => $permisos,
                "Items" => $iterms,
                "Opcionales" => $opcionales
            )
        );

        $results = $this->client->FEXAuthorizeResult($params);
        $e = $this->_checkErrors($results, "FEXAuthorize");

        $resp_cae = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAE;
        $resp_caefvto = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAEFchVto;

        return $e == false ? array(
            "cae" => $resp_cae,
            "fecha_vencimiento" => $resp_caefvto
        ) : false;
    }
}
?>
