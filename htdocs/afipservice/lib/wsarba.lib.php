<?php
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */




// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}


require_once DOL_DOCUMENT_ROOT . '/afipservice/class/wsarba.class.php';

//test arba
$wsarba = new wsarba();
$ret = $wsarba->ConsultarContribuyentes("20180101","20180131","20253553384" );

//test
