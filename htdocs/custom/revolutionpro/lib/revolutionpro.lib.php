<?php

function revolutionpro_admin_prepare_head()
{
	global $langs, $conf;
	$langs->load('revolutionpro@revolutionpro');

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/revolutionpro/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("RevolutionproSettings");
	$head[$h][2] = 'settings';
	$h++;
	// $head[$h][0] = dol_buildpath("/revolutionpro/admin/about.php", 1);
	// $head[$h][1] = $langs->trans("revolutionproAbout");
	// $head[$h][2] = 'about';
	// $h++;
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'revolutionpro');

	return $head;
}

?>