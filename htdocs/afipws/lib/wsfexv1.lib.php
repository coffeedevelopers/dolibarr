<?php
require_once DOL_DOCUMENT_ROOT . '/afipws/class/wsfev1db.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipws/class/wsaa.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipws/class/wsfev1.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipws/exceptionhandler.php';

/**
 * Función para emitir una factura electrónica con AFIP
 * 
 * @param object $object Objeto factura
 * @return int Resultado de la operación
 */
function wsfexv1($object) {
    global $db, $user, $conf, $langs;
    
    $langs->load("companies");
    
    // Si la factura ya está validada, redirigir
    if ($object->statut == 1) {
        header("Location: " . $_SERVER["PHP_SELF"] . "?facid=" . $object->id);
        $result = -1;
        return;
    }
    
    $tva = array();
    $localtax1 = array();
    $localtax2 = array();
    $atleastoneratenotnull = 0;
    $atleastonediscount = 0;
    
    $cuitemisor = str_replace("-", '', $conf->global->MAIN_INFO_SIREN);
    $object->date = dol_now();
    $object->date_lim_reglement = $object->calculate_date_lim_reglement();
    
    // Determinar si hay productos o servicios en la factura
    $nblignes = count($object->lines);
    $isproduct = false;
    $isservice = false;
    
    for ($i = 0; $i < $nblignes; $i++) {
        if ($object->lines[$i]->product_type == 0) {
            $isproduct = true;
            $concepto = 1;
        } elseif ($object->lines[$i]->product_type == 1) {
            $isservice = true;
            $concepto = 2;
        }
    }
    
    if ($isproduct == true and $isservice == true) {
        $concepto = 3;
    }
    
    // Si hay servicios, configurar fechas de servicio
    if ($concepto != 1) {
        $fecha_venc_pago = date("Ymd", $object->date_lim_reglement);
        $fecha_serv_desde = date("Ymd", $object->date);
        $fecha_serv_hasta = date("Ymd", $object->date_lim_reglement);
    } else {
        $fecha_venc_pago = NULL;
        $fecha_serv_desde = NULL;
        $fecha_serv_hasta = NULL;
    }
    
    $imp_total = round(abs($object->total_ttc), 2);
    $imp_trib = 0.0;
    $fecha_cbte = date("Ymd", $object->date);
    $moneda_id = "PES";
    $moneda_ctz = 1.0;
    
    // Procesar impuestos
    $nblignes = count($object->lines);
    for ($i = 0; $i < $nblignes; $i++) {
        $tvaligne = $object->lines[$i]->total_tva;
        $localtax1ligne = $object->lines[$i]->total_localtax1;
        $localtax2ligne = $object->lines[$i]->total_localtax2;
        $basetvaligne = $object->lines[$i]->total_ht;
        
        if ($object->remise_percent) {
            $tvaligne -= $tvaligne * $object->remise_percent / 100;
        }
        if ($object->remise_percent) {
            $localtax1ligne -= $localtax1ligne * $object->remise_percent / 100;
        }
        if ($object->remise_percent) {
            $localtax2ligne -= $localtax2ligne * $object->remise_percent / 100;
        }
        if ($object->remise_percent) {
            $basetvaligne -= $basetvaligne * $object->remise_percent / 100;
        }
        
        $vatrate = (string) $object->lines[$i]->tva_tx;
        $localtax1rate = (string) $object->lines[$i]->localtax1_tx;
        $localtax2rate = (string) $object->lines[$i]->localtax2_tx;
        
        if (($object->lines[$i]->info_bits & 1) == 1) {
            $vatrate .= "*";
        }
        
        $tva[$vatrate][0] += abs($tvaligne);
        $localtax1[$localtax1rate] += abs($localtax1ligne);
        $localtax2[$localtax2rate] += abs($localtax2ligne);
        $tva[$vatrate][1] += abs($basetvaligne);
    }
    
    // Generar array de alícuotas de IVA
    $i = 0;
    $regfeiva = array();
    foreach ($tva as $tasa => $valor) {
        if ($tasa == "0.00") {
            $regfeiva["AlicIva"][] = array(
                "Id" => 3,
                "BaseImp" => round($valor[1], 2),
                "Importe" => round($valor[0], 2)
            );
        } elseif ($tasa == "10.50") {
            $regfeiva["AlicIva"][] = array(
                "Id" => 4,
                "BaseImp" => round($valor[1], 2),
                "Importe" => round($valor[0], 2)
            );
        } elseif ($tasa == "21.000") {
            $regfeiva["AlicIva"][] = array(
                "Id" => 5,
                "BaseImp" => round($valor[1], 2),
                "Importe" => round($valor[0], 2)
            );
        } elseif ($tasa == "27.000") {
            $regfeiva["AlicIva"][] = array(
                "Id" => 6,
                "BaseImp" => round($valor[1], 2),
                "Importe" => round($valor[0], 2)
            );
        } elseif ($tasa == "5.000") {
            $regfeiva["AlicIva"][] = array(
                "Id" => 8,
                "BaseImp" => round($valor[1], 2),
                "Importe" => round($valor[0], 2)
            );
        } elseif ($tasa == "2.500") {
            $regfeiva["AlicIva"][] = array(
                "Id" => 9,
                "BaseImp" => round($valor[1], 2),
                "Importe" => round($valor[0], 2)
            );
        }
        $i++;
    }
    
    // Procesar impuestos locales
    $i = 0;
    $regfetrib = array();
    foreach ($localtax1 as $tasa => $valor) {
        $regfetrib["Tributo"][] = array(
            "Id" => "2",
            "Desc" => $langs->transcountry("AmountLT1", $object->thirdparty->country_code),
            "BaseImp" => round($object->total_ht, 2),
            "Alic" => round($tasa, 2),
            "Importe" => round($valor, 2)
        );
        $i++;
    }
    
    $i = 0;
    foreach ($localtax2 as $tasa => $valor) {
        $regfetrib["Tributo"][] = array(
            "Id" => "99",
            "Desc" => $langs->transcountry("AmountLT2", $object->thirdparty->country_code),
            "BaseImp" => round($object->total_ht, 2),
            "Alic" => round($tasa, 2),
            "Importe" => round($valor, 2)
        );
        $i++;
    }
    
    $imp_tot_conc = round($object->total_localtax1 + $object->total_localtax2, 2);
    
    // Obtener tipo de comprobante
    $typecomprobante = get_typecomprobante($object);
    $tipo_cbte = $typecomprobante["tipo_cbte"];
    $typeent = $typecomprobante["typeent"];
    
    // Calcular importes según tipo de entidad
    if ($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE != 2301) {
        $imp_iva = round(abs($object->total_tva), 2);
        $imp_op_ex = 0.0;
        $imp_neto = round(abs($object->total_ht), 2);
    } else {
        $imp_iva = 0.0;
        $imp_op_ex = 0.0;
        $imp_neto = $imp_total;
        $imp_tot_conc = 0;
        unset($regfeiva);
        unset($regfetrib);
    }
    
    // Determinar tipo de documento del cliente
    $nro_doc = (double) preg_replace('/[^0-9]/', '', $object->thirdparty->idprof1);
    if (strlen($nro_doc) == 8) {
        $tipo_doc = 96;
    } elseif (strlen($nro_doc) == 11) {
        $tipo_doc = 80;
    } else {
        $tipo_doc = 99;
        $nro_doc = 0;
    }
    
    $ptovta = get_ptovta();
    
    // Armar array de datos para solicitar CAE
    $regfe["CbteTipo"] = $tipo_cbte;
    $regfe["Concepto"] = $concepto;
    $regfe["DocTipo"] = $tipo_doc;
    $regfe["DocNro"] = $nro_doc;
    $regfe["CbteFch"] = $fecha_cbte;
    $regfe["ImpNeto"] = $imp_neto;
    $regfe["ImpTotConc"] = $imp_tot_conc;
    $regfe["ImpIVA"] = $imp_iva;
    $regfe["ImpTrib"] = $imp_trib;
    $regfe["ImpOpEx"] = $imp_op_ex;
    $regfe["ImpTotal"] = $imp_total;
    $regfe["FchServDesde"] = $fecha_serv_desde;
    $regfe["FchServHasta"] = $fecha_serv_hasta;
    $regfe["FchVtoPago"] = $fecha_venc_pago;
    $regfe["MonId"] = $moneda_id;
    $regfe["MonCotiz"] = $moneda_ctz;
    
    $regfeasoc = array();
    
    // Generar Token de Acceso
    generar_TA();
    
    // Inicializar cliente AFIP
    $wsfev1 = new wsfev1($db);
    $wsfev1->openTA();
    
    $nro1 = intval(substr($object->newref, -1));
    
    // Solicitar CAE a AFIP
    $cae = $wsfev1->FECAESolicitar($nro1, $ptovta, $regfe, $regfeasoc, $regfetrib, $regfeiva, $cuitemisor);
    
    if ($cae == false || $cae["cae"] <= 0) {
        setEventMessage("Error al obtener CAE " . "Code: " . $wsfev1->Code . " " . $wsfev1->Msg . " " . $wsfev1->ObsCode . " Msg: " . $wsfev1->ObsMsg, "errors");
        $result = -1;
    } else {
        $caenum = $cae["cae"];
        $caefvt = $cae["fecha_vencimiento"];
        setEventMessage("CAE " . $caenum . " Vencimiento: " . $caefvt);
        $result = 1;
    }
    
    // Si hay éxito, guardar datos del CAE en base de datos
    if ($result >= 0) {
        $wsfedb = new wsfedb($db);
        $wsfedb->fk_facture = $object->id;
        $wsfedb->cae = $caenum;
        $wsfedb->caevto = $caefvt;
        $wsfedb->obs = $wsfev1->ObsCode . " " . $wsfev1->ObsMsg;
        $wsfedb->puntodeventa = $ptovta;
        $wsfedb->cbtnro = $nro1;
        $wsfedb->fk_facture = $object->id;
        $wsfedb->version = "php2";
        $wsfedb->entity_id = $_SESSION["dol_entity"];
        $wsfedb->divisa = "PES";
        $wsfedb->concepto = $regfe["concepto"];
        $wsfedb->cbttipo = $regfe["CbteTipo"];
        $wsfedb->cuitemisor = $cuitemisor;
        $wsfedb->xmlrequest = '';
        $wsfedb->xmlresponse = '';
        $wsfedb->errc = $wsfev1->Code;
        $wsfedb->errm = $wsfev1->error;
        
        $ret = $wsfedb->update();
        if ($ret < 0) {
            $object->errors++;
        }
    }
    
    if (count($object->errors)) {
        setEventMessage($object->error, "errors");
        $result = -1;
    }
}

/**
 * Verifica si un número es un CUIT válido
 * 
 * @param string $cuit Número de CUIT a verificar
 * @return bool Resultado de la verificación
 */
function isCUIT($cuit) {
    $esCuit = false;
    $cuit_rearmado = '';
    
    // Eliminar caracteres no numéricos
    for ($i = 0; $i < strlen($cuit); $i++) {
        if (Ord(substr($cuit, $i, 1)) >= 48 && Ord(substr($cuit, $i, 1)) <= 57) {
            $cuit_rearmado = $cuit_rearmado . substr($cuit, $i, 1);
        }
    }
    
    $cuit = $cuit_rearmado;
    
    if (strlen($cuit_rearmado) != 11) {
        $esCuit = false;
    } else {
        $x = $i = $dv = 0;
        
        // Algoritmo de verificación de CUIT
        $vec[0] = substr($cuit, 0, 1) * 5;
        $vec[1] = substr($cuit, 1, 1) * 4;
        $vec[2] = substr($cuit, 2, 1) * 3;
        $vec[3] = substr($cuit, 3, 1) * 2;
        $vec[4] = substr($cuit, 4, 1) * 7;
        $vec[5] = substr($cuit, 5, 1) * 6;
        $vec[6] = substr($cuit, 6, 1) * 5;
        $vec[7] = substr($cuit, 7, 1) * 4;
        $vec[8] = substr($cuit, 8, 1) * 3;
        $vec[9] = substr($cuit, 9, 1) * 2;
        
        for ($i = 0; $i <= 9; $i++) {
            $x += $vec[$i];
        }
        
        $dv = (11 - $x % 11) % 11;
        
        if ($dv == substr($cuit, 10, 1)) {
            $esCuit = true;
        }
    }
    
    return $esCuit;
}

/**
 * Obtiene el punto de venta configurado
 * 
 * @return int Número de punto de venta
 */
function get_ptovta() {
    global $conf;
    
    if ($conf->global->AFIPWS_WSFE_MODE == "no") {
        $ptovta = (int) $conf->global->AFIPWS_WSFE_PTOVTA;
    } else {
        $ptovta = (int) $conf->global->AFIPWS_WSFE_PTOVTA;
    }
    
    return $ptovta;
}

/**
 * Determina el tipo de comprobante según el objeto factura
 * 
 * @param object $object Objeto factura
 * @return array Información del tipo de comprobante
 */
function get_typecomprobante($object) {
    global $conf;
    
    if ($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE != 2301) {
        if ($object->thirdparty->typent_code == "A" || $object->thirdparty->typent_code == "TE_A_RI") {
            if ($object->type == 0) {
                $typeent = "FA-";
                $tipo_cbte = 1;
            } elseif ($object->type == 2) {
                $typeent = "NCA-";
                $tipo_cbte = 3;
            } elseif ($object->type == 1) {
                $typeent = "NDA-";
                $tipo_cbte = 2;
            }
        } else {
            if ($object->type == 0) {
                $typeent = "FB-";
                $tipo_cbte = 6;
            } elseif ($object->type == 2) {
                $typeent = "NCB-";
                $tipo_cbte = 8;
            } elseif ($object->type == 1) {
                $typeent = "NDB-";
                $tipo_cbte = 7;
            }
        }
    } else {
        if ($object->type == 0) {
            $typeent = "FC-";
            $tipo_cbte = 11;
        } elseif ($object->type == 2) {
            $typeent = "NCC-";
            $tipo_cbte = 13;
        } elseif ($object->type == 1) {
            $typeent = "NDC-";
            $tipo_cbte = 12;
        }
    }
    
    return $ret = array(
        "typeent" => $typeent,
        "tipo_cbte" => $tipo_cbte
    );
}

/**
 * Genera un Token de Acceso para AFIP
 */
function generar_TA() {
    global $db;
    
    $wsaa = new wsaa($db);
    $wsaa->service = "wsfe";
    
    if ($wsaa->get_expiration() < date("c", date("U"))) {
        if ($wsaa->generar_TA()) {
            setEventMessage("Renovacion de TA satisfactoria", "mesgs");
        } else {
            setEventMessage("Error al obtener el TA", "errors");
        }
    }
}

/**
 * Obtiene el próximo número de comprobante
 * 
 * @param object $object Objeto factura
 * @return string Número de comprobante
 */
function get_CompNextNum2($object) {
    global $db, $conf;
    
    $wsfev1 = new wsfev1($db);
    generar_TA();
    $wsfev1->openTA();
    
    $ptovta = get_ptovta();
    $typecomprobante = get_typecomprobante($object);
    $tipo_cbte = $typecomprobante["tipo_cbte"];
    $typeent = $typecomprobante["typeent"];
    $cuitemisor = str_replace("-", '', $conf->global->MAIN_INFO_SIREN);
    
    if ($wsfev1->error) {
        setEventMessage($wsfev1->error, "errors");
        return '';
    }
    
    $nro = $wsfev1->FECompUltimoAutorizado($ptovta, $tipo_cbte, $cuitemisor);
    $numref = $typeent . str_pad($ptovta, 4, "0", STR_PAD_LEFT) . "-" . str_pad($nro + 1, 8, "0", STR_PAD_LEFT);
    
    return $numref;
}
?>