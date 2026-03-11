<?php
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/afipservice/class/wsfev1db.class.php';

class pdf_afipws_fe extends ModelePDFFactures
{
    var $db;
    var $name;
    var $description;
    var $type;
    var $phpmin = array(4,3,0); // Minimum version of PHP required by module
    var $version = 'dolibarr';
    var $page_largeur;
    var $page_hauteur;
    var $format;
  	var $marge_gauche;
  	var	$marge_droite;
  	var	$marge_haute;
  	var	$marge_basse;
  	var $emetteur;	// Objet societe qui emet

	public $situationinvoice;

	public $posxprogress;


	function __construct($db)
	{
		global $conf,$langs,$mysoc, $object;

		$langs->load("main");
		$langs->load("bills");

		$this->db = $db;
		$this->name = "Factura Electronica";
		$this->description = "Modelo para Factura Electronica AFIPWS";

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Affiche mode reglement
		$this->option_condreg = 1;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 1;                // Affiche si il y a eu escompte
		$this->option_credit_note = 1;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   // Support add of a watermark on drafts

		$this->franchise=!$mysoc->tva_assuj;

        // Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if was not defined


		$this->tva=array();
		$this->localtax1=array();
		$this->localtax2=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
		$this->situationinvoice=False;

		//WSFE
		$this->copias=array(
			1=>'Original',
			2=>'Duplicado',
			3=>'Triplicado',
			4=>'Cutriplicado');

		$this->posxfatipo=95;
		$this->tipos_fact=array(
			1=>'A',
			2=>'A',
			3=>'A',
			4=>'A',
			5=>'A',
			39=>'A',
			60=>'A',
			63=>'A',
		    6=>'B',
			7=>'B',
			8=>'B',
			9=>'B',
			10=>'B',
			40=>'B',
			61=>'B',
			64=>'B',
            11=>'C',
			12=>'C',
			13=>'C',
			15=>'C',
	        51=>'M',
			52=>'M',
			53=>'M',
			54=>'M',
	        19=>'E',
			20=>'E',
			21=>'E',
	        91=>'R');

		//$rows = array_map('str_getcsv', file(DOL_DOCUMENT_ROOT.'/afipservice/plantillas/factura.csv'));
        // Load CSV configuration file
        $afipws_base_path = '/var/www/test.aseoargentina.com.ar/documents/afipws/1/wsfev1/';
		$csvfile = $afipws_base_path . (isset($conf->global->AFIPWS_WSFE_PDF_CSV) ? $conf->global->AFIPWS_WSFE_PDF_CSV : 'factura.csv');

		if (file_exists($csvfile)) {
            $rows = array_map('str_getcsv', file($csvfile));
        } else {
            $rows = array();
        }
        if (!empty($rows)) {
            $header = array_shift($rows);
            unset($header[0]);
            foreach ($rows as $row) {
                $nom = $row[0];
                unset($row[0]);
                $this->posfac[$nom] = array_combine($header, $row);
            }
        } else {
            $this->posfac = array(); // default empty
        }

        //wsfe
//		if ($object->statut!=0 and  $object->array_options[options_fe] = 1) {

			$wsfedb = new wsfedb($db);
			$wsfedb->fk_facture = $object->id;
			$wsfedb->fetch();
			$this->wsfe = $wsfedb;
//		}
        //----------------------


    }

	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$mysoc,$db,$hookmanager;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");


		$nblignes = count($object->lines);

		if ($conf->facture->dir_output)
		{
			$object->fetch_thirdparty();

            $deja_regle = $object->getSommePaiement(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
            $amount_credit_notes_included = $object->getSumCreditNotesUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
            $amount_deposits_included = $object->getSumDepositsUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);


            // Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->facture->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->facture->dir_output . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
			}
			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('beforePDFCreation',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

				// Create pdf instance
				$pdf=pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
				$heightforinfotot = 50;	// Height reserved to output the info and total part
		        $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            //$heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
				$heightforfooter = 61; //margen inferior para corte de pagina
                $pdf->SetAutoPageBreak(1,0);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));

               // Load PDF template
				$afipws_base_path = '/var/www/test.aseoargentina.com.ar/documents/afipws/1/wsfev1/';
				$pdf_template = $afipws_base_path . (!empty($conf->global->AFIPWS_WSFE_PDF_TEMPLATE) ? $conf->global->AFIPWS_WSFE_PDF_TEMPLATE : 'factura.pdf');
				
				// Check if template file exists before loading
				if (!file_exists($pdf_template)) {
					$this->error = 'PDF template file not found: ' . $pdf_template;
					return 0;
				}
				
                $pagecount = $pdf->setSourceFile($pdf_template);
                $tplidx = $pdf->importPage(1);

                // Set path to the background PDF File
                if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
                {
				    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
                }

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("PdfInvoiceTitle"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION." afipservice_2.0");
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfInvoiceTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				$intCopias=1;
				if ($conf->global->AFIPWS_WSFE_PDF_COPIES >4)  $conf->global->AFIPWS_WSFE_PDF_COPIES = 4;
				if (empty($conf->global->AFIPWS_WSFE_PDF_COPIES)) $conf->global->AFIPWS_WSFE_PDF_COPIES=1;
				while ($intCopias <= $conf->global->AFIPWS_WSFE_PDF_COPIES){
               $this->tva=array();
			    // New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs,$intCopias);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				$tab_top = 90;
				//$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
				//$tab_top_newpage = $tab_top-10;
					$tab_top_newpage = $this->posfac['item_desc']['y'];
					$tab_height = 130;
				$tab_height_newpage = 150;




				// Affiche notes
				$notetoshow=empty($object->note_public)?'':$object->note_public;
				if($this->wsfe->cbttipo >= 200){
				    $cbu = "CBU del Emisor:\r\n".$conf->global->MAIN_INFO_TVAINTRA;
				    $pdf->SetFont($this->posfac['factu_cbu']['font'],$this->posfac['factu_cbu']['style'],$this->posfac['factu_cbu']['size']);
				    $pdf->SetXY($this->posfac['factu_cbu']['x'], $this->posfac['factu_cbu']['y']);
				    $pdf->MultiCell($this->posfac['factu_cbu']['w'], $this->posfac['factu_cbu']['h'], $cbu, 0, $this->posfac['factu_cbu']['alig']);

				}

				if (! empty($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_NOTE))
				{
					// Get first sale rep
					if (is_object($object->thirdparty))
					{
						$salereparray=$object->thirdparty->getSalesRepresentatives($user);
						$salerepobj=new User($this->db);
						$salerepobj->fetch($salereparray[0]['id']);
						if (! empty($salerepobj->signature)) $notetoshow=dol_concatdesc($notetoshow, $salerepobj->signature);
					}
				}
				if ($notetoshow) {
					//$tab_top = 88 + $height_incoterms;
					//$tab_top = 88;

					$pdf->SetFont($this->posfac['factu_note']['font'],$this->posfac['factu_note']['style'],$this->posfac['factu_note']['size']);
					$pdf->SetXY($this->posfac['factu_note']['x'], $this->posfac['factu_note']['y']);
					$pdf->MultiCell($this->posfac['factu_note']['w'], $this->posfac['factu_note']['h'], $notetoshow, 0, $this->posfac['factu_note']['alig']);
				}
				else
				{
					//$height_note=0;
				}


//LINEAS----------------------------------------------------------
         		// Loop on each lines
				$nexY=$this->posfac['item_desc']['y'];
				for ($i = 0; $i < $nblignes; $i++)
				{
					$curY = $nexY;

					$pdf->SetFont($this->posfac['item_desc']['font'],$this->posfac['item_desc']['style'], $this->posfac['item_desc']['size']);   // Into loop to work with multipage
					$pdf->SetTextColor(0,0,0);

					// Define size of image if we need it
					$imglinesize=array();
					if (! empty($realpatharray[$i])) $imglinesize=pdf_getSizeForImage($realpatharray[$i]);

					$pdf->setTopMargin($tab_top_newpage);
					//$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pdf->setPageOrientation('', 1, $heightforfooter);
					$pageposbefore=$pdf->getPage();

					$showpricebeforepagebreak=1;
					$posYAfterImage=0;
					$posYAfterDescription=0;



					// Description of product line
					$curX =$this->posfac['item_desc']['x'];
					$pdf->startTransaction();
					pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posfac['item_desc']['w'],$this->posfac['item_desc']['h'],$curX,$curY,$hideref,$hidedesc);
					$pageposafter=$pdf->getPage();

					if ($pageposafter > $pageposbefore)	// There is a pagebreak
					{
						$pdf->rollbackTransaction(true);
						$pageposafter=$pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posfac['item_desc']['w'],$this->posfac['item_desc']['h'],$curX,$curY,$hideref,$hidedesc);

						$pageposafter=$pdf->getPage();
						$posyafter=$pdf->GetY();
						//var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
						if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
						{
							if ($i == ($nblignes-1))	// No more lines, and no space left to show total, so we create a new page
							{
								$pdf->AddPage('','',true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs,$intCopias);
								$pdf->setPage($pageposafter+1);
							}
						}
						else
						{
							// We found a page break
							$showpricebeforepagebreak=0;
							if (! empty($tplidx)) $pdf->useTemplate($tplidx); //agrega template
						}
					}
					else	// No pagebreak
					{
						$pdf->commitTransaction();
					}
					$posYAfterDescription=$pdf->GetY();

					$nexY = $pdf->GetY();
					$pageposafter=$pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}


				  //Se agregaron estas lineas por que el la funcion pdf_getlinetotalwithtax y pdf_getlineupwithtax mo chequea signo
					$sign=1;
					if (isset($object->type) && $object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;
                   ///-------------------
					if ($this->wsfe->cbttipo == '1' or $object->thirdparty->typent_code =='A' || $object->thirdparty->typent_code =='TE_A_RI' || $object->thirdparty->typent_code =='TA_A_RI') {

					    $tvat = price($sign*$object->lines[$i]->total_tva,0,$outputlangs);
						$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
						$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
					}else{
						$tvat='';
						//$up_excl_tax = price($sign * pdf_getlineupwithtax($object, $i, $outputlangs, $hidedetails));
						//$total_excl_tax = price($sign * pdf_getlinetotalwithtax($object, $i, $outputlangs, $hidedetails));

                        //$up_excl_tax = price($sign * ($object->lines[$i]->total_ttc/$object->lines[$i]->qty));
						//$total_excl_tax = price($sign*$object->lines[$i]->total_ttc);
                        $up_excl_tax=pdf_getlineupwithtax($object, $i, $outputlangs, $hidedetails);
                        $total_excl_tax=pdf_getlinetotalwithtax($object, $i, $outputlangs, $hidedetails);

					}
					// VAT Rate y VAT
					if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
					{
						$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
						$pdf->SetFont($this->posfac['item_ivarate']['font'],$this->posfac['item_ivarate']['style'],$this->posfac['item_ivarate']['size']);
						$pdf->SetXY($this->posfac['item_ivarate']['x'], $curY);
						$pdf->MultiCell($this->posfac['item_ivarate']['w'], $this->posfac['item_ivarate']['h'], $vat_rate, 0, $this->posfac['item_ivarate']['alig']);


						//$tvat = pdf_getlinetotalwithtax($object, $i, $outputlangs, $hidedetails) - pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
						$pdf->SetFont($this->posfac['item_iva']['font'], $this->posfac['item_iva']['style'], $this->posfac['item_iva']['size']);
						$pdf->SetXY($this->posfac['item_iva']['x'], $curY);
						$pdf->MultiCell($this->posfac['item_iva']['w'], $this->posfac['item_iva']['h'], $tvat, 0, $this->posfac['item_iva']['alig']);

					}



					// Unit price before discount
					//$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetFont($this->posfac['item_prec']['font'],$this->posfac['item_prec']['style'],$this->posfac['item_prec']['size']);
					$pdf->SetXY($this->posfac['item_prec']['x'], $curY);
					$pdf->MultiCell($this->posfac['item_prec']['w'], $this->posfac['item_prec']['h'], $up_excl_tax, 0, $this->posfac['item_prec']['alig'], 0);

					// Quantity
					$pdf->SetFont($this->posfac['item_qty']['font'],$this->posfac['item_qty']['style'],$this->posfac['item_qty']['size']);
					$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->posfac['item_qty']['x'], $curY);
					$pdf->MultiCell($this->posfac['item_qty']['w'], $this->posfac['item_qty']['h'], $qty, 0, $this->posfac['item_qty']['alig'], 0);

					// Enough for 6 chars
					if($conf->global->PRODUCT_USE_UNITS)
					{
						$unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->SetFont($this->posfac['item_uni']['font'],$this->posfac['item_uni']['style'],$this->posfac['item_uni']['size']);
						$pdf->SetXY($this->posfac['item_uni']['x'], $curY);
						$pdf->MultiCell($this->posfac['item_uni']['w'], $this->posfac['item_uni']['h'], $unit, 0, $this->posfac['item_uni']['alig'], 0);
					}


					// Discount on line
					if ($object->lines[$i]->remise_percent)
					{
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						$pdf->SetFont($this->posfac['item_disc']['font'],$this->posfac['item_disc']['style'],$this->posfac['item_disc']['size']);
						$pdf->SetXY($this->posfac['item_disc']['x'], $curY);
						$pdf->MultiCell($this->posfac['item_disc']['w'], $this->posfac['item_disc']['h'], $remise_percent, 0, $this->posfac['item_disc']['alig'], 0);

					}


                        $hidefreetext=1;

					if ($this->situationinvoice)
					{
						// Situation progress
						$progress = pdf_getlineprogress($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxprogress, $curY);
						$pdf->MultiCell($this->postotalht-$this->posxprogress, 3, $progress, 0, 'R');
					}

					// Total HT line
					//$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetFont($this->posfac['item_imp']['font'],$this->posfac['item_imp']['style'],$this->posfac['item_imp']['size']);
					$pdf->SetXY($this->posfac['item_imp']['x'], $curY);
					$pdf->MultiCell($this->posfac['item_imp']['w'], $this->posfac['item_imp']['h'], $total_excl_tax, 0, $this->posfac['item_imp']['alig'], 0);


					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					$prev_progress = $object->lines[$i]->get_prev_progress($object->id);

                    if ($prev_progress > 0 && !empty($object->lines[$i]->situation_percent)) // Compute progress from previous situation
                    {
                        if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) $tvaligne = $sign * $object->lines[$i]->multicurrency_total_tva * ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent;
                        else $tvaligne = $sign * $object->lines[$i]->total_tva * ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent;
                    } else {
                        if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) $tvaligne= $sign * $object->lines[$i]->multicurrency_total_tva;
                        else $tvaligne= $sign * $object->lines[$i]->total_tva;
                    }

					$localtax1ligne=$object->lines[$i]->total_localtax1;
					$localtax2ligne=$object->lines[$i]->total_localtax2;
					$localtax1_rate=$object->lines[$i]->localtax1_tx;
					$localtax2_rate=$object->lines[$i]->localtax2_tx;
					$localtax1_type=$object->lines[$i]->localtax1_type;
					$localtax2_type=$object->lines[$i]->localtax2_type;

					if ($object->remise_percent) $tvaligne-=($tvaligne*$object->remise_percent)/100;
					if ($object->remise_percent) $localtax1ligne-=($localtax1ligne*$object->remise_percent)/100;
					if ($object->remise_percent) $localtax2ligne-=($localtax2ligne*$object->remise_percent)/100;

					$vatrate=(string) $object->lines[$i]->tva_tx;

					// Retrieve type from database for backward compatibility with old records
					if ((! isset($localtax1_type) || $localtax1_type=='' || ! isset($localtax2_type) || $localtax2_type=='') // if tax type not defined
					&& (! empty($localtax1_rate) || ! empty($localtax2_rate))) // and there is local tax
					{
						$localtaxtmp_array=getLocalTaxesFromRate($vatrate,0, $object->thirdparty, $mysoc);
						$localtax1_type = $localtaxtmp_array[0];
						$localtax2_type = $localtaxtmp_array[2];
					}

				    // retrieve global local tax
					if ($localtax1_type && $localtax1ligne != 0)
						$this->localtax1[$localtax1_type][$localtax1_rate]+=$localtax1ligne;
					if ($localtax2_type && $localtax2ligne != 0)
						$this->localtax2[$localtax2_type][$localtax2_rate]+=$localtax2ligne;

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate.='*';
					if (! isset($this->tva[$vatrate])) 				$this->tva[$vatrate]=0;
					$this->tva[$vatrate] += $tvaligne;


					if ($posYAfterImage > $posYAfterDescription) $nexY=$posYAfterImage;

					// Add line
					if (! empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblignes -1))
					{
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'1,1','color'=>array(210,210,210)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY+1, $this->page_largeur - $this->marge_droite, $nexY+1);
						$pdf->SetLineStyle(array('dash'=>0));
					}



					$nexY+=2;    // Passe espace entre les lignes

                    //Agrego FREETEXT despues de las lineas
                    if ($i == ($nblignes-1)){

                        $nexY=$this->_tableau_feetext($pdf,$outputlangs, $object,$hidefreetext,$nexY);
                    }

					// Detect if some page were added automatically and output _tableau for past pages

					while ($pagenb < $pageposafter)
					{
						$pdf->setPage($pagenb);
						if ($pagenb == 1)
						{
                            $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						}
						else
						{
                            $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);

						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs,$intCopias);
					}


					if (isset($object->lines[$i+1]->pagebreak) && $object->lines[$i+1]->pagebreak)
					{
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);


						// New page
						$pdf->AddPage();
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs,$intCopias);
					}
				}
//Fin LINEAS-------------------------------------------------------------------




                    // Show square

 				if ($pagenb == 1)
				{
                    $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
                    $bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
				else
				{
                    $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code);
                    $bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}



                    // Affiche zone infos
				$posy=$this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);


                    // Affiche zone totaux
				$posy=$this->_tableau_tot($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);


				// Affiche zone versements
//				if ($deja_regle || $amount_credit_notes_included || $amount_deposits_included)
//				{
//					$posy=$this->_tableau_versements($pdf, $object, $posy, $outputlangs);
//				}



					// Pied de page
				$this->_pagefoot($pdf,$object,$outputlangs);
				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

					$intCopias++;

				} // COPIAS

				$pdf->Close();

				$pdf->Output($file,'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

        $this->result = array('fullpath'=>$file);
				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","FAC_OUTPUTDIR");
			return 0;
		}
		//$this->error=$langs->trans("ErrorUnknown");
		//return 0;   // Erreur par defaut
	}


	/**
	 *  Show payments table
	 *
     *  @param				$pdf           Object PDF
     *  @param  Object		$object         Object invoice
     *  @param  int			$posy           Position y in PDF
     *  @param  Translate	$outputlangs    Object langs for output
     *  @return int             			<0 if KO, >0 if OK
	 */
//No muestro pagos
/*
	function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

        $sign=1;
        if ($object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

        $tab3_posx = 120;
		$tab3_top = $posy + 8;
		$tab3_width = 80;
		$tab3_height = 4;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$tab3_posx -= 20;
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$title=$outputlangs->transnoentities("PaymentsAlreadyDone");
		if ($object->type == 2) $title=$outputlangs->transnoentities("PaymentsBackAlreadyDone");

		$pdf->SetFont('','', $default_font_size - 3);
		$pdf->SetXY($tab3_posx, $tab3_top - 4);
		$pdf->MultiCell(60, 3, $title, 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top, $tab3_posx+$tab3_width, $tab3_top);

		$pdf->SetFont('','', $default_font_size - 4);
		$pdf->SetXY($tab3_posx, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+21, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Amount"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+40, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Type"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+58, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Num"), 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top-1+$tab3_height, $tab3_posx+$tab3_width, $tab3_top-1+$tab3_height);

		$y=0;

		$pdf->SetFont('','', $default_font_size - 4);

		// Loop on each deposits and credit notes included
		$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
		$sql.= " re.description, re.fk_facture_source,";
		$sql.= " f.type, f.datef";
		$sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re, ".MAIN_DB_PREFIX ."facture as f";
		$sql.= " WHERE re.fk_facture_source = f.rowid AND re.fk_facture = ".$object->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			$invoice=new Facture($this->db);
			while ($i < $num)
			{
				$y+=3;
				$obj = $this->db->fetch_object($resql);

				if ($obj->type == 2) $text=$outputlangs->trans("CreditNote");
				elseif ($obj->type == 3) $text=$outputlangs->trans("Deposit");
				else $text=$outputlangs->trans("UnknownType");

				$invoice->fetch($obj->fk_facture_source);

				$pdf->SetXY($tab3_posx, $tab3_top+$y);
				$pdf->MultiCell(20, 3, dol_print_date($obj->datef,'day',false,$outputlangs,true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price($obj->amount_ttc, 0, $outputlangs), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+40, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $text, 0, 'L', 0);
				$pdf->SetXY($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $invoice->ref, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3);

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

		// Loop on each payment
		$sql = "SELECT p.datep as date, p.fk_paiement as type, p.num_paiement as num, pf.amount as amount,";
		$sql.= " cp.code";
		$sql.= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."paiement as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON p.fk_paiement = cp.id";
		$sql.= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = ".$object->id;
		$sql.= " ORDER BY p.datep";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num) {
				$y+=3;
				$row = $this->db->fetch_object($resql);

				$pdf->SetXY($tab3_posx, $tab3_top+$y);
				$pdf->MultiCell(20, 3, dol_print_date($this->db->jdate($row->date),'day',false,$outputlangs,true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price($sign * $row->amount, 0, $outputlangs), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+40, $tab3_top+$y);
				$oper = $outputlangs->transnoentitiesnoconv("PaymentTypeShort" . $row->code);

				$pdf->MultiCell(20, 3, $oper, 0, 'L', 0);
				$pdf->SetXY($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(30, 3, $row->num, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3);

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

	}
*/

	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 //*   @param			$pdf     		Object PDF
	 *   @param		Object		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('','', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && $this->franchise == 1)
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy=$pdf->GetY()+4;
		}

		$posxval=52;

		// Show payments conditions
//		if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement))
//		{
//			$titre = $outputlangs->transnoentities("PaymentConditions").':';
//			$lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code)!=('PaymentCondition'.$object->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code):$outputlangs->convToOutputCharset($object->cond_reglement_doc);
//			$lib_condition_paiement=str_replace('\n',"\n",$lib_condition_paiement);
//
//			//Forma de pago
//			$pdf->SetFont($this->posfac['factu_cpago']['font'],$this->posfac['factu_cpago']['style'],$this->posfac['factu_cpago']['size']);
//			$pdf->SetXY($this->posfac['factu_cpago']['x'],$this->posfac['factu_cpago']['y']);
//			$pdf->MultiCell($this->posfac['factu_cpago']['w'],$this->posfac['factu_cpago']['h'],$titre.' '.$lib_condition_paiement,0,$this->posfac['factu_cpago']['alig']);

			//$posy=$pdf->GetY()+3;
//		}

//		if ($object->type != 2)
//		{
//			// Check a payment mode is defined
//			if (empty($object->mode_reglement_code)
//			&& empty($conf->global->FACTURE_CHQ_NUMBER)
//			&& empty($conf->global->FACTURE_RIB_NUMBER))
//			{
//				$this->error = $outputlangs->transnoentities("ErrorNoPaiementModeConfigured");
//			}
//			// Avoid having any valid PDF with setup that is not complete
//			elseif (($object->mode_reglement_code == 'CHQ' && empty($conf->global->FACTURE_CHQ_NUMBER))
//				|| ($object->mode_reglement_code == 'VIR' && empty($conf->global->FACTURE_RIB_NUMBER)))
//			{
//				$outputlangs->load("errors");
//
//				$pdf->SetXY($this->marge_gauche, $posy);
//				$pdf->SetTextColor(200,0,0);
//				$pdf->SetFont('','B', $default_font_size - 2);
//				$this->error = $outputlangs->transnoentities("ErrorPaymentModeDefinedToWithoutSetup",$object->mode_reglement_code);
//				$pdf->MultiCell(80, 3, $this->error,0,'L',0);
//				$pdf->SetTextColor(0,0,0);
//
//				$posy=$pdf->GetY()+1;
//			}
//
//			// Show payment mode
//			if ($object->mode_reglement_code
//			&& $object->mode_reglement_code != 'CHQ'
//			&& $object->mode_reglement_code != 'VIR')
//			{
//				$pdf->SetFont('','B', $default_font_size - 2);
//				$pdf->SetXY($this->marge_gauche, $posy);
//				$titre = $outputlangs->transnoentities("PaymentMode").':';
//				$pdf->MultiCell(80, 5, $titre, 0, 'L');
//
//				$pdf->SetFont('','', $default_font_size - 2);
//				$pdf->SetXY($posxval, $posy);
//				$lib_mode_reg=$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code)!=('PaymentType'.$object->mode_reglement_code)?$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code):$outputlangs->convToOutputCharset($object->mode_reglement);
//				$pdf->MultiCell(80, 5, $lib_mode_reg,0,'L');
//
//				$posy=$pdf->GetY()+2;
//			}
//
//			// Show payment mode CHQ
//			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ')
//			{
//				// Si mode reglement non force ou si force a CHQ
//				if (! empty($conf->global->FACTURE_CHQ_NUMBER))
//				{
//					$diffsizetitle=(empty($conf->global->PDF_DIFFSIZE_TITLE)?3:$conf->global->PDF_DIFFSIZE_TITLE);
//
//					if ($conf->global->FACTURE_CHQ_NUMBER > 0)
//					{
//						$account = new Account($this->db);
//						$account->fetch($conf->global->FACTURE_CHQ_NUMBER);
//
//						$pdf->SetXY($this->marge_gauche, $posy);
//						$pdf->SetFont('','B', $default_font_size - $diffsizetitle);
//						$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$account->proprio),0,'L',0);
//						$posy=$pdf->GetY()+1;
//
//			            if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS))
//			            {
//							$pdf->SetXY($this->marge_gauche, $posy);
//							$pdf->SetFont('','', $default_font_size - $diffsizetitle);
//							$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($account->owner_address), 0, 'L', 0);
//							$posy=$pdf->GetY()+2;
//			            }
//					}
//					if ($conf->global->FACTURE_CHQ_NUMBER == -1)
//					{
//						$pdf->SetXY($this->marge_gauche, $posy);
//						$pdf->SetFont('','B', $default_font_size - $diffsizetitle);
//						$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$this->emetteur->name),0,'L',0);
//						$posy=$pdf->GetY()+1;
//
//			            if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS))
//			            {
//							$pdf->SetXY($this->marge_gauche, $posy);
//							$pdf->SetFont('','', $default_font_size - $diffsizetitle);
//							$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($this->emetteur->getFullAddress()), 0, 'L', 0);
//							$posy=$pdf->GetY()+2;
//			            }
//					}
//				}
//			}
//
//			// If payment mode not forced or forced to VIR, show payment with BAN
//			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR')
//			{
//				if (! empty($object->fk_account) || ! empty($object->fk_bank) || ! empty($conf->global->FACTURE_RIB_NUMBER))
//				{
//					$bankid=(empty($object->fk_account)?$conf->global->FACTURE_RIB_NUMBER:$object->fk_account);
//					if (! empty($object->fk_bank)) $bankid=$object->fk_bank;   // For backward compatibility when object->fk_account is forced with object->fk_bank
//					$account = new Account($this->db);
//					$account->fetch($bankid);
//
//					$curx=$this->marge_gauche;
//					$cury=$posy;
//
//					$posy=pdf_bank($pdf,$outputlangs,$curx,$cury,$account,0,$default_font_size);
//
//					$posy+=2;
//				}
//			}
//		}

//		return $posy;
	}

//TOTALES
	/**
	 *	Show total to pay
	 *
	 *	@param				$pdf           Object PDF
	 *	@param  Facture		$object         Object invoice
	 *	@param  int			$deja_regle     Montant deja regle
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		global $conf,$mysoc;

        $sign=1;
       if ($object->type == 2 ) $sign=-1; // Siempre positiva las NC

        $default_font_size = pdf_getPDFFontSize($outputlangs);

		$tab2_top = $this->posfac['tot_col1']['y'];
		//$tab2_hl = 4;
		$tab2_hl = $this->posfac['tot_col1']['h'];
		$pdf->SetFont('','', $default_font_size - 1);

		// Verificar la moneda
		$currency = $object->multicurrency_code;//?CAMBIO TOMAS -> Verifico si la factura es en USD
		$currency_label = ($currency == 'USD') ? ' (USD)' : '';

		// Tableau total
		//$pdf->SetXY($this->posfac['factu_subt']['x'],$this->posfac['factu_subt']['y']);

		$col1x = $this->posfac['tot_col1']['x'];
		$col2x = $this->posfac['tot_col2']['x'];
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$col2x-=20;
		}
		$largcol2 = $this->posfac['tot_col2']['w'];

		$useborder=0;
		$index = -3; //?CAMBIO TOMAS -> Elevo el cuadro de toatles para evitar superposicion

		if ($object->multicurrency_code == 'USD'){//?CAMBIO TOMAS -> Si la factura es en USD
			$pdf->SetFont($this->posfac['factu_obstx']['font'],$this->posfac['factu_obstx']['style'],$this->posfac['factu_obstx']['size']);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($this->posfac['factu_obstx']['w'],$this->posfac['factu_obstx']['h'],'Moneda USD: Dolar Estadounidense',0,$this->posfac['factu_obstx']['alig']);
			$index++;
		}
		// Total HT //subtotal
        if ($this->wsfe->cbttipo =='1' || $object->thirdparty->typent_code=='A' || $object->thirdparty->typent_code=='TE_A_RI' || $object->thirdparty->typent_code=='TA_A_RI') {
            $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->SetFillColor(255, 255, 255);

			if ($conf->global->AFIPWS_WSFE_PDF_LABELS) {
				$pdf->MultiCell($this->posfac['tot_col1']['w'], $this->posfac['tot_col1']['h'], $outputlangs->transnoentities("TotalHT") . $currency_label, 0, $this->posfac['tot_col1']['alig'], 1);//?CAMBIO TOMAS -> Muestro USD en caso necesario
			}

            $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
            $total_ht = ($conf->multicurrency->enabled && $object->mylticurrency_tx != 1 ? $object->multicurrency_total_ht : $object->total_ht);
            $pdf->MultiCell($this->posfac['tot_col2']['w'],$this->posfac['tot_col2']['h'], price($sign * ($total_ht + (! empty($object->remise)?$object->remise:0)), 0, $outputlangs), 0, $this->posfac['tot_col2']['alig'], 1);
        }

    // Show VAT by rates and total
		$pdf->SetFillColor(248,248,248);
        $total_ttc = ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;

        $this->atleastoneratenotnull=0;
		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
		{
			$tvaisnull=((! empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
			if (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_IFNULL) && $tvaisnull)
			{
				// Nothing to do
			}
			else
			{
                // FIXME amount of vat not supported with multicurrency
				//Local tax 1 before VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
				//{
					foreach( $this->localtax1 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('1','3','5'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey!=0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;

								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}

                                if ($conf->global->AFIPWS_WSFE_PDF_LABELS) {
                                    $totalvat = $outputlangs->transcountrynoentities("TotalLT1", $mysoc->country_code) . ' ';
                                }
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
							}
						}
					}
	      		//}
				//Local tax 2 before VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
				//{
					foreach( $this->localtax2 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('1','3','5'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey!=0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;



								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
            		{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
                                if ($conf->global->AFIPWS_WSFE_PDF_LABELS) {
                                    $totalvat = $outputlangs->transcountrynoentities("TotalLT2", $mysoc->country_code) . ' ';
                                }
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);

							}
						}
					}
				//}
				// VAT
				if ($this->wsfe->cbttipo =='1' or $object->thirdparty->typent_code=='A' || $object->thirdparty->typent_code=='TE_A_RI' || $object->thirdparty->typent_code=='TA_A_RI'){
				foreach($this->tva as $tvakey => $tvaval)
				{
					if ($tvakey != 0)    // On affiche pas taux 0
					{
						$this->atleastoneratenotnull++;

						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

						$tvacompl = '';
						if (preg_match('/\*/', $tvakey)) {
							$tvakey = str_replace('*', '', $tvakey);
							$tvacompl = " (" . $outputlangs->transnoentities("NonPercuRecuperable") . ")";
						}
                        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) {
                            $totalvat = $outputlangs->transnoentities("TotalVAT") . ' ' . vatrate($tvakey, 1) . $tvacompl . $currency_label;//?CAMBIO TOMAS -> Muestro USD en caso necesario
                            //	$totalvat .= vatrate($tvakey, 1) . $tvacompl;
                            //	$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);
                            $pdf->MultiCell($this->posfac['tot_col1']['w'], $tab2_hl, $totalvat, 0, 'L', 1);
                        }
						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
					}
					}
				}//fin typeent

				//Local tax 1 after VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
				//{
					foreach( $this->localtax1 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('2','4','6'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey != 0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;

								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}

                                if ($conf->global->AFIPWS_WSFE_PDF_LABELS) {
                                    $totalvat = $outputlangs->transcountrynoentities("TotalLT1", $mysoc->country_code) . ' ';
                                }
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;

								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);
								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
							}
						}
					}
	      		//}
				//Local tax 2 after VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
				//{
					foreach( $this->localtax2 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('2','4','6'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
						    // retrieve global local tax
							if ($tvakey != 0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;

								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
                                if ($conf->global->AFIPWS_WSFE_PDF_LABELS) {
                                    $totalvat = $outputlangs->transcountrynoentities("TotalLT2", $mysoc->country_code) . ' ';
                                }
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
							}
						}
					//}
				}
                //Extratax
                if (!empty($conf->extratax->enabled)) {
                    require_once DOL_DOCUMENT_ROOT . '/extratax/lib/extratax.lib.php';
                     $extratax=getExtrataxFacture($object);

                     if ($extratax) {


                             foreach ($extratax as $key => $value) {
                                 // retrieve global local tax



                                     $index++;
                                     $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

                                     if ($conf->global->AFIPWS_WSFE_PDF_LABELS) {
                                         $totalvat = $value->label." ";
                                     }
                                     $totalvat .= $value->type !="fixed" ? vatrate(abs($value->rate), 1):abs($value->rate);
                                     $pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

                                     $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                                     $pdf->MultiCell($largcol2, $tab2_hl, price($value->amount, 0, $outputlangs), 0, 'R', 1);

                             }


                     }
                }
                //Fin Extratax
				// Revenue stamp
				if (price2num($object->revenuestamp) != 0)
				{
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                    if ($conf->global->AFIPWS_WSFE_PDF_LABELS) {
                        $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RevenueStamp"), $useborder, 'L', 1);
                    }
					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->revenuestamp), $useborder, 'R', 1);
				}

				// Total TTC
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->SetTextColor(0,0,60);
				$pdf->SetFillColor(224,224,224);
				//$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);
				if ($conf->global->AFIPWS_WSFE_PDF_LABELS) {
					$pdf->MultiCell($this->posfac['tot_col1']['w'], $tab2_hl, $outputlangs->transnoentities("TotalTTC") . $currency_label, $useborder, 'L', 1); //?CAMBIO TOMAS -> Muestro USD en caso necesario
				}
                $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				//$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->total_ttc, 0, $outputlangs), $useborder, 'R', 1);
				$pdf->MultiCell($this->posfac['tot_col2']['w'],$tab2_hl, price($sign * $total_ttc, 0, $outputlangs), $useborder, 'R', 1);
				// if ($object->multicurrency_code == 'USD'){//?CAMBIO TOMAS -> Si la factura es en USD
				// 	$pdf->SetFont($this->posfac['factu_obstx']['font'],$this->posfac['factu_obstx']['style'],$this->posfac['factu_obstx']['size']);
				// 	$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index - 3);
				// 	$pdf->MultiCell($this->posfac['factu_obstx']['w'],$this->posfac['factu_obstx']['h'],'Moneda USD: Dolar Estadounidense',0,$this->posfac['factu_obstx']['alig']);

				// }
			}
		}

		$pdf->SetTextColor(0,0,0);

        $creditnoteamount=$object->getSumCreditNotesUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
        $depositsamount=$object->getSumDepositsUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
		//print "x".$creditnoteamount."-".$depositsamount;exit;
		$resteapayer = price2num($total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
		if ($object->paye) $resteapayer=0;

//Impromi pagos
//		if ($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0)
//		{
//			// Already paid + Deposits
//			$index++;
//			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
//			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("Paid"), 0, 'L', 0);
//			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
//			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle + $depositsamount, 0, $outputlangs), 0, 'R', 0);
//
//			// Credit note
//			if ($creditnoteamount)
//			{
//				$index++;
//				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
//				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("CreditNotes"), 0, 'L', 0);
//				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
//				$pdf->MultiCell($largcol2, $tab2_hl, price($creditnoteamount, 0, $outputlangs), 0, 'R', 0);
//			}
//
//			// Escompte
//			if ($object->close_code == Facture::CLOSECODE_DISCOUNTVAT)
//			{
//				$index++;
//				$pdf->SetFillColor(255,255,255);
//
//				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
//				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOffered"), $useborder, 'L', 1);
//				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
//				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 0, $outputlangs), $useborder, 'R', 1);
//
//				$resteapayer=0;
//			}
//
//			$index++;
//			$pdf->SetTextColor(0,0,60);
//			$pdf->SetFillColor(224,224,224);
//			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
//			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);
//			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
//			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs), $useborder, 'R', 1);
//
//			$pdf->SetFont('','', $default_font_size - 1);
//			$pdf->SetTextColor(0,0,0);
//		}

		$index++;

		//AFIP Barras y obs
		if ($this->wsfe->cae != null) {
			$barras=$this->wsfe->cuitemisor.str_pad($this->wsfe->cbttipo,2,'0',STR_PAD_LEFT).str_pad($this->wsfe->puntodeventa,4,'0',STR_PAD_LEFT).$this->wsfe->cae.$this->wsfe->caevto;

			$pares=0;
			$impares=0;
			for ($i=1;$i<strlen($barras);$i++){
				if ($i%2==0) {
					$pares+=substr($barras,$i-1,1);

				}else{
					$impares+=substr($barras,$i-1,1);

				}
			}
			$digito=$pares+($impares*3);
			$digito = 10 - ($digito - (intval($digito / 10) * 10));
			if ($digito == 10) $digito = 0;

			$barras=$barras.$digito;
			$estado='Comprobante Autorizado';
			$mensajecae='C.A.E.: '.$this->wsfe->cae.' Fecha Vto. CAE: '.date_format(date_create_from_format('Ymd', $this->wsfe->caevto), 'd/m/Y');
		}else{
			$barras= "";
			$estado='Comprobante No Autorizado';
			$mensajecae='';
		}
		if (str_replace(' ','',$this->wsfe->obs) !=null ){
			$pdf->SetFont($this->posfac['factu_obs']['font'],$this->posfac['factu_obs']['style'],$this->posfac['factu_obs']['size']);
			$pdf->SetXY($this->posfac['factu_obs']['x'],$this->posfac['factu_obs']['y']);
			$pdf->MultiCell($this->posfac['factu_obs']['w'],$this->posfac['factu_obs']['h'],'Observaciones AFIP',0,$this->posfac['factu_obs']['alig']);

			$pdf->SetFont($this->posfac['factu_obstx']['font'],$this->posfac['factu_obstx']['style'],$this->posfac['factu_obstx']['size']);
			$pdf->SetXY($this->posfac['factu_obstx']['x'],$this->posfac['factu_obstx']['y']);
			$pdf->MultiCell($this->posfac['factu_obstx']['w'],$this->posfac['factu_obstx']['h'],$this->wsfe->obs,0,$this->posfac['factu_obstx']['alig']);

		}
		if ($object->multicurrency_code == 'USD'){//?CAMBIO TOMAS -> Si la factura es en USD
			$pdf->SetFont($this->posfac['factu_obstx']['font'],$this->posfac['factu_obstx']['style'],$this->posfac['factu_obstx']['size']);
			$pdf->SetXY($this->posfac['factu_obstx']['x'],$this->posfac['factu_obstx']['y']-15);
			$pdf->MultiCell($this->posfac['factu_obstx']['w'],$this->posfac['factu_obstx']['h'],'El total de este comprobante expresado en moneda de curso legal - Pesos Argentinos - considerandose un tipo de cambio consignado de '.round(1/$object->multicurrency_tx, 2).' asiende a $'.round($object->total_ttc, 2),0,$this->posfac['factu_obstx']['alig']);

		}

		// define barcode style
		$style = array(
			'position' => '',
			'align' => 'L',
			'stretch' => false,
			'fitwidth' => true,
			'cellfitalign' => '',
			'border' => false,
			'hpadding' => 'auto',
			'vpadding' => 'auto',
			'fgcolor' => array(0,0,0),
			'bgcolor' => false, //array(255,255,255),
			'text' => true,
			'font' => 'helvetica',
			'fontsize' => 8,
			'stretchtext' => 0
		);

		$posx=$this->posfac['factu_afip']['x'];
		$posy=$this->posfac['factu_afip']['y'];
		$pdf->SetX($posx);
		$pdf->SetY($posy);

		$pdf->Image(DOL_DOCUMENT_ROOT.'/afipservice/img/afip.png',$posx,	$posy, 27, 7.5);

		$pdf->SetX($posx+30);

		$pdf->SetFont('helvetica','I', 10);
		$pdf->Multicell(100,3,$estado,0,'L');

		$pdf->SetY($posy+7.5);
		$pdf->SetFont('helvetica','I', 7);
		$pdf->Multicell(150,3,'La Administración Federal no se responsabiliza por los datos ingresados en el detalle de la operación','','L'); //?CAMBIO TOMAS (Version) -> Reemplace las "ó" erroneas
		$pdf->SetY($posy+9.5);
		$pdf->SetFont('helvetica','N', 10);
		$pdf->Multicell(150,3,$mensajecae,'','L');
		$pdf->write1DBarcode($barras, 'I25', $posx,$posy+12,100,13, 0.4,$style, 'N');

        //FIN AFIP Barras y obs

		//Codigo QR

		// genero los datos para AFIP
		$url = 'https://www.afip.gob.ar/fe/qr/'; // URL que pide AFIP que se ponga en el QR.
		$datos_cmp_base_64 = json_encode([
		        "ver" => 1,                         // NumÃ©rico 1 digito -  OBLIGATORIO â€“ versiÃ³n del formato de los datos del comprobante	1
		        "fecha" => dol_print_date($object->date,'dayrfc'),            // full-date (RFC3339) - OBLIGATORIO â€“ Fecha de emisiÃ³n del comprobante
		        "cuit" => $this->wsfe->cuitemisor,        // NumÃ©rico 11 dÃ­gitos -  OBLIGATORIO â€“ Cuit del Emisor del comprobante
		        "ptoVta" => (int) $this->wsfe->puntodeventa,               // NumÃ©rico hasta 5 digitos - OBLIGATORIO â€“ Punto de venta utilizado para emitir el comprobante
		        "tipoCmp" => (int) $this->wsfe->cbttipo,               // NumÃ©rico hasta 3 dÃ­gitos - OBLIGATORIO â€“ tipo de comprobante (segÃºn Tablas del sistema. Ver abajo )
		        "nroCmp" => (int) $this->wsfe->cbtnro, // NumÃ©rico hasta 8 dÃ­gitos - OBLIGATORIO â€“ NÃºmero del comprobante
		        "importe" => (float) $object->total_ttc,         // Decimal hasta 13 enteros y 2 decimales - OBLIGATORIO â€“ Importe Total del comprobante (en la moneda en la que fue emitido)
		        "moneda" => $this->wsfe->divisa,                  // 3 caracteres - OBLIGATORIO â€“ Moneda del comprobante (segÃºn Tablas del sistema. Ver Abajo )
		        "ctz" => (float) 1,                 // Decimal hasta 13 enteros y 6 decimales - OBLIGATORIO â€“ CotizaciÃ³n en pesos argentinos de la moneda utilizada (1 cuando la moneda sea pesos)
		        "tipoDocRec" =>  '' ,               // NumÃ©rico hasta 2 dÃ­gitos - DE CORRESPONDER â€“ CÃ³digo del Tipo de documento del receptor (segÃºn Tablas del sistema )
		        "nroDocRec" =>  '',        // NumÃ©rico hasta 20 dÃ­gitos - DE CORRESPONDER â€“ NÃºmero de documento del receptor correspondiente al tipo de documento indicado
		        "tipoCodAut" => "E",                // string - OBLIGATORIO â€“ â€œAâ€� para comprobante autorizado por CAEA, â€œEâ€� para comprobante autorizado por CAE
		        "codAut" =>  $this->wsfe->cae    // NumÃ©rico 14 dÃ­gitos -  OBLIGATORIO â€“ CÃ³digo de autorizaciÃ³n otorgado por AFIP para el comprobante
		]);


		$datos_cmp_base_64 = base64_encode($datos_cmp_base_64);
		$to_qr = $url.'?p='.$datos_cmp_base_64;

		$pdf->write2DBarcode($to_qr, 'QRCODE', $posx+170,$posy+2,20,20, $style, '');

		//Fin Codigo QR

		return ($tab2_top + ($tab2_hl * $index));
	}

	/**
	 *   Show table for lines
	 *
	 *   @param			$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height=0, $nexY, $outputlangs, $hidetop=1, $hidebottom=0, $multicurrency_code)//?CAMBIO TOMAS -> Agrego parametro $multicurrency_code
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;
		$hidetop=1;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','', $default_font_size - 2);

		if (empty($hidetop))
		{
			$titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$conf->currency));
			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top-4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			//$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
			if (! empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_droite-$this->marge_gauche, 5, 'F', null, explode(',',$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR));
		}

		$pdf->SetDrawColor(128,128,128);
		$pdf->SetFont('','', $default_font_size - 1);

		// Output Rect
		//$this->printRect($pdf,$this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect prend une longueur en 3eme param et 4eme param

		if (empty($hidetop))
		{
			$pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);	// line prend une position y en 2eme param et 4eme param

			$pdf->SetXY($this->posxdesc-1, $tab_top+1);
			$pdf->MultiCell(108,2, $outputlangs->transnoentities("Designation"),'','L');
		}

		if (! empty($conf->global->MAIN_GENERATE_INVOICES_WITH_PICTURE))
		{
		//	$pdf->line($this->posxpicture-1, $tab_top, $this->posxpicture-1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				//$pdf->SetXY($this->posxpicture-1, $tab_top+1);
				//$pdf->MultiCell($this->posxtva-$this->posxpicture-1,2, $outputlangs->transnoentities("Photo"),'','C');
			}
		}

		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
		{
		//	$pdf->line($this->posxtva-1, $tab_top, $this->posxtva-1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxtva-3, $tab_top+1);
				$pdf->MultiCell($this->posxup-$this->posxtva+3,2, $outputlangs->transnoentities("VAT"),'','C');
			}
		}

		//$pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxup-1, $tab_top+1);
			$pdf->MultiCell($this->posxqty-$this->posxup-1,2, $outputlangs->transnoentities("PriceUHT"),'','C');
		}
		//$pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxqty-1, $tab_top+1);
			if($conf->global->PRODUCT_USE_UNITS)
			{
				$pdf->MultiCell($this->posxunit-$this->posxqty-1,2, $outputlangs->transnoentities("Qty"),'','C');
			}
			else
			{
				$pdf->MultiCell($this->posxdiscount-$this->posxqty-1,2, $outputlangs->transnoentities("Qty"),'','C');
			}
		}

		if($conf->global->PRODUCT_USE_UNITS) {
		//	$pdf->line($this->posxunit - 1, $tab_top, $this->posxunit - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				$pdf->SetXY($this->posxunit - 1, $tab_top + 1);
				$pdf->MultiCell($this->posxdiscount - $this->posxunit - 1, 2, $outputlangs->transnoentities("Unit"), '',
					'C');
			}
		}

		//$pdf->line($this->posxdiscount-1, $tab_top, $this->posxdiscount-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			if ($this->atleastonediscount)
			{
				$pdf->SetXY($this->posxdiscount-1, $tab_top+1);
				$pdf->MultiCell($this->postotalht-$this->posxdiscount+1,2, $outputlangs->transnoentities("ReductionShort"),'','C');
			}
		}
		if ($this->atleastonediscount)
		{
		//	$pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
		}
		if (empty($hidetop))
		{
			$pdf->SetXY($this->postotalht-1, $tab_top+1);
			$pdf->MultiCell(30,2, $outputlangs->transnoentities("TotalHT"),'','C');
		}

        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) {

            $labelsizeplus=0;
            $labelposplus=-6;

		    //Qty
            $pdf->SetFont($this->posfac['item_qty']['font'], $this->posfac['item_qty']['style'], $this->posfac['item_qty']['size']+$labelsizeplus);
            $pdf->SetXY($this->posfac['item_qty']['x'], $this->posfac['item_qty']['y']+$labelposplus);
            $pdf->MultiCell($this->posfac['item_qty']['w'], $this->posfac['item_qty']['h'], $outputlangs->transnoentities("Qty"), 0, 'C');

            //Descripcion
            $pdf->SetFont($this->posfac['item_desc']['font'], $this->posfac['item_desc']['style'], $this->posfac['item_qty']['size']+$labelsizeplus);
            $pdf->SetXY($this->posfac['item_desc']['x'], $this->posfac['item_desc']['y']+$labelposplus);
            $pdf->MultiCell($this->posfac['item_desc']['w'], $this->posfac['item_desc']['h'], $outputlangs->transnoentities("Designation"), 0, 'C');

            //Unidades
            if($conf->global->PRODUCT_USE_UNITS) {

                $pdf->SetFont($this->posfac['item_uni']['font'], $this->posfac['item_uni']['style'], $this->posfac['item_uni']['size']+$labelsizeplus);
                $pdf->SetXY($this->posfac['item_uni']['x'], $this->posfac['item_uni']['y']+$labelposplus);
                $pdf->MultiCell($this->posfac['item_uni']['w'], $this->posfac['item_uni']['h'], $outputlangs->transnoentities("Unit"), 0, 'C');

            }

			// Verificar la moneda
			$currency_label = ($multicurrency_code == 'USD') ? ' (USD)' : '';//?CAMBIO TOMAS -> Verifico si la factura es en USD

            //Precio unitario
            $pdf->SetFont($this->posfac['item_prec']['font'], $this->posfac['item_prec']['style'], $this->posfac['item_prec']['size']+$labelsizeplus);
            $pdf->SetXY($this->posfac['item_prec']['x'], $this->posfac['item_prec']['y']+$labelposplus);
            $pdf->MultiCell($this->posfac['item_prec']['w'], $this->posfac['item_prec']['h'], $outputlangs->transnoentities("PriceUHT") . $currency_label, 0, 'C');

            //IVA
            $pdf->SetFont($this->posfac['item_iva']['font'], $this->posfac['item_iva']['style'], $this->posfac['item_iva']['size']+$labelsizeplus);
            $pdf->SetXY($this->posfac['item_iva']['x'], $this->posfac['item_iva']['y']+$labelposplus);
            // $pdf->MultiCell($this->posfac['item_iva']['w'], $this->posfac['item_iva']['h'], $outputlangs->transnoentities("VAT") . $currency_label, 0, 'C');
            $pdf->MultiCell($this->posfac['item_iva']['w'] + 5, $this->posfac['item_iva']['h'], 'IVA' . $currency_label, 0, 'C'); //?CAMBIO TOMAS -> Hardcodeo 'IVA' para evitar superposicion (Cambio temporal para produccion)

            //Subtotal
            $pdf->SetFont($this->posfac['item_imp']['font'], $this->posfac['item_imp']['style'], $this->posfac['item_imp']['size']+$labelsizeplus);
            $pdf->SetXY($this->posfac['item_imp']['x'], $this->posfac['item_imp']['y']+$labelposplus);
            // $pdf->MultiCell($this->posfac['item_imp']['w'], $this->posfac['item_imp']['h'], $outputlangs->transnoentities("TotalHT")  . $currency_label, 0, 'C');
            $pdf->MultiCell($this->posfac['item_imp']['w'] + 5, $this->posfac['item_imp']['h'], 'Total (B.I.)'  . $currency_label, 0, 'C'); //?CAMBIO TOMAS -> Hardcodeo 'Total (B.I.)' para evitar superposicion (Cambio temporal para produccion)

            //Descuento
            $pdf->SetFont($this->posfac['item_disc']['font'], $this->posfac['item_disc']['style'], $this->posfac['item_disc']['size']+$labelsizeplus);
            $pdf->SetXY($this->posfac['item_disc']['x'], $this->posfac['item_disc']['y']+$labelposplus);
            $pdf->MultiCell($this->posfac['item_disc']['w'], $this->posfac['item_disc']['h'], $outputlangs->transnoentities("ReductionShort"), 0, 'C');

            //IVA Rate
            $pdf->SetFont($this->posfac['item_ivarate']['font'], $this->posfac['item_ivarate']['style'], $this->posfac['item_ivarate']['size']+$labelsizeplus);
            $pdf->SetXY($this->posfac['item_ivarate']['x'], $this->posfac['item_ivarate']['y']+$labelposplus);
            $pdf->MultiCell($this->posfac['item_ivarate']['w'], $this->posfac['item_ivarate']['h'], '%', 0, 'C');


        }
	}
//ENCABEZADO
	/**
	 *  Show top header of page.
	 *
	 *  @param				$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  int         $intCopias      Variable que define copias //Catriel
	 *  @return	void
	 */
    function _pagehead(&$pdf, $object, $showaddress, $outputlangs,$intCopias=1)
    {
        global $conf, $langs, $db;

        $outputlangs->load("main");
        $outputlangs->load("bills");
        $outputlangs->load("propal");
        $outputlangs->load("companies");

        $default_font_size = pdf_getPDFFontSize($outputlangs);
        $label=''; //Print label check AFIPWS_WSFE_PDF_LABELS
        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

        // Show Draft Watermark
        if ($object->statut == 0 && (!empty($conf->global->FACTURE_DRAFT_WATERMARK))) {
            pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->FACTURE_DRAFT_WATERMARK);
        }

        $posy = $this->marge_haute;
        $posx = $this->marge_gauche;

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $default_font_size + 3);

        $w = 110;

        $posy = $this->marge_haute;
        $posx = $this->page_largeur - $this->marge_droite - $w;

        $pdf->SetXY($this->marge_gauche, $posy);

        // Logo

        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) {
        $logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
        if ($this->emetteur->logo)
        {
            if (is_readable($logo))
            {
                $pdf->Image($logo, $this->posfac['empresa_logo']['x'], $this->posfac['empresa_logo']['y'], $this->posfac['empresa_logo']['w'], $this->posfac['empresa_logo']['h']);	// width=0 (auto)
            }
            else
            {
                $pdf->SetTextColor(200,0,0);
                $pdf->SetFont('','B',$default_font_size - 2);
                $pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
                $pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }

        }


            //Nombre Empresa
            $pdf->SetFont($this->posfac['empresa_nom']['font'], $this->posfac['empresa_nom']['style'], $this->posfac['empresa_nom']['size']);
            $pdf->SetXY($this->posfac['empresa_nom']['x'], $this->posfac['empresa_nom']['y']);
            $pdf->MultiCell($this->posfac['empresa_nom']['w'], $this->posfac['empresa_nom']['h'], $outputlangs->convToOutputCharset($this->emetteur->name), 0, $this->posfac['empresa_nom']['alig']);

            // Direccion Empresa
            $carac_emetteur = str_replace("\n\n", "\n", pdf_build_address_afip($outputlangs, $this->emetteur, $object->thirdparty));
            $pdf->SetFont($this->posfac['empresa_dom']['font'], $this->posfac['empresa_dom']['style'], $this->posfac['empresa_dom']['size']);
            $pdf->SetXY($this->posfac['empresa_dom']['x'], $this->posfac['empresa_dom']['y']);
            $pdf->MultiCell($this->posfac['empresa_dom']['w'], $this->posfac['empresa_dom']['h'], $carac_emetteur, 0, $this->posfac['empresa_dom']['alig']);
        }


        //Tipo de Documento
        $pdf->SetTextColor(0,0,60);
        if (!empty($this->wsfe->cbttipo)){
            $codfact='COD.'.str_pad($this->wsfe->cbttipo,2,'0', STR_PAD_LEFT);
            $letfact=$this->tipos_fact[$this->wsfe->cbttipo];
        }else{
            $codfact='COD.'.str_pad('0',2,'0', STR_PAD_LEFT);
            $letfact='X';

        }
        $pdf->SetFont($this->posfac['factu_letra']['font'],$this->posfac['factu_letra']['style'],$this->posfac['factu_letra']['size']);
        $pdf->SetXY($this->posfac['factu_letra']['x'],$this->posfac['factu_letra']['y']);
        $pdf->MultiCell($this->posfac['factu_letra']['w'],$this->posfac['factu_letra']['h'],$letfact,0,$this->posfac['factu_letra']['alig']);

        $pdf->SetFont($this->posfac['factu_cod']['font'],$this->posfac['factu_cod']['style'],$this->posfac['factu_cod']['size']);
        $pdf->SetXY($this->posfac['factu_cod']['x'],$this->posfac['factu_cod']['y']);
        $pdf->MultiCell($this->posfac['factu_cod']['w'],$this->posfac['factu_cod']['h'],$codfact,0,$this->posfac['factu_cod']['alig']);



        if ($object->type == 0) {
            $titre=$outputlangs->transnoentities("Invoice");
            $wsfedb = new wsfedb($db);
            $wsfedb->fk_facture = $object->id;
            $wsfedb->fetch();
            if ($wsfedb->cbttipo == 201) $titre="Factura de Credito Electronica MIPyMEs (FCE)";
        }
        if ($object->type == 1) {
            $titre=$outputlangs->transnoentities("InvoiceReplacement");
            $wsfedb = new wsfedb($db);
            $wsfedb->fk_facture = $object->fk_facture_source;
            $wsfedb->fetch();
            if ($wsfedb->cbttipo >= 200) $titre="Nota de Debito Electronica MIPyMEs (FCE)";
        }
        if ($object->type == 2) {
            $titre=$outputlangs->transnoentities("InvoiceAvoir");
            $wsfedb = new wsfedb($db);
            $wsfedb->fk_facture = $object->fk_facture_source;
            $wsfedb->fetch();
            if ($wsfedb->cbttipo >= 200) $titre="Nota de Credito Electronica MIPyMEs (FCE)";
        }
        if ($object->type == 3) $titre=$outputlangs->transnoentities("InvoiceDeposit");
        if ($object->type == 4) $titre=$outputlangs->transnoentities("InvoiceProFormat");





        $pdf->SetFont($this->posfac['factu_cbte']['font'],$this->posfac['factu_cbte']['style'],$this->posfac['factu_cbte']['size']);
        $pdf->SetXY($this->posfac['factu_cbte']['x'],$this->posfac['factu_cbte']['y']);
        $pdf->MultiCell($this->posfac['factu_cbte']['w'],$this->posfac['factu_cbte']['h'],$titre,0,$this->posfac['factu_cbte']['alig']);


       //Numero de Factura
        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $label='Nro: ';
       // $titre = str_pad($this->wsfe->puntodeventa, 4,"0",STR_PAD_LEFT) ."-".str_pad($this->wsfe->cbtnro, 8,"0",STR_PAD_LEFT);
        $titre=$object->ref;
        $pdf->SetFont($this->posfac['factu_nro']['font'],$this->posfac['factu_nro']['style'],$this->posfac['factu_nro']['size']);
        $pdf->SetXY($this->posfac['factu_nro']['x'],$this->posfac['factu_nro']['y']);
        $pdf->MultiCell($this->posfac['factu_nro']['w'],$this->posfac['factu_nro']['h'],$label.$titre,0,$this->posfac['factu_nro']['alig']);

        $intPag=floor($pdf->PageNo()/$intCopias);
        //$intTotalPag=floor($pdf->PageNo());
        $titre=$this->copias[$intCopias].' Pagina: '.$intPag;
        $pdf->SetFont($this->posfac['factu_pag']['font'],$this->posfac['factu_pag']['style'],$this->posfac['factu_pag']['size']);
        $pdf->SetXY($this->posfac['factu_pag']['x'],$this->posfac['factu_pag']['y']);
        $pdf->MultiCell($this->posfac['factu_pag']['w'],$this->posfac['factu_pag']['h'],$titre,0,$this->posfac['factu_pag']['alig']);


        if ($object->ref_client)
        {

            $pdf->SetFont($this->posfac['factu_refcli']['font'],$this->posfac['factu_refcli']['style'],$this->posfac['factu_refcli']['size']);
            $pdf->SetXY($this->posfac['factu_refcli']['x'],$this->posfac['factu_refcli']['y']);
            if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $label=  $outputlangs->transnoentities("RefCustomer")." : " ;
            $pdf->MultiCell($this->posfac['factu_refcli']['w'],$this->posfac['factu_refcli']['h'],$label. $outputlangs->convToOutputCharset($object->ref_client),0,$this->posfac['factu_refcli']['alig']);
        }

        /*		$objectidnext=$object->getIdReplacingInvoice('validated');
                if ($object->type == 0 && $objectidnext)
                {
                    $objectreplacing=new Facture($this->db);
                    $objectreplacing->fetch($objectidnext);

                    $posy+=3;
                    $pdf->SetXY($posx,$posy);
                    $pdf->SetTextColor(0,0,60);
                //	$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ReplacementByInvoice").' : '.$outputlangs->convToOutputCharset($objectreplacing->ref), '', 'R');
                }
                if ($object->type == 1)
                {
                    $objectreplaced=new Facture($this->db);
                    $objectreplaced->fetch($object->fk_facture_source);

                    $posy+=4;
                    $pdf->SetXY($posx,$posy);
                    $pdf->SetTextColor(0,0,60);
                //	$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ReplacementInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
                }
                if ($object->type == 2)
                {
                    $objectreplaced=new Facture($this->db);
                    $objectreplaced->fetch($object->fk_facture_source);

                    $posy+=3;
                    $pdf->SetXY($posx,$posy);
                    $pdf->SetTextColor(0,0,60);
                //	$pdf->MultiCell($w, 3, $outputlangs->transnoentities("CorrectionInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
                }*/

        $pdf->SetFont($this->posfac['factu_fec']['font'],$this->posfac['factu_fec']['style'],$this->posfac['factu_fec']['size']);
        $pdf->SetXY($this->posfac['factu_fec']['x'],$this->posfac['factu_fec']['y']);
        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $label= $outputlangs->transnoentities("DateInvoice")." : ";
        $pdf->MultiCell($this->posfac['factu_fec']['w'],$this->posfac['factu_fec']['h'], $label . dol_print_date($object->date,"day",false,$outputlangs),0,$this->posfac['factu_fec']['alig']);


        if ($object->type != 2)
        {
            $pdf->SetFont($this->posfac['factu_vto']['font'],$this->posfac['factu_vto']['style'],$this->posfac['factu_vto']['size']);
            $pdf->SetXY($this->posfac['factu_vto']['x'],$this->posfac['factu_vto']['y']);
            if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $label= $outputlangs->transnoentities("DateDue")." : ";
            $pdf->MultiCell($this->posfac['factu_vto']['w'],$this->posfac['factu_vto']['h'], $label. dol_print_date($object->date_lim_reglement,"day",false,$outputlangs,true),0,$this->posfac['factu_fec']['alig']);

        }


        //CUIT Empresa
        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $label='CUIT: ';
        $titre = $this->wsfe->cuitemisor;
        $pdf->SetFont($this->posfac['empresa_cuit']['font'],$this->posfac['empresa_cuit']['style'],$this->posfac['empresa_cuit']['size']);
        $pdf->SetXY($this->posfac['empresa_cuit']['x'],$this->posfac['empresa_cuit']['y']);
        $pdf->MultiCell($this->posfac['empresa_cuit']['w'],$this->posfac['empresa_cuit']['h'],$label.$titre,0,$this->posfac['empresa_cuit']['alig']);



        //Numero IIBB
        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $label='IIBB: ';
        $titre = $conf->global->MAIN_INFO_SIRET;
        $pdf->SetFont($this->posfac['empresa_iibb']['font'],$this->posfac['empresa_iibb']['style'],$this->posfac['empresa_iibb']['size']);
        $pdf->SetXY($this->posfac['empresa_iibb']['x'],$this->posfac['empresa_iibb']['y']);
        $pdf->MultiCell($this->posfac['empresa_iibb']['w'],$this->posfac['empresa_iibb']['h'],$label.$titre,0,$this->posfac['empresa_iibb']['alig']);

        //Fecha Incripcion
        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $label='Inicio Actividades: ';
        $titre = $conf->global->AFIPWS_WSFE_PDF_INICIOACT;
        $pdf->SetFont($this->posfac['empresa_inicio']['font'],$this->posfac['empresa_inicio']['style'],$this->posfac['empresa_inicio']['size']);
        $pdf->SetXY($this->posfac['empresa_inicio']['x'],$this->posfac['empresa_inicio']['y']);
        $pdf->MultiCell($this->posfac['empresa_inicio']['w'],$this->posfac['empresa_inicio']['h'],$label.$titre,0,$this->posfac['empresa_inicio']['alig']);



        //Referencia Factura para Nota de Credito
        if ($object->type == 2)
        {
            $objectreplaced=new Facture($this->db);
            $objectreplaced->fetch($object->fk_facture_source);

            $pdf->SetFont($this->posfac['factu_debit']['font'],$this->posfac['factu_debit']['style'],$this->posfac['factu_debit']['size']);
            $pdf->SetXY($this->posfac['factu_debit']['x'],$this->posfac['factu_debit']['y']);
            if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $label= $outputlangs->transnoentities("CorrectionInvoice")." : ";

            $pdf->MultiCell($this->posfac['factu_debit']['w'],$this->posfac['factu_debit']['h'], $label.$outputlangs->convToOutputCharset($objectreplaced->ref),0,$this->posfac['factu_debit']['alig']);
        }


        // If BILLING contact defined on invoice, we use it
        $usecontact=false;
        $arrayidcontact=$object->getIdContact('external','BILLING');
        if (count($arrayidcontact) > 0)
        {
            $usecontact=true;
            $result=$object->fetch_contact($arrayidcontact[0]);
        }

        //Cliente Nombre
        // On peut utiliser le nom de la societe du contact
        if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
            $thirdparty = $object->contact;
        } else {
            //$thirdparty = $object->thirdparty;  //version 3.8xx
            $thirdparty = $object->thirdparty;
        }

        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $carac_client_name=$outputlangs->transnoentities("Customer").": ";
        $carac_client_name .= pdfBuildThirdpartyName($thirdparty, $outputlangs);
        $pdf->SetFont($this->posfac['cliente_nom']['font'],$this->posfac['cliente_nom']['style'],$this->posfac['cliente_nom']['size']);
        $pdf->SetXY($this->posfac['cliente_nom']['x'],$this->posfac['cliente_nom']['y']);
        $pdf->MultiCell($this->posfac['cliente_nom']['w'],$this->posfac['cliente_nom']['h'],$carac_client_name,0,$this->posfac['cliente_nom']['alig']);

        //Direccion Cliente
        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $carac_client=$outputlangs->transnoentities("Address").": ";
        $carac_client.=pdf_build_address_afip($outputlangs,$this->emetteur,$object->thirdparty,($usecontact?$object->contact:''),$usecontact,'target');
        $pdf->SetFont($this->posfac['cliente_dom']['font'],$this->posfac['cliente_dom']['style'],$this->posfac['cliente_dom']['size']);
        $pdf->SetXY($this->posfac['cliente_dom']['x'],$this->posfac['cliente_dom']['y']);
        $pdf->MultiCell($this->posfac['cliente_dom']['w'],$this->posfac['cliente_dom']['h'],$carac_client,0,$this->posfac['cliente_dom']['alig']);

        //Cliente Provincia
        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $carac_client_state=$outputlangs->transnoentities("State").": ";
        $carac_client_state.=$object->thirdparty->state;
        $pdf->SetFont($this->posfac['cliente_prov']['font'],$this->posfac['cliente_prov']['style'],$this->posfac['cliente_prov']['size']);
        $pdf->SetXY($this->posfac['cliente_prov']['x'],$this->posfac['cliente_prov']['y']);
        $pdf->MultiCell($this->posfac['cliente_prov']['w'],$this->posfac['cliente_prov']['h'],$carac_client_state,0,$this->posfac['cliente_prov']['alig']);


        //Condicion de pago
        // Show payments conditions
        if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement))
        {
            if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $label = $outputlangs->transnoentities("PaymentConditions") . ': ';
            $lib_condition_paiement = $outputlangs->transnoentities("PaymentCondition" . $object->cond_reglement_code) != ('PaymentCondition' . $object->cond_reglement_code) ? $outputlangs->transnoentities("PaymentCondition" . $object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc);
            $lib_condition_paiement = str_replace('\n', "\n", $lib_condition_paiement);


            $pdf->SetFont($this->posfac['factu_cpago']['font'], $this->posfac['factu_cpago']['style'], $this->posfac['factu_cpago']['size']);
            $pdf->SetXY($this->posfac['factu_cpago']['x'], $this->posfac['factu_cpago']['y']);
            $pdf->MultiCell($this->posfac['factu_cpago']['w'], $this->posfac['factu_cpago']['h'], $label. $lib_condition_paiement, 0, $this->posfac['factu_cpago']['alig']);


            //Modo de pago
            // Show payment mode
            if ($object->mode_reglement_code){

                if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $label = $outputlangs->transnoentities("PaymentMode").': ';
                $lib_mode_reg=$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code)!=('PaymentType'.$object->mode_reglement_code)?$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code):$outputlangs->convToOutputCharset($object->mode_reglement);

                $pdf->SetFont($this->posfac['factu_mpago']['font'], $this->posfac['factu_mpago']['style'], $this->posfac['factu_mpago']['size']);
                $pdf->SetXY($this->posfac['factu_mpago']['x'], $this->posfac['factu_mpago']['y']);
                $pdf->MultiCell($this->posfac['factu_mpago']['w'], $this->posfac['factu_mpago']['h'], $label . $lib_mode_reg, 0, $this->posfac['factu_mpago']['alig']);
            }
        }



        //IVA Cliente
        require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
        $formcompany = new FormCompany($db);

        $arr = $formcompany->typent_array(1);
        $thirdparty->typent = $arr[$thirdparty->typent_code];
        $id_impositivo = $thirdparty->typent;


        $pdf->SetFont($this->posfac['cliente_iva']['font'],$this->posfac['cliente_iva']['style'],$this->posfac['cliente_iva']['size']);
        $pdf->SetXY($this->posfac['cliente_iva']['x'],$this->posfac['cliente_iva']['y']);

        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $label= 'Condicion frente al IVA: ';

        $pdf->MultiCell($this->posfac['cliente_iva']['w'],$this->posfac['cliente_iva']['h'],$label.$id_impositivo,0,$this->posfac['cliente_iva']['alig']);



        // TODO:
              //IVA Empresa
//        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $label='IVA: ';
//        $titre = $this->wsfe->cuitemisor;
//        $pdf->SetFont($this->posfac['empresa_cuit']['font'],$this->posfac['empresa_cuit']['style'],$this->posfac['empresa_cuit']['size']);
//        $pdf->SetXY($this->posfac['empresa_cuit']['x'],$this->posfac['empresa_cuit']['y']);
//        $pdf->MultiCell($this->posfac['empresa_cuit']['w'],$this->posfac['empresa_cuit']['h'],$label.$titre,0,$this->posfac['empresa_cuit']['alig']);


        //CUIT Cliente

        if ($conf->global->AFIPWS_WSFE_PDF_LABELS) $label = $outputlangs->transnoentities("ProfId1AR").': ';
        $pdf->SetFont($this->posfac['cliente_cuit']['font'],$this->posfac['cliente_cuit']['style'],$this->posfac['cliente_cuit']['size']);
        $pdf->SetXY($this->posfac['cliente_cuit']['x'],$this->posfac['cliente_cuit']['y']);
        $pdf->MultiCell($this->posfac['cliente_cuit']['w'],$this->posfac['cliente_cuit']['h'],$label.$object->thirdparty->idprof1,0,$this->posfac['cliente_cuit']['alig']);


        // Show list of linked objects
        $posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $this->posfac['factu_remito']['x'], $this->posfac['factu_remito']['y'], $this->posfac['factu_remito']['w'], 3, $this->posfac['factu_fec']['alig'], $this->posfac['factu_remito']['size']);


    }




    /**
     *   of page for PDF generation
     *
     *	@param	TCPDF			$pdf     		The PDF factory
     *  @param  Translate	$outputlangs	Object lang for output
     * 	@param	Object		$object			Object shown in PDF
     *  @param	int			$hidefreetext	1=Hide free text, 0=Show free text
     *  @param       $nexY
     * 	@return	int							Return posY
     */


	function _tableau_feetext(&$pdf,$outputlangs,$object,$hidefreetext,$nexY)
    {
        global $conf;


            $paramfreetext="INVOICE_FREE_TEXT";
            $outputlangs->load("dict");
            $line='';


            // Line of free text
            if (!empty($hidefreetext) && ! empty($conf->global->$paramfreetext))
            {
                $substitutionarray=pdf_getSubstitutionArray($outputlangs, null, $object);
                // More substitution keys

                complete_substitutions_array($substitutionarray, $outputlangs, $object);
                $newfreetext=make_substitutions($conf->global->$paramfreetext, $substitutionarray, $outputlangs);

                // Make a change into HTML code to allow to include images from medias directory.
                // <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
                // become
                // <img alt="" src="'.DOL_DATA_ROOT.'/medias/image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
                $newfreetext=preg_replace('/(<img.*src=")[^\"]*viewimage\.php[^\"]*modulepart=medias[^\"]*file=([^\"]*)("[^\/]*\/>)/', '\1'.DOL_DATA_ROOT.'/medias/\2\3', $newfreetext);

                $line.=$outputlangs->convToOutputCharset($newfreetext);
            }


            // The start of the bottom of this page footer is positioned according to # of lines
            $freetextheight=0;
            if ($line)	// Free text
            {
                //$line="eee<br>\nfd<strong>sf</strong>sdf<br>\nghfghg<br>";
                if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))
                {
                    $width=20000; $align='L';	// By default, ask a manual break: We use a large value 20000, to not have automatic wrap. This make user understand, he need to add CR on its text.
                    if (! empty($conf->global->MAIN_USE_AUTOWRAP_ON_FREETEXT)) {
                        $width=200; $align='C';
                    }
                    $freetextheight=$pdf->getStringHeight($width,$line);
                }
                else
                {
                    $freetextheight=pdfGetHeightForHtmlContent($pdf,dol_htmlentitiesbr($line, 1, 'UTF-8', 0));      // New method (works for HTML content)
                    //print '<br>'.$freetextheight;exit;
                }
            }

            if ($line)	// Free text
            {
               //posY
                //
                //$nexY=$pdf->GetY();
                //Linea separadora
                $pdf->SetLineStyle(array('dash'=>'1,1','color'=>array(210,210,210)));
                $pdf->line($this->marge_gauche, $nexY+1, $this->page_largeur - $this->marge_droite, $nexY+1);
                $pdf->SetLineStyle(array('dash'=>0));
                $pdf->SetY($nexY+2);

                if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))   // by default
                {
                    $pdf->MultiCell(0, 3, $line, 0, $align, 0);
                }
                else
                {
                    $pdf->writeHTMLCell($pdf->page_largeur - $pdf->margin_left - $pdf->margin_right, $freetextheight, $pdf->GetX(), $pdf->GetY(), dol_htmlentitiesbr($line, 1, 'UTF-8', 0));
                }
            }

        return $freetextheight;


    }
//------------


    /**
     *   	Show footer of page. Need this->emetteur object
     *
     *   	@param				$pdf
     * 		@param	Object		$object				Object to show
     *      @param	Translate	$outputlangs		Object lang for output
     *      @param	int			$hidefreetext		1=Hide free text
     *      @return	int								Return height of bottom margin including footer text
     */
    function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
    {
        //global $conf;
        //$showdetails=$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
        //return pdf_pagefoot($pdf,$outputlangs,'INVOICE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,$showdetails,$hidefreetext);
    }


}

function pdf_build_address_afip($outputlangs, $sourcecompany, $targetcompany = '', $targetcontact = '', $usecontact = 0, $mode = 'source', $object = null)
{
    global $conf, $hookmanager;

    if ($mode == 'source' && ! is_object($sourcecompany)) return -1;
    if ($mode == 'target' && ! is_object($targetcompany)) return -1;

    if (! empty($sourcecompany->state_id) && empty($sourcecompany->state))             $sourcecompany->state=getState($sourcecompany->state_id);
    if (! empty($targetcompany->state_id) && empty($targetcompany->state))             $targetcompany->state=getState($targetcompany->state_id);

    $reshook=0;
    $stringaddress = '';
    if (is_object($hookmanager))
    {
        $parameters = array('sourcecompany'=>&$sourcecompany, 'targetcompany'=>&$targetcompany, 'targetcontact'=>&$targetcontact, 'outputlangs'=>$outputlangs, 'mode'=>$mode, 'usecontact'=>$usecontact);
        $action='';
        $reshook = $hookmanager->executeHooks('pdf_build_address', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
        $stringaddress.=$hookmanager->resPrint;
    }
    if (empty($reshook))
    {
        if ($mode == 'source')
        {
            $withCountry = 0;
            if (!empty($sourcecompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) $withCountry = 1;

            $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($sourcecompany, $withCountry, "\n", $outputlangs))."\n";

            if (empty($conf->global->MAIN_PDF_DISABLESOURCEDETAILS))
            {
                // Phone
                if ($sourcecompany->phone) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("PhoneShort").": ".$outputlangs->convToOutputCharset($sourcecompany->phone);
                // Fax
                if ($sourcecompany->fax) $stringaddress .= ($stringaddress ? ($sourcecompany->phone ? " - " : "\n") : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($sourcecompany->fax);
                // EMail
                if ($sourcecompany->email) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($sourcecompany->email);
                // Web
                if ($sourcecompany->url) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($sourcecompany->url);
            }
            // Intra VAT
            if (! empty($conf->global->MAIN_TVAINTRA_IN_SOURCE_ADDRESS))
            {
                if ($sourcecompany->tva_intra) $stringaddress.=($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($sourcecompany->tva_intra);
            }
        }

        if ($mode == 'target' || preg_match('/targetwithdetails/', $mode))
        {
            if ($usecontact)
            {
                $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset($targetcontact->getFullName($outputlangs, 1));

                if (!empty($targetcontact->address)) {
                    $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcontact));
                } else {
                    $companytouseforaddress = $targetcompany;

                    // Contact on a thirdparty that is a different thirdparty than the thirdparty of object
                    if ($targetcontact->socid > 0 && $targetcontact->socid != $targetcompany->id)
                    {
                        $targetcontact->fetch_thirdparty();
                        $companytouseforaddress = $targetcontact->thirdparty;
                    }

                    $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($companytouseforaddress));
                }
                // Country
                if (!empty($targetcontact->country_code) && $targetcontact->country_code != $sourcecompany->country_code) {
                    $stringaddress.= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcontact->country_code));
                }
                elseif (empty($targetcontact->country_code) && !empty($targetcompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
                    $stringaddress.= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code));
                }
            }
            else
            {
                $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcompany));
                // Country
                if (!empty($targetcompany->country_code) && $targetcompany->country_code != $sourcecompany->country_code) $stringaddress.=($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code));
            }

            // Intra VAT
            if (empty($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS))
            {
                if ($targetcompany->tva_intra) $stringaddress.=($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($targetcompany->tva_intra);
            }

            // Public note
            if (! empty($conf->global->MAIN_PUBLIC_NOTE_IN_ADDRESS))
            {
                if ($mode == 'source' && ! empty($sourcecompany->note_public))
                {
                    $stringaddress.=($stringaddress ? "\n" : '' ).dol_string_nohtmltag($sourcecompany->note_public);
                }
                if (($mode == 'target' || preg_match('/targetwithdetails/', $mode)) && ! empty($targetcompany->note_public))
                {
                    $stringaddress.=($stringaddress ? "\n" : '' ).dol_string_nohtmltag($targetcompany->note_public);
                }
            }
        }
    }

    return $stringaddress;
}
