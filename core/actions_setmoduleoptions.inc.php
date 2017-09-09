<?php
/* Copyright (C) 2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *	\file			htdocs/core/actions_setnotes.inc.php
 *  \brief			Code for actions on setting notes of object page
 */


// $action must be defined
// $_FILES may be defined
// $nomessageinsetmoduleoptions can be set to 1

// Define constants for submodules that contains parameters (forms with param1, param2, ... and value1, value2, ...)
if ($action == 'setModuleOptions')
{
    $db->begin();

    // Process common param fields
    if (is_array($_POST))
    {
        foreach($_POST as $key => $val)
        {
            if (preg_match('/^param(\d*)$/', $key, $reg))    // Works for POST['param'], POST['param1'], POST['param2'], ...
            {
                $param=GETPOST("param".$reg[1],'alpha');
                $value=GETPOST("value".$reg[1],'alpha');
                if ($param)
                {
                    $res = dolibarr_set_const($db,$param,$value,'chaine',0,'',$conf->entity);
                    if (! $res > 0) $error++;
                }
            }
        }
    }

    // Process upload fields
    if (GETPOST('upload','alpha') && GETPOST('keyforuploaddir','aZ09'))
    {
        include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        $keyforuploaddir=GETPOST('keyforuploaddir','aZ09');
        $listofdir=explode(',',preg_replace('/[\r\n]+/',',',trim($conf->global->$keyforuploaddir)));
        foreach($listofdir as $key=>$tmpdir)
        {
            $tmpdir=trim($tmpdir);
            $tmpdir=preg_replace('/DOL_DATA_ROOT/',DOL_DATA_ROOT,$tmpdir);
            if (! $tmpdir) {
                unset($listofdir[$key]); continue;
            }
            if (! is_dir($tmpdir)) $texttitle.=img_warning($langs->trans("ErrorDirNotFound",$tmpdir),0);
            else
            {
                $upload_dir=$tmpdir;
            }
        }
        if ($upload_dir)
        {
            $result = dol_add_file_process($upload_dir, 0, 1, 'uploadfile', '');
            if ($result <= 0) $error++;
        }
    }

    if (! $error)
    {
        $db->commit();
        if (empty($nomessageinsetmoduleoptions)) setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        $db->rollback();
        if (empty($nomessageinsetmoduleoptions)) setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
    }
}

