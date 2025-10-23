<?php
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/revolutionpro/class/revolutionpro.class.php');

class ActionsREVOLUTIONPRO
{

	public $resprints;

	public function __construct($db)
	{
		$this->db = $db;
	}

	function doActions($parameters, &$object, &$action, $hookmanager){
		// global $db, $form;
		// $form = new Form($db);
		// // Session FOR Menu
		// if($action == 'reset' || $action == 'set' || $action == 'delrights' || $action == 'addrights'){
		// 	$sqls = "UPDATE `".MAIN_DB_PREFIX."user_extrafields` SET `revolutionpromenu` = '0';";
		// 	$resql = $db->query($sqls);
		// }

		return 0;
	}

	function setHtmlTitle($parameters=false)
	{
		global $hookmanager, $conf, $form, $db;
		// $form = new Form($db);
		if (!isset($form) || !is_object($form)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form = new Form($db);
		}
		// $hookmanager->resPrint = $parameters['title'];

		// $conf->dol_hide_leftmenu = 1;
		$conf->dol_hide_topmenu = 0;

		return 0;
	}

	function printTopRightMenu($parameters, $object, $action)
	{
		return 0;
	}

	function addHtmlHeader($parameters, $object, $action)
	{
		global $conf, $langs, $db, $user, $mysoc, $form;
		global $mainmenu;
		$langs->load("revolutionpro@revolutionpro");

		$mainmenu2 = $mainmenu;

		?>
		<script type="text/javascript">
			$(document).ready(function(){
				var foldorunfold = 'unfold';
				var ds_rp_menu = window.localStorage.getItem("ds_rp_menu");
				if(ds_rp_menu){
					foldorunfold = ds_rp_menu;
				}
	            $('body').addClass('animsition site-navbar-small dashboard site-menubar-'+foldorunfold);
	            $('html').addClass('css-menubar');
        	});
		</script>
		<?php
		$revolutionpro = new revolutionpro($db);
		$revolutionpro->getStyleloadRevolutionPro();

		// $conf->dol_hide_leftmenu = 1;
		$conf->dol_hide_topmenu = 0;

		return 0;
	}

	/**
	 * printCommonFooter
	 *
	 * @param array 		$parameters		array of parameters
	 * @param Object	 	$object			Object
	 * @param string		$action			Actions
	 * @param HookManager	$hookmanager	Hook manager
	 * @return int
	 */
	public function printCommonFooter($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;
		
		$jsscript = '';

		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/breakpoints/breakpoints.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/babel-external-helpers/babel-external-helpers.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/bootstrap/bootstrap.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/includes/jquery', 1).'/js/jquery-ui.min.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/asscrollbar/jquery-asScrollbar.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/asscrollable/jquery-asScrollable.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/jquery-mmenu/jquery.mmenu.min.all.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/js/Component.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/js/Plugin.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/js/Base.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/vendor/waves/waves.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/assets/js/Section/Menubar.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/assets/js/Section/Sidebar.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/assets/js/Section/GridMenu.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/assets/js/Site.js"></script>';
		$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/custom/custom.js"></script>';


		if($user && !$user->admin && $conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX && $conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX == 'demo'){
			$jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 1).'/global/js/skintools.js"></script>';
		}

		$jsscript .= '<script> Breakpoints();</script>'."\n";




		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/popper-js/umd/popper.min.js"></script>';
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/mousewheel/jquery.mousewheel.js"></script>';
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Config.js"></script>';
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/assets/js/Section/PageAside.js"></script>';
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/jquery/jquery.js"></script>';
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/animsition/animsition.js"></script>';
		//<!-- Plugins -->
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/switchery/switchery.js"></script>';
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/intro-js/intro.js"></script>';
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/screenfull/screenfull.js"></script>';
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/slidepanel/jquery-slidePanel.js"></script>';
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/ladda/spin.min.js"></script>';
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/vendor/ladda/ladda.min.js"></script>';
		//<!-- Config -->
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/config/colors.js"></script>';
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/assets/js/config/tour.js"></script>';
		// $jsscript .= '<script>Config.set("assets", "'.dol_buildpath('/revolutionpro/theme', 2).'/assets");</script>';
		//<!-- Page -->
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Plugin/asscrollable.js"></script>';
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Plugin/slidepanel.js"></script>';
		// $jsscript .= '<script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Plugin/switchery.js"></script>';
		// $jsscript .= '    <script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Plugin/loading-button.js"></script>';
		// $jsscript .= '    <script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Plugin/more-button.js"></script>';
		// $jsscript .= '    <script src="'.dol_buildpath('/revolutionpro/theme', 2).'/global/js/Plugin/ladda.js"></script>';



		$jsscript .= '<script type="text/javascript">';
		$jsscript .= '(function(document, window, $){';
		$jsscript .= '"use strict";';
		$jsscript .= 'var Site = window.Site;';
		$jsscript .= '$(document).ready(function(){';
		$jsscript .= 'Site.run();';
		$jsscript .= '});';
		$jsscript .= '})(document, window, jQuery);';
		$jsscript .= "$(window).scroll(function() {
				if($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
		       	$('span.revolutionscrollinpage').removeClass('md-long-arrow-down bottom');
		       	$('span.revolutionscrollinpage').addClass('md-long-arrow-up top');
		       	$('span.revolutionscrollinpage').attr('onClick','scrollrevolutionprotop()');

		   	}else{
		       	$('span.revolutionscrollinpage').removeClass('md-long-arrow-up top');
		   		$('span.revolutionscrollinpage').addClass('md-long-arrow-down bottom');
		       	$('span.revolutionscrollinpage').attr('onClick','scrollrevolutionprobottom()');
			}
			
		});
		function scrollrevolutionprotop(){
				var percentageToScroll = 100;
			    var percentage = percentageToScroll/100;
			    var height = $(document).scrollTop();
			    var scrollAmount = height * (1 - percentage);
			    $('html,body').animate({ scrollTop: scrollAmount }, 'slow', function () {});
		}
		function scrollrevolutionprobottom(){
				var percentageToScroll = 100;
			    var height = $(document).innerHeight();
			    var scrollAmount = height * percentageToScroll/ 100;
			    var overheight = jQuery(document).height() - jQuery(window).height();
				jQuery('html, body').animate({scrollTop: scrollAmount}, 'slow', function () {}); 
		}
		$(document).ready(function(){
			
		});";
		$jsscript .= '</script>';

		print $jsscript;

		// $hookmanager->resprint = $jsscript;
		
		return 0;

	}
}

?>