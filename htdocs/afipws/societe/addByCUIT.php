<?php
/* Copyright (C) 2006-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/societe/checkvat/checkVatPopup.php
 *		\ingroup    societe
 *		\brief      Popup screen to validate VAT
 */



// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}


require_once DOL_DOCUMENT_ROOT . '/afipws/class/wsaa.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipws/class/wssrpadron.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipws/exceptionhandler.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

$cuitemisor= str_replace("-","",$conf->global->MAIN_INFO_SIREN);
$vatNumber =str_replace("-","", GETPOST("idPersona",'alpha'));
$result = array();

/*****************
//WSAA
 ****************/

$wsaa = new wsaa();
$wsaa->service='ws_sr_padron_a4';

if($wsaa->get_expiration() < date('c',date('U'))) {

    $wsaa->generar_TA();
}

    $wssrpadron = new wssrpadron($db);

// Carga el archivo TA.xml
$wssrpadron->openTA();

$result=$wssrpadron->getPersona($vatNumber,$cuitemisor);

//Array idImpuesto map to Dolibarr typeent

$idImpuesto=array(20=>'TE_C_FE', //'Monotributo/consumidor final'
                  30=>'TE_A_RI', //IVA Responsable inscripto
                  34=>'TE_B_RNI'  ); //'IVA Responsable no inscripto'

$idProvincia=array(0=>2317,
    1=>2316,
    2=>2301,
    3=>2315,
    4=>2307,
    5=>2308,
    6=>2302,
    7=>2312,
    8=>2311,
    9=>2305,
    10=>2313,
    11=>2314,
    12=>2310,
    13=>2304,
    14=>2303,
    16=>2306,
    17=>2321,
    18=>2309,
    19=>2326,
    20=>2319,
    21=>2318,
    22=>2320,
    23=>2322,
    24=>2323,
);

//get domicilio
$resultDomicilio =array();
foreach ($result->domicilio as $key=>$value) {
    if ($value->tipoDomicilio == 'FISCAL'){
        $resultDomicilio=$value;

        if (array_key_exists($value->idProvincia, $idProvincia)) {
            $resultDomicilio->idProvincia = dol_getIdFromCode($db, $idProvincia[$value->idProvincia], 'c_departements', 'code_departement','rowid');

        }
    }
}
//get impuesto

$resulImpuesto = dol_getIdFromCode($db, $idImpuesto[20], 'c_typent', 'code');


foreach ($result->impuesto as $key=>$value) {
    if ($value->estado == 'ACTIVO' && array_key_exists($value->idImpuesto, $idImpuesto)) {
        $resulImpuesto = dol_getIdFromCode($db, $idImpuesto[$value->idImpuesto], 'c_typent', 'code');

    }
}

//get nombre
if ($result->tipoPersona=='FISICA'){
    $resultName=$result->apellido.' '.$result->nombre;
}else{
    $resultName=$result->razonSocial;
}

$resultToJson=array(
    'estadoClave'=>$result->estadoClave,
    'nombre'=>$resultName,
    'tipoClave'=>$result->tipoClave,
    'tipoDocumento'=>$result->tipoDocumento,
    'idPersona'=>$result->idPersona,
    'numeroDocumento'=>$result->numeroDocumento,
    'domicilio'=>array($resultDomicilio),
    'impuesto'=>$resulImpuesto,
    'nombre'=>$resultName,
    'telefono'=>$result->telefono->numero
);

echo json_encode($resultToJson);



?>