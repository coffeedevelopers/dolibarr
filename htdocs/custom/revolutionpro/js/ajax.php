<?php
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

dol_include_once('/revolutionpro/class/revolutionpro.class.php');

$revolutionpro2 = new revolutionpro($db);

$ajxaction 	 = GETPOST('ajxaction');

global $conf, $langs, $hookmanager;
if($ajxaction == 'notifications'){

	$SCRIPT_FILENAME  	= GETPOST('SCRIPT_FILENAME');
	$REQUEST_URI  		= GETPOST('REQUEST_URI');
	$SCRIPT_NAME  		= GETPOST('SCRIPT_NAME');
	$PHP_SELF 	 		= GETPOST('PHP_SELF');


	$_SERVER['SCRIPT_FILENAME'] = urldecode($SCRIPT_FILENAME);
	$_SERVER['REQUEST_URI'] 	= urldecode($REQUEST_URI);
	$_SERVER['SCRIPT_NAME'] 	= urldecode($SCRIPT_NAME);
	$_SERVER['PHP_SELF'] 		= urldecode($PHP_SELF);

	$returned = '';
	if($user && !$user->admin && $conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX && $conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX == 'demo'){
		$langs->load('revolutionpro@revolutionpro');
		print '<li class="nav-item" id="ordernowbuttoninmenu" >';
	  	print '<a target="_blank"class="butAction hover" href="https://www.dolibarrstore.com/fr/themes-et-modeles-dolibarr/47-revolution-pro-theme-dolibarr.html">'.$langs->trans('OrderNow').'</a>';
		print '</li>';
	}

	// $parameters = array();
	// $result = $hookmanager->executeHooks('printTopRightMenu', $parameters); // Note that $action and $object may have been modified by some hooks
	// $rs = '';
	// if (is_numeric($result))
	// {
	// 	if ($result == 0)
	// 		$rs .= $hookmanager->resPrint; // add
	// 	else
	// 		$rs .= $hookmanager->resPrint; // replace
	// }
	// else
	// {
	// 	$rs .= $result; // For backward compatibility
	// }
	// if(!empty($rs)){
	// 	print '<li class="nav-item hooksmenusontop ">';
	// 	print $rs;
	// 	print '</li>';
	// }

	?>
	<li class="nav-item dropdown flags">

	  	<div class="dropdown-menu" role="menu">
		<?php

		$langs_available=$langs->get_available_languages(DOL_DOCUMENT_ROOT, 12);
		$selected = !empty($user->conf->MAIN_LANG_DEFAULT)?$user->conf->MAIN_LANG_DEFAULT : $langs->defaultlang;

		asort($langs_available);

		foreach ($langs_available as $key => $value)
		{
			$actif = '';
			if ($selected == $key)
			{
				$slctltxt = $value;
				$actif = 'active';
			}

			$picto = $revolutionpro2->pictolangflag($key);

			print '<a class="dropdown-item '.$actif.'" href="'.dol_buildpath('/revolutionpro/admin/lang.php?lang='.$key.'&backtopage='.$REQUEST_URI,2).'" role="menuitem"><span class="flag-icon ">'.$picto.'</span> '.$value.'</a>';
		}

		?>
	  	</div>
	  	<?php
	  	$picto = $revolutionpro2->pictolangflag($selected);
	  	print '<a class="nav-link" data-toggle="dropdown" href="javascript:void(0)" data-animation="scale-up" aria-expanded="false" role="button"><span style="margin-right: 5px;" class="flag-icon ">'.$picto.'</span> '.$slctltxt.'</a>';
	  	?>
	</li>
	<?php

	$userImage = '';
	if (!empty($user->photo))
	{
	    $userImage          = Form::showphoto('userphoto', $user, 0, 0, 0, 'photouserphoto userphoto', 'small', 0, 1);
	    $userDropDownImage  = Form::showphoto('userphoto', $user, 0, 0, 0, 'dropdown-user-image', 'small', 0, 1);
	}
	else {
	    $nophoto = '/public/theme/common/user_anonymous.png';
	    if ($user->gender == 'man') $nophoto = '/public/theme/common/user_man.png';
	    if ($user->gender == 'woman') $nophoto = '/public/theme/common/user_woman.png';

	    $userImage = '<img class="photo photouserphoto userphoto" alt="No photo" src="'.DOL_URL_ROOT.$nophoto.'">';
	    $userDropDownImage = '<img class="photo dropdown-user-image" alt="No photo" src="'.DOL_URL_ROOT.$nophoto.'">';
	}
	?>
	<li class="nav-item dropdown">
	  <a class="nav-link navbar-avatar" data-toggle="dropdown" href="#" aria-expanded="false"
	    data-animation="scale-up" role="button">
	    <span class="avatar avatar-online">
	      	<?php echo $userImage; ?>
	      <i></i>
	    </span>
	  </a>
	  <div class="dropdown-menu" role="menu">
	    <?php
			$logoutlink = DOL_URL_ROOT.'/user/logout.php';
			$profilLink = DOL_URL_ROOT.'/user/card.php?id='.$user->id;
	    	print '<a class="dropdown-item" href="'.$profilLink.'" role="menuitem"><i class="icon md-account" aria-hidden="true"></i> '.$langs->trans("Card").'</a>';
	    	print '<div class="dropdown-divider"></div>';
	    	print '<a class="dropdown-item" href="'.$logoutlink.'" role="menuitem"><i class="icon md-power" aria-hidden="true"></i> '.$langs->trans("Logout").'</a>';
	    ?>
	  </div>
	</li>


	<?php

	$notifications = $revolutionpro2->getNotifications(15);

	print '<li class="nav-item dropdown notifications">';

		print '<a class="nav-link" data-toggle="dropdown" href="javascript:void(0)" aria-expanded="false" data-animation="scale-up" role="button">';
			print '<i class="icon md-notifications" aria-hidden="true"></i>';
			if($notifications)
				print '<span class="badge badge-pill badge-danger up">'.count($notifications).'</span>';
			else
				print '<span class="badge badge-pill badge-danger up"></span>';
		print '</a>';

	  	print '<div class="dropdown-menu dropdown-menu-right dropdown-menu-media" role="menu">';

			print '<div class="dropdown-menu-header">';
			print '<h5>'.$langs->trans('Events').'</h5>';
			if($notifications)
			print '<span class="badge badge-round badge-danger">'.$langs->trans('New').' '.count($notifications).'</span>';
			print '</div>';

			print '<div class="list-group">';
				print '<div data-role="container">';
					print '<div data-role="content">';
						if($notifications){
							$form = new Form($db);
							foreach ($notifications as $key => $obj) {
								$utilisateur = '';
								$userstatic = new User($db);
								if ($obj->fk_user_action > 0)
								{
									$userstatic->fetch($obj->fk_user_action);
									
									$utilisateur = $userstatic->lastname .' '.$userstatic->firstname;
								}

								$urltoact = dol_buildpath('/comm/action/card.php?id='.$obj->id, 2);

								print '<a class="list-group-item dropdown-item" href="'.$urltoact.'" role="menuitem">';
									print '<div class="media">';
										$userImage = '';

								        if (!empty($userstatic->photo))
									    {
									        $userImage          = Form::showphoto('userphoto', $userstatic, 0, 0, 0, 'photouserphoto userphoto', 'small', 0, 1);
									        $userDropDownImage  = Form::showphoto('userphoto', $userstatic, 0, 0, 0, 'dropdown-user-image', 'small', 0, 1);
									    }
									    else {
									        $nophoto = '/public/theme/common/user_anonymous.png';
									        if ($userstatic->gender == 'man') $nophoto = '/public/theme/common/user_man.png';
									        if ($userstatic->gender == 'woman') $nophoto = '/public/theme/common/user_woman.png';

									        $userImage = '<img class="photo photouserphoto userphoto" alt="No photo" src="'.DOL_URL_ROOT.$nophoto.'">';
									        $userDropDownImage = '<img class="photo dropdown-user-image" alt="No photo" src="'.DOL_URL_ROOT.$nophoto.'">';
									    }
										print '<div class="pr-10">';
											print $userImage;
										print '</div>';

										
										
										$notif =  $obj->label;;

										print '<div class="media-body">';
											print '<h6 class="media-heading">'.$notif.'</h6>';
											print '<span class="media-meta">';
												print $utilisateur;
											print '</span>';
											$formatToUse = $obj->fulldayevent ? 'day' : 'dayhour';
											print '  -  ';
											print '<span class="media-meta">';
												print dol_print_date($db->jdate($obj->datep), $formatToUse);
											print '</span>';
										print '</div>';
									print '</div>';
								print '</a>';
							}
						}


					print '</div>';
				print '</div>';
			print '</div>';

			print '<div class="dropdown-menu-footer">';
				print '<a class="dropdown-menu-footer-btn" href="'.dol_buildpath('/comm/action/list.php',2).'" role="button">';
					print '<i class="icon md-settings" aria-hidden="true"></i>';
				print '</a>';

				print '<a class="dropdown-item" href="'.dol_buildpath('/comm/action/list.php',2).'" role="menuitem">';
					print $langs->trans('Events');
				print '</a>';
			print '</div>';

		print '</div>';
	print '</li>';

	$toprightmenu = $revolutionpro2->getOthersRightMenu();
	?>
	<li class="nav-item dropdown othersrightmenu">
	  	<a class="nav-link" data-toggle="dropdown" href="javascript:void(0)" aria-expanded="false" data-animation="scale-up" role="button">
			<i class="icon md-more-vert" aria-hidden="true"></i>
			
		</a>
	  <div class="dropdown-menu revolutionothersrightmenus" role="menu">
	    <?php
			print $toprightmenu;
	    ?>
	  </div>
	</li>

	<?php
}


elseif($ajxaction == 'boxes'){

	require_once DOL_DOCUMENT_ROOT.'/core/class/workboardresponse.class.php';

	$val8 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE8 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE8 : 'tiers'; // First Boxe
	$val9 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE9 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE9 : 'projets'; // Second Boxe
	$val10 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE10 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE10 : 'devis'; // Third Boxe
	$val11 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE11 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE11 : 'factures'; // Fourth Boxe

	$toget = [$val8 => $val8, $val9 => $val9, $val10 => $val10, $val11 => $val11];

	$boxs = array();

	$returns['1'] = $returns['2'] = $returns['3'] = $returns['4'] = '00';
	$boxs['tiers'] = $boxs['projets'] = $boxs['devis'] = $boxs['factures'] = $boxs['factfourni'] = $boxs['catalogues'] = $boxs['cmdclients'] = $boxs['cmdfournis'] = $boxs['facturesfour'] = $boxs['commercial'] = '00';

	// Tiers
	if(isset($toget['tiers'])){
		$sql = 'select count(rowid) as sum from '.MAIN_DB_PREFIX.'societe WHERE entity = '.$conf->entity.' AND status IN (1)';
		$resql = $db->query($sql);

		if($resql){
			$obj = $db->fetch_object($resql);
			$boxs['tiers'] = sprintf("%02d", $obj->sum);
		}
	}

	// Projets
	if(isset($toget['projets'])){
		$dashtot = array();
		if ((!empty($conf->projet->enabled) && $user->rights->projet->lire) || $user->admin) {
			require_once DOL_DOCUMENT_ROOT."/projet/class/project.class.php";
	        $board = new Project($db);
	        $dashtot = $board->load_board($user);
	        if($dashtot->nbtodo){
	    		$boxs['projets'] = sprintf("%02d", $dashtot->nbtodo);
	        }
	    }
    }

	// Devis
	if(isset($toget['devis'])){
		$dashtot = array();
		if ((!empty($conf->propal->enabled) && $user->rights->propale->lire) || $user->admin) {
			require_once DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php";
	        $board = new Propal($db);
	        $dashtot = $board->load_board($user,'opened');
	        if($dashtot->nbtodo){
	    		$boxs['devis'] = sprintf("%02d", $dashtot->nbtodo);
	        }
	    }
    }

	// Espace commercial
	if(isset($toget['commercial'])){
		$dashtot = array();
		if ((!empty($conf->propal->enabled) && $user->rights->propale->lire) || $user->admin) {
			require_once DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php";
	        $board = new Propal($db);
	        $dashtot = $board->load_board($user,'opened');
	        if($dashtot->nbtodo){
	    		$boxs['commercial'] = sprintf("%02d", $dashtot->nbtodo);
	        }
	    }
    }

	// Facture
	if(isset($toget['factures'])){
		$dashtot = array();
		if ((!empty($conf->facture->enabled) && $user->rights->facture->lire) || $user->admin) {
	        include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	        $board = new Facture($db);
	        $dashtot = $board->load_board($user);
	        if($dashtot->nbtodo)
	        	$boxs['factures'] = sprintf("%02d", $dashtot->nbtodo);
	    }
    }

	// Facture Fournisseur
	if(isset($toget['facturesfour'])){
		$dashtot = array();
		if ((!empty($conf->fournisseur->facture->enabled) && $user->rights->fournisseur->facture->lire) || $user->admin) {
	        include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	        $board = new FactureFournisseur($db);
	        $dashtot = $board->load_board($user);
	        if($dashtot->nbtodo)
	        	$boxs['facturesfour'] = sprintf("%02d", $dashtot->nbtodo);
	    }
    }

	// // Facture Fournisseurs
	// if(isset($toget['factfourni'])){
	// 	$dashtot = array();
	// 	if ((!empty($conf->supplier_invoice->enabled) && $user->rights->fournisseur->facture->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_INVOICES_STATS)) || $user->admin) {
	//         include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	//         $board = new FactureFournisseur($db);
	//         $dashtot = $board->load_board($user);
	//         if($dashtot->nbtodo)
	//         	$boxs['factfourni'] = sprintf("%02d", $dashtot->nbtodo);
	//     }
	// }

	// Products
	if(isset($toget['catalogues'])){
		$dashtot = array();
		if ((!empty($conf->product->enabled) && $user->rights->produit->lire) || $user->admin) {
			require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
	        $board = new Product($db);
	        $dashtot = $board->load_state_board();

	        if($board->nb){
	        	if(isset($board->nb["products"]) && $board->nb["products"] > 0)
	        		$boxs['catalogues'] = sprintf("%02d", $board->nb["products"]);
	        }
	    }
    }

	// Commandes Clients
	if(isset($toget['cmdclients'])){
		$dashtot = array();
		if ((!empty($conf->commande->enabled) && $user->rights->commande->lire) || $user->admin) {
			require_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";
	        $board = new Commande($db);
	        $dashtot = $board->load_board($user);
	        if($dashtot->nbtodo){
	    		$boxs['cmdclients'] = sprintf("%02d", $dashtot->nbtodo);
	        }
	    }
    }

	// Commandes Fournisseurs
	if(isset($toget['cmdfournis'])){
		$dashtot = array();
		if ((!empty($conf->supplier_order->enabled) && $user->rights->fournisseur->commande->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_ORDERS_STATS)) || $user->admin) {
			require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php";
	        $board = new CommandeFournisseur($db);
	        $dashtot = $board->load_board($user);
	        if($dashtot->nbtodo){
	    		$boxs['cmdfournis'] = sprintf("%02d", $dashtot->nbtodo);
	        }
	    }
    }


 	// $returns[1] = $boxs['tiers'];
	// $returns[2] = $boxs['projets'];
	// $returns[3] = $boxs['devis'];
	// $returns[4] = $boxs['factures'];

	if(isset($boxs[$val8]))  $returns[1] = $boxs[$val8];
	if(isset($boxs[$val9]))  $returns[2] = $boxs[$val9];
	if(isset($boxs[$val10])) $returns[3] = $boxs[$val10];
	if(isset($boxs[$val11])) $returns[4] = $boxs[$val11];

    echo json_encode($returns);
}

elseif($ajxaction == 'searchbar'){

	$html = '';
	$searchform = '';
  	$html .= '<form role="search" class="formajax">';
	    $html .= '<div class="form-group">';
	     $html .= '<div class="input-search blockvmenusearch" id="blockvmenusearch">';
	        	global $hookmanager;
	        	$usedbyinclude = 1;
	            $arrayresult = null;
	            $hookmanager->initHooks(array('searchform', 'leftblock'));
	            include DOL_DOCUMENT_ROOT.'/core/ajax/selectsearchbox.php'; // This set $arrayresult
	             require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	            $form = new Form($db);

	            if ($conf->use_javascript_ajax && empty($conf->global->MAIN_USE_OLD_SEARCH_FORM)) {
	                $searchform .= $form->selectArrayFilter('searchselectcombo', $arrayresult, $selected, '', 1, 0, (empty($conf->global->MAIN_SEARCHBOX_CONTENT_LOADED_BEFORE_KEY) ? 1 : 0), 'vmenusearchselectcombo', 1, $langs->trans("Search"), 0);
	            } else {
	                if (is_array($arrayresult)) {
	                    foreach ($arrayresult as $key => $val) {
	                        $searchform .= printSearchForm($val['url'], $val['url'], $val['label'], 'maxwidth125', 'sall', $val['shortcut'], 'searchleft'.$key, img_picto('', $val['img'], '', false, 1, 1));
	                    }
	                }
	            }

	            // Execute hook printSearchForm
	            $parameters = array('searchform' => $searchform);
	            $reshook = $hookmanager->executeHooks('printSearchForm', $parameters); // Note that $action and $object may have been modified by some hooks
	            if (empty($reshook)) {
	                $searchform .= $hookmanager->resPrint;
	            } else $searchform = $hookmanager->resPrint;

	            // Force special value for $searchform
	            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) || empty($conf->use_javascript_ajax)) {
	                $urltosearch = DOL_URL_ROOT.'/core/search_page.php?showtitlebefore=1';
	                $searchform = '<div class="blockvmenuimpair blockvmenusearchphone"><div id="divsearchforms1"><a href="'.$urltosearch.'" accesskey="s" alt="'.dol_escape_htmltag($langs->trans("ShowSearchFields")).'">'.$langs->trans("Search").'...</a></div></div>';
	            } elseif ($conf->use_javascript_ajax && !empty($conf->global->MAIN_USE_OLD_SEARCH_FORM)) {
	                $searchform = '<div class="blockvmenuimpair blockvmenusearchphone"><div id="divsearchforms1"><a href="#" alt="'.dol_escape_htmltag($langs->trans("ShowSearchFields")).'">'.$langs->trans("Search").'...</a></div><div id="divsearchforms2" style="display: none">'.$searchform.'</div>';
	                $searchform .= '<script>
	            	jQuery(document).ready(function () {
	            		jQuery("#divsearchforms1").click(function(){
		                   jQuery("#divsearchforms2").toggle();
		               });
	            	});
	                </script>' . "\n";
	                $searchform .= '</div>';
	            }
            	$searchform .= '<script>
            	jQuery(document).ready(function () {
	               jQuery("#site-navbar-search #blockvmenusearch>span").addClass("form-control");
            	});
                </script>' . "\n";

                
	            $html .= $searchform;
	        $html .= '<button type="button" class="input-search-close icon md-close" data-target="#site-navbar-search" data-toggle="collapse" aria-label="Close"></button>';
	      $html .= '</div>';
	    $html .= '</div>';
  	$html .= '</form>';

  	echo $html;
}

