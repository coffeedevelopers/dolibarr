<?php

function afipserviceAdminPrepareHead() {
    global $langs, $conf;
    $h = 0;
    $head = array();
    
    $head[$h][0] = dol_buildpath("/afipservice/admin/wsaa.php", 1);
    $head[$h][1] = $langs->trans("WSAAConfig");
    $head[$h][2] = "wsaa";
    $h++;
    
    $head[$h][0] = dol_buildpath("/afipservice/admin/wsfev1.php", 1);
    $head[$h][1] = $langs->trans("wsfev1Config");
    $head[$h][2] = "wsfev1";
    $h++;
    
    $head[$h][0] = dol_buildpath("/afipservice/admin/status.php", 1);
    $head[$h][1] = $langs->trans("AFIPServerStatus");
    $head[$h][2] = "serverstatus";
    $h++;
    
    $head[$h][0] = dol_buildpath("/afipservice/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = "about";
    
    return $head;
}

function afipservice_wscdcv1_prepare_head($object, $user = 0) {
    global $langs, $conf;
    $langs->load("afipservice@afipservice");
    
    $h = 0;
    $head = array();
    
    $head[$h][0] = dol_buildpath("/factory/product/index.php?id=" . $object->id, 1);
    $head[$h][1] = $langs->trans("Composition");
    $head[$h][2] = "composition";
    $h++;
    
    $head[$h][0] = dol_buildpath("/factory/product/direct.php?id=" . $object->id, 1);
    $head[$h][1] = $langs->trans("DirectBuild");
    $head[$h][2] = "directbuild";
    $h++;
    
    return $head;
}

?>