<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/setup.php
 * 	\ingroup	revolutionpro
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

dol_include_once('/revolutionpro/class/revolutionpro.class.php');
dol_include_once('/revolutionpro/lib/revolutionpro.lib.php');

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
// Translations
$langs->load("admin");
$langs->load("revolutionpro@revolutionpro");



/*
 * Actions
 */
$mesg="";
$action = GETPOST('action', 'aZ09');
$sendit = GETPOST('sendit', 'alpha');

if(!$conf->revolutionpro->enabled){
	accessforbidden();
}
if(!empty($action) || !empty($sendit)){
	if (!$user->admin) accessforbidden();
}

if (!$user->admin && $conf->global->REVOLUTIONPRO_PARAMETRES_VALUEX != 'demo') accessforbidden();

// Default Values
$val1 = "light";
$val2 = "inverse";
$val3 = "teal";
$val6 = "primary";
$val4 = "show";
$val7 = "show";
$val5 = 'revolutionprologin1.jpg';

// Get Values
$val1 	= GETPOST('value1','alpha') ? GETPOST('value1','alpha') : $val1; // Sidebar Skins
$val2 	= GETPOST('value2','alpha') ? GETPOST('value2','alpha') : $val2; // Navbar Type
$val3 	= GETPOST('value3','alpha') ? GETPOST('value3','alpha') : $val3; // Navbar Skins
$val6 	= GETPOST('value6','alpha') ? GETPOST('value6','alpha') : $val6; // Buttons Color
$val4 	= GETPOST('value4','alpha') ? GETPOST('value4','alpha') : $val4; // 4 Boxes
$val7 	= GETPOST('value7','alpha') ? GETPOST('value7','alpha') : $val7; // Company Name
$val5 	= GETPOST('value5','alpha') ? GETPOST('value5','alpha') : $val5; // Login Image

$val8 	= GETPOST('value8','alpha') ? GETPOST('value8','alpha') : 'tiers'; // First Box
$val9 	= GETPOST('value9','alpha') ? GETPOST('value9','alpha') : 'projets'; // Second Box
$val10 	= GETPOST('value10','alpha') ? GETPOST('value10','alpha') : 'devis'; // Third Box
$val11 	= GETPOST('value11','alpha') ? GETPOST('value11','alpha') : 'factures'; // Fourth Box

$val8c 	= GETPOST('value8c','alpha') ? GETPOST('value8c','alpha') : 'indigo-400'; // Color First Box
$val9c 	= GETPOST('value9c','alpha') ? GETPOST('value9c','alpha') : 'green-300'; // Color Second Box
$val10c = GETPOST('value10c','alpha') ? GETPOST('value10c','alpha') : 'purple-300'; // Color Third Box
$val11c = GETPOST('value11c','alpha') ? GETPOST('value11c','alpha') : 'amber-600'; // Color Fourth Box

$valcss = '';

// if($val5 < 1 || $val5 > 5) $val5 = 1;
$arr = explode(".", $val5);
if(empty($val5) || count($arr) <= 1) $val5 = 'revolutionprologin1.jpg';


if ($action == 'update') { 
	$val2 = GETPOST('value2','alpha') ? GETPOST('value2','alpha') : 'notinverse'; // Navbar Type
   	dolibarr_set_const($db, "MAIN_HOME", dol_htmlcleanlastbr(GETPOST("main_home", 'none')), 'chaine', 0, '', $conf->entity);

   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUECSS", dol_htmlcleanlastbr(GETPOST("valuecss", 'none')), 'chaine', 0, '', $conf->entity);
} 

if ($action == 'update' || $action == 'defaultparameters') { 
 	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE1", $val1,'chaine',0,'',$conf->entity);
   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE2", $val2,'chaine',0,'',$conf->entity);
   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE3", $val3,'chaine',0,'',$conf->entity);
   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE6", $val6,'chaine',0,'',$conf->entity);
   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE4", $val4,'chaine',0,'',$conf->entity);
   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE7", $val7,'chaine',0,'',$conf->entity);
   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE5", $val5,'chaine',0,'',$conf->entity);

   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE8", $val8,'chaine',0,'',$conf->entity);
   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE9", $val9,'chaine',0,'',$conf->entity);
   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE10", $val10,'chaine',0,'',$conf->entity);
   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE11", $val11,'chaine',0,'',$conf->entity);

   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE8C", $val8c,'chaine',0,'',$conf->entity);
   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE9C", $val9c,'chaine',0,'',$conf->entity);
   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE10C", $val10c,'chaine',0,'',$conf->entity);
   	dolibarr_set_const($db, "REVOLUTIONPRO_PARAMETRES_VALUE11C", $val11c,'chaine',0,'',$conf->entity);
} 



// Get settings
$val1 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE1 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE1 : $val1;
$val2 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE2 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE2 : $val2;
$val3 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE3 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE3 : $val3;
$val6 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE6 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE6 : $val6;
$val4 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE4 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE4 : $val4;
$val7 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE7 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE7 : $val7;
$val5 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE5 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE5 : $val5;

$val8 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE8 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE8 : $val8;
$val9 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE9 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE9 : $val9;
$val10 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE10 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE10 : $val10;
$val11 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE11 ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE11 : $val11;

$val8c 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE8C ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE8C : $val8c;
$val9c 	= $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE9C ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE9C : $val9c;
$val10c = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE10C ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE10C : $val10c;
$val11c = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE11C ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUE11C : $val11c;

$valcss = $conf->global->REVOLUTIONPRO_PARAMETRES_VALUECSS ? $conf->global->REVOLUTIONPRO_PARAMETRES_VALUECSS : $valcss;

$arr = explode(".", $val5);
if(empty($val5) || count($arr) <= 1) $val5 = 'revolutionprologin1.jpg';

$revolutionpro = new revolutionpro($db);
// $revolutionpro->id = 1;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');

$permissiontoadd 	= $user->admin;

$upload_dir = $conf->mycompany->dir_output."/logos/login/1";
$relativepathwithnofile = "/logos/login/1/";
$permissiontoadd = 1;
include_once DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

global $user,$conf;
$form = new Form($db);
$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
$relativepathwithnofile = "/logos/login/1/";
// $modulepart = 'revolutionpro';
$modulepart = 'mycompany';
$permission = $user->admin;
$permtoedit = $user->admin;
$param = '';


/*
 * View
 */
$pagen = "RevolutionproSetup";
llxHeader('', $langs->trans($pagen),'','','','', array(),'' );

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'	. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($pagen), $linkback);

// Configuration header
$head = revolutionpro_admin_prepare_head();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("Module940326081Name"),
	0,
	"revolutionpro@revolutionpro"
);

dol_htmloutput_mesg($mesg);



print '<form id="settingrevopro" method="post" action="setup.php">';

print '<div id="settingrevolutionprotheme">';

print '<input type="hidden" name="action" value="update">';
print '<div class="nav-tabs-horizontal">';
print '<ul role="tablist" class="nav nav-tabs nav-tabs-line">';
	print '<li class="nav-item revoprocolors"><a class="nav-link active show" role="tab" aria-controls="revoprocolors" href="#revoprocolors" data-toggle="tab" aria-expanded="true" aria-selected="true">'.$langs->trans('Colors').'</a></li>';
	print '<li class="nav-item revoprofourboxcontent"><a class="nav-link" role="tab" aria-controls="revoprofourboxcontent" href="#revoprofourboxcontent" data-toggle="tab" aria-expanded="true" aria-selected="true">'.$langs->trans('RevolutionProFourBoxesContent').'</a></li>';
	print '<li class="nav-item revoprofourbox"><a class="nav-link" role="tab" aria-controls="revoprofourbox" href="#revoprofourbox" data-toggle="tab" aria-expanded="true" aria-selected="true">'.$langs->trans('RevolutionProFourBoxes').'</a></li>';
	print '<li class="nav-item revoprologin"><a class="nav-link" role="tab" aria-controls="revoprologin" href="#revoprologin" data-toggle="tab" aria-expanded="true" aria-selected="true">'.$langs->trans('LoginPage').'</a></li>';
	print '<li class="nav-item revoproCSSaddi"><a class="nav-link" role="tab" aria-controls="revoproCSSaddi" href="#revoproCSSaddi" data-toggle="tab" aria-expanded="true" aria-selected="true">'.$langs->trans('CSS additionnel').'</a></li>';
print '</ul>';

print '<div class="tab-content">';


// COLORS
print '<div role="tabpanel" id="revoprocolors" class="tab-pane active show">';
print '<div class="site-skintools2">';
	print '<div class="site-skintools2-inner">';
	print '<div class="site-skintools2-content">';
	print '<div class="nav-tabs-horizontal">';

	print '<div class="tab-content">';
	print '<table class="tableconfigurationrevopro">';
	print '<tr>';
		print '<td>';
			print '<div>';
			print '<h4 class="site-skintools2-title">Sidebar Skins</h4>';
			$chd = ''; if($val1 == 'dark') $chd = 'checked';
			print '<div class="radio-custom radio-dark"><input id="skintoolsSidebar2-dark" type="radio" name="value1" '.$chd.' value="dark"><label for="skintoolsSidebar2-dark">dark</label></div>';
			$chd = ''; if($val1 == 'light') $chd = 'checked';
			print '<div class="radio-custom radio-light"><input id="skintoolsSidebar2-light" type="radio" name="value1" '.$chd.' value="light"><label for="skintoolsSidebar2-light">light</label></div>';
			print '</div>';
		print '</td>';


		print '<td>';
			print '<div>';
				print '<h4 class="site-skintools2-title">Navbar Type</h4>';
				$chd = ''; if($val2 == 'inverse') $chd = 'checked';
				print '<div class="checkbox-custom checkbox-inverse"><input id="skintoolsNavbar2-inverse" type="checkbox" name="value2" '.$chd.' value="inverse"><label for="skintoolsNavbar2-inverse">inverse</label></div>';
		print '</td>';
	print '</tr>';

	print '<tr>';
		print '<td colspan="2" id="colornavbar">';
			print '<div>';
				$chd = '';
				print '<h4 class="site-skintools2-title">Navbar Skins</h4>';
				$chd = ''; if($val3 == 'primary') $chd = 'checked';
				print '<div class="radio-custom radio-primary"><input id="skintoolsNavbar2-primary" type="radio" name="value3" '.$chd.' value="primary"><label for="skintoolsNavbar2-primary">primary</label></div>';
				$chd = ''; if($val3 == 'blue') $chd = 'checked';
				print '<div class="radio-custom radio-blue"><input id="skintoolsNavbar2-blue" type="radio" name="value3" '.$chd.' value="blue"><label for="skintoolsNavbar2-blue">blue</label></div>';
				$chd = ''; if($val3 == 'brown') $chd = 'checked';
				print '<div class="radio-custom radio-brown"><input id="skintoolsNavbar2-brown" type="radio" name="value3" '.$chd.' value="brown"><label for="skintoolsNavbar2-brown">brown</label></div>';
				$chd = ''; if($val3 == 'cyan') $chd = 'checked';
				print '<div class="radio-custom radio-cyan"><input id="skintoolsNavbar2-cyan" type="radio" name="value3" '.$chd.' value="cyan"><label for="skintoolsNavbar2-cyan">cyan</label></div>';
				$chd = ''; if($val3 == 'green') $chd = 'checked';
				print '<div class="radio-custom radio-green"><input id="skintoolsNavbar2-green" type="radio" name="value3" '.$chd.' value="green"><label for="skintoolsNavbar2-green">green</label></div>';
				$chd = ''; if($val3 == 'grey') $chd = 'checked';
				print '<div class="radio-custom radio-grey"><input id="skintoolsNavbar2-grey" type="radio" name="value3" '.$chd.' value="grey"><label for="skintoolsNavbar2-grey">grey</label></div>';
				$chd = ''; if($val3 == 'orange') $chd = 'checked';
				print '<div class="radio-custom radio-orange"><input id="skintoolsNavbar2-orange" type="radio" name="value3" '.$chd.' value="orange"><label for="skintoolsNavbar2-orange">orange</label></div>';
				$chd = ''; if($val3 == 'pink') $chd = 'checked';
				print '<div class="radio-custom radio-pink"><input id="skintoolsNavbar2-pink" type="radio" name="value3" '.$chd.' value="pink"><label for="skintoolsNavbar2-pink">pink</label></div>';
				$chd = ''; if($val3 == 'purple') $chd = 'checked';
				print '<div class="radio-custom radio-purple"><input id="skintoolsNavbar2-purple" type="radio" name="value3" '.$chd.' value="purple"><label for="skintoolsNavbar2-purple">purple</label></div>';
				$chd = ''; if($val3 == 'red') $chd = 'checked';
				print '<div class="radio-custom radio-red"><input id="skintoolsNavbar2-red" type="radio" name="value3" '.$chd.' value="red"><label for="skintoolsNavbar2-red">red</label></div>';
				$chd = ''; if($val3 == 'teal') $chd = 'checked';
				print '<div class="radio-custom radio-teal"><input id="skintoolsNavbar2-teal" type="radio" name="value3" '.$chd.' value="teal"><label for="skintoolsNavbar2-teal">teal</label></div>';
				$chd = ''; if($val3 == 'yellow') $chd = 'checked';
				print '<div class="radio-custom radio-yellow"><input id="skintoolsNavbar2-yellow" type="radio" name="value3" '.$chd.' value="yellow"><label for="skintoolsNavbar2-yellow">yellow</label></div>';
			print '</div>';
		print '<td>';
	print '</tr>';

	print '<tr>';
		print '<td colspan="2" id="colorbuttons">';
			print '<div>';
				$chd = '';
				print '<h4 class="site-skintools2-title">Buttons Color</h4>';
				$chd = ''; if($val6 == 'primary') $chd = 'checked';
				print '<div class="radio-custom radio-primary"><input id="skintoolsNavbar3-primary" type="radio" name="value6" '.$chd.' value="primary"><label for="skintoolsNavbar3-primary">primary</label></div>';
				$chd = ''; if($val6 == 'blue') $chd = 'checked';
				print '<div class="radio-custom radio-blue"><input id="skintoolsNavbar3-blue" type="radio" name="value6" '.$chd.' value="blue"><label for="skintoolsNavbar3-blue">blue</label></div>';
				$chd = ''; if($val6 == 'brown') $chd = 'checked';
				print '<div class="radio-custom radio-brown"><input id="skintoolsNavbar3-brown" type="radio" name="value6" '.$chd.' value="brown"><label for="skintoolsNavbar3-brown">brown</label></div>';
				$chd = ''; if($val6 == 'cyan') $chd = 'checked';
				print '<div class="radio-custom radio-cyan"><input id="skintoolsNavbar3-cyan" type="radio" name="value6" '.$chd.' value="cyan"><label for="skintoolsNavbar3-cyan">cyan</label></div>';
				$chd = ''; if($val6 == 'green') $chd = 'checked';
				print '<div class="radio-custom radio-green"><input id="skintoolsNavbar3-green" type="radio" name="value6" '.$chd.' value="green"><label for="skintoolsNavbar3-green">green</label></div>';
				$chd = ''; if($val6 == 'grey') $chd = 'checked';
				print '<div class="radio-custom radio-grey"><input id="skintoolsNavbar3-grey" type="radio" name="value6" '.$chd.' value="grey"><label for="skintoolsNavbar3-grey">grey</label></div>';
				$chd = ''; if($val6 == 'orange') $chd = 'checked';
				print '<div class="radio-custom radio-orange"><input id="skintoolsNavbar3-orange" type="radio" name="value6" '.$chd.' value="orange"><label for="skintoolsNavbar3-orange">orange</label></div>';
				$chd = ''; if($val6 == 'pink') $chd = 'checked';
				print '<div class="radio-custom radio-pink"><input id="skintoolsNavbar3-pink" type="radio" name="value6" '.$chd.' value="pink"><label for="skintoolsNavbar3-pink">pink</label></div>';
				$chd = ''; if($val6 == 'purple') $chd = 'checked';
				print '<div class="radio-custom radio-purple"><input id="skintoolsNavbar3-purple" type="radio" name="value6" '.$chd.' value="purple"><label for="skintoolsNavbar3-purple">purple</label></div>';
				$chd = ''; if($val6 == 'red') $chd = 'checked';
				print '<div class="radio-custom radio-red"><input id="skintoolsNavbar3-red" type="radio" name="value6" '.$chd.' value="red"><label for="skintoolsNavbar3-red">red</label></div>';
				$chd = ''; if($val6 == 'teal') $chd = 'checked';
				print '<div class="radio-custom radio-teal"><input id="skintoolsNavbar3-teal" type="radio" name="value6" '.$chd.' value="teal"><label for="skintoolsNavbar3-teal">teal</label></div>';
				$chd = ''; if($val6 == 'yellow') $chd = 'checked';
				print '<div class="radio-custom radio-yellow"><input id="skintoolsNavbar3-yellow" type="radio" name="value6" '.$chd.' value="yellow"><label for="skintoolsNavbar3-yellow">yellow</label></div>';
			print '</div>';
		print '<td>';
	print '</tr>';
	print '</table>';
	print '</div>';

	print '</div>';
	print '</div>';
	print '</div>';
print '</div>';
print '</div>';


// FOUR BOXE CONTENT
print '<div role="tabpanel" id="revoprofourboxcontent" class="tab-pane ">';
print '<div class="site-skintools2" >';
	print '<div class="site-skintools2-inner">';
	print '<div class="site-skintools2-content" style="min-height: initial !important;">';
	print '<div class="nav-tabs-horizontal">';

	print '<div class="tab-content">';
	print '<br>';
	print '<table class="tableconfigurationrevopro tagtable liste">';
	print '<tr class="oddeven firstbox">';
		print '<td>'.$langs->trans('RevolutionProfirstbox').'</td>';
		print '<td>';
		print $revolutionpro->selectFourBoxContent($val8, 'value8');
		print '<div class="radiocolorfourboxcontent">';
			$chd = ''; if($val8c == 'indigo-400') $chd = 'checked';
			print '<div class="radio-custom radio-indigo-400"><input id="box-value8c-indigo-400" type="radio" name="value8c" '.$chd.' value="indigo-400"><label for="box-value8c-indigo-400"> </label></div>';
			$chd = ''; if($val8c == 'green-300') $chd = 'checked';
			print '<div class="radio-custom radio-green-300"><input id="box-value8c-green-300" type="radio" name="value8c" '.$chd.' value="green-300"><label for="box-value8c-green-300"> </label></div>';
			$chd = ''; if($val8c == 'purple-300') $chd = 'checked';
			print '<div class="radio-custom radio-purple-300"><input id="box-value8c-purple-300" type="radio" name="value8c" '.$chd.' value="purple-300"><label for="box-value8c-purple-300"> </label></div>';
			$chd = ''; if($val8c == 'amber-600') $chd = 'checked';
			print '<div class="radio-custom radio-amber-600"><input id="box-value8c-amber-600" type="radio" name="value8c" '.$chd.' value="amber-600"><label for="box-value8c-amber-600"> </label></div>';
		print '</div>';
		print '</td>';
	print '</tr>';
	print '<tr class="oddeven secondbox">';
		print '<td>'.$langs->trans('RevolutionProsecondtbox').'</td>';
		print '<td>';
		print $revolutionpro->selectFourBoxContent($val9, 'value9');
		print '<div class="radiocolorfourboxcontent">';
			$chd = ''; if($val9c == 'indigo-400') $chd = 'checked';
			print '<div class="radio-custom radio-indigo-400"><input id="box-value9c-indigo-400" type="radio" name="value9c" '.$chd.' value="indigo-400"><label for="box-value9c-indigo-400"> </label></div>';
			$chd = ''; if($val9c == 'green-300') $chd = 'checked';
			print '<div class="radio-custom radio-green-300"><input id="box-value9c-green-300" type="radio" name="value9c" '.$chd.' value="green-300"><label for="box-value9c-green-300"> </label></div>';
			$chd = ''; if($val9c == 'purple-300') $chd = 'checked';
			print '<div class="radio-custom radio-purple-300"><input id="box-value9c-purple-300" type="radio" name="value9c" '.$chd.' value="purple-300"><label for="box-value9c-purple-300"> </label></div>';
			$chd = ''; if($val9c == 'amber-600') $chd = 'checked';
			print '<div class="radio-custom radio-amber-600"><input id="box-value9c-amber-600" type="radio" name="value9c" '.$chd.' value="amber-600"><label for="box-value9c-amber-600"> </label></div>';
		print '</div>';
		print '</td>';
	print '</tr>';	
	print '<tr class="oddeven thirdbox">';
		print '<td>'.$langs->trans('RevolutionProthirdbox').'</td>';
		print '<td>';
		print $revolutionpro->selectFourBoxContent($val10, 'value10');
		print '<div class="radiocolorfourboxcontent">';
			$chd = ''; if($val10c == 'indigo-400') $chd = 'checked';
			print '<div class="radio-custom radio-indigo-400"><input id="box-value10c-indigo-400" type="radio" name="value10c" '.$chd.' value="indigo-400"><label for="box-value10c-indigo-400"> </label></div>';
			$chd = ''; if($val10c == 'green-300') $chd = 'checked';
			print '<div class="radio-custom radio-green-300"><input id="box-value10c-green-300" type="radio" name="value10c" '.$chd.' value="green-300"><label for="box-value10c-green-300"> </label></div>';
			$chd = ''; if($val10c == 'purple-300') $chd = 'checked';
			print '<div class="radio-custom radio-purple-300"><input id="box-value10c-purple-300" type="radio" name="value10c" '.$chd.' value="purple-300"><label for="box-value10c-purple-300"> </label></div>';
			$chd = ''; if($val10c == 'amber-600') $chd = 'checked';
			print '<div class="radio-custom radio-amber-600"><input id="box-value10c-amber-600" type="radio" name="value10c" '.$chd.' value="amber-600"><label for="box-value10c-amber-600"> </label></div>';
		print '</div>';
		print '</td>';
	print '</tr>';	
	print '<tr class="oddeven fourthbox">';
		print '<td>'.$langs->trans('RevolutionProfourthbox').'</td>';
		print '<td>';
		print $revolutionpro->selectFourBoxContent($val11, 'value11');
		print '<div class="radiocolorfourboxcontent">';
			$chd = ''; if($val11c == 'indigo-400') $chd = 'checked';
			print '<div class="radio-custom radio-indigo-400"><input id="box-value11c-indigo-400" type="radio" name="value11c" '.$chd.' value="indigo-400"><label for="box-value11c-indigo-400"> </label></div>';
			$chd = ''; if($val11c == 'green-300') $chd = 'checked';
			print '<div class="radio-custom radio-green-300"><input id="box-value11c-green-300" type="radio" name="value11c" '.$chd.' value="green-300"><label for="box-value11c-green-300"> </label></div>';
			$chd = ''; if($val11c == 'purple-300') $chd = 'checked';
			print '<div class="radio-custom radio-purple-300"><input id="box-value11c-purple-300" type="radio" name="value11c" '.$chd.' value="purple-300"><label for="box-value11c-purple-300"> </label></div>';
			$chd = ''; if($val11c == 'amber-600') $chd = 'checked';
			print '<div class="radio-custom radio-amber-600"><input id="box-value11c-amber-600" type="radio" name="value11c" '.$chd.' value="amber-600"><label for="box-value11c-amber-600"> </label></div>';
		print '</div>';
		print '</td>';
	print '</tr>';	
	print '</table>';
	print '</div>';

	print '</div>';
	print '</div>';
	print '</div>';
print '</div>';
print '</div>';


// SHOW & HIDE
print '<div role="tabpanel" id="revoprofourbox" class="tab-pane ">';
print '<div class="site-skintools2" >';
	print '<div class="site-skintools2-inner">';
	print '<div class="site-skintools2-content" style="min-height: initial !important;">';
	print '<div class="nav-tabs-horizontal">';

	print '<div class="tab-content">';
	print '<table class="tableconfigurationrevopro">';
	print '<tr>';

		print '<td class="revolproshowhiditems" id="showorhidefourboxes">';
			print '<br>';
			$chd = ''; if($val4 == 'show') $chd = 'checked';
			print '<div class="radio-custom radio-green"><input id="fourboxes-show" type="radio" name="value4" value="show" '.$chd.'><label for="fourboxes-show">'.$langs->trans('RevolutionProFourBoxesShow').'</label></div>';
			$chd = ''; if($val4 == 'hide') $chd = 'checked';
			print '<div class="radio-custom radio-red"><input id="fourboxes-hide" type="radio" name="value4" value="hide" '.$chd.'><label for="fourboxes-hide">'.$langs->trans('RevolutionProFourBoxesHide').'</label></div>';
		print '</td>';

	print '</tr>';
	print '<tr>';

		print '<td class="revolproshowhiditems" id="showorhidecompanyname">';
			print '<br>';
			$chd = ''; if($val7 == 'show') $chd = 'checked';
			print '<div class="radio-custom radio-green"><input id="fourcompanyname-show" type="radio" name="value7" value="show" '.$chd.'><label for="fourcompanyname-show">'.$langs->trans('RevolutionProFourCompanynameShow').'</label></div>';
			$chd = ''; if($val7 == 'hide') $chd = 'checked';
			print '<div class="radio-custom radio-red"><input id="fourcompanyname-hide" type="radio" name="value7" value="hide" '.$chd.'><label for="fourcompanyname-hide">'.$langs->trans('RevolutionProFourCompanynameHide').'</label></div>';
		print '</td>';

	print '</tr>';	

	print '</table>';
	print '</div>';

	print '</div>';
	print '</div>';
	print '</div>';
print '</div>';
print '</div>';


// LOGIN PAGE
print '<div role="tabpanel" id="revoprologin" class="tab-pane">';
print '<div class="site-skintools2" >';
	print '<div class="site-skintools2-inner">';
	print '<div class="site-skintools2-content">';
	print '<div class="nav-tabs-horizontal">';

	print '<br>';
	print '<div class="tab-content">';
	print '<table class="tableconfigurationrevopro">';


	// Message on login page
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$form=new Form($db);
	$substitutionarray=getCommonSubstitutionArray($langs, 0, array('object','objectamount','user'));
	complete_substitutions_array($substitutionarray, $langs);
	print '<tr class=""><td style="width: 250px;">';
	$texthelp=$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
	foreach($substitutionarray as $key => $val)
	{
		$texthelp.=$key.'<br>';
	}
	print $form->textwithpicto($langs->trans("MessageLogin"), $texthelp, 1, 'help', '', 0, 2, 'tooltipmessagelogin');
	print '</td><td >';
	$doleditor = new DolEditor('main_home', (isset($conf->global->MAIN_HOME)?$conf->global->MAIN_HOME:''), '', 142, 'dolibarr_notes', 'In', false, true, true, ROWS_4, '99%');
	$doleditor->Create();
	print '</td></tr>'."\n";

	print '<tr class="">';
	print '</td></tr>'."\n";

	print '</table>';
	print '</div>';

	print '<br>';

	print '<div class="uploadimagelogin">';
	print '<ul class="blocks blocks-100 blocks-xxl-4 blocks-lg-3 blocks-md-2" data-plugin="filterable" data-filters="#exampleFilter">';
	
	
	$urlimgs = dol_buildpath('/revolutionpro/img/login', 1);
	$i = 1;
	$nameoffile = 'revolutionprologin1.jpg';
	print '<li data-type="bg">';
	$chd = ''; if($val5 == $nameoffile) $chd = 'selected';
	$urlf = $urlimgs."/".$nameoffile;
	print '<div class="card '.$chd.' card-shadow">';
	print '<figure class="card-img-top overlay-hover overlay" style="min-height: 272px;">';
	print '<img class="overlay-figure overlay-scale" src="'.$urlf.'"';
	print 'alt="...">';
	print '<figcaption class="overlay-panel overlay-background overlay-fade overlay-icon">';
	print '<a class="icon md-search pictopreview documentpreview" href="'.$urlf.'" mime="image/jpeg"></a>';
	print '</figcaption>';
	print '</figure>';
	print '<div class="card-block revolutionprofilename">';
	$chd = ''; if($val5 == $nameoffile) $chd = 'checked';
	print '<div class="radio-custom radio-blue"><input class="radiologinbg" '.$chd.' id="loginbg-hide'.$i.'" type="radio" name="value5" value="'.$nameoffile.'"><label for="loginbg-hide'.$i.'">'.$langs->trans('Image').' #'.$i.'</label></div>';
	// print '<h5 class="card-title">'.$langs->trans('Image').' #'.$i.'</h5>';
	print '</div>';
	print '</div>';
	print '</li>';

	$i++;
	$nameoffile = 'revolutionprologin2.jpg';
	print '<li data-type="bg">';
	$chd = ''; if($val5 == $nameoffile) $chd = 'selected';
	$urlf = $urlimgs."/".$nameoffile;
	print '<div class="card '.$chd.' card-shadow">';
	print '<figure class="card-img-top overlay-hover overlay" style="min-height: 272px;">';
	print '<img class="overlay-figure overlay-scale" src="'.$urlf.'"';
	print 'alt="...">';
	print '<figcaption class="overlay-panel overlay-background overlay-fade overlay-icon">';
	print '<a class="icon md-search pictopreview documentpreview" href="'.$urlf.'" mime="image/jpeg"></a>';
	print '</figcaption>';
	print '</figure>';
	print '<div class="card-block revolutionprofilename">';
	$chd = ''; if($val5 == $nameoffile) $chd = 'checked';
	print '<div class="radio-custom radio-blue"><input class="radiologinbg" '.$chd.' id="loginbg-hide'.$i.'" type="radio" name="value5" value="'.$nameoffile.'"><label for="loginbg-hide'.$i.'">'.$langs->trans('Image').' #'.$i.'</label></div>';
	// print '<h5 class="card-title">'.$langs->trans('Image').' #'.$i.'</h5>';
	print '</div>';
	print '</div>';
	print '</li>';

	$i++;

	$dir = dol_buildpath('$dir', 0);
	
	$urlimgs = dol_buildpath('/revolutionpro/img/login', 1);
	
 	$filearray = dol_dir_list($upload_dir, "files", 0, '', null, 'date', '', 0);

 	$modulepart = 'mycompany';
	foreach ($filearray as $key => $file)
	{
		if (!is_dir($file['name'])
		&& $file['name'] != '.'
		&& $file['name'] != '..'
		&& $file['name'] != 'CVS'
		&& !preg_match('/\.meta$/i', $file['name']))
		{		

			$documenturl = DOL_URL_ROOT.'/document.php';
			if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP; // To use another wrapper

			$relativepath = '/logos/login/1/'.$file["name"]; // Cas general
			$urladvancedpreview = getAdvancedPreviewUrl($modulepart, $relativepath, 1, $param); // Return if a file is qualified for preview.
			
			$urlf = $documenturl.'?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).($param ? '&'.$param : '').'"';

			$nameoffile = urlencode($file["name"]);
			print '<li data-type="bg">';
			$chd = ''; if($val5 == $nameoffile) $chd = 'selected';
			print '<div class="card '.$chd.' card-shadow">';
			print '<figure class="card-img-top overlay-hover overlay" style="min-height: 272px;">';
			print '<img class="overlay-figure overlay-scale" src="'.$urlf.'"';
			print 'alt="...">';
			print '<figcaption class="overlay-panel overlay-background overlay-fade overlay-icon">';
			print '<a class="icon md-search pictopreview documentpreview" href="'.$urlf.'" mime="'.$urladvancedpreview['mime'].'"></a>';
			print '</figcaption>';
			print '</figure>';
			print '<div class="card-block revolutionprofilename">';
			$chd = ''; if($val5 == $nameoffile) $chd = 'checked';
			print '<div class="radio-custom radio-blue"><input class="radiologinbg" '.$chd.' id="loginbg-hide'.$i.'" type="radio" name="value5" value="'.$nameoffile.'"><label for="loginbg-hide'.$i.'">'.$langs->trans('Image').' #'.$i.'</label></div>';
			// print '<h5 class="card-title">'.$langs->trans('Image').' #'.$i.'</h5>';
			print '</div>';
			print '</div>';
			print '</li>';

			$i++;
		}
	}

	print '</ul>';

	print '</div>';
	print '<div style="clear:both;"></div>';

	print '</div>';
	print '</div>';
	print '</div>';
print '</div>';
print '</div>';



// CSS additionnel
print '<div role="tabpanel" id="revoproCSSaddi" class="tab-pane">';
print '<div class="site-skintools2" >';
	print '<div class="site-skintools2-inner">';
	print '<div class="site-skintools2-content">';
	print '<div class="nav-tabs-horizontal">';

	print '<br>';
	print '<div class="tab-content">';


	print '<textarea id="valuecss" name="valuecss" rows="8" cols="50" placeholder="#idelement .classelement {'."\n".'  background-color: #FFFFFF;'."\n".'  cursor: pointer;'."\n".'  opacity: 0;'."\n".'}">';
	print ($valcss);
	print '</textarea>';

	print '</div>';

	print '<br>';

	print '<div style="clear:both;"></div>';

	print '</div>';
	print '</div>';
	print '</div>';
print '</div>';
print '</div>';


print '</div>';


print '</div>';


print '<div class="divrevolutionprobuttonadmin">';
print '<div style="clear:both;"></div>';
print '<input type="submit" id="submitbutton" class="butAction" value="'.$langs->trans('Validate').'" style="float: left;">';
print '<a class="butActionDelete" style="float: right;" href="'.dol_buildpath('/revolutionpro/admin/setup.php', 2).'?action=defaultparameters">'.$langs->trans('RevoProDefaultsParamets').'</a>';
print '</div>';
print '</form>';

print '<div id="uploadimagelogin" style="display:none;">';
include_once DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
print '</div>';

	


?>
<style type="text/css">
	div.tabs a.ds_url_module_name{
	    margin-right: 11px;
	}
</style>
<script type="text/javascript">
$(document).ready(function() {
	$('div.revolutionprofilename').click(function(){
		if($(this).find('input').is(':checked')){
	       
	    } else {
	    	$(this).find('input').click();
	    }
	});
	$('ul.nav-tabs li').click(function(){
		if($(this).hasClass('revoprologin')){
	       	$('#uploadimagelogin').show();
	    } else {
	    	$('#uploadimagelogin').hide();
	    }
	});
	$('#skintoolsNavbar2-inverse').click(function(){
	    if($(this).is(':checked')){
	        $('body.site-navbar-small .site-navbar').addClass('navbar-inverse');
	    } else {
	        $('body.site-navbar-small .site-navbar').removeClass('navbar-inverse');
	    }
	});
	$('#revoprocolors .radio-custom input[name="value1"]').change(function() {
		if (this.value == 'dark') {
	        $('body #tmenu_tooltip > .site-menubar').addClass('site-menubar-dark');
	    }
	    else {
	        $('body #tmenu_tooltip > .site-menubar').removeClass('site-menubar-dark');
	    }
	});
	$('#showorhidefourboxes input[name="value4"]').change(function() {
		if (this.value == 'hide') {
	        $('body .row.thefourboxes').hide();
	    }
	    else {
	        $('body .row.thefourboxes').show();
	    }
	});
	$('#showorhidecompanyname input[name="value7"]').change(function() {

		if($('body').hasClass('site-menubar-fold')){
			$('.navbar-toolbar #toggleMenubar>a').click();
		}
		if (this.value == 'hide') {
			setTimeout( function() { 
	       		$('body.site-menubar-unfold .site-navbar .navbar-brand-text').hide();
			}, 300);
	    }
	    else {
	        setTimeout( function() { 
	       		$('body.site-menubar-unfold .site-navbar .navbar-brand-text').show();
			}, 300);
	    }

	});

	$('#revoprocolors .radio-custom input[name="value6"]').change(function() {
		var val = this.value;
		var bg = "bg-".concat(val, "-600");
		$('.butAction, #mainbody input.button:not(.buttongen):not(.bordertransp)').removeClass (function (index, className) {
		    return (className.match (/(^|\s)bg-\S+/g) || []).join(' ');
		});
		if (val === 'yellow') {
			bg = 'bg-yellow-800';
		}

		// if (val === 'primary') {
		// 	bg = '';
		// }
		$('.butAction, #mainbody input.button:not(.buttongen):not(.bordertransp)').addClass(bg);
	});

	$('#revoprocolors .radio-custom input[name="value3"]').change(function() {
		var val = this.value;
		var bg = "bg-".concat(val, "-600");

		$("body.site-navbar-small .site-navbar").removeClass (function (index, className) {
		    return (className.match (/(^|\s)bg-\S+/g) || []).join(' ');
		});

		if (val === 'yellow') {
			bg = 'bg-yellow-800';
		}

		if (val === 'primary') {
			bg = '';
		}
  		$('body.site-navbar-small .site-navbar').addClass(bg);
	});
});
</script>
<?php

// Page end
dol_fiche_end();
llxFooter();
$db->close();
?>