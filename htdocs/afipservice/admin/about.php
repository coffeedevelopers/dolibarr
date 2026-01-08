<?php
/*
 * Argentina Electronic Invoice module for Dolibarr
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
 * \file admin/about.php
 * \ingroup financial
 * Module about page
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}


require_once '../lib/afipservice.lib.php';
require_once '../core/modules/modAFIPservice.class.php';



global $conf, $db, $user, $langs;

$langs->load('afipservice@wafipservice');
$langs->load('admin');
$langs->load('help');

// only readable by admin
if (!$user->admin) {
    accessforbidden();
}

$module = new modafipservice($db);

/*
 * View
 */

// Little folder on the html page
llxHeader();
/// Navigation in the modules
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print load_fiche_titre($langs->trans("Configuracion Factura Electronica"), $linkback, 'setup');

$head = afipserviceAdminPrepareHead();

dol_fiche_head($head,
    'about',
    $langs->trans("Module1050003Name"),
    -1,
    'afipservice@afipservice'
);

echo '<h3>',$langs->trans("Module1050003Name"),' — ',$langs->Trans('Module1050003Desc'),'</h3>';
echo '<em>', $langs->trans("Version"), ' ',$module->version, '</em><br>';
echo '<em>Copyright &copy; 2019-2022 '.$module->editor_name.'<br><em>';
echo '<a target="_blank" href="'.$module->editor_url.'">';
echo '<h3>', $langs->trans("Publisher"), ' '.$module->editor_name.'</h3>';
echo '<a href="mailto:'.$module->editor_mail.'">'.$module->editor_mail.'</a>';



print '</table><br>';


dol_fiche_end();
llxFooter();
