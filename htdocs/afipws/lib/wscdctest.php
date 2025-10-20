<?php
/**
 * Created by PhpStorm.
 * User: kty
 * Date: 5/8/2017
 * Time: 2:56 PM
 */


// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

require_once DOL_DOCUMENT_ROOT . '/afipws/class/wsaa.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipws/class/wscdcv1.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipws/exceptionhandler.php';


$cuitemisor= (float)str_replace("-","",$conf->global->MAIN_INFO_SIREN);


/*****************
//WSAA
 ****************/
//header();
$wsaa = new wsaa();
$wsaa->service='wscdc';

if($wsaa->get_expiration() < date('c',date('U'))) {

    if ($wsaa->generar_TA()) {
        //	echo '<br>Nuevo TA';
        setEventMessage('Renovacion de TA satisfactoria' , 'mesgs');
    } else {
        setEventMessage('Error al obtener el TA' , 'errors');
        //echo '<br>Error al obtener el TA';
    }
} else {
    //echo '<br>TA expiration:' . $wsaa->get_expiration();

}


/*****************
//WSFEV1
 ****************/
$wscdcv1 = new wscdcv1();

// Carga el archivo TA.xml
$wscdcv1->openTA();

// Obtener el �ltimo n�mero para este tipo de comprobante / punto de venta:

$cbte_modo = "CAE";
$cuit_emisor = 20267565393;
$pto_vta = 4002;
$cbte_tipo = 1;
$cbte_nro = 109;
$cbte_fch = "20131227";
$imp_total = "121.0";
$cod_autorizacion = 63523178385550;
$doc_tipo_receptor = 80;
$doc_nro_receptor = 30628789661;


$dummy= $wscdcv1->ComprobanteDummy();
$res = $wscdcv1->ComprobanteConstatar($cbte_modo,$cuit_emisor,$pto_vta, $cbte_tipo,$cbte_nro,$cbte_fch,$imp_total,$cod_autorizacion,$doc_tipo_receptor,$doc_nro_receptor, $cuitemisor);

echo json_encode($dummy);
echo '<br>';
echo json_encode($res);
echo '<br>';
echo json_encode($wscdcv1->ComprobantesModalidadConsultar($cuitemisor));
echo '<br>';
echo json_encode($wscdcv1->ComprobantesTipoConsultar($cuitemisor));

echo '<br>';
echo json_encode($wscdcv1->ComprobantesTipoConsultar($cuitemisor));



