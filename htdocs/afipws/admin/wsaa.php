<?php
/*
 * Argentina Electronic Invoice module for Dolibarr
 * Copyright (C) 2017 Primetec <info@primetec.com.ar> https://primetec.com.ar
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
 * \file admin/wsaa.php
 * \ingroup finacial
 * Module configuration page
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

require_once '../lib/afipws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

//include_once(DOL_DOCUMENT_ROOT.'/afipws/class/wsaa_db.class.php');
global $conf, $db, $user, $langs;

$mesg = ""; // User message

$langs->load('afipws@afipws');
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

$wsaaprod= GETPOST('wsaaprod');
$cetificate=GETPOST('crt');
$privatekey=GETPOST('key');
$modo=GETPOST('modo');
$emisorcuit = str_replace("-","",$conf->global->MAIN_INFO_SIREN);


/*
 * Actions
 */



if ($action == 'update' && empty($_POST["cancel"]))
{
    // Send mode parameters

    //genero las carpetas para los XMLS
    $dirforxml=$conf->afipws->dir_output.'/'.$conf->entity.'/xml/';
    if (! is_dir($dirforxml))
    {
        dol_mkdir($dirforxml);
    }



// KEY
    $varforkey='AFIPWS_WSAA_KEY';
    $dirforkey=$conf->afipws->dir_output.'/'.$conf->entity.'/keys/';
    if ($_FILES[$varforkey]["tmp_name"])
    {
        if (preg_match('/([^\\/:]+)$/i',$_FILES[$varforkey]["name"],$reg))
        {
            $original_file=$reg[1];

                if (! is_dir($dirforkey))
                {
                    dol_mkdir($dirforkey);
                }
                $result=dol_move_uploaded_file($_FILES[$varforkey]["tmp_name"],$dirforkey.$original_file,1,0,$_FILES[$varforkey]['error']);
                if ($result > 0) {
                    dolibarr_set_const($db, "AFIPWS_WSAA_KEY", $original_file, 'chaine', 0, '', $conf->entity);
                }
                else
                {
                    $error++;
                    setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
                }
            
        }
    }
//--------
//CRT
    $varforcrt='AFIPWS_WSAA_CRT';
    $dirforcrt=$conf->afipws->dir_output.'/'.$conf->entity.'/keys/';
    if ($_FILES[$varforcrt]["tmp_name"])
    {
        if (preg_match('/([^\\/:]+)$/i',$_FILES[$varforcrt]["name"],$reg))
        {
            $original_file=$reg[1];

            if (! is_dir($dirforcrt))
            {
                dol_mkdir($dirforcrt);
            }
            $result=dol_move_uploaded_file($_FILES[$varforcrt]["tmp_name"],$dirforcrt.$original_file,1,0,$_FILES[$varforcrt]['error']);
            if ($result > 0) {
                dolibarr_set_const($db, "AFIPWS_WSAA_CRT", $original_file, 'chaine', 0, '', $conf->entity);
            }
            else
            {
                $error++;
                setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
            }

        }
    }
//--------




    if (isset($_POST["AFIPWS_WSAA_SERVER"]))   dolibarr_set_const($db, "AFIPWS_WSAA_SERVER",   GETPOST("AFIPWS_WSAA_SERVER"),'chaine',0,'',$conf->entity);
//    if (isset($_POST["AFIPWS_WSAA_CRT"]))      dolibarr_set_const($db, "AFIPWS_WSAA_CRT",      GETPOST("AFIPWS_WSAA_CRT"),'chaine',0,'',$conf->entity);
//    if (isset($_POST["AFIPWS_WSAA_KEY"]))      dolibarr_set_const($db, "AFIPWS_WSAA_KEY",      GETPOST("AFIPWS_WSAA_KEY"), 'chaine',0,'',$conf->entity);
    if (isset($_POST["AFIPWS_WSAA_MODE"]))     dolibarr_set_const($db, "AFIPWS_WSAA_MODE",     GETPOST("AFIPWS_WSAA_MODE"), 'yesno',0,'',$conf->entity);


    header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
    exit;
}


if ($action == 'removekey')
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    $keyfile=$conf->afipws->dir_output.'/'.$conf->entity.'/keys/'.$conf->global->AFIPWS_WSAA_KEY;
    dol_delete_file($keyfile);
    dolibarr_set_const($db, "AFIPWS_WSAA_KEY",      '', 'chaine',0,'',$conf->entity);

}

if ($action == 'removecrt')
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    $crtfile=$conf->afipws->dir_output.'/'.$conf->entity.'/keys/'.$conf->global->AFIPWS_WSAA_CRT;
    dol_delete_file($crtfile);
    dolibarr_set_const($db, "AFIPWS_WSAA_CRT",      '', 'chaine',0,'',$conf->entity);
}




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
    print load_fiche_titre($langs->trans("wsfePHPConfig"), $linkback, 'setup');

$head = afipwsAdminPrepareHead();
dol_fiche_head(
    $head,
    'wsaa',
    $langs->trans("Module1050003Name"),
    -1,
    'afipws@afipws'
);



if ($action == 'edit') {

    $form=new Form($db);


    print '<form enctype="multipart/form-data" method="POST" action="'.$_SERVER["PHP_SELF"].'" name="form_index">';

//    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    // WSAA server
   
    print '<tr '.$bc[$var].'><td>';
    $wsaaserver = (! empty($conf->global->AFIPWS_WSAA_SERVER)?$conf->global->AFIPWS_WSAA_SERVER:'');
    print $langs->trans("AFIPWS_WSAA_SERVER");
    print '</td><td>';
    print '<input class="flat" id="AFIPWS_WSAA_SERVER" name="AFIPWS_WSAA_SERVER" size="18" value="' . $wsaaserver . '">';
    print '</td></tr>';

    // WSAA CRT
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    $wsaacrt = (! empty($conf->global->AFIPWS_WSAA_CRT) ? $conf->global->AFIPWS_WSAA_CRT : '');
    print $langs->trans("AFIPWS_WSAA_CRT");
    print '</td><td>';
    print '<table width="100%" class="nobordernopadding"><tr class="nocellnopadd"><td valign="middle" class="nocellnopadd">';
    print '<input type="file" class="flat class=minwidth200" name="AFIPWS_WSAA_CRT" id="AFIPWS_WSAA_CRT">';
    print '</td><td class="nocellnopadd" valign="middle" align="right">';

    if (file_exists($conf->afipws->dir_output.'/'.$conf->entity.'/keys/'.$wsaacrt)) {
        print $wsaacrt;
        print '<a href="' . $_SERVER["PHP_SELF"] . '?action=removecrt">' . img_delete($langs->trans("Delete")) . '</a>';
    }
    print '</td></tr></table>';
    print '</td></tr>';


    // WSAA KEY
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    $wsaakey = (! empty($conf->global->AFIPWS_WSAA_KEY) ? $conf->global->AFIPWS_WSAA_KEY : '');
    print $langs->trans("AFIPWS_WSAA_KEY");
    print '</td><td>';

    print '<table width="100%" class="nobordernopadding"><tr class="nocellnopadd"><td valign="middle" class="nocellnopadd">';
    print '<input type="file" class="flat class=minwidth200" name="AFIPWS_WSAA_KEY" id="AFIPWS_WSAA_KEY">';
    print '</td><td class="nocellnopadd" valign="middle" align="right">';

    if (file_exists($conf->afipws->dir_output.'/'.$conf->entity.'/keys/'.$wsaakey)) {
        print $wsaakey;
        print '<a href="' . $_SERVER["PHP_SELF"] . '?action=removekey">' . img_delete($langs->trans("Delete")) . '</a>';
    }
    print '</td></tr></table>';
    print '</td></tr>';





    // WSAA MODE
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    //print $langs->trans("AFIPWS_WSAA_MODE");
    $text = $langs->trans("AFIPWS_WSAA_MODE");
    $htmltext = $langs->trans("WSAAYesNoModeMessaje");
    print $form->textwithpicto($text,$htmltext,1,'info');
    print '</td><td>';
    print $form->selectyesno('AFIPWS_WSAA_MODE',$conf->global->AFIPWS_WSAA_MODE,1);
    print '</td></tr>';

    print '</table>';

    print '<br><center>';
    print '<input class="button" type="submit" name="save" value="'.$langs->trans("Save").'">';
    print ' &nbsp; &nbsp; ';
    print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</center>';

    print '</form>';
    print '<br>';

}else {

    $var = true;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>' . $langs->trans("Parameter") . '</td><td>' . $langs->trans("Value") . '</td></tr>';


    //WSAA Server
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSAA_SERVER", ini_get('WSAASERVER') ? ini_get('WSAASERVER') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPWS_WSAA_SERVER) ? $conf->global->AFIPWS_WSAA_SERVER : '') . '</td></tr>';
    // WSAA CRT
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSAA_CRT", ini_get('WSAACRT') ? ini_get('WSAACRT') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPWS_WSAA_CRT) ? $conf->global->AFIPWS_WSAA_CRT : '') . '</td></tr>';

    // WSAA KEY
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSAA_KEY", ini_get('WSAAKEY') ? ini_get('WSAAKEY') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPWS_WSAA_KEY) ? $conf->global->AFIPWS_WSAA_KEY : '') . '</td></tr>';
    // WSAA MODE
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSAA_MODE") . '</td><td>' . yn($conf->global->AFIPWS_WSAA_MODE) . '</td></tr>';

    print '</table>';

    // Boutons actions
    print '<div class="tabsAction">';
    print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit">' . $langs->trans("Modify") . '</a>';
    print '</div>';

}

dol_fiche_end();
llxFooter();
