<?php
 class wsaa {
    const PASSPHRASE = '';
    const PROXY_ENABLE = false;
    public $error = '';
    private $client;
    public $service;

    public function __construct() {
        global $conf;
        $this->path = $conf->afipservice->dir_output . "/" . $conf->entity . "/";
        $this->cert = $this->path . "keys/" . $conf->global->AFIPWS_WSAA_CRT;
        $this->key = $this->path . "keys/" . $conf->global->AFIPWS_WSAA_KEY;
        $this->service = $this->service ? '' : "wsfe";

        if ($conf->global->AFIPWS_WSAA_MODE == 0) {
            $this->url = "https://wsaahomo.afip.gov.ar/ws/services/LoginCms?WSDL";
        } else {
            $this->url = $conf->global->AFIPWS_WSAA_SERVER;
        }

        ini_set("soap.wsdl_cache_enabled", "0");

        // SSL stream context to allow AFIP's smaller DH keys (OpenSSL 3.x compatibility)
        $sslContext = stream_context_create(array(
            'ssl' => array(
                'ciphers' => 'DEFAULT:@SECLEVEL=1',
            ),
        ));

        if (!file_exists($this->cert)) {
            $this->error .= " Failed to open " . $this->cert;
        }
        if (!file_exists($this->key)) {
            $this->error .= " Failed to open " . $this->key;
        }
        if (!file_get_contents($this->url, false, $sslContext)) {
            $this->error .= " Failed to open " . $this->url;
        }
        if (!empty($this->error)) {
            return $this->error;
        }

        $this->client = new SoapClient($this->url, array(
            "soap_version" => SOAP_1_2,
            "location" => $this->url,
            "trace" => 1,
            "exceptions" => 1,
            "stream_context" => $sslContext
        ));
    }

    private function create_TRA() {
        if (file_exists($this->path . "xml/TRA-{$this->service}.xml")) {
            unlink($this->path . "xml/TRA-{$this->service}.xml");
        }
        $TRA = new SimpleXMLElement(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
            "<loginTicketRequest version=\"1.0\">" .
            "</loginTicketRequest>"
        );
        $TRA->addChild("header");
        $TRA->header->addChild("uniqueId", date("U"));
        $TRA->header->addChild("generationTime", date("c", date("U") - 60));
        $TRA->header->addChild("expirationTime", date("c", date("U") + 60));
        $TRA->addChild("service", $this->service);
        $TRA->asXML($this->path . "xml/TRA-{$this->service}.xml");
    }

    private function sign_TRA() {
        $STATUS = openssl_pkcs7_sign(
            $this->path . "xml/TRA-{$this->service}.xml",
            $this->path . "xml/TRA-{$this->service}.tmp",
            "file://" . $this->cert,
            array("file://" . $this->key, self::PASSPHRASE),
            array(),
            !PKCS7_DETACHED
        );

        if (!$STATUS) {
            throw new Exception("ERROR generating PKCS#7 signature");
        }

        $inf = fopen($this->path . "xml/TRA-{$this->service}.tmp", "r");
        $i = 0;
        $CMS = '';
        while (!feof($inf)) {
            $buffer = fgets($inf);
            if ($i++ >= 4) {
                $CMS .= $buffer;
            }
        }
        fclose($inf);
        unlink($this->path . "xml/TRA-{$this->service}.tmp");
        return $CMS;
    }

    private function call_WSAA($cms) {
        $results = $this->client->loginCms(array("in0" => $cms));
        file_put_contents($this->path . "xml/request-loginCms.xml", $this->client->__getLastRequest());
        file_put_contents($this->path . "xml/response-loginCms.xml", $this->client->__getLastResponse());

        if (is_soap_fault($results)) {
            throw new Exception("SOAP Fault: " . $results->faultcode . ": " . $results->faultstring);
        }

        return $results->loginCmsReturn;
    }

    private function xml2array($xml) {
        $json = json_encode(simplexml_load_string($xml));
        return json_decode($json, TRUE);
    }

    public function generar_TA() {
        $this->create_TRA();
        if (file_exists($this->path . "xml/TA-{$this->service}.xml")) {
            unlink($this->path . "xml/TA-{$this->service}.xml");
        }
        $TA = $this->call_WSAA($this->sign_TRA());

        if (!file_put_contents($this->path . "xml/TA-{$this->service}.xml", $TA)) {
            throw new Exception("Error al generar al archivo TA.xml");
        }

        $this->TA = $this->xml2Array($TA);
        return true;
    }

    public function get_expiration() {
        if (empty($this->TA)) {
            $TA_file = file($this->path . "xml/TA-{$this->service}.xml", FILE_IGNORE_NEW_LINES);
            if ($TA_file) {
                $TA_xml = '';
                for ($i = 0; $i < sizeof($TA_file); $i++) {
                    $TA_xml .= $TA_file[$i];
                }
                $this->TA = $this->xml2Array($TA_xml);
                $r = $this->TA["header"]["expirationTime"];
            } else {
                $r = false;
            }
        } else {
            $r = $this->TA["header"]["expirationTime"];
        }
        return $r;
    }
}
?>
