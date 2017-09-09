<?php
/* Copyright (C) 2010-2011	Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2013		Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García <marcosgdf@gmail.com>
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
 *
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<?php

global $user;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];
$langs->load("members");

$var=true;
$total=0;
foreach($linkedObjectBlock as $key => $objectlink)
{
	
?>
<tr <?php echo $GLOBALS['bc'][$var]; ?> >
    <td><?php echo $langs->trans("Subscription"); ?></td>
    <td><?php echo $objectlink->getNomUrl(1); ?></td>
	<td align="center"></td>
	<td align="center"><?php echo dol_print_date($objectlink->dateh,'day'); ?></td>
	<td align="right"><?php
		if ($user->rights->adherent->lire) {
			$total = $total + $objectlink->amount;
			echo price($objectlink->amount);
		} ?></td>
	<td align="right"></td>
	<td align="right"><a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&dellinkid='.$key; ?>"><?php echo img_delete($langs->transnoentitiesnoconv("RemoveLink")); ?></a></td>
</tr>
<?php
}
?>

<!-- END PHP TEMPLATE -->