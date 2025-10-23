<?php
/* Copyright (C) 2010-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020  Frédéric France         <contact@dolibarrstore.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file		htdocs/core/menus/standard/revolutionpro.lib.php
 *  \brief		Library for file revolutionpro menus
 */
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';
dol_include_once('/revolutionpro/class/revolutionpro.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';


$ardolv = DOL_VERSION;
$ardolv = explode(".", $ardolv);
$dolvs = $ardolv[0];

if ($dolvs < 7) unActivateModule("modrevolutionpro"); elseif($dolvs > 15) $dolvs = 15;

require_once 'versions/'.$dolvs.'-0-0.php';

/**
 * Core function to output top menu revolutionpro
 *
 * @param 	DoliDB	$db				Database handler
 * @param 	string	$atarget		Target (Example: '' or '_top')
 * @param 	int		$type_user     	0=Menu for backoffice, 1=Menu for front office
 * @param  	array	$tabMenu        If array with menu entries already loaded, we put this array here (in most cases, it's empty)
 * @param	Menu	$menu			Object Menu to return back list of menu entries
 * @param	int		$noout			1=Disable output (Initialise &$menu only).
 * @param	string	$mode			'top', 'topnb', 'left', 'jmobile'
 * @return	int						0
 */
function print_revolutionpro_menu($db, $atarget, $type_user, &$tabMenu, &$menu, $noout = 0, $mode = '', $justfillsess = 0)
{
	global $user, $conf, $langs, $mysoc;
	global $dolibarr_main_db_name;

	
	$action = GETPOST('action', 'alpha');

	$revolutionpro = new revolutionpro($db);

	$sesslm = 0;


	// // Session FOR Menu
	// if($user && $user->id){
	// 	$object = new User($db);
	// 	$object->fetch($user->id);
	// 	if(isset($object->array_options['options_revolutionpromenu']) && $object->array_options['options_revolutionpromenu'] > 0){
	// 		$sesslm = 1;
	// 	}else{
	// 		$object->array_options['options_revolutionpromenu'] = 0;
	// 		$object->update($user);
	// 	}
	// }

	// $revolutionpro->styleNewToAdd();

	// // Session FOR Menu
	// if ($sesslm && isset($_SESSION['revolutionproleftmenu']) && !empty($_SESSION['revolutionproleftmenu'])){
	// 	$html = $_SESSION['revolutionproleftmenu'];
	// 	echo $html;
	// 	return 0;
	// }

	$substitarray = getCommonSubstitutionArray($langs, 0, null, null);
	
	$showmode = 1;
	$menu_arr = getMenusOfTop($db, $atarget, $type_user, $tabMenu, $menu, $noout, $mode);

	// Add menus
	foreach ($menu_arr as $key => $smenu)
	{
		$smenu = (object) $smenu;

		if ($smenu->enabled)
		{
			if ($smenu->session)
			{
				$_SESSION['idmenu'] = '';
			}

			// Load Langue
			if (!empty($smenu->loadLangs))
			{
				$langs->loadLangs($smenu->loadLangs);
			}

			// Trans title
			$mtitle = '';
			if (is_array($smenu->title))
			{
				foreach ($smenu->title as $item)
				{
					$mtitle .= $langs->trans($item);
				}
			}
			else
			{
				$mtitle = $langs->trans($smenu->title);
			}
			// Add item
			$menu->add($smenu->link, $mtitle, $smenu->level, $smenu->enabled, $smenu->target, $smenu->mainmenu, $smenu->leftmenu, $smenu->position, $smenu->id, $smenu->idsel, $smenu->classname, $smenu->prefix);
		}
	}

	// Show personalized menus
	$menuArbo = new Menubase($db, 'revolutionpro');
	$newTabMenu = $menuArbo->menuTopCharger('', '', $type_user, 'revolutionpro', $tabMenu); // Return tabMenu with only top entries

	$listofmodulesforexternal = explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL);
	
	$num = count($newTabMenu);
	for ($i = 0; $i < $num; $i++)
	{
		$idsel = (empty($newTabMenu[$i]['mainmenu']) ? 'none' : $newTabMenu[$i]['mainmenu']);

		$showmode = isVisibleToUserType($type_user, $newTabMenu[$i], $listofmodulesforexternal);
		if ($showmode == 1)
		{
			$newTabMenu[$i]['url'] = make_substitutions($newTabMenu[$i]['url'], $substitarray);

		    // url = url from host, shorturl = relative path into dolibarr sources
			$url = $shorturl = $newTabMenu[$i]['url'];
			if (!preg_match("/^(http:\/\/|https:\/\/)/i", $newTabMenu[$i]['url']))	// Do not change url content for external links
			{
				$tmp = explode('?', $newTabMenu[$i]['url'], 2);
				$url = $shorturl = $tmp[0];
				$param = (isset($tmp[1]) ? $tmp[1] : '');

				if (!preg_match('/mainmenu/i', $param) || !preg_match('/leftmenu/i', $param)) $param .= ($param ? '&' : '').'mainmenu='.$newTabMenu[$i]['mainmenu'].'&amp;leftmenu=';
				//$url.="idmenu=".$newTabMenu[$i]['rowid'];    // Already done by menuLoad
				$url = dol_buildpath($url, 1).($param ? '?'.$param : '');
				//$shorturl = $shorturl.($param?'?'.$param:'');
                $shorturl = $url;
				if (DOL_URL_ROOT) $shorturl = preg_replace('/^'.preg_quote(DOL_URL_ROOT, '/').'/', '', $shorturl);
			}

			// Define the class (top menu selected or not)
			if (!empty($_SESSION['idmenu']) && $newTabMenu[$i]['rowid'] == $_SESSION['idmenu']) $classname = 'class="tmenusel"';
			elseif (!empty($_SESSION["mainmenu"]) && $newTabMenu[$i]['mainmenu'] == $_SESSION["mainmenu"]) $classname = 'class="tmenusel"';
			else $classname = 'class="tmenu"';
		}
		elseif ($showmode == 2) $classname = 'class="tmenu"';

		$menu->add($shorturl, $newTabMenu[$i]['titre'], 0, $showmode, ($newTabMenu[$i]['target'] ? $newTabMenu[$i]['target'] : $atarget), ($newTabMenu[$i]['mainmenu'] ? $newTabMenu[$i]['mainmenu'] : $newTabMenu[$i]['rowid']), ($newTabMenu[$i]['leftmenu'] ? $newTabMenu[$i]['leftmenu'] : ''), $newTabMenu[$i]['position'], $id, $idsel, $classname);
	}

	// Sort on position
	$menu->liste = dol_sort_array($menu->liste, 'position');

	// $revolutionpro->print_revolutionpro_new_top_menu($menu, $tabMenu, $showmode);

	$html = $revolutionpro->print_revolutionpro_new_top_menu($menu, $tabMenu, $showmode);


	// // Session FOR Menu
	// $_SESSION['revolutionproleftmenu'] = $html;
	// if($user && $user->id){
	// 	$object = new User($db);
	// 	$object->fetch($user->id);
	// 	$object->array_options['options_revolutionpromenu'] = 1;
	// 	$object->update($user);
	// }

	echo $html;

	return 0;
}