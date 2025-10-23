<?php
if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))         define('NOLOGIN', 1); // File must be accessed by logon page so without login
if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', 1);
define('ISLOADEDBYSTEELSHEET', '1');
session_cache_limiter('public');

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

global $langs,$db,$mysoc;

// Define css type
top_httphead('text/javascript; charset=UTF-8');

?>
$(document).ready(function(){
	if($('.info-box-module-external').length >0){
		var arrmodl=['ajaxlivesearch','bluetheme','extranetdolibarr','moderntheme','monitoring','orangetheme','payrollmod','personalizdoli','pointagemod','reservsalle','revolutionpro','taskgantt'];
		var url = '<?php echo dol_escape_js(dol_buildpath("",1)); ?>';
		$.each(arrmodl, function(index, value) {
			var img = $('.info-box-module-external .info-box-module .info-box-icon img[src*="/'+value+'/"]');
			if(!img.hasClass('ds_imgmodl')){
				img.wrap('<a class="ds_image_module_logo" target="_blank" href="https://www.dolibarrstore.com"></a>');
				img.addClass('ds_imgmodl');
			}
		});
	}
});
<?php

// // Session FOR Menu
// $mainmenu9 = $_SESSION["mainmenu"];
// .site-menu>.site-menu-item.


global $conf,$langs;

// // Session FOR Menu
// if(!isset($_SESSION["dol_login"])){
// 	unset($_SESSION['revolutionproleftmenu']);
// }


?>
$(window).on('load',function(){
	setTimeout( function() { 
		if(!$('.site-menubar .mm-panel.mm-highest.mm-opened').is(':visible')){
			$('.site-menu-item.has-sub.active a').click(); 
		}
	}, 1600);
	$('#loader-overlay').hide();
});

<?php


if(isset($_SESSION["dol_login"])) die;

$ardolv = DOL_VERSION;
$ardolv = explode(".", $ardolv);
$dolvs = $ardolv[0];

if ( $dolvs > 9 ) die;


dol_include_once('/revolutionpro/class/revolutionpro.class.php');
$revolutionpro = new revolutionpro($db);
$urllogo = $revolutionpro->getLogoCompanyUrl();

// $pagecnx = $langs->trans('Hello')."!";
$pagecnx = " ";
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

$pagecnx = dol_escape_htmltag($pagecnx);

global $mysoc;
$societename = 'Dolibarr Store';
if(!empty($mysoc->name))
	$societename = $mysoc->name;
elseif(!empty($conf->global->MAIN_INFO_SOCIETE_NOM))
	$societename = dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM);

$htmllogin = '<div class="page" id="pagelogindivcontent" style="display:none;" data-animsition-in="fade-in" data-animsition-out="fade-out"><div class="page-content"><div class="page-brand-info"><div class="brand"><img class="brand-img" style="height:26px;" src="'.dol_escape_js($urllogo).'" alt="..."><h2 class="brand-text font-size-40">'.dol_escape_js($societename).'</h2></div><p class="font-size-20">'.dol_escape_js($pagecnx).'</p></div><div class="page-login-main form-material" id="logindivcontent"></div></div></div>';



?>
$(window).on('load',function(){
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
	?>

});
<?php
?>
$(document).ready(function(){
	
	var foldorunfold = 'unfold';
	var ds_rp_menu = window.localStorage.getItem("ds_rp_menu");
	if(ds_rp_menu){
		foldorunfold = ds_rp_menu;
	}
	$('body').addClass('animsition site-navbar-small dashboard site-menubar-'+foldorunfold);
	$('html').addClass('css-menubar');
	if($('body').hasClass('bodylogin')){
		var txtlogin = $('.bodylogin div.login_center').html();
		if(txtlogin !== ''){
			$('.bodylogin div.login_center').hide();
			$('.bodylogin').prepend('<?php echo dol_escape_js($htmllogin); ?>');
			$('.bodylogin #pagelogindivcontent #logindivcontent').html(txtlogin);
			$('.bodylogin').addClass('page-login-v2 page-dark layout-full');
			$('.bodylogin #pagelogindivcontent').show();
			$('.bodylogin div.login_center').remove();
		}
	}
	$('body .site-menu>.site-menu-item>a ').on('click', function(e) {
	     $(this).parent('li').find('a.mm-next').click();
	});
});