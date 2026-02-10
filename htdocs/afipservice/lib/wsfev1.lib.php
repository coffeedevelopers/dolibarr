<?php
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

/*Tabla de comprobantes

* C�digo Descripci�n
* 1 Facturas A
* 2 Notas de D�bito A
* 3 Notas de Cr�dito A
* 4 Recibos A
* 5 Notas de Venta al contado A
* 6 Facturas B
* 7 Notas de D�bito B
* 8 Notas de Cr�dito B
* 9 Recibos B
* 10 Notas de Venta al contado B
* 39 Otros comprobantes A que cumplan con la R.G. N� 3419
* 40 Otros comprobantes B que cumplan con la R.G. N� 3419
* 60 Cuenta de Venta y L�quido producto A
* 61 Cuenta de Venta y L�quido producto B
* 63 Liquidaci�n A
* 64 Liquidaci�n B
* 11: Factura C
* 12: Nota de D�bito C
* 13: Nota de Cr�dito C
* 15: Recibo C

 * 2. C�digos de tipo de documento

* 80 - CUIT
* 86 - CUIL
* 87 - CDI
* 89 - LE
* 90 - LC
* 91 - CI extranjera
* 92 - en tr�mite
* 93 - Acta nacimiento
* 95 - CI Bs. As. RNP
* 96 - DNI
* 94 - Pasaporte
* 00 - CI Polic�a Federal
* 01 - CI Buenos Aires
* 07 - CI Mendoza
* 08 - CI La Rioja
* 09 - CI Salta
* 10 - CI San Juan
* 11 - CI San Luis
* 12 - CI Santa Fe
* 13 - CI Santiago del Estero
* 14 - CI Tucum�n
* 16 - CI Chaco
* 17 - CI Chubut
* 18 - CI Formosa
* 19 - CI Misiones
* 20 - CI Neuqu�n
* 20	CI Neuqu�n
* 21	CI La Pampa
* 22	CI R�o Negro
* 23	CI Santa Cruz
* 24	CI Tierra del Fuego
* 99	Doc. (Otro)
*
*/



/**
 *	\file       htdocs/wsfe/actions_wsfe.inc.php
 *	\ingroup    facture
 *	\brief      Generacion de Factura Electronica
 */
require_once DOL_DOCUMENT_ROOT . '/afipservice/class/wsfev1db.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipservice/class/wsaa.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipservice/class/wsfev1.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipservice/exceptionhandler.php';





function wsfev1($object)
{
 global $db, $user, $conf, $langs;
    $langs->load("companies");
    //si esta validada sale
 //   if ($object->statut==1) {
 //       header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id);
 //       return $result = -1;

 //   }

    //para obtener alicuotas de IVA e impuestos locales
		$tva=array();
		$localtax1=array();
		$localtax2=array();
		$atleastoneratenotnull=0;
		$atleastonediscount=0;



$cuitemisor= str_replace("-","",$conf->global->MAIN_INFO_SIREN);

    //Fix 3.0.1- Se pueden hacer facturas con fechas anterior a la fecha de autorizacion.
    // siempre y cuando no existan factuas autorizadas con fecha superior.

	//Fuerzo la fecha de la factura a la fecha de validacion
     //			$object->date = dol_now();
	//			$object->date_lim_reglement = $object->calculate_date_lim_reglement();


 /*
  * Tipos de Conceptos
  * 1 Producto
  * 2 Servicios
  * 3 Productos y Servicios
  */

	$nblignes = count($object->lines);
				for ($i = 0 ; $i < $nblignes ; $i++){

					if ($object->lines[$i]->product_type == 0) {
					     $isproduct=true;
					     $concepto=1;
					}elseif ($object->lines[$i]->product_type == 1) {
					     $isservice=true;
					     $concepto=2;
					}
				}
    if ($isproduct == true and $isservice == true ) $concepto = 3;



    if ($concepto != 1) {
        $fecha_venc_pago = date("Ymd",$object->date_lim_reglement); //Fechas del per�odo del servicio facturado (solo si concepto = 1?)
        $fecha_serv_desde = date("Ymd",$object->date);
        $fecha_serv_hasta = date("Ymd",$object->date_lim_reglement);
    }else{
        $fecha_venc_pago = NULL;
        $fecha_serv_desde = NULL;
        $fecha_serv_hasta = NULL;
    }


    $fecha_cbte = date("Ymd",$object->date);

    $moneda_id = '';
    $moneda_ctz = 1.00000000;
    if ($object->multicurrency_code == 'ARS') { //?Cambio TOMAS -> If para seleccionar moneda utilizada
        $moneda_id = 'PES'; # no utilizar DOL u otra moneda
    } else if ($object->multicurrency_code == 'USD') {
        $moneda_id = 'DOL';
        $moneda_ctz = get_Cotizacion($moneda_id);
        // $cot = floatval($moneda_ctz) * ($object->total_ttc / $object->multicurrency_total_ttc);
        if ($moneda_ctz != null) {
            // $sql = "UPDATE ".MAIN_DB_PREFIX."facture SET multicurrency_tx = ".((float) 1 / $moneda_ctz)." WHERE rowid = ".((int) $object->id);
            $sql = "UPDATE ".MAIN_DB_PREFIX."facture
            SET multicurrency_tx = ".((float) 1 / $moneda_ctz).",
                total_tva = ".((float) $object->multicurrency_total_tva * $moneda_ctz).",
                total_ht = ".((float) $object->multicurrency_total_ht * $moneda_ctz).",
                total_ttc = ".((float) $object->multicurrency_total_ttc * $moneda_ctz)."
            WHERE rowid = ".((int) $object->id);
            $resql = $db->query($sql);
            if (!$resql) {
                setEventMessage($db->lasterror(), 'errors');
                return -1;
            }
        }
    }

    $imp_total = round(abs(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc),2); //"121.00" ;
    $imp_trib = 0.00;



//  Agrego tasas de IVA
    // Loop on each lines Linas de Productos

    $nblignes = count($object->lines);
    for ($i = 0 ; $i < $nblignes ; $i++)
    {


        $tvaligne= ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->lines[$i]->multicurrency_total_tva : $object->lines[$i]->total_tva;
        $localtax1ligne=$object->lines[$i]->total_localtax1;
        $localtax2ligne=$object->lines[$i]->total_localtax2;

        //FIXED ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;

        //$basetvaligne=$object->lines[$i]->total_ht;
        $basetvaligne=($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht;

        if ($object->remise_percent) $tvaligne-=($tvaligne*$object->remise_percent)/100;
        if ($object->remise_percent) $localtax1ligne-=($localtax1ligne*$object->remise_percent)/100;
        if ($object->remise_percent) $localtax2ligne-=($localtax2ligne*$object->remise_percent)/100;

        if ($object->remise_percent) $basetvaligne-=($basetvaligne*$object->remise_percent)/100;

        $vatrate=(string) $object->lines[$i]->tva_tx;
        $localtax1rate=(string) $object->lines[$i]->localtax1_tx;
        $localtax2rate=(string) $object->lines[$i]->localtax2_tx;


        if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate.='*';
        //?CAMBIO TOMAS (Version) -> Aseguro que las claves existan antes de sumar valores
        if (!isset($localtax1[$localtax1rate])) $localtax1[$localtax1rate] = 0;
        if (!isset($localtax2[$localtax2rate])) $localtax2[$localtax2rate] = 0;
        if (!isset($tva[$vatrate][0])) $tva[$vatrate][0] = 0;
        if (!isset($tva[$vatrate][1])) $tva[$vatrate][1] = 0;
        $localtax1[$localtax1rate] += abs($localtax1ligne);
        $localtax2[$localtax2rate] += abs($localtax2ligne);
        $tva[$vatrate][0] += abs($tvaligne);
        $tva[$vatrate][1] += abs($basetvaligne);
    }


    // Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
/*
 * Alicuotas de IVA
  3 0%
  4 10.5%
  5 21%
  6 27%
  8 5%
  9 2.5%
*/

    $i=0;
    $regfeiva = array ();

    foreach  ($tva as $tasa=>$valor)
    {
        if ($tasa == "0.00"){
            $regfeiva['AlicIva'][]=
                array (
                    'Id' => 3,
                    'BaseImp' =>round($valor[1],2),
                    'Importe' => round($valor[0],2),

                );

        }elseif ($tasa == "10.50"){
            $regfeiva['AlicIva'][]=
                array (
                    'Id' => 4,
                    'BaseImp' =>round($valor[1],2),
                    'Importe' => round($valor[0],2),
                );

        }elseif ($tasa == "21.000") {
            $regfeiva['AlicIva'][]=
                array (
                    'Id' => 5,
                    'BaseImp' =>round($valor[1],2),
                    'Importe' => round($valor[0],2),
                );

        }elseif ($tasa == "27.000") {
            $regfeiva['AlicIva'][]=
                array (
                    'Id' => 6,
                    'BaseImp' =>round($valor[1],2),
                    'Importe' => round($valor[0],2),
                );

        }elseif ($tasa == "5.000") {
            $regfeiva['AlicIva'][]=
                array (
                    'Id' => 8,
                    'BaseImp' =>round($valor[1],2),
                    'Importe' => round($valor[0],2),
                );

        }elseif ($tasa == "2.500"){
            $regfeiva['AlicIva'][]=
                array (
                    'Id' => 9,
                    'BaseImp' =>round($valor[1],2),
                    'Importe' => round($valor[0],2),
                );

        }
        $i++;
    }
//tipos de impuestos
//1	Impuestos nacionales
//2	Impuestos provinciales
//3	Impuestos municipales
//4	Impuestos Internos
//99	Otro
    // Detalle de otros tributos
//$regfetrib['Id'] = 1;
//$regfetrib['Desc'] = 'impuesto';
//$regfetrib['BaseImp'] = 0;
//$regfetrib['Alic'] = 0;
//$regfetrib['Importe'] = 0;

$i=0;
//Compatibilidad para Dolibarr LocalTax
    $regfetrib = array ();
    foreach  ($localtax1 as $tasa=>$valor)
{

    $regfetrib['Tributo'][] =
        array(
            'Id'=> '2',
            'Desc'=>$langs->transcountry("AmountLT1", $object->thirdparty->country_code),
            'BaseImp' => abs(round($object->total_ht, 2)),
            'Alic'=>abs(round($tasa, 2)),
            'Importe' => abs(round($valor, 2)),
        );
    $i++;
}
    $i=0;
    foreach  ($localtax2 as $tasa=>$valor)
    {

        $regfetrib['Tributo'][] =
            array(
                'Id'=> '99',
                'Desc'=>$langs->transcountry("AmountLT2", $object->thirdparty->country_code),
                'BaseImp' => abs(round($object->total_ht, 2)),
                'Alic'=>abs(round($tasa, 2)),
                'Importe' =>abs(round($valor, 2)),
            );
        $i++;
    }



    //FIXME No soportada multicurency
   // $imp_tot_conc=round($object->total_localtax1+$object->total_localtax2,2); //Importe total del comprobante, Debe ser igual a Importe neto no gravado + Importe exento + Importe neto gravado + todos los campos de IVA al XX% + Importe de tributos.
      $imp_tot_conc =0;

    //------------------------------------------
    //AGREGAR EXTRATAX

    if($conf->extratax->enabled) {
        require_once DOL_DOCUMENT_ROOT . '/extratax/lib/extratax.lib.php';
        $extrataxforthisfacture = extrataxForThisFacture($object);


        $totaltax=0;
        foreach ($extrataxforthisfacture as $value) {

            $totaltax += (float)$value['amount'];

            $regfetrib['Tributo'][] =
                array(
                    'Id'=> $value['tipo_afip'],
                    'Desc'=>$value['label'],
                    'BaseImp' => round($object->total_ht, 2),
                    'Alic'=>round($value['rate'],2),
                    'Importe' => round($value['amount'], 2)
                );
        }
        $imp_trib=round($object->total_localtax1+$object->total_localtax2+$totaltax,2);
        $imp_total = round(abs(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc+$imp_trib : $object->total_ttc+$imp_trib),2); //"121.00" ;


    }




    $typecomprobante=get_typecomprobante($object);
    $tipo_cbte=$typecomprobante['tipo_cbte'];
    $typeent=$typecomprobante['typeent'];

   //Chequeo tipo empresa


//    ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;

    if ($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE !=2301){  //Monotributista
        // $imp_iva = round(abs($object->total_tva),2);
        $imp_iva = round(abs(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->multicurrency_total_tva : $object->total_tva),2);//?CAMBIO TOMAS -> Correccion de seleccion de iva para usd
        $imp_op_ex = 0.00;
        // $imp_neto = round(abs($object->total_ht),2);
        $imp_neto = round(abs(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ht : $object->total_ht),2);//?CAMBIO TOMAS -> Correccion de seleccion de total neto para usd

    }else{ //factura C
        $imp_iva = 0.00; //no se informa imp_iva para Factura C
        $imp_op_ex = 0.00;
        $imp_neto = round(abs(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc),2); //"121.00" ;;
        $imp_tot_conc=0;
        unset($regfeiva);
        unset($regfetrib);

    }


//Verifico que documento informmar.

    //if($tipo_cbte == 6 || $tipo_cbte == 7 || $tipo_cbte == 8 ||  $tipo_cbte == 11 ||  $tipo_cbte == 13 ||  $tipo_cbte == 12) {
        $nro_doc = (double)preg_replace('/[^0-9]/', '', $object->thirdparty->idprof1); //"20241952569"

        if (strlen($nro_doc) == 8) {  //es DNI
            $tipo_doc = 96;
        }elseif (strlen($nro_doc) == 11) { //es cuit
            $tipo_doc = 80;
        }else{ //consumidor final
            $tipo_doc = 99;
            $nro_doc = 0; //"20241952569"
        }
    //}

$ptovta = get_ptovta(); //Punto de Venta
if (!$conf->global->AFIP_NOT_SEND_COND_IVA_RECEPTOR) {//?CAMBIO TOMAS (Cambio temporal para produccion)
    // $condicionIva = get_CondicionIva($tipo_cbte);//?CAMBIO TOMAS

    //?Hardcodeo de iva (Cambio temporal para produccion)
    $condicionIva = null;
    switch ($object->thirdparty->typent_code) {
        case 'TE_A_RI':
            $condicionIva = 1;
            break;
        case 'TE_B_RNI':
            $condicionIva = 4;
            break;
        case 'TE_C_FE':
            $condicionIva = 4;
            break;
        case 'TE_C_MO':
            $condicionIva = 4;
            break;
    }
    $regfe['CondicionIVA'] = $condicionIva; //?CAMBIO TOMAS Condicion de IVA
}

$regfe['CbteTipo']=$tipo_cbte;
$regfe['Concepto']=$concepto;
$regfe['DocTipo']=$tipo_doc; //80=CUIL
$regfe['DocNro']=$nro_doc;
// $regfe['CondicionIVA'] = $condicionIva; //?CAMBIO TOMAS Condicion de IVA
//$regfe['CbteDesde']=; 	// nro de comprobante desde (para cuando es lote)
//$regfe['CbteHasta']=;	// nro de comprobante hasta (para cuando es lote)
$regfe['CbteFch']=$fecha_cbte; 	// fecha emision de factura
$regfe['ImpNeto']=$imp_neto;			// neto gravado
$regfe['ImpTotConc']=$imp_tot_conc;			// no gravado
$regfe['ImpIVA']=$imp_iva;			// IVA liquidado
$regfe['ImpTrib']=$imp_trib;			// otros tributos
$regfe['ImpOpEx']=$imp_op_ex;			// operacion exentas
$regfe['ImpTotal']=$imp_total;			// total de la factura. ImpNeto + ImpTotConc + ImpIVA + ImpTrib + ImpOpEx
$regfe['FchServDesde']=$fecha_serv_desde;	// solo concepto 2 o 3
$regfe['FchServHasta']=$fecha_serv_hasta;	// solo concepto 2 o 3
$regfe['FchVtoPago']=$fecha_venc_pago;		// solo concepto 2 o 3
$regfe['MonId']=$moneda_id; 			// Id de moneda 'PES'
$regfe['MonCotiz']=$moneda_ctz;			// Cotizacion moneda. Solo exportacion

// Comprobantes asociados (solo notas de crédito y débito):
    $regfeasoc=array();

    if($object->fk_facture_source OR $object->fk_fac_rec_source){
        if($object->fk_facture_source) {
            $source_id =$object->fk_facture_source;
        }
        if($object->fk_fac_rec_source) {
            $source_id =$object->fk_fac_rec_source;
        }

        $facture_source = new Facture($db);
        $facture_source->fetch($source_id);

        $wsfedb_source = new wsfedb($db);
        $wsfedb_source->fk_facture = $source_id;
        $wsfedb_source->fetch();

        $regfeasoc['CbteAsoc']['Tipo'] = $wsfedb_source->cbttipo; //91; //tipo 91|5
        $regfeasoc['CbteAsoc']['PtoVta'] = $wsfedb_source->puntodeventa;
        $regfeasoc['CbteAsoc']['Nro'] = $wsfedb_source->cbtnro;
        $regfeasoc['CbteAsoc']['Cuit'] = $wsfedb_source->cuitemisor;
        $regfeasoc['CbteAsoc']['CbteFch'] = date("Ymd",$facture_source->date);

        if($object->array_options['options_fcerech']=='1') $rechazada=true;
    }

    if($object->type==200){
        $regfe['FchVtoPago']=date("Ymd",$object->date_lim_reglement);
    }




/*****************
 //WSAA
 ****************/
    generar_TA();



/*****************
 //WSFEV1
 ****************/
$wsfev1 = new wsfev1($db);

// Carga el archivo TA.xml
$wsfev1->openTA();

    //?CAMBIO TOMAS (Version) -> Verifico que $_SESSION['wsfereproceso'] sea un array antes de darlo como parametro a array_key_exists()
    if (is_array($_SESSION['wsfereproceso']) && array_key_exists($object->ref,$_SESSION['wsfereproceso'])){   //si existe llamdo a CompConsultar para traer los datos del comprobante

        $nro1=intval(substr($_SESSION['wsfereproceso'][$object->ref],-8));
        $compconsul=$wsfev1->FECompConsultar($tipo_cbte,$nro1,$ptovta,$cuitemisor);
        $wsfev1->ObsCode= $compconsul->ResultGet->Observaciones->Obs->Code;
        $wsfev1->ObsMsg= $compconsul->ResultGet->Observaciones->Obs->Msg;



        if ($compconsul->Errors){
            setEventMessage($compconsul->Errors->Err->Code .": ".$compconsul->Errors->Err->Msg, 'errors');
            unset($_SESSION['wsfereproceso'][$object->ref]); //limpio para no reproceso
            $result = -1;

        }else{

            $caenum = $compconsul->ResultGet->CodAutorizacion;
            $caefvt = $compconsul->ResultGet->FchVto;

            setEventMessage('Reproceso CAE ' . $caenum . ' Vencimiento: ' . $caefvt);
            $result = 1;
        }

    }else {


        $_SESSION['wsfereproceso'][$object->ref] = $object->newref;

        $nro1 = intval(substr($object->newref, -8));

        $cae = $wsfev1->FECAESolicitar(
            $nro1, // ultimo numero de comprobante autorizado mas uno
            $ptovta,  // el punto de venta
            $regfe, // los datos a facturar
            $regfeasoc,
            $regfetrib,
            $regfeiva,
            $cuitemisor,
            $rechazada
        );

        // Si detecto error 10192, significa que necesito FCE, cambio el numero de comprobante y pido nuevo cae
        if ($cae->FeDetResp->FECAEDetResponse->Observaciones->Obs->Code == "10192"){
            $_SESSION['wsfereproceso'][$object->id] = "10192";
        }
        /*  $regfe['CbteTipo'] = $regfe['CbteTipo'] + 200; // Cambio el tipo de comprobante
        	$regfe['FchVtoPago']=date("Ymd",$object->date_lim_reglement); // Agrego Fecha de vencimiento de Pago

        	$nro1=intval($wsfev1->FECompUltimoAutorizado($ptovta,$regfe['CbteTipo'],$cuitemisor))+1; //Verifico el ultimo comprobante autorizado

        	if($regfe['CbteTipo']==201){$tipo="FCEA";}
        	elseif($regfe['CbteTipo']==202){$tipo="NDEA";}
        	elseif($regfe['CbteTipo']==203){$tipo="NCEA";}

        	$maskPto = "0000";
        	$len = strlen($ptovta);
        	$maskPto = substr($maskPto,0,strlen($maskPto)-$len);
        	$maskPto .= $ptovta;

        	$maskNro="00000000";
        	$len = strlen($nro1);
        	$maskNro = substr($maskNro,0,strlen($maskNro)-$len);
        	$maskNro .= $nro1;

        	$ref = $tipo."-".$maskPto."-".$maskNro;

        	$object->ref = $ref;

        	$cae = $wsfev1->FECAESolicitar(
        			$nro1, // ultimo numero de comprobante autorizado mas uno
        			$ptovta,  // el punto de venta
        			$regfe, // los datos a facturar
        			$regfeasoc,
        			$regfetrib,
        			$regfeiva,
        			$cuitemisor
        	);
        }*/

        if ($cae->FeDetResp->FECAEDetResponse->Resultado == 'R' || $cae->FeDetResp->FECAEDetResponse->CAE == '') {

            //si hay errores de CAE  prox Valid NO ES Reproceso
            unset($_SESSION['wsfereproceso'][$object->ref]); //limpio para no reproceso
            $result = -1;

        } else {

            $caenum = $cae->FeDetResp->FECAEDetResponse->CAE;
            $caefvt = $cae->FeDetResp->FECAEDetResponse->CAEFchVto;

            unset($_SESSION['wsfereproceso'][$object->id]); //limpio para no reproceso

            setEventMessage('CAE ' . $caenum . ' Vencimiento: ' . $caefvt);
            $result = 1;
        }
    }

    //muestros errores y observaciones
    $msj = array();

    if ($cae->FeDetResp->FECAEDetResponse->Observaciones->Obs) {
     $style="warnings";
        foreach ($cae->FeDetResp->FECAEDetResponse->Observaciones->Obs as $value) {

           if (!is_object($value)){
               $msj[] .= $cae->FeDetResp->FECAEDetResponse->Observaciones->Obs->Code.": ".$cae->FeDetResp->FECAEDetResponse->Observaciones->Obs->Msg;

               if ($cae->FeDetResp->FECAEDetResponse->Observaciones->Obs->Code == "10192"){
                   $msj[] .="<br/>Es necesario hacer una factura de credito electronica, por favor valide la factura nuevamente.";
               }
               if ($cae->FeDetResp->FECAEDetResponse->Observaciones->Obs->Code == "10055"){
                   $msj[] .="<br/>Verifique que tiene correctamente cargado el CBU de la empresa.";
               }
                              break;
               }else {

               $msj[] .= $value->Code . ": " . $value->Msg;
           }

        }
    }

    if ($cae->FeDetResp->FECAEDetResponse->Errors->Errs) {
        $style="errors";
        foreach ($cae->FeDetResp->FECAEDetResponse->Errors->Errs as $value) {

            if (!is_object($value)){
                $msj[] .= $cae->FeDetResp->FECAEDetResponse->Errors->Errs->Code.": ".$cae->FeDetResp->FECAEDetResponse->Errors->Errs->Msg;
                break;
            }else {

                $msj[] .= $value->Code . ": " . $value->Msg;
            }

        }
    }

    if ($wsfev1->error) {
        $style="errors";
        $msj[] .= $wsfev1->error;
      }



    setEventMessage ($msj, $style);
    // -------------

# Verifico que no haya rechazo o advertencia al generar el CAE
    if ($result >= 0) {
        //Grabo datos en WSFE_DB

        $wsfedb = new wsfedb($db);
        $wsfedb->fk_facture = $object->id;
        $wsfedb->cae = $caenum;
        $wsfedb->caevto = $caefvt;
        $wsfedb->obs = $wsfev1->ObsCode." ".$wsfev1->ObsMsg;
        $wsfedb->puntodeventa = $ptovta;
        $wsfedb->cbtnro = $nro1;
        $wsfedb->fk_facture = $object->id;
        $wsfedb->version = "php2";
        $wsfedb->entity_id = $_SESSION['dol_entity'];
        $wsfedb->divisa = $regfe['MonId']; //?CAMBIO TOMAS -> Agrego moneda
        $wsfedb->concepto = $regfe['Concepto'];
        $wsfedb->cbttipo = $regfe['CbteTipo'];
        $wsfedb->cuitemisor = $cuitemisor;
        $wsfedb->xmlrequest = ""; //$WSFE->XmlRequest;
        $wsfedb->xmlresponse =""; //$WSFE->XmlResponse;
        $wsfedb->errc = $wsfev1->Code;
        $wsfedb->errm = $wsfev1->error;
        $ret=$wsfedb->update();

        if ($ret <0) $object->errors++;

    }

    if (count($object->errors)) {
        setEventMessage($object->error, 'errors');
        $result = -1;
    }else{
        //si no hay errores   prox Valid NO ES Reproceso
        unset($_SESSION['wsfereproceso'][$object->ref]); //limpio para no reproceso


        }

    //extratax
    if($conf->extratax->enabled && $result >= 0 ) {
        require_once DOL_DOCUMENT_ROOT . '/extratax/lib/extratax.lib.php';
        forceTotalOnValidate($object,$imp_trib); //actualizo el TOTAL de la factura
        putExtrataxFacture($object, $extrataxforthisfacture); //grabo info de EXTRATAX en factura.
    }

    return $result;
}

function isCUIT( $cuit ) {
    $esCuit=false;
    $cuit_rearmado="";
    //separo cualquier caracter que no tenga que ver con numeros
    for ($i=0; $i < strlen($cuit); $i++) {
        if ((Ord(substr($cuit, $i, 1)) >= 48) && (Ord(substr($cuit, $i, 1)) <= 57))     {
            $cuit_rearmado = $cuit_rearmado . substr($cuit, $i, 1);
        }
    }
    $cuit=$cuit_rearmado;
    if ( strlen($cuit_rearmado) <> 11) {  // si to estan todos los digitos
        $esCuit=false;
    } else {
        $x=$i=$dv=0;
        // Multiplico los d�gitos.
        $vec[0] = (substr($cuit, 0, 1)) * 5;
        $vec[1] = (substr($cuit, 1, 1)) * 4;
        $vec[2] = (substr($cuit, 2, 1)) * 3;
        $vec[3] = (substr($cuit, 3, 1)) * 2;
        $vec[4] = (substr($cuit, 4, 1)) * 7;
        $vec[5] = (substr($cuit, 5, 1)) * 6;
        $vec[6] = (substr($cuit, 6, 1)) * 5;
        $vec[7] = (substr($cuit, 7, 1)) * 4;
        $vec[8] = (substr($cuit, 8, 1)) * 3;
        $vec[9] = (substr($cuit, 9, 1)) * 2;

        // Suma cada uno de los resultado.
        for( $i = 0;$i<=9; $i++) {
            $x += $vec[$i];
        }
        $dv = (11 - ($x % 11)) % 11;
        if ($dv == (substr($cuit, 10, 1)) ) {
            $esCuit=true;
        }
    }
    return( $esCuit );
}


function get_ptovta(){
    global $conf;
    if ($conf->global->AFIPWS_WSFE_MODE == 0) {    //0=punto de venta global 1=punto de venta por usuario
        $ptovta = (int)$conf->global->AFIPWS_WSFE_PTOVTA; //Punto de Venta
    } else {
    //futuras versiones
        $ptovta = (int)$conf->global->AFIPWS_WSFE_PTOVTA; //Punto de Venta
    }

    return $ptovta;
}

function get_typecomprobante($object){

    global $conf,$db;

    if ($_SESSION['wsfereproceso'][$object->id]=="10192" && $object->type<200){
        $object->type = 200 + $object->type;
    }

    if($object->fk_facture_source OR $object->fk_fac_rec_source){
        if($object->fk_facture_source) {$source_id =$object->fk_facture_source;}
        if($object->fk_fac_rec_source) {$source_id =$object->fk_fac_rec_source;}

        $wsfedb_source = new wsfedb($db);
        $wsfedb_source->fk_facture = $source_id;
        $wsfedb_source->fetch();

        if($wsfedb_source->cbttipo>=200 AND $object->type < 200){
            $object->type=$object->type+200;
        }
    }

    //Chequeo tipo empresa
    if ($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE !=2301){  //Monotributista

        // type of invoice (0=Standard invoice, 1=Replacement invoice, 2=Credit note invoice, 3=Deposit invoice)
        if ($object->thirdparty->typent_code == "A" || $object->thirdparty->typent_code == "TE_A_RI") {  //"A" por compatibilidad modulo viejo

            if ($object->type == 0){
                $typeent="FA-";
                $tipo_cbte = 1;
            }elseif ($object->type == 2) {
                $typeent="NCA-";
                $tipo_cbte = 3;
            }elseif ($object->type == 1) { //nota de debito A
                $typeent="NDA-";
                $tipo_cbte = 2;
            }elseif ($object->type == 200){
                $typeent="FCEA-";
                $tipo_cbte = 201;
            }elseif ($object->type == 202) {
                $typeent="NCEA-";
                $tipo_cbte = 203;
            }elseif ($object->type == 201) { //nota de debito A
                $typeent="NDEA-";
                $tipo_cbte = 202;
            }

        }else{

            if ($object->type == 0){
                $typeent="FB-";
                $tipo_cbte = 6;
            }elseif ($object->type == 2) {
                $typeent="NCB-";
                $tipo_cbte = 8;
            }elseif ($object->type == 1) { //nota de debito B
                $typeent="NDB-";
                $tipo_cbte = 7;
            }
        }

    }else{ //factura C

        if ($object->type == 0){
            $typeent="FC-";
            $tipo_cbte = 11;
        }elseif ($object->type == 2) {
            $typeent="NCC-";
            $tipo_cbte = 13;
        }elseif ($object->type == 1) { //nota de debito C
            $typeent="NDC-";
            $tipo_cbte = 12;
        }
    }
    return $ret = array('typeent'=>$typeent,
                        'tipo_cbte'=>$tipo_cbte);
}

function generar_TA()
{
    global $db;
    $wsaa = new wsaa($db);
    $wsaa->service = 'wsfe';


        if ($wsaa->get_expiration() < date('c', date('U'))) {

            if ($wsaa->generar_TA()) {
                //	echo '<br>Nuevo TA';
                setEventMessage('Renovacion de TA satisfactoria', 'mesgs');
            } else {
                setEventMessage('Error al obtener el TA', 'errors');
                //echo '<br>Error al obtener el TA';
            }
        }


}

function get_CompNextNum($object,$numref=false)
{
    global $db, $conf;

    $wsfev1 = new wsfev1($db);

    generar_TA();
    // Carga el archivo TA.xml
    $wsfev1->openTA();

    $ptovta = get_ptovta();
    $typecomprobante = get_typecomprobante($object);
    $tipo_cbte = $typecomprobante['tipo_cbte'];
    $typeent = $typecomprobante['typeent'];
    $cuitemisor = str_replace("-", "", $conf->global->MAIN_INFO_SIREN);

// Obtener el �ltimo n�mero para este tipo de comprobante / punto de venta:



    $nro = $wsfev1->FECompUltimoAutorizado($ptovta, $tipo_cbte, $cuitemisor);
    if ($wsfev1->error) {

        setEventMessage($wsfev1->error, 'errors');
        return 0;
    }

        $numref = $typeent . str_pad($ptovta, 4, "0", STR_PAD_LEFT) . "-" . str_pad($nro + 1, 8, "0", STR_PAD_LEFT); //numero de factura electronica



        return $numref;



}

function get_TiposTributos()
{
    global $db, $conf;

    $wsfev1 = new wsfev1($db);

    generar_TA();
    // Carga el archivo TA.xml
    $wsfev1->openTA();

    $cuitemisor = str_replace("-", "", $conf->global->MAIN_INFO_SIREN);

// Obtener el �ltimo n�mero para este tipo de comprobante / punto de venta:

    if ($wsfev1->error) {

        setEventMessage($wsfev1->error, 'errors');
        return '';
    }

    $TiposTributos = $wsfev1->FEParamGetTiposTributos($cuitemisor);



    return $TiposTributos;



}


function get_CondicionIva($tipo_cbte)//?CAMBIO TOMAS
{
    global $db, $conf;

    $wsfev1 = new wsfev1($db);

    generar_TA();
    // Carga el archivo TA.xml
    $wsfev1->openTA();

    $cuitemisor = str_replace("-", "", $conf->global->MAIN_INFO_SIREN);

    // Obtener la condicion de iva del receptor:

    if ($wsfev1->error) {

        setEventMessage($wsfev1->error, 'errors');
        return '';
    }

    $condIva = $wsfev1->FEParamGetCondicionIvaReceptor($cuitemisor);

    $tipo_cbte = $tipo_cbte == 6 ? 5 : $tipo_cbte;//Modifico valor en caso de que sea 6 ya que en afip factura B es 5, a diferencia de la asignacion en get_typecomprobante()

    $condIvaTipoCbt = null;
    foreach ($condIva as $key => $value) {
        if ($value->Id == $tipo_cbte) {
            $condIvaTipoCbt = $value->Id;
            break;
        }
    }

    return $condIvaTipoCbt;
}

function get_Cotizacion($moneda_id)//?CAMBIO TOMAS
{
    global $db, $conf;

    $wsfev1 = new wsfev1($db);

    generar_TA();
    // Carga el archivo TA.xml
    $wsfev1->openTA();

    $cuitemisor = str_replace("-", "", $conf->global->MAIN_INFO_SIREN);

    // Obtener la condicion de iva del receptor:

    if ($wsfev1->error) {

        setEventMessage($wsfev1->error, 'errors');
        return '';
    }

    $cotDol = $wsfev1->FEParamGetCotizacion($cuitemisor, $moneda_id);

    return $cotDol;
}

?>
