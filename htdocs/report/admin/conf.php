<?php
/*
 * Reportico into Dolibarr
 * Copyright (C) 2017 Catriel Rios <catriel_r@hotmail.com>
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
 * \file admin/conf.php
 * \ingroup other
 * Module configuration page
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

require_once '../lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
global $conf, $db, $user, $langs;

$mesg = ""; // User message

$langs->load('report@report');
$langs->load('admin');
$langs->load('help');

// Access control
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
// Get parameters
$id			= GETPOST('id','int');
$backtopage = GETPOST('backtopage');
$myparam	= GETPOST('myparam','alpha');


/*
 * Actions
 */






/**
 * view
 */
llxHeader();
// Error / confirmation messages
dol_htmloutput_mesg($mesg);
$form = new Form($db);
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print load_fiche_titre($langs->trans("reportConfig"), $linkback, 'setup');

$head = reportAdminPrepareHead();
dol_fiche_head(
    $head,
    'conf',
    $langs->trans("Module500350Name"),
    0,
    'report@report'
);


require_once('../includes/reportico/reportico.php');


$q = new reportico(); // Create instance

$q->access_mode = "FULL";                     // Allows access to all Reportico pages
$q->initial_execute_mode = "ADMIN";           // Starts user in administration page
$q->initial_project = "dolibarr";                // Required for access to admin mode

//$q->bootstrap_styles = "3";                   // Set to "3" for bootstrap v3, "2" for V2 or false for no bootstrap
//$q->force_reportico_mini_maintains = true;    // Often required
//$q->bootstrap_preloaded = true;               // true if you dont need Reportico to load its own bootstrap

$q->clear_reportico_session = true;           // Normally required


//  $q = new reportico();
$q->embedded_report = true;

//$q->access_mode = "REPORTOUTPUT";               // Allows access to all Reportico pages

// $q->initial_execute_mode = "MENU";            // Starts user in administration page
// $q->initial_project = "admin";

$q->initial_project_password = "superreportico";


//  $q->bootstrap_styles = "3";                   // Set to "3" for bootstrap v3, "2" for V2 or false for no bootstrap
// $q->force_reportico_mini_maintains = true;    // Often required
//$q->initial_report = "";

//   $q->reportico_ajax_mode = true;
//   $q->initial_show_criteria = "show";
$q->output_template_parameters["show_hide_navigation_menu"] = "show";
$q->output_template_parameters["show_hide_dropdown_menu"] = "show";
$q->output_template_parameters["show_hide_prepare_go_buttons"] = "show";
//$q->output_template_parameters["show_hide_report_output_title"] = "hide";
//$q->output_template_parameters["show_hide_prepare_section_boxes"] = "hide";
//$q->output_template_parameters["show_hide_prepare_pdf_button"] = "hide";
//$q->output_template_parameters["show_hide_prepare_html_button"] = "hide";
//$q->output_template_parameters["show_hide_prepare_print_html_button"] = "hide";
//$q->output_template_parameters["show_hide_prepare_csv_button"] = "hide";
//$q->output_template_parameters["show_hide_prepare_page_style"] = "hide";


// $q->initial_output_format = "HTML";


$q->execute();




dol_fiche_end();
llxFooter();
