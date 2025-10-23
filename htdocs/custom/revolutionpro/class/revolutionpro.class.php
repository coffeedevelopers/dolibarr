<?php 
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php'; 
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
dol_include_once('/revolutionpro/core/modules/modRevolutionpro.class.php');

class revolutionpro extends Commonobject{ 

	public $errors = array();
	public $rowid;
	public $ref;

	public $element='revolutionpro';
	public $table_element='revolutionpro';
	
	public function __construct($db){ 
		global $langs;

		$this->db = $db;

		$langs->loadLangs(['companies','commercial','bills','propal','orders','boxes','products']);

		$this->fourboxcontent = [
	        'tiers'			=> $langs->trans('ThirdParty').' ('.$langs->trans('Opened').')',
	        'projets'		=> $langs->trans('Projects').' ('.$langs->trans('Opened').')',
	        'devis'			=> $langs->trans('Proposals').' ('.$langs->trans('Validated').')',
	        'commercial' 	=> $langs->trans('CommercialArea').' ('.$langs->trans('Proposal').')',
	        'factures'		=> $langs->trans('BillsCustomers').' ('.$langs->trans('BillStatusNotPaid').')',
	        'facturesfour' 	=> $langs->trans('BillsSuppliers').' ('.$langs->trans('BillStatusNotPaid').')',

	        'catalogues'	=> $langs->trans('RevolutionProCatalogue').' ('.$langs->trans('Products').')',
	        'cmdclients'	=> $langs->trans('ForCustomersOrders').' ('.$langs->trans('Opened').')',
	        'cmdfournis'	=> $langs->trans('SuppliersOrders').' ('.$langs->trans('Opened').')',
	    ];  

		return 1;
    }

    public function getStyleloadRevolutionPro()
	{
		global $conf, $langs, $user,$mysoc;
		global $mainmenu;
		$mainmenu2 = $mainmenu;

		$noreqm = 0;
		if (!empty($conf->dol_hide_leftmenu) || (defined('NOREQUIREMENU')))
			$noreqm = 1;
		?>

		<script type="text/javascript">
			$(document).ready(function(){
				var noreqm = parseInt('<?php echo dol_escape_js($noreqm); ?>');

				$('#loader-overlay').hide();
				
				var txtbox = $('#rowboxrevolutionpro').html();

				if ($('#mainbody > .fiche ~ table input[type="submit"]').length) {
					var submitbutton = $('#mainbody > .fiche ~ table');
					var txtsubmit = submitbutton.html();
					submitbutton.remove();
					$('#mainbody > .fiche > form').append('<br> <div class="center mainbody_fiche_form"> <table width="100%"> '+txtsubmit+' </table> </div>');
				}


				$('#mainbody > * ').not('.side-nav-vert').wrapAll($('<div class="page"> <div class="page-content container-fluid dolibarrstorecontainter"> </div> </div>'));
				// $('#mainbody > .fiche ').wrap($('<div class="page"> <div class="page-content container-fluid"> </div> </div>'));
				if(txtbox !== '')
					$('#mainbody > .page > .page-content').prepend(txtbox);

	        	// $('body').addClass('animsition site-navbar-small dashboard site-menubar-fold');
	            // $('html').addClass('css-menubar');
	            $('body').addClass('revolutionpro');
    			
    			if($('body').hasClass('bodylogin')){
    				var txtlogin = $('.bodylogin div.login_center').html();
    				if(txtlogin !== ''){
    					$('.bodylogin div.login_center').hide();
    					$('.bodylogin #pagelogindivcontent #logindivcontent').html(txtlogin);
    					$('.bodylogin').addClass('page-login-v2 page-dark layout-full');
    					$('.bodylogin #pagelogindivcontent').show();
    					$('.bodylogin div.login_center').remove();
    				}
    			}
    			$('body .site-menu>.site-menu-item>a ').on('click', function(e) {
				     $(this).parent('li').find('a.mm-next').click();
				});

    			if(noreqm < 1) $('body').addClass('revolutionpronoreqm');

				// revolutionproajax('leftmenu');
				if(noreqm < 1){
					revolutionproajax('notifications');
				}

				<?php
					$val4 = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE4 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE4 : 'show';
					if($val4 == 'show' && !$noreqm){
						?>
						revolutionproajax2('boxes');
						<?php
					}
				?>
				if(noreqm < 1){
					revolutionproajax3('searchbar');
				}

	        });
		</script>

		<!-- Stylesheets -->
	    <link rel="stylesheet" href="<?php echo dol_buildpath('/revolutionpro/theme', 1); ?>/global/css/skintools.min.css">
		<?php

		$noreqm = 0;
		if (!empty($conf->dol_hide_leftmenu) || (defined('NOREQUIREMENU'))) $noreqm = 1;

		if(!$noreqm) { 
		    print '<link rel="stylesheet" href="'.dol_buildpath('/revolutionpro/theme', 1) .'/global/css/bootstrap.min.css">';
	    	print '<link rel="stylesheet" href="'.dol_buildpath('/revolutionpro/theme', 1) .'/global/css/bootstrap-extend.min.css">';
		}

		?>
	    
	    <!-- Plugins -->
	    <link rel="stylesheet" href="<?php echo dol_buildpath('/revolutionpro/theme', 1); ?>/global/vendor/animsition/animsition.min.css">
	    <!-- <link rel="stylesheet" href="<?php echo dol_buildpath('/revolutionpro/theme', 1); ?>/assets/css/site.min.css"> -->
	    <link rel="stylesheet" href="<?php echo dol_buildpath('/revolutionpro/theme', 1); ?>/global/vendor/asscrollable/asScrollable.min.css">
	    <link rel="stylesheet" href="<?php echo dol_buildpath('/revolutionpro/theme', 1); ?>/global/vendor/jquery-mmenu/jquery-mmenu.min.css">
	    <!-- <link rel="stylesheet" href="<?php echo dol_buildpath('/revolutionpro/theme', 1); ?>/global/vendor/flag-icon-css/flag-icon.min.css"> -->
	    <link rel="stylesheet" href="<?php echo dol_buildpath('/revolutionpro/theme', 1); ?>/global/vendor/waves/waves.min.css">
	        <!-- <link rel="stylesheet" href="<?php echo dol_buildpath('/revolutionpro/theme', 1); ?>/global/vendor/ladda/ladda.css"> -->
	        <!-- <link rel="stylesheet" href="<?php echo dol_buildpath('/revolutionpro/theme', 1); ?>/assets/examples/css/uikit/buttons.css"> -->
	    
	    
	    <!-- Fonts -->
	    <link rel="stylesheet" href="<?php echo dol_buildpath('/revolutionpro/theme', 1); ?>/global/fonts/material-design/material-design.min.css">
	    <link rel="stylesheet" href="<?php echo dol_buildpath('/revolutionpro/theme', 1); ?>/global/fonts/brand-icons/brand-icons.min.css">
	    <!-- <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Roboto:300,400,500,300italic'> -->
		  
			

		<div class="loader-overlay" id="loader-overlay"><div class="loader-content"><div class="loader-index"><div></div><div></div><div></div><div></div><div></div><div></div></div></div></div>

		<?php


		$SCRIPT_FILENAME 	= urlencode($_SERVER['SCRIPT_FILENAME']);
		$REQUEST_URI 		= urlencode($_SERVER['REQUEST_URI']);
		$SCRIPT_NAME 		= urlencode($_SERVER['SCRIPT_NAME']);
		$PHP_SELF 			= urlencode($_SERVER['PHP_SELF']);


		if(!isset($_SESSION["dol_login"])){

			$urllogo = $this->getLogoCompanyUrl();

			// $pagecnx = $langs->trans('Hello')."!";
			$pagecnx = "";
			$main_home = '';
			if (! empty($conf->global->MAIN_HOME))
			{
			    $substitutionarray=getCommonSubstitutionArray($langs);
			    complete_substitutions_array($substitutionarray, $langs);
			    $texttoshow = make_substitutions($conf->global->MAIN_HOME, $substitutionarray, $langs);

				$main_home=dol_htmlcleanlastbr($texttoshow);
			}
			if($main_home)
				$pagecnx = $main_home;

			print '<div class="page" id="pagelogindivcontent" style="display:none;" data-animsition-in="fade-in" data-animsition-out="fade-out">';
		      print '<div class="page-content">';
		        print '<div class="page-brand-info">';
		          print '<div class="brand">';
		            print '<img class="brand-img" style="height:26px;" src="'.$urllogo.'" alt="...">';
		            $societename = 'Dolibarr Store';
					
					if(!empty($mysoc->name))
						$societename = $mysoc->name;

					$sociname = $societename;
					if($conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX && $conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX == 'demo'){
						$sociname = '<a href="https://www.dolibarrstore.com/themes-et-modeles-dolibarr/73-revolution-pro-v13-theme-dolibarr-.html" target="_blank">'.$societename.'</a>';
					}

		            print '<h2 class="brand-text font-size-40">'.$sociname.'</h2>';
		          print '</div>';
		          print '<p class="font-size-20">'.$pagecnx.'</p>';
		        print '</div>';

		        print '<div class="page-login-main form-material" id="logindivcontent">';
		        print '</div>';

		      print '</div>';
		    print '</div>';

		    $langs->load('revolutionpro@revolutionpro');
		    $OrderNow = $langs->trans('OrderNow');
		    
		}

	    $societename = 'Dolibarr Store'; if(!empty($mysoc->name)) $societename = $mysoc->name;
    	// $footertxt = '<span class="revolutionscrollinpage md-long-arrow-down bottom" onclick="scrollrevolutionprobottom()"></span>';
    	$footertxt .= '<div class="site-footer-legal">© '.date('Y').' '.$societename.'</div><div class="site-footer-right">  Crafted with <i class="red-600 icon md-favorite"></i> by <a class="ds_url_module_name" target="_blank" href="https://www.dolibarrstore.com">DolibarrStore</a>	</div>';

    	$linkajax = dol_buildpath('/revolutionpro/js/ajax.php',1);
		// $curr = parse_url($linkajax);
		// $domainconf = $curr['scheme'].'://'.$curr['host'];
  //   	$currdomain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
  //   	$currdomain = rtrim($currdomain,"/");
  //   	if($domainconf != $currdomain) $linkajax = str_replace($domainconf, $currdomain, $linkajax);

		?>

		<script type="text/javascript">
			$(document).ready(function(){
				var noreqm = parseInt('<?php echo dol_escape_js($noreqm); ?>');
	            // $('html').addClass('css-menubar');
	        	// $('body').addClass('animsition site-navbar-small dashboard site-menubar-fold');
				$('#id-top > .login_block, #id-top > .login_block_user').remove();
    			if(noreqm == 1){
    				$('body').addClass('noreqm');
    			}
    			if($('body').hasClass('bodylogin')){
    				var txtlogin = $('.bodylogin div.login_center').html();
    				if(txtlogin !== ''){
    					$('.bodylogin div.login_center').hide();
    					$('.bodylogin #pagelogindivcontent #logindivcontent').html(txtlogin);
    					$('.bodylogin').addClass('page-login-v2 page-dark layout-full');
    					$('.bodylogin #pagelogindivcontent').show();
    					$('.bodylogin div.login_center').remove();
    				}
    			}

    			$('body .site-menu>.site-menu-item>a ').on('click', function(e) {
				     $(this).parent('li').find('a.mm-next').click();
				});

    			if(noreqm < 1){
					var content = $('#site-footer-hideen').html();
					$('#mainbody').append('<div style="clear:both;"></div><br><footer id="site-footer" class="site-footer"  style="position: relative;">'+content+'</footer>');
    			}else{
					var content = '<?php echo dol_escape_js($footertxt); ?>';
					$('#mainbody').append('<div style="clear:both;"></div><br><footer id="site-footer" class="site-footer"  style="position: relative;">'+content+'</footer>');
    			}

	        });
	        $(window).on('load',function(){

				// // var txtfoot = '<div class="addafterpage"></div>';
				// var txtbox = $('#rowboxrevolutionpro').html();

				// if ($('#mainbody > .fiche ~ table input[type="submit"]').length) {
				// 	var submitbutton = $('#mainbody > .fiche ~ table');
				// 	var txtsubmit = submitbutton.html();
				// 	submitbutton.remove();
				// 	$('#mainbody > .fiche > form').append('<br> <div class="center"> <table width="100%"> '+txtsubmit+' </table> </div>');
				// }

				// // $('body').css({'opacity':'1'});
				// $('#mainbody > .fiche ').wrap($('<div class="page"> <div class="page-content container-fluid"> </div> </div>'));


				// // $('#mainbody > .page ').after(txtfoot);
				// $('#mainbody > .page > .page-content').prepend(txtbox);

				// // var txtbox2 = '<span class="revolutionscrollinpage md-long-arrow-down bottom"></span>';
				// // $('body.site-navbar-small .page .page-content').prepend(txtbox2);
				// var content = $('#site-footer-hideen').html();
				// // $('#mainbody > .addafterpage').replaceWith('<div style="clear:both;"></div><br><footer id="site-footer" class="site-footer"  style="position: relative;">'+content+'</footer>');
				// $('#mainbody').append('<div style="clear:both;"></div><br><footer id="site-footer" class="site-footer"  style="position: relative;">'+content+'</footer>');

				
				// $('#site-footer-hideen').remove();



				// $('#site-navbar-search #blockvmenusearch>span').addClass('form-control');

				// setTimeout( function() { $('#loader-overlay').hide(); }, 250);
				// $('#loader-overlay').hide();

				
	        });

	        function revolutionproajax(ajxaction){
			    $.ajax({
			        data:{
			        	'ajxaction': ajxaction
			        	,'SCRIPT_FILENAME': '<?php echo dol_escape_js($SCRIPT_FILENAME); ?>'
			        	,'REQUEST_URI': '<?php echo dol_escape_js($REQUEST_URI); ?>'
			        	,'SCRIPT_NAME': '<?php echo dol_escape_js($SCRIPT_NAME); ?>'
			        	,'PHP_SELF': '<?php echo dol_escape_js($PHP_SELF); ?>'
			        },
			        url:"<?php echo dol_escape_js($linkajax); ?>",
			        type:'POST',
			        success:function(result){
			            if(result){
			                $('#rpronotificationwithmenu').html(result);
			                $('#dsotherblocklogin').show();
			            }
			        }
			    });
			}

	        function revolutionproajax2(ajxaction){
			    $.ajax({
			        data:{
			        	'ajxaction': ajxaction
			        },
			        url:"<?php echo dol_escape_js($linkajax); ?>",
			        type:'POST',
			        dataType: 'json',
			        cache: false,
			        success:function(result){
			            if(result){
			                $('.thefourboxes .onebox.box1 .counter .counter-number').html(result['1']);
			                $('.thefourboxes .onebox.box2 .counter .counter-number').html(result['2']);
			                $('.thefourboxes .onebox.box3 .counter .counter-number').html(result['3']);
			                $('.thefourboxes .onebox.box4 .counter .counter-number').html(result['4']);
			            }
			        }
			    });
			}

	        function revolutionproajax3(ajxaction){
			    $.ajax({
			        data:{
			        	'ajxaction': ajxaction
			        },
			        url:"<?php echo dol_escape_js($linkajax); ?>",
			        type:'POST',
			        cache: false,
			        success:function(result){
			            if(result){
			                $('#site-navbar-search.rpsearchtopbar').html(result);			                
			            }
			        }
			    });
			}

			$(document).ready(function(){
		        <?php
	        	$val1 = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE1 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE1 : 'light';
	        	$val3 = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE3 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE3 : 'teal';
				if($val1 == 'dark'){
					?>
						$('body #tmenu_tooltip > .site-menubar').addClass('site-menubar-dark');
					<?php
				}
				if($val3 != 'primary'){
					$num = 600;
					if($val3 == 'yellow') $num = 800;

					$bg = 'bg-'.$val3.'-'.$num;
					
					?>

					$('body.site-navbar-small .site-navbar').addClass('<?php echo dol_escape_js($bg); ?>');
					<?php
				}

				if($user && !$user->admin && $conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX && $conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX == 'demo'){
					?>

					var counter = 0;
					var ds_rp_demo_changecolor = window.localStorage.getItem("ds_rp_demo_changecolor");

					if(ds_rp_demo_changecolor){
			        	counter = parseInt(ds_rp_demo_changecolor) + 1;
			    	}

					window.localStorage.setItem('ds_rp_demo_changecolor', counter);
					
			        if(counter < 1){
						setTimeout( function() { $('body .site-skintools .site-skintools-toggle').click(); }, 1000);
			        }

			        setTimeout( function() { 
						if($('body .site-skintools').hasClass('is-open')){
							$('body .site-skintools .site-skintools-toggle').click(); 
						}
					}, 20000);
					
					<?php
				}
	        	?>
			});


			<?php
			if($conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX && $conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX == 'demo'){
			?>
				$(window).on('load',function(){
					var txtcomm = '<a target="_blank" id="ordernowbutton" class="butAction" href="https://www.dolibarrstore.com/themes-et-modeles-dolibarr/73-revolution-pro-v13-theme-dolibarr-.html">Order Now!</a>';
					$('.bodylogin #pagelogindivcontent #logindivcontent>.login_vertical_align>form').after(txtcomm); 
					
					$('li#ordernowbuttoninmenu>a').on("mouseover", function () {
					     $(this).removeClass('hover');
					});
				});
			<?php
			}
			?>

	    </script>

		<?php


    }

    public function getLogoCompanyUrl()
	{
		global $conf, $mysoc, $langs;


		$ardolv = DOL_VERSION;
		$ardolv = explode(".", $ardolv);
		$dolvs = $ardolv[0];
		
		if (!empty($mysoc->logo_squarred_mini) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_squarred_mini))
		{

			$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_squarred_mini);
		}
		elseif (!empty($mysoc->logo_mini) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini))
		{
			$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_mini);
			if($dolvs < 9){
				$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('/thumbs/'.$mysoc->logo_mini);
			}
		}
		else
		{
			$urllogo = dol_buildpath('/revolutionpro/img/object_thumb.png',1);
			$logoContainerAdditionalClass = '';
		}


		return $urllogo;
    }

    public function print_revolutionpro_new_top_menu($menu=array(),$tabMenu,$showmode)
	{
		global $conf, $mysoc, $langs, $user, $db, $hookmanager;

		$html = '';
		$urllogo = $this->getLogoCompanyUrl();

		$societename = 'DolibarrStore';
		global $mysoc;
		if(!empty($mysoc->name))
			$societename = $mysoc->name;

	
		
		$val2 = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE2 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE2 : 'inverse';
		$clss = '';
		if($val2 == 'inverse')
			$clss = 'navbar-inverse';

		$cmpstyle = "";
		$val7 = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE7 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE7 : 'show';
		if($val7 == 'hide')
		$cmpstyle = "display:none;";


		$rootlink = dol_buildpath('/',2);
		$curr = parse_url($rootlink);
		$domainconf = $curr['scheme'].'://'.$curr['host'];
    	$currdomain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    	$currdomain = rtrim($currdomain,"/");
    	if($domainconf != $currdomain) $rootlink = str_replace($domainconf, $currdomain, $rootlink);

		$html .= '<nav class="site-navbar navbar navbar-default navbar-fixed-top navbar-mega '.$clss.'" role="navigation">';
	    
	      	$html .= '<div class="navbar-header">';
	        $html .= '<button type="button" class="navbar-toggler hamburger hamburger-close navbar-toggler-left hided"
	          data-toggle="menubar">';
	          $html .= '<span class="sr-only">Toggle navigation</span>';
	          $html .= '<span class="hamburger-bar"></span>';
	        $html .= '</button>';
	        $html .= '<button type="button" class="navbar-toggler collapsed" data-target="#site-navbar-collapse"
	          data-toggle="collapse">';
	          $html .= '<i class="icon md-more" aria-hidden="true"></i>';
	        $html .= '</button>';
	        	$html .= '<a href="'. $rootlink .'?mainmenu=home" class="navbar-brand navbar-brand-center site-gridmenu-toggle">';
		          $html .= '<img class="navbar-brand-logo" src="'. $urllogo .'">';
		          $html .= '<span class="navbar-brand-text hidden-xs-down" style="'. $cmpstyle .'"> '. $societename .'</span>';
	      		$html .= '</a>';



	        $html .= '<button type="button" class="navbar-toggler collapsed" data-target="#site-navbar-search" data-toggle="collapse">';
	          $html .= '<span class="sr-only">Toggle Search</span>';
	          $html .= '<i class="icon md-search" aria-hidden="true"></i>';
	        $html .= '</button>';
	      $html .= '</div>';
	    
	      	$html .= '<div class="navbar-container container-fluid">';
	        $html .= '<div class="collapse navbar-collapse navbar-collapse-toolbar" id="site-navbar-collapse">';

	        
	          	$html .= '<ul class="nav navbar-toolbar">';
	           	$html .= ' <li class="nav-item hidden-float" id="toggleMenubar">';
	              $html .= '<a class="nav-link" data-toggle="menubar" href="#" role="button">';
	                $html .= '<i class="icon hamburger hamburger-arrow-left">';
	                  $html .= '<span class="sr-only">Toggle menubar</span>';
	                  $html .= '<span class="hamburger-bar"></span>';
	                $html .= '</i>';
	              $html .= '</a>';
	            $html .= '</li>';
	            $html .= '<li class="nav-item hidden-float">';
	              $html .= '<a class="nav-link icon md-search" data-toggle="collapse" href="#" data-target="#site-navbar-search"
	                role="button">';
	                $html .= '<span class="sr-only">Toggle Search</span>';
	              $html .= '</a>';
	            $html .= '</li>';
	         	$html .= '</ul>';
	    
				
				$html .= '<ul class="nav navbar-toolbar navbar-right navbar-toolbar-right" id="rpronotificationwithmenu">';
					$html .= '<li class="nav-item dropdown flags">';
					$html .= '</li>';
					$html .= '<li class="nav-item dropdown">';
					$html .= '</li>';
					$html .= '<li class="nav-item dropdown notifications">';
						$html .= '<a class="nav-link" data-toggle="dropdown" href="javascript:void(0)" aria-expanded="false" data-animation="scale-up" role="button">';
							$html .= '<i class="icon md-notifications" aria-hidden="true"></i>';
							$html .= '<span class="badge badge-pill badge-danger up"></span>';
						$html .= '</a>';
					$html .= '</li>';
						$html .= '<li class="nav-item dropdown othersrightmenu">';
							$html .= '<a class="nav-link" data-toggle="dropdown" href="javascript:void(0)" aria-expanded="false" data-animation="scale-up" role="button">';
							$html .= '<i class="icon md-more-vert" aria-hidden="true"></i>';
						$html .= '</a>';
					$html .= '</li>';
		  		$html .= '</ul>';


		  		$html .= '<ul class="nav navbar-toolbar navbar-right navbar-toolbar-right dsotherblocklogin" id="dsotherblocklogin">';
					$html .= '<li class="nav-item ">';
			           	$html .= '<div class="login_block_other">';

							// Execute hook printTopRightMenu (hooks should output string like '<div class="login"><a href="">mylink</a></div>')
							// $searchform = '';
							// $bookmarks = '';

							// Instantiate hooks for external modules
							$hookmanager->initHooks(array('toprightmenu'));
							$parameters = array();
							$result = $hookmanager->executeHooks('printTopRightMenu', $parameters); // Note that $action and $object may have been modified by some hooks
							if (is_numeric($result)) {
								if ($result == 0) {
									$toprightmenu .= $hookmanager->resPrint; // add
								} else {
									$toprightmenu = $hookmanager->resPrint; // replace
								}
							} else {
								$toprightmenu .= $result; // For backward compatibility
							}

							// $html .= $toprightmenu;
							$html .= preg_replace('#<script(.*?)>(.*?)</script>#is', '', $toprightmenu);


			           	$html .= '</div>';
		            $html .= '</li>';
	         	$html .= '</ul>';


	        $html .= '</div>';
	    
	        $html .= '<div class="collapse navbar-search-overlap rpsearchtopbar" id="site-navbar-search">';
	          // $html .= '<form role="search">';
	            // $html .= '<div class="form-group">';
	             // $html .= '<div class="input-search blockvmenusearch" id="blockvmenusearch">';
						// global $hookmanager;
						// $usedbyinclude = 1;
						// $arrayresult = null;
						// $hookmanager->initHooks(array('searchform', 'leftblock'));
						// include DOL_DOCUMENT_ROOT.'/core/ajax/selectsearchbox.php'; // This set $arrayresult
						//  require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
						// $form = new Form($db);

						// if ($conf->use_javascript_ajax && empty($conf->global->MAIN_USE_OLD_SEARCH_FORM)) {
						//     $searchform .= $form->selectArrayFilter('searchselectcombo', $arrayresult, $selected, '', 1, 0, (empty($conf->global->MAIN_SEARCHBOX_CONTENT_LOADED_BEFORE_KEY) ? 1 : 0), 'vmenusearchselectcombo', 1, $langs->trans("Search"), 1);
						// } else {
						//     if (is_array($arrayresult)) {
						//         foreach ($arrayresult as $key => $val) {
						//             $searchform .= printSearchForm($val['url'], $val['url'], $val['label'], 'maxwidth125', 'sall', $val['shortcut'], 'searchleft'.$key, img_picto('', $val['img'], '', false, 1, 1));
						//         }
						//     }
						// }

						// // Execute hook printSearchForm
						// $parameters = array('searchform' => $searchform);
						// $reshook = $hookmanager->executeHooks('printSearchForm', $parameters); // Note that $action and $object may have been modified by some hooks
						// if (empty($reshook)) {
						//     $searchform .= $hookmanager->resPrint;
						// } else $searchform = $hookmanager->resPrint;

						// // Force special value for $searchform
						// if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) || empty($conf->use_javascript_ajax)) {
						//     $urltosearch = DOL_URL_ROOT.'/core/search_page.php?showtitlebefore=1';
						//     $searchform = '<div class="blockvmenuimpair blockvmenusearchphone"><div id="divsearchforms1"><a href="'.$urltosearch.'" accesskey="s" alt="'.dol_escape_htmltag($langs->trans("ShowSearchFields")).'">'.$langs->trans("Search").'...</a></div></div>';
						// } elseif ($conf->use_javascript_ajax && !empty($conf->global->MAIN_USE_OLD_SEARCH_FORM)) {
						//     $searchform = '<div class="blockvmenuimpair blockvmenusearchphone"><div id="divsearchforms1"><a href="#" alt="'.dol_escape_htmltag($langs->trans("ShowSearchFields")).'">'.$langs->trans("Search").'...</a></div><div id="divsearchforms2" style="display: none">'.$searchform.'</div>';
						//     $searchform .= '<script>
						// 	jQuery(document).ready(function () {
						// 		jQuery("#divsearchforms1").click(function(){
						//            jQuery("#divsearchforms2").toggle();
						//        });
						// 	});
						//     </script>' . "\n";
						//     $searchform .= '</div>';
						// }

						// $html .= $searchform;
						// $html .= '<button type="button" class="input-search-close icon md-close" data-target="#site-navbar-search" data-toggle="collapse" aria-label="Close"></button>';
	              // $html .= '</div>';
	            // $html .= '</div>';
	          // $html .= '</form>';
	        $html .= '</div>';
	      $html .= '</div>';
	    $html .= '</nav>';

	    $html .= '<input type="hidden" id="LinkToSettinginput" value="'.dol_buildpath('/revolutionpro/admin/setup.php',2).'">';
	    $html .= '<input type="hidden" id="LinkToLogoutinput" value="'.DOL_URL_ROOT.'/user/logout.php'.'">';

	   	$html .= $this->printLeftMenu($menu,$tabMenu,$showmode);

	   	$societename = 'Dolibarr Store';
		
		if(!empty($mysoc->name))
			$societename = $mysoc->name;

	    $html .= '<footer id="site-footer-hideen" class="site-footer">';
	    	$html .= '<span class="revolutionscrollinpage md-long-arrow-down bottom" onclick="scrollrevolutionprobottom()"></span>';
	    	$html .= '<div class="site-footer-legal">© '.date('Y').' '.$societename.'</div><div class="site-footer-right">  Crafted with <i class="red-600 icon md-favorite"></i> by <a class="ds_url_module_name" target="_blank" href="https://www.dolibarrstore.com">DolibarrStore</a>	</div>';
	    $html .= '</footer>';
		
		return $html;
	}

    public function printLeftMenu($menu=array(),$tabMenu,$showmode)
	{
		global $dolibarr_main_url_root,$conf;
		global $langs;
		global $mainmenu;

		$html = '';

		$mainmenu9 = $_SESSION["mainmenu"];

		if(GETPOST('mainmenu', 'alpha')){
			$mainmenu9 = GETPOST('mainmenu', 'alpha');
		}

		if(empty($mainmenu9)) $mainmenu9 = 'home';

		$html .= '<div class="site-menubar " >';
		$html .= '<ul class="site-menu">';

		// print_r($menu);
		// if (empty($noout)) {
	    foreach ($menu->liste as $menukey => $menuval) {

	    	if(!empty($menuval['titre']) && $menuval['titre'] != 'ErrorBadValueForParamNotAString'){
				// if ($showmode == 1 || $showmode == 2){
	    		// echo "showmode : ".$showmode;
				if ($menuval['enabled']){

	    			$discls = '';
					
					
	    			$permiscls = '';
					if($menuval['enabled'] == 2)
						$permiscls = 'disabledrp';

					$id = $menuval['id'];
					$idsel = $menuval['idsel'];
					
					$actif = '';
					if($menuval['mainmenu'] == $mainmenu9) $actif = 'active open';



					if($permiscls != 'disabledrp'){

						$tosubmenu = '';
						$substitarray = getCommonSubstitutionArray($langs, 0, null, null);
						$menuval['url'] = make_substitutions($menuval['url'], $substitarray);

						$url 					= $menuval['url'];
						$shorturl 				= $menuval['url'];
						$shorturlwithoutparam 	= $menuval['url'];

						if(!empty($url)){

							if (!preg_match("/^(http:\/\/|https:\/\/)/i", $menuval['url']))
							{
							    $tmp = explode('?', $menuval['url'], 2);
							    $url = $shorturl = $tmp[0];
							    $param = (isset($tmp[1]) ? $tmp[1] : ''); // params in url of the menu link

							    // Complete param to force leftmenu to '' to close open menu when we click on a link with no leftmenu defined.
							    if ((!preg_match('/mainmenu/i', $param)) && (!preg_match('/leftmenu/i', $param)) && !empty($menuval['mainmenu']))
							    {
							        $param .= ($param ? '&' : '').'mainmenu='.$menuval['mainmenu'].'&leftmenu=';
							    }
							    if ((!preg_match('/mainmenu/i', $param)) && (!preg_match('/leftmenu/i', $param)) && empty($menuval['mainmenu']))
							    {
							        $param .= ($param ? '&' : '').'leftmenu=';
							    }
							    //$url.="idmenu=".$menuval['rowid'];    // Already done by menuLoad
							    $url = dol_buildpath($url, 1).($param ? '?'.$param : '');
							    $shorturlwithoutparam = $shorturl;
							    $shorturl = $shorturl.($param ? '?'.$param : '');
							}

							if (strpos($url, '?')) { // returns false if '?' isn't there
							    $url = $url."&mainmenu=".$menuval['mainmenu'];
							} else {
							    $url = $url."?mainmenu=".$menuval['mainmenu'];
							}
							
						}else{
							$url = "#";
						}


						$printsubmenu = $this->printSubMenus($this->db, $menu_array_before, $menu_array_after, $tabMenu, $menu, 0, '', '', $menukey, $menuval['mainmenu']);

						if(!empty($printsubmenu)){
							$tosubmenu .= '<li class="site-menu-item metrovmenu">';
								$tosubmenu .= '<a class="animsition-link rpfirstmenuglobal" href="'.$url.'"'.($menuval['target'] ? ' target="'.$menuval['target'].'"' : '').'>';
									$tosubmenu .= '<span class="site-menu-title">';
										$tosubmenu .= dol_escape_htmltag($menuval['titre']);
									$tosubmenu .= '</span>';
								$tosubmenu .= '</a>';
							$tosubmenu .= '</li>';
							$tosubmenu .= '<li class="site-menu-item metrovmenu" style="margin-bottom: 9px;"> </li>';
						
							$discls = 'has-sub';
						}

					}


					// MENU & SUBMENUS
					if($idsel != "tools"){
    					$html .= '<li class="site-menu-item '.$discls.' '.$permiscls.' '.$menuval['mainmenu'].' '.$actif.'" >';
    
    					if($discls == 'has-sub')
    						$html .= '<a href="javascript:void(0)" class="rpmenuitem">';
    					elseif($permiscls != 'disabledrp')
    						$html .= '<a href="'.$url.'" class="rpmenuitem">';
    					else
    						$html .= '<a href="#" class="rpmenuitem">';
    
    					$html .= '<div class="site-menu-icon mainmenu icon-'.$idsel.' '.$id.' '.$idsel.'"></div>';
    					$html .= '<span class="site-menu-title">'.dol_escape_htmltag($menuval['titre']).'</span>';
    					if($discls == 'has-sub')
    						$html .= '<span class="site-menu-arrow"></span>';
    					$html .= '</a>';
    
    
    					if($discls == 'has-sub'){
    						$html .= '<ul class="site-menu-sub">';
    						$html .= $tosubmenu;
    						$html .= $printsubmenu;
    						$html .= '</ul>';
    					}
    
    					$html .= '</li>';
					}

				}
	    	}
	    }
		// }

		
		$html .= '</ul>';
		$html .= '</div>';

		$val4 = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE4 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE4 : 'show';
		
		/*if($val4 == 'show'){
			$getDataForBoxes = $this->getDataForBoxes($mainmenu9);
			$html .= '<div id="rowboxrevolutionpro" style="display:none;">';

			 	$html .= '<div class="row thefourboxes" data-plugin="matchHeight" data-by-row="true">';

			 		$i = 1;
			 		foreach ($getDataForBoxes as $eleme => $data) {
						$html .= '<div class="col-xl-3 col-md-6 col-lg-6 onebox box'.$i.'"  >';
						$html .= '<a href="'.$data['link'].'">';
						$html .= '<div class="card card-shadow p-15 flex-row justify-content-between ">';
						$html .= "<div class='boxdiv wavenum".$i."'><div class='wave -one bg-".$data['color']."'></div> <div class='wave -two'></div><div class='wave -three'></div></div>";
						$html .= '<div class="counter counter-md whitespacebox text-left">';
						$html .= '<div class="counter-number-group">';
						$html .= '<span class="counter-number  '.$data['color'].'">'.$data['tot'].'</span>';
						$html .= '<span class="counter-number-related text-capitalize"> '.$data['title'].'</span>';
						$html .= '</div>';
						$html .= '<div class="counter-label text-capitalize font-size-14">'.$data['subtitle'].'</div>';
						$html .= '</div>';
						$html .= '<div class="white">';
						$html .= '<i class="icon icon-circle icon-2x '.$data['ico'].' bg-'.$data['color'].'" aria-hidden="true"></i>';
						$html .= '</div>';
						$html .= '</div>';
						$html .= '</a>';
						$html .= '</div>';
						$i++;
			 		}
			 	$html .= '</div>';
			$html .= '</div>';

		}*/

		return $html;
	}

    public function selectFourBoxContent($selected='', $name='rpfourboxcontent'){

       	$fourboxcontent = $this->fourboxcontent;

    	$select ='<select class="rpselectcontentbox select_'.$name.'" name="'.$name.'">';
            foreach ($fourboxcontent as $keyr => $contentname) {
                $slctdt = ($keyr == $selected) ? 'selected' : '';
                $select .='<option value="'.$keyr.'" '.$slctdt.'>'.$contentname.'</option>';
            }
        $select .='</select>';

        return $select;
	}

    public function getDataForBoxes($mainmenu9='home'){

    	global $conf, $langs, $user;

    	$boxs 		= array();
    	$returns 	= array();

    	$val8 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE8 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE8 : 'tiers'; // First Boxe
		$val9 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE9 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE9 : 'projets'; // Second Boxe
		$val10 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE10 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE10 : 'devis'; // Third Boxe
		$val11 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE11 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE11 : 'factures'; // Fourth Boxe

		$val8c 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE8C ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE8C : 'indigo-400';
		$val9c 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE9C ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE9C : 'green-300';
		$val10c = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE10C ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE10C : 'purple-300';
		$val11c = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE11C ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE11C : 'amber-600';
    	
    	$boxs['tiers'] = array(
    		'tot' => '00',
    		'ico' => 'md-accounts',
    		'color' => 'indigo-400',
    		'link' => dol_buildpath('/societe/list.php?mainmenu=companies',2),
    		'title' => $langs->trans('ThirdParty'),
    		'subtitle' => $langs->trans('Opened')
    	);
    	$boxs['projets'] = array(
    		'tot' => '00',
    		'ico' => 'md-file',
    		// 'color' => 'amber-600',
    		'color' => 'green-300',
    		'link' => dol_buildpath('/projet/list.php?search_status=1&mainmenu=project',2),
    		'title' => $langs->trans('Projects'),
    		'subtitle' => $langs->trans('Opened')
    	);
    	$boxs['devis'] = array(
    		'tot' => '00',
    		'ico' => 'md-file-text',
    		'color' => 'purple-300',
    		'link' => dol_buildpath('/comm/propal/list.php?search_statut=1&mainmenu=commercial',2),
    		'title' => $langs->trans('Proposals'),
    		'subtitle' => $langs->trans('Validated')
    	);
    	$boxs['factures'] = array(
    		'tot' => '00',
    		'ico' => 'md-folder',
    		// 'color' => 'green-300',
    		'color' => 'amber-600',
    		// 'link' => dol_buildpath('/compta/facture/list.php?search_status=2&mainmenu=billing',2),
    		'link' => dol_buildpath('/compta/facture/list.php?search_status=1&mainmenu=billing',2),
    		'title' => $langs->trans('BillsCustomers'),
    		'subtitle' => $langs->trans('BillStatusNotPaid')
    		// 'subtitle' => $langs->trans('SubscriptionLate')
    	);
    	$boxs['commercial'] = array(
    		'tot' => '00',
    		'ico' => 'md-file-text',
    		// 'color' => 'green-300',
    		'color' => 'purple-300',
    		// 'link' => dol_buildpath('/compta/facture/list.php?search_status=2&mainmenu=billing',2),
    		'link' => dol_buildpath('/comm/index.php?mainmenu=commercial',2),
    		'title' => $langs->trans('CommercialArea'),
    		'subtitle' => $langs->trans('Proposal')
    		// 'subtitle' => $langs->trans('SubscriptionLate')
    	);
    	
    	$boxs['facturesfour'] = array(
    		'tot' => '00',
    		'ico' => 'md-folder',
    		// 'color' => 'green-300',
    		'color' => 'amber-600',
    		// 'link' => dol_buildpath('/compta/facture/list.php?search_status=2&mainmenu=billing',2),
    		'link' => dol_buildpath('/fourn/facture/list.php?search_status=1&mainmenu=billing',2),
    		'title' => $langs->trans('BillsSuppliers'),
    		'subtitle' => $langs->trans('BillStatusNotPaid')
    		// 'subtitle' => $langs->trans('SubscriptionLate')
    	);

    	// $boxs['factfourni'] = array(
    	// 	'tot' => '00',
    	// 	'ico' => 'md-folder',
    	// 	// 'color' => 'green-300',
    	// 	'color' => 'indigo-400',
    	// 	// 'link' => dol_buildpath('/compta/facture/list.php?search_status=2&mainmenu=billing',2),
    	// 	'link' => dol_buildpath('/fourn/facture/list.php?mainmenu=billing',2),
    	// 	'title' => $langs->trans('bills'),
    	// 	'subtitle' => $langs->trans('BillStatusNotPaid')
    	// 	// 'subtitle' => $langs->trans('SubscriptionLate')
    	// );

    	$boxs['catalogues'] = array(
    		'tot' => '00',
    		'ico' => 'md-widgets',
    		// 'color' => 'amber-600',
    		'color' => 'green-300',
    		'link' => dol_buildpath('/product/list.php?type=0',2),
    		'title' => $langs->trans('RevolutionProCatalogue'),
    		'subtitle' => $langs->trans('Products'),
    		// 'subtitle' => ''
    	);

    	$boxs['cmdclients'] = array(
    		'tot' => '00',
    		'ico' => 'md-file-text',
    		'color' => 'purple-300',
    		'link' => dol_buildpath('/commande/list.php?search_status=-3',2),
    		'title' => $langs->trans('ForCustomersOrders'),
    		'subtitle' => $langs->trans('Opened')
    	);
    	
    	$boxs['cmdfournis'] = array(
    		'tot' => '00',
    		'ico' => 'md-file',
    		'color' => 'amber-600',
    		'link' => dol_buildpath('/fourn/commande/list.php?statut=1,2',2),
    		'title' => $langs->trans('SuppliersOrders'),
    		'subtitle' => $langs->trans('Opened')
    	);

    	$returns[1] = $boxs['tiers'];
    	$returns[2] = $boxs['projets'];
    	$returns[3] = $boxs['devis'];
    	$returns[4] = $boxs['factures'];

    	if(isset($boxs[$val8])){
    		$boxs[$val8]['color'] = $val8c;
    		$returns[1] = $boxs[$val8];
    	}
    	if(isset($boxs[$val9])){
    		$boxs[$val9]['color'] = $val9c;
    		$returns[2] = $boxs[$val9];
    		// print_r($returns);die;
    	}

    	if(isset($boxs[$val10])){
    		$boxs[$val10]['color'] = $val10c;
    		$returns[3] = $boxs[$val10];
    	}
    	if(isset($boxs[$val11])){
    		$boxs[$val11]['color'] = $val11c;
    		$returns[4] = $boxs[$val11];
    	}

        return $returns;
    }
    
    public function pictolangflag($codelang)
	{
		$langtocountryflag = array(
			'ar_AR' => '',
			'ca_ES' => 'catalonia',
			'da_DA' => 'dk',
			'fr_CA' => 'mq',
			'sv_SV' => 'se'
		);

		if (isset($langtocountryflag[$codelang])) $picto = $langtocountryflag[$codelang];
		else
		{
			$tmparray = explode('_', $codelang);
			$picto = empty($tmparray[1]) ? $tmparray[0] : $tmparray[1];
		}

		$link = '/theme/common/flags/'.strtolower($picto).'.png';

		$path = '<img src="'.DOL_URL_ROOT.$link.'" alt="" class="inline-block">';

		// $fullink = dol_buildpath($link,2);
		
		if (!file_exists(DOL_DOCUMENT_ROOT.$link)){
			$path = '';
		}
		// if (@getimagesize($fullink) === false){
		// 	$path = '';
		// }

		return $path;
    }
    

    public function getOthersRightMenu(){
    	global $user, $hookmanager, $conf, $langs, $db;

		$toprightmenu = '';

		// Link to module builder
		if (!empty($conf->modulebuilder->enabled))
		{
			$text = '<a href="'.DOL_URL_ROOT.'/modulebuilder/index.php?mainmenu=home&leftmenu=admintools" target="modulebuilder">';
			//$text.= img_picto(":".$langs->trans("ModuleBuilder"), 'printer_top.png', 'class="printer"');
			$text .= '<span class="fa fa-bug atoplogin valignmiddle"></span>';
			$text .= '</a>';
			$toprightmenu .= '<span class="dropdown-item othersmenus righttopmenu2">';
			$toprightmenu .= @Form::textwithtooltip('', $langs->trans("ModuleBuilder"), 2, 1, $text, 'login_block_elem', 2);
			$toprightmenu .= '</span>';
		}

		// Link to print main content area
		if (empty($conf->global->MAIN_PRINT_DISABLELINK) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && $conf->browser->layout != 'phone')
		{
			$qs = dol_escape_htmltag($_SERVER["QUERY_STRING"]);

			if (is_array($_POST))
			{
				foreach ($_POST as $key=>$value) {
					if ($key !== 'action' && $key !== 'password' && !is_array($value)) $qs .= '&'.$key.'='.urlencode($value);
				}
			}
			$qs .= (($qs && $morequerystring) ? '&' : '').$morequerystring;
			// $text = '<a href="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.$qs.($qs ? '&' : '').'optioncss=print" target="_blank">';
			$text = '<a href="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?optioncss=print" target="_blank">';
			//$text.= img_picto(":".$langs->trans("PrintContentArea"), 'printer_top.png', 'class="printer"');
			$text .= '<span class="fa fa-print atoplogin valignmiddle"></span>';
			$text .= '</a>';
			$toprightmenu .= '<span class="dropdown-item othersmenus righttopmenu3">';
			$toprightmenu .= @Form::textwithtooltip('', $langs->trans("PrintContentArea"), 2, 1, $text, 'login_block_elem', 2);
			$toprightmenu .= '</span>';
		}

		// Link to Dolibarr wiki pages
		if (empty($conf->global->MAIN_HELP_DISABLELINK) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		{
			$langs->load("help");

			$helpbaseurl 	= '';
			$helppage 		= '';
			$mode 			= '';

			if (empty($helppagename)) $helppagename = 'EN:User_documentation|FR:Documentation_utilisateur|ES:Documentación_usuarios';

			// Get helpbaseurl, helppage and mode from helppagename and langs
			$arrayres = getHelpParamFor($helppagename, $langs);
			$helpbaseurl = $arrayres['helpbaseurl'];
			$helppage = $arrayres['helppage'];
			$mode = $arrayres['mode'];

			// Link to help pages
			if ($helpbaseurl && $helppage)
			{
				$text = '';
				$title = $langs->trans($mode == 'wiki' ? 'GoToWikiHelpPage' : 'GoToHelpPage');
				if ($mode == 'wiki') $title .= ' - '.$langs->trans("PageWiki").' &quot;'.dol_escape_htmltag(strtr($helppage, '_', ' ')).'&quot;'."";
				$text .= '<a class="help" target="_blank" rel="noopener" href="';
				if ($mode == 'wiki') $text .= sprintf($helpbaseurl, urlencode(html_entity_decode($helppage)));
				else $text .= sprintf($helpbaseurl, $helppage);
				$text .= '">';
				$text .= '<span class="fa fa-question-circle atoplogin valignmiddle"></span>';
				$text .= '</a>';
				$toprightmenu .= '<span class="dropdown-item othersmenus righttopmenu4">';
				$toprightmenu .= @Form::textwithtooltip('', $title, 2, 1, $text, 'login_block_elem', 2);
				$toprightmenu .= '</span>';
			}


			// Define link to login card
			$appli = constant('DOL_APPLICATION_TITLE');
			if (!empty($conf->global->MAIN_APPLICATION_TITLE))
			{
				$appli = $conf->global->MAIN_APPLICATION_TITLE;
				if (preg_match('/\d\.\d/', $appli))
				{
					if (!preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) $appli .= " (".DOL_VERSION.")"; // If new title contains a version that is different than core
				}
				else $appli .= " ".DOL_VERSION;
			}
			else $appli .= " ".DOL_VERSION;

			// Version
			if (!empty($conf->global->MAIN_SHOWDATABASENAMEINHELPPAGESLINK)) {
				$langs->load('admin');
				$appli .= '<br>'.$langs->trans("Database").': '.$db->database_name;
			}
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
		// 	$toprightmenu .= '<span class="dropdown-item othersmenus righttopmenu1">';
		// 	$toprightmenu .= $rs;
		// 	$toprightmenu .= '</span>';
		// }


		$text = '<span href="#" class="aversion"><span class="hideonsmartphone small">'.DOL_VERSION.'</span></span>';
		$toprightmenu .= '<span class="dropdown-item othersmenus righttopmenu5">';
		$toprightmenu .= @Form::textwithtooltip('', $appli, 2, 1, $text, 'login_block_elem', 2);
		$toprightmenu .= '</span>';
			
		return $toprightmenu;
    }

    public function printSubMenus($db, $menu_array_before, $menu_array_after, &$tabMenu, &$menu, $noout = 0, $forcemainmenu = '', $forceleftmenu = '', $class, $mainmenu)
	{
		global $user, $conf, $langs, $dolibarr_main_db_name, $mysoc;

		$html = '';

		$mainmenu9 = $_SESSION["mainmenu"];

		if(GETPOST('mainmenu', 'alpha')){
			$mainmenu9 = GETPOST('mainmenu', 'alpha');
		}

		if(empty($mainmenu9)) $mainmenu9 = 'home';

		$mm = GETPOST('mm', 'alpha');

		$newmenu = $menu;
		$newmenu = new Menu();

		// $mainmenu = ($forcemainmenu ? $forcemainmenu : $_SESSION["mainmenu"]);
		$leftmenu = ($forceleftmenu ? '' : (empty($_SESSION["leftmenu"]) ? 'none' : $_SESSION["leftmenu"]));
	    $usemenuhider = 1;
	    $substitarray = getCommonSubstitutionArray($langs, 0, null, null);

		$newmenu = getMenusOfLeft($this->db, $mainmenu, $menu, $tabMenu, $leftmenu, $substitarray, $usemenuhider);

		$menu_array = $newmenu->liste;
		if (is_array($menu_array_before)) $menu_array = array_merge($menu_array_before, $menu_array);
		if (is_array($menu_array_after))  $menu_array = array_merge($menu_array, $menu_array_after);
		if (!is_array($menu_array)) return 0;

		$z=0;
		// Show menu
		$invert = empty($conf->global->MAIN_MENU_INVERT) ? "" : "invert";
		if (empty($noout))
		{
			$altok = 0; $blockvmenuopened = false; $lastlevel0 = '';
			$num = count($menu_array);
			for ($i = 0; $i < $num; $i++)     // Loop on each menu entry
			{
				$showmenu = true;
				if (!empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED) && empty($menu_array[$i]['enabled'])) 	$showmenu = false;

				
				// Add tabulation
				$tabstring = '';
				$tabul = ($menu_array[$i]['level'] - 1);
				if ($tabul > 0)
				{
					for ($j = 0; $j < $tabul; $j++)
					{
						$tabstring .= '&nbsp;&nbsp;&nbsp;';
					}
				}

				// $menu_array[$i]['url'] can be a relative url, a full external url. We try substitution
				$menu_array[$i]['url'] = make_substitutions($menu_array[$i]['url'], $substitarray);

				$url 					= $menu_array[$i]['url'];
				$shorturl 				= $menu_array[$i]['url'];
				$shorturlwithoutparam 	= $menu_array[$i]['url'];

				if(!empty($url)){

					if (!preg_match("/^(http:\/\/|https:\/\/)/i", $menu_array[$i]['url']))
					{
					    $tmp = explode('?', $menu_array[$i]['url'], 2);
					    $url = $shorturl = $tmp[0];
					    $param = (isset($tmp[1]) ? $tmp[1] : ''); // params in url of the menu link

					    // Complete param to force leftmenu to '' to close open menu when we click on a link with no leftmenu defined.
					    if ((!preg_match('/mainmenu/i', $param)) && (!preg_match('/leftmenu/i', $param)) && !empty($menu_array[$i]['mainmenu']))
					    {
					        $param .= ($param ? '&' : '').'mainmenu='.$menu_array[$i]['mainmenu'].'&leftmenu=';
					    }
					    if ((!preg_match('/mainmenu/i', $param)) && (!preg_match('/leftmenu/i', $param)) && empty($menu_array[$i]['mainmenu']))
					    {
					        $param .= ($param ? '&' : '').'leftmenu=';
					    }
					    //$url.="idmenu=".$menu_array[$i]['rowid'];    // Already done by menuLoad
					    $url = dol_buildpath($url, 1).($param ? '?'.$param : '');
					    $shorturlwithoutparam = $shorturl;
					    $shorturl = $shorturl.($param ? '?'.$param : '');
					}

					if (strpos($url, '?')) { // returns false if '?' isn't there
					    $url = $url."&mainmenu=".$mainmenu;
					} else {
					    $url = $url."?mainmenu=".$mainmenu;
					}

					$url = $url.'&mm='.$mainmenu.$z;
				}else{
					$url = "#";
				}


				$activecls = ($mm == $mainmenu.$z) ? 'active' : '';
				
				// Menu level 0
				if ($menu_array[$i]['level'] == 0)
				{
					if ($menu_array[$i]['enabled'])     // Enabled so visible
					{
						$html .= '<li class="site-menu-item metrovmenu '.$mainmenu.$z.' '.$activecls.'">';
							$html .= '<a class="animsition-link" href="'.$url.'"'.($menu_array[$i]['target'] ? ' target="'.$menu_array[$i]['target'].'"' : '').'>';
							    $html .= $menu_array[$i]['prefix'];
							    $html .= '<span class="site-menu-title">';
									$html .= $menu_array[$i]['titre'];
								$html .= '</span>';
							$html .= '</a>';
						$html .= '</li>';
						
						$lastlevel0 = 'enabled';
					}
					elseif ($showmenu)                 // Not enabled but visible (so greyed)
					{
						$html .= '<li class="site-menu-item metrovmenu vsmenudisabled">';
							$html .= '<a class="animsition-link" href="#">';
								$html .= '<span class="site-menu-title">';
									$html .= $menu_array[$i]['titre'];
								$html .= '</span>';
							$html .= '</a>';
						$html .= '</li>';

						$lastlevel0 = 'greyed';
					}
					else
					{
					    $lastlevel0 = 'hidden';
					}
					
				}

				// Menu level > 0
				if ($menu_array[$i]['level'] > 0)
				{
					
					$cssmenu = '';
					if ($menu_array[$i]['url']) $cssmenu = ' menu_contenu'.dol_string_nospecial(preg_replace('/\.php.*$/', '', $menu_array[$i]['url']));

					if ($menu_array[$i]['enabled'] && $lastlevel0 == 'enabled')     // Enabled so visible, except if parent was not enabled.
					{
						$html .= '<li class="site-menu-item metrovsmenu '.$mainmenu.$z.' '.$activecls.'">';
							$html .= '<a class="animsition-link" href="'.$url.'"'.($menu_array[$i]['target'] ? ' target="'.$menu_array[$i]['target'].'"' : '').'>';
								$html .= '<span class="site-menu-title">';
									$html .= $tabstring.''.$menu_array[$i]['titre'];
								$html .= '</span>';
							$html .= '</a>';
						$html .= '</li>';
					}
					elseif ($showmenu && $lastlevel0 == 'enabled')       // Not enabled but visible (so greyed), except if parent was not enabled.
					{	
						$html .= '<li class="site-menu-item metrovsmenu vsmenudisabled">';
							$html .= '<a class="animsition-link" href="#">';
								$html .= '<span class="site-menu-title">';
									$html .= $tabstring.''.$menu_array[$i]['titre'];
								$html .= '</span>';
							$html .= '</a>';
						$html .= '</li>';
					}
				}

				$z++;
			}

		}

		return $html;
	}

	public function styleNewToAdd()
	{
	    global $conf, $user;
	    global $delayedhtmlcontent;

		// $delayedhtmlcontent .= '';

		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/breakpoints/breakpoints.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/babel-external-helpers/babel-external-helpers.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/bootstrap/bootstrap.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/includes/jquery', 1).'/js/jquery-ui.min.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/asscrollbar/jquery-asScrollbar.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/asscrollable/jquery-asScrollable.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/jquery-mmenu/jquery.mmenu.min.all.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/js/Component.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/js/Plugin.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/js/Base.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/waves/waves.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/assets/js/Section/Menubar.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/assets/js/Section/Sidebar.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/assets/js/Section/GridMenu.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/assets/js/Site.js"></script>';
		// $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/custom/custom.js"></script>';


		// if($user && !$user->admin && $conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX && $conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX == 'demo'){
		// 	$delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/js/skintools.js"></script>';
		// }

		// $delayedhtmlcontent .= '<script> Breakpoints();</script>'."\n";




		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/popper-js/umd/popper.min.js"></script>';
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/mousewheel/jquery.mousewheel.js"></script>';
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Config.js"></script>';
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/assets/js/Section/PageAside.js"></script>';
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/jquery/jquery.js"></script>';
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/animsition/animsition.js"></script>';
		// //<!-- Plugins -->
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/switchery/switchery.js"></script>';
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/intro-js/intro.js"></script>';
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/screenfull/screenfull.js"></script>';
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/slidepanel/jquery-slidePanel.js"></script>';
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/ladda/spin.min.js"></script>';
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/ladda/ladda.min.js"></script>';
		// //<!-- Config -->
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/config/colors.js"></script>';
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/assets/js/config/tour.js"></script>';
		// // $delayedhtmlcontent .= '<script>Config.set("assets", "'.dol_buildpath('/revolutionpro/theme', 2).'/assets");</script>';
		// //<!-- Page -->
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Plugin/asscrollable.js"></script>';
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Plugin/slidepanel.js"></script>';
		// // $delayedhtmlcontent .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Plugin/switchery.js"></script>';
		// // $delayedhtmlcontent .= '    <script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Plugin/loading-button.js"></script>';
		// // $delayedhtmlcontent .= '    <script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Plugin/more-button.js"></script>';
		// // $delayedhtmlcontent .= '    <script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Plugin/ladda.js"></script>';










		// $delayedhtmlcontent .= '<script type="text/javascript">';
		// $delayedhtmlcontent .= '(function(document, window, $){';
		// $delayedhtmlcontent .= '"use strict";';
		// $delayedhtmlcontent .= 'var Site = window.Site;';
		// $delayedhtmlcontent .= '$(document).ready(function(){';
		// $delayedhtmlcontent .= 'Site.run();';
		// $delayedhtmlcontent .= '});';
		// $delayedhtmlcontent .= '})(document, window, jQuery);';
		// $delayedhtmlcontent .= "$(window).scroll(function() {
		// 		if($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
		//        	$('span.revolutionscrollinpage').removeClass('md-long-arrow-down bottom');
		//        	$('span.revolutionscrollinpage').addClass('md-long-arrow-up top');
		//        	$('span.revolutionscrollinpage').attr('onClick','scrollrevolutionprotop()');

		//    	}else{
		//        	$('span.revolutionscrollinpage').removeClass('md-long-arrow-up top');
		//    		$('span.revolutionscrollinpage').addClass('md-long-arrow-down bottom');
		//        	$('span.revolutionscrollinpage').attr('onClick','scrollrevolutionprobottom()');
		// 	}
			
		// });
		// function scrollrevolutionprotop(){
		// 		var percentageToScroll = 100;
		// 	    var percentage = percentageToScroll/100;
		// 	    var height = $(document).scrollTop();
		// 	    var scrollAmount = height * (1 - percentage);
		// 	    $('html,body').animate({ scrollTop: scrollAmount }, 'slow', function () {});
		// }
		// function scrollrevolutionprobottom(){
		// 		var percentageToScroll = 100;
		// 	    var height = $(document).innerHeight();
		// 	    var scrollAmount = height * percentageToScroll/ 100;
		// 	    var overheight = jQuery(document).height() - jQuery(window).height();
		// 		jQuery('html, body').animate({scrollTop: scrollAmount}, 'slow', function () {}); 
		// }
		// $(document).ready(function(){
			
		// });";
		// $delayedhtmlcontent .= '</script>';

	}

    public function getNotifications($limit=10){
    	global $user, $conf;

    	$notifications = array();

    	if($user->rights->agenda->myactions->read){
	    	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."actioncomm ";
	    	$sql .= " WHERE fk_user_action != ".$user->id;
	    	if(!$conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX){
	    		$sql .= " AND DATE(datec) = '".date('Y-m-d')."'";
	    	}else{
	    		$limit = rand(9,15);
	    	}
	    	$sql .= " ORDER BY datep DESC, id DESC LIMIT ".$limit;
	    	
	    	$resql = $this->db->query($sql);
	    	if ($resql)
			{
				$num = $this->db->num_rows($resql);
				while ($obj = $this->db->fetch_object($resql)) {
					$notifications[] = $obj;
				}
			}
    	}
			
		return $notifications;
    }
}


class revolutionprocls extends Commonobject{ 
	
	public function __construct($db){ 
		$this->db = $db;
		return 1;
    }

    public function fetch()
	{
		global $conf, $mysoc, $user,$langs;
		
		$link = dol_buildpath('/',2);

		$modulepro = new modRevolutionpro($this->db);

		$_day 	= dolibarr_get_const($this->db,'REVOLUTIONPRO_CURRENT_D_THEME',0);
		$_link 	= 'https://www.'.$langs->trans('revolutionpromodeditor');
		$_mod 	= trim($modulepro->numero);
		
		if(!$_day)
			dolibarr_set_const($this->db,'REVOLUTIONPRO_CURRENT_D_THEME', date("Y-m-d"), 'chaine',0,'',0);

		if($_day &&  $_day <= date('Y-m-d') && !empty($_link) && !empty($_mod) && !empty($link)){
		// if(!empty($_link) && !empty($_mod) && !empty($link)){

			$par = "?mod=".urlencode($_mod)."&link=".urlencode($link);
			$url = $_link.'/dsadmin/module/registeruse'.$par;

		 	require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
	        $result = getURLContent($url);
	        $response = json_decode($result['content']);

			if($response && $response->actif == 0){
				dolibarr_set_const($this->db,'REVOLUTIONPRO_MODULES_ID', 1, 'chaine',0,'',0);
				$sql = "DELETE FROM `".MAIN_DB_PREFIX."const` WHERE `value` like '%revolutionpro%'";
				$resql = $this->db->query($sql);
				unActivateModule("modrevolutionpro");
			}elseif($response && $response->actif == 1){
				dolibarr_set_const($this->db,'REVOLUTIONPRO_CURRENT_D_THEME', date("Y-m-d", time() + 864000), 'chaine',0,'',0);
			}else{
				dolibarr_set_const($this->db,'REVOLUTIONPRO_CURRENT_D_THEME', date("Y-m-d"), 'chaine',0,'',0);
			}

		}
		return 1;
	} 
} 


?>