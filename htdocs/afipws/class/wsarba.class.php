<?php
/*
 * Argentina Electronic Invoice module for Dolibarr
 * 2015 Pablo <pablin.php@gmail.com>
 * Copyright (C) 2017 Primetec <info@primetec.com.ar> https://primetec.com.ar
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


//include_once DOL_DOCUMENT_ROOT . '/afipws/class/wsaa_db.class.php';


class wsarba {
    const LOG_XMLS = true;                     		# For debugging purposes

    /*



    /*
    * Constructor
    */
    public function __construct()
    {
        global $conf;


    }

    function build_data_files($boundary, $fields, $files){
        $data = '';
        $eol = "\r\n";

        $delimiter = '-------------' . $boundary;

        foreach ($fields as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
                . $content . $eol;
        }


        //foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $files['formName'] . '"; filename="' . $files['fileName'] . '"' . $eol
                //. 'Content-Type: image/png'.$eol
               // . 'Content-Transfer-Encoding: binary'.$eol
                .'Content-Type: '.$files['type'].$eol
            ;

            $data .= $eol;
            $data .= $files['content'] . $eol;
      //  }
        $data .= "--" . $delimiter . "--".$eol;


        return $data;
    }


    function getFileMimeType($file) {
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $type = finfo_file($finfo, $file);
            finfo_close($finfo);
        } else {
            require_once 'upgradephp/ext/mime.php';
            $type = mime_content_type($file);
        }

        if (!$type || in_array($type, array('application/octet-stream', 'text/plain'))) {
            $secondOpinion = exec('file -b --mime-type ' . escapeshellarg($file), $foo, $returnCode);
            if ($returnCode === 0 && $secondOpinion) {
                $type = $secondOpinion;
            }
        }

        if (!$type || in_array($type, array('application/octet-stream', 'text/plain'))) {
            require_once 'upgradephp/ext/mime.php';
            $exifImageType = exif_imagetype($file);
            if ($exifImageType !== false) {
                $type = image_type_to_mime_type($exifImageType);
            }
        }

        return $type;
    }


    function ConsultarContribuyentes($fechaDesde, $fechaHasta, $cuitContribuyente)
    {
        global $conf;

        //creo archivo xml
        $objxml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="ISO-8859-1"?>' .
            '<CONSULTA-ALICUOTA>' .
            '</CONSULTA-ALICUOTA>');
        $objxml->addChild('fechaDesde', $fechaDesde);
        $objxml->addChild('fechaHasta', $fechaHasta);
        $objxml->addChild('cantidadContribuyentes', '1');
        $objxml->addChild('contribuyentes');
        $objxml->contribuyentes->addAttribute('class', 'list');
        $objxml->contribuyentes->addChild('contribuyente');
        $objxml->contribuyentes->contribuyente->addChild('cuitContribuyente', $cuitContribuyente);

        $xml_hash = md5($objxml->asXML());
        $filename = "DFEServicioConsulta_" . $xml_hash . ".xml";

        $objxml->asXML(DOL_DOCUMENT_ROOT . "/afipws/xml/" . $filename);


        //-------------
        // data fields for POST request
        $fields = array("user" => $conf->global->AFIPWS_WSARBA_USER,
            "password" => $conf->global->AFIPWS_WSARBA_PWD
        );

        // files to upload
        $files=array();
        $files['content'] = file_get_contents(DOL_DOCUMENT_ROOT . "/afipws/xml/" . $filename);
        $files['type']= $this->getFileMimeType(DOL_DOCUMENT_ROOT . "/afipws/xml/" . $filename);
        $files['fileName']=$filename;
        $files['formName']='file';


        // URL to upload to
        $url =  $conf->global->AFIPWS_WSARBA_SERVER;


        $url_data = http_build_query($fields);

        $boundary = uniqid();
        $delimiter = '-------------' . $boundary;

        $post_data = $this->build_data_files($boundary, $fields, $files);

// curl
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
                CURLOPT_CAINFO => DOL_DOCUMENT_ROOT . "/afipws/keys/arba.crt",
            //CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => array(
                //"Authorization: Bearer $TOKEN",
                "Content-Type: multipart/form-data; boundary=" . $delimiter,
                "Content-Length: " . strlen($post_data)

            ),


        ));


        $response = curl_exec($curl);

        $errors = curl_error($curl);
        $response2 = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);



        $xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);



        $json = json_encode($xml);
        return json_decode($json,TRUE);


        //------------



    }




} // class

?>
