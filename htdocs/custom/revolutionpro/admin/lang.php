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
 * 	\file		admin/admin.php
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

// Translations
$langs->load("admin");
$langs->load("revolutionpro@revolutionpro");

$lang = GETPOST('lang');
$backtopage = GETPOST('backtopage', 'alpha');

// Access control
// if (! $user->admin) 
if (!$user->admin && !$user->rights->revolutionpro->changelang) {
	accessforbidden();
}
global $db, $conf;



$tabparam["MAIN_LANG_DEFAULT"] = GETPOST("lang", 'aZ09');

$result = dol_set_user_param($db, $conf, $user, $tabparam);

if($user && $user->id){
	$object = new User($db);
	$object->fetch($user->id);
	$object->array_options['options_revolutionpromenu'] = 0;
	$object->update($user);
}

// dolibarr_set_const($db,'REVOLUTIONPRO_LESTMENU_FROM_SESSION', 0,'chaine',0,'',0);

if (!empty($backtopage))
{
    header("Location: ".$backtopage);
    exit;
}
header("Location: ".$backtopage);
exit;

/*
 * Actions
 */
$mesg="";
$action = GETPOST('action', 'alpha');

// if (preg_match('/^set/',$action)) {
  // This is to force to add a new param after css urls to force new file loading
  // This set must be done before calling llxHeader().
//  $_SESSION['dol_resetcache']=dol_print_date(dol_now(),'dayhourlog');
// }


print '<br>';


// Page end
dol_fiche_end();
llxFooter();
$db->close();
?>