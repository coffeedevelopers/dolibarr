<?php
if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))         define('NOLOGIN', 1); // File must be accessed by logon page so without login
if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');
define('ISLOADEDBYSTEELSHEET', '1');
session_cache_limiter('public');

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

global $langs;
// Define css type
top_httphead('text/css');

// ini_set('display_startup_errors', 1);
// ini_set('display_errors', 1);
// error_reporting(-1);

$ardolv = DOL_VERSION;
$ardolv = explode(".", $ardolv);
$dolvs = $ardolv[0];

// Add here more div for other menu entries. moduletomainmenu=array('module name'=>'name of class for div')

$moduletomainmenu = array(
    'user'=>'', 'syslog'=>'', 'societe'=>'companies', 'projet'=>'project', 'propale'=>'commercial', 'commande'=>'commercial',
    'produit'=>'products', 'service'=>'products', 'stock'=>'products',
    'don'=>'accountancy', 'tax'=>'accountancy', 'banque'=>'accountancy', 'facture'=>'accountancy', 'compta'=>'accountancy', 'accounting'=>'accountancy', 'adherent'=>'members', 'import'=>'tools', 'export'=>'tools', 'mailing'=>'tools',
    'contrat'=>'commercial', 'ficheinter'=>'commercial', 'ticket'=>'ticket', 'deplacement'=>'commercial',
    'fournisseur'=>'companies',
    'barcode'=>'', 'fckeditor'=>'', 'categorie'=>'',
);
$mainmenuused = 'home';
foreach ($conf->modules as $val)
{
    $mainmenuused .= ','.(isset($moduletomainmenu[$val]) ? $moduletomainmenu[$val] : $val);
}
$mainmenuusedarray = array_unique(explode(',', $mainmenuused));


if(isset($_GET['actif']) && $_GET['actif'] == 1){
    dol_include_once('/revolutionpro/core/modules/modRevolutionpro.class.php');
    require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
    dolibarr_set_const($db,'REVOLUTIONPRO_MODULES_ID', 0, 'chaine',0,'',0);
    ActivateModule("modrevolutionpro");
    print 'body .modrevolutionpro .activationmod span {'."\n";
    print '  opacity:1;'."\n";
    print '}'."\n"."\n";
    print '/*--- Actif ---*/'."\n"."\n";
}

$generic = 1;
// Put here list of menu entries when the div.mainmenu.menuentry was previously defined
$divalreadydefined = array('home', 'companies', 'products', 'mrp', 'commercial', 'externalsite', 'accountancy', 'project', 'tools', 'members', 'agenda', 'ftp', 'holiday', 'hrm', 'bookmark', 'cashdesk', 'takepos', 'ecm', 'geoipmaxmind', 'gravatar', 'clicktodial', 'paypal', 'stripe', 'webservices', 'website');
// Put here list of menu entries we are sure we don't want
$divnotrequired = array('multicurrency', 'salaries', 'ticket', 'margin', 'opensurvey', 'paybox', 'expensereport', 'incoterm', 'prelevement', 'propal', 'workflow', 'notification', 'supplier_proposal', 'cron', 'product', 'productbatch', 'expedition');
foreach ($mainmenuusedarray as $val)
{
    if (empty($val) || in_array($val, $divalreadydefined)) continue;
    if (in_array($val, $divnotrequired)) continue;
    //print "XXX".$val;

    // Search img file in module dir
    $found = 0; $url = '';
    foreach ($conf->file->dol_document_root as $dirroot)
    {
        if (file_exists($dirroot."/".$val."/img/object_".$val."_over.png"))
        {
            $url = dol_buildpath('/'.$val.'/img/object_'.$val.'_over.png', 1);
            $found = 1;
            break;
        }
		elseif (file_exists($dirroot."/".$val."/img/object_".$val.".png"))    // Retro compatibilité
		{
			$url = dol_buildpath('/'.$val.'/img/object_'.$val.'.png', 1);
			$found = 1;
			break;
		}
        elseif (file_exists($dirroot."/".$val."/img/".$val.".png"))    // Retro compatibilité
        {
            $url = dol_buildpath('/'.$val.'/img/'.$val.'.png', 1);
            $found = 1;
            break;
        }
    }


    // Img file not found

    if (!$found)
    {
        if (!defined('DISABLE_FONT_AWSOME')) {
            print "/* A mainmenu entry was found but img file ".$val.".png not found (check /".$val."/img/".$val.".png), so we use a generic one */\n";
            print 'body .site-menu-icon.mainmenu.'.$val.':before {
                content: "\f249";
            }';
        }
        else
        {
            print "/* A mainmenu entry was found but img file ".$val.".png not found (check /".$val."/img/".$val.".png), so we use a generic one */\n";
            $url = dol_buildpath($path.'/theme/eldy/img/menus/generic'.(min($generic, 4)).".png", 1);
            print "body .site-menu-icon.mainmenu.".$val." {\n";
            print "	background-image: url(".$url.") !important;\n";
				print ' height: 15px;background-size: 15px;filter: gray; -webkit-filter: grayscale(1); filter: grayscale(1);';      
            print "}\n";

            print "body .site-menubar-dark .site-menu-icon.mainmenu.".$val." {\n";
            print 'filter: brightness(0) invert(1);'."\n";
            print 'opacity: 0.5;'."\n";
            print "}\n";

            print "body .site-menu-icon.mainmenu.".$val.":before{\n";
            print "display:none !important;";
            print "}\n";
        }
        $generic++;
    }
    else
    {
        print "body .site-menu-icon.mainmenu.".$val." {\n";
        print "	background-image: url(".$url.") !important;\n";
		print ' height: 15px;background-size: 15px;filter: gray; -webkit-filter: grayscale(1); filter: grayscale(1);';
        print "}\n";
        print "body .site-menubar-dark .site-menu-icon.mainmenu.".$val." {\n";
        print 'filter: brightness(0) invert(1);'."\n";
        print 'opacity: 0.5;'."\n";
        print "}\n";
        print "body .site-menu-icon.mainmenu.".$val.":before{\n";
        print "display:none !important;";
        print "}\n";
    }
}

global $conf;

$val3 = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE3 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE3 : 'teal';
$val6 = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE6 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE6 : 'primary';

$colorsarr['primary']  = '3f51b5';
$colorsarr['blue']     = '1e88e5';
$colorsarr['brown']    = '6d4c41';
$colorsarr['cyan']     = '00acc1';
$colorsarr['green']    = '43a047';
$colorsarr['grey']     = '757575';
$colorsarr['orange']   = 'fb8c00';
$colorsarr['pink']     = 'd81b60';
$colorsarr['purple']   = '8e24aa';
$colorsarr['red']      = 'e53935';
$colorsarr['teal']     = '00897b';
$colorsarr['yellow']   = 'f9a825';

$globcol = $colorsarr['primary'];
if(isset($colorsarr[$val3])){
    $globcol = $colorsarr[$val3];
}
$butglobcol = $colorsarr['green'];
if(isset($colorsarr[$val6])){
    $butglobcol = $colorsarr[$val6];
}



$val5 = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE5 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE5 : 'revolutionprologin2.jpg';
// $arr = explode(".", $val5);
// if(empty($val5) || count($arr) <= 1) $val5 = 'revolutionprologin1.jpg';

$urlf = dol_buildpath('revolutionpro/img/login/revolutionprologin1.jpg',1); $def = $urlf;
if($val5 == 'revolutionprologin1.jpg' || $val5 == 'revolutionprologin2.jpg'){
    $urlf = dol_buildpath('revolutionpro/img/login/'.$val5,1);
}else{
    $modulepart = 'mycompany';
    $documenturl = DOL_URL_ROOT.'/viewimage.php';
    if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP; // To use another wrapper
    $relativepath = '/logos/login/1/'.$val5; // Cas general
    $urlf = $documenturl.'?modulepart='.$modulepart.'&file='.urlencode($relativepath);
    $origf = $conf->mycompany->dir_output.'/'.$relativepath;
    if (!dol_is_file($origf)){
        $urlf = $def;
    }
}

?>
.site-navbar{background-color:#<?php echo trim($globcol); ?>}


.bodylogin.page-login-v2 .page-login-main{
    background-image: url(<?php echo dol_buildpath('/revolutionpro/img/login/revolutionprologindesign.png', 1); ?>) !important;
}
.bodylogin.page-login-v2:before {
    background-image: url(<?php echo $urlf; ?>) !important;
}
body .liste_titre .badge:not(.nochangebackground) {
    background-color: #<?php echo trim($globcol); ?>;
}
.badge-secondary, .tabs .badge {
    background-color: #<?php echo trim($globcol); ?>d4;
}
body .tabactive, body a.tab#active{
    border-top: 2px solid #<?php echo trim($globcol); ?> !important;
}
body .ui-widget-header {
    border: 1px solid #<?php echo trim($globcol); ?>;
    background: #<?php echo trim($globcol); ?>;
}
@media (max-width: 767.98px){
    .site-navbar.navbar-inverse .navbar-container{
        background-color: #<?php echo trim($globcol); ?> !important;
    }   
}

body div.liste_titre_bydiv, 
body .mc-dropdown-menu > .mc-header,
body .updf-dropdown-menu > .updf-header,
body .liste_titre div.tagtr, 
body tr.liste_titre, 
body tr.liste_titre_sel, 
body .tagtr.liste_titre, 
body .tagtr.liste_titre_sel, 
body form.liste_titre, 
body form.liste_titre_sel, 
body table.dataTable thead tr
{
    background: #<?php echo trim($globcol); ?>d9 !important;
}
body .navbar-inverse .navbar-collapse,body .navbar-inverse .navbar-form {
    border-color: #<?php echo trim($globcol); ?>;
}
body .loader-overlay {
    background: #<?php echo trim($globcol); ?>;
}
body .liste_titre_filter{
    background:#<?php echo trim($globcol); ?>61 !important
}

body .thefourboxes .card:hover {
    background-color: #<?php echo trim($globcol); ?>b5;
}
.butAction, #mainbody input.button:not(.buttongen):not(.bordertransp) 
,body.bodylogin .login_table input[type="submit"] 
{
    background: #<?php echo trim($butglobcol); ?>  !important;
    background-color: #<?php echo trim($butglobcol); ?>  !important;
    border-color: #<?php echo trim($butglobcol); ?>  !important;
    margin-bottom: 15px !important;
}


<?php
if($dolvs >= 12){
?>
    /*body span.widthpictotitle.pictotitle{ background:#<?php echo trim($globcol); ?>b5 !important }*/
<?php
}

?>
body span.widthpictotitle.pictotitle {
    background:transparent !important;
    color: #bbb !important;
    /*margin-left: 20px;*/
}

<?php if (GETPOST('optioncss', 'aZ09') == 'print') {  ?>
#mainbody .page{
   margin-left: 0;
}
#mainbody .site-footer{
    display:none;
}
<?php } ?>

body .info-box-text-module .info-box-desc .ds_url_module_desc{
    /*opacity: 1 !important;
    color: #A9AFB5 !important;*/
}
body .info-box-text-module .info-box-title .ds_url_module_name 
{
    text-transform: uppercase;
    text-decoration: none !important;
    font-weight: normal;
    margin-bottom: 3px;
    color: #000;
    cursor: default;
}
body .info-box-module .info-box-icon a.ds_image_module_logo {
    display: inline-block;
    width: 100%;
    height: 100%;
    cursor: default;
}
body .info-box-module .info-box-icon .ds_image_module_logo img {
    max-width: 60%;
}
body .info-box-content .info-box-desc .ds_url_module_desc
{
    text-decoration: none !important;
    color: #A9AFB5;
    cursor: default;
}
body table[summary="list_of_modules"] .ds_url_module_desc
{
    text-decoration: none !important;
    color: #202020;
    cursor: default;
}
body table[summary="list_of_modules"] .ds_url_module_name
{
    text-decoration: none !important;
    color: #202020;
    cursor: default;
}


:root {
    --colorbackhmenu1: rgb(38,60,92);
    --colorbackvmenu1: rgb(250,250,250);
    --colorbacktitle1: rgb(233,234,237);
    --colorbacktabcard1: rgb(255,255,255);
    --colorbacktabactive: rgb(234,234,234);
    --colorbacklineimpair1: rgb(255,255,255);
    --colorbacklineimpair2: rgb(255,255,255);
    --colorbacklinepair1: rgb(251,251,251);
    --colorbacklinepair2: rgb(251,251,251);
    --colorbacklinepairhover: rgb(230,237,244);
    --colorbacklinepairchecked: rgb(230,237,244);
    --colorbacklinebreak: rgb(248,247,244);
    --colorbackbody: rgb(255,255,255);
    --colortexttitlenotab: #424242;
    --colortexttitlenotab2: rgb(100,0,100);
    --colortexttitle: rgb(0,0,0);
    --colortext: rgb(0,0,0);
    --colortextlink: rgb(10, 20, 100);
    --colortextbackhmenu: #FFFFFF;
    --colortextbackvmenu: #000000;
    --listetotal: #888888;
    --inputbackgroundcolor: #FFF;
    --inputbordercolor: rgba(0,0,0,.2);
    --tooltipbgcolor: rgba(255, 255, 255, 0.96);
    --tooltipfontcolor : #333;
    --oddevencolor: #202020;
    --colorboxstatsborder: #e0e0e0;
    --dolgraphbg: rgba(255,255,255,0);
    --fieldrequiredcolor: #000055;
    --colortextbacktab: #000000;
    --colorboxiconbg: #eee;
    --refidnocolor:#444;
    --tableforfieldcolor:#666;
    --amountremaintopaycolor:#880000;
    --amountpaymentcomplete:#008800;
    --amountremaintopaybackcolor:none;
}

<?php

$valcss = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUECSS ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUECSS : '';
if($valcss){
    print ($valcss);
}