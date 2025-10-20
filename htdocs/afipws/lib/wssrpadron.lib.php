<?php
/* Copyright (C) 2015 Primetec <catrielr@gmail.com>
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
 * or see http://www.gnu.org/
 */




/**
 *	\file       htdocs/afipws/lib/wssrpadron.lib.php
 *	\ingroup    other
 *	\brief      Obtener contribuyente
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}


require_once DOL_DOCUMENT_ROOT . '/afipws/class/wsaa.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipws/class/wssrpadron.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipws/exceptionhandler.php';

$cuitemisor= str_replace("-","",$conf->global->MAIN_INFO_SIREN);


/*****************
//WSAA
 ****************/

$wsaa = new wsaa();
//$wsaa->service='ws_sr_padron_a4';
$wsaa->service= 'ws_sr_padron_a5';


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


$wssrpadron = new wssrpadron($db);

// Carga el archivo TA.xml
$wssrpadron->openTA();



$idPersona='20241952569';

$dummy=$wssrpadron->dummy();
$cae = $wssrpadron->getPersona($idPersona,$cuitemisor);

?>
    