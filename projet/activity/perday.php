<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      François Legastelois <flegastelois@teclib.com>
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
 */

/**
 *	\file       htdocs/projet/activity/perday.php
 *	\ingroup    projet
 *	\brief      List activities of tasks (per day entry)
 */

require ("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$langs->load('projects');
$langs->load('users');

$action=GETPOST('action','aZ09');
$mode=GETPOST("mode");
$id=GETPOST('id','int');
$taskid=GETPOST('taskid');

$mine=0;
if ($mode == 'mine') $mine=1;

$projectid='';
$projectid=isset($_GET["id"])?$_GET["id"]:$_POST["projectid"];

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $projectid);

$now=dol_now();
$nowtmp=dol_getdate($now);
$nowday=$nowtmp['mday'];
$nowmonth=$nowtmp['mon'];
$nowyear=$nowtmp['year'];

$year=GETPOST('reyear')?GETPOST('reyear'):(GETPOST("year","int")?GETPOST("year","int"):date("Y"));
$month=GETPOST('remonth')?GETPOST('remonth'):(GETPOST("month","int")?GETPOST("month","int"):date("m"));
$day=GETPOST('reday')?GETPOST('reday'):(GETPOST("day","int")?GETPOST("day","int"):date("d"));
$day = (int) $day;
$week=GETPOST("week","int")?GETPOST("week","int"):date("W");

$search_categ=GETPOST("search_categ",'alpha');
$search_usertoprocessid=GETPOST('search_usertoprocessid', 'int');
$search_task_ref=GETPOST('search_task_ref', 'alpha');
$search_task_label=GETPOST('search_task_label', 'alpha');
$search_project_ref=GETPOST('search_project_ref', 'alpha');
$search_thirdparty=GETPOST('search_thirdparty', 'alpha');

$monthofday=GETPOST('addtimemonth');
$dayofday=GETPOST('addtimeday');
$yearofday=GETPOST('addtimeyear');

$daytoparse = $now;
if ($yearofday && $monthofday && $dayofday) $daytoparse=dol_mktime(0, 0, 0, $monthofday, $dayofday, $yearofday);	// xxxofday is value of day after submit action 'addtime'
else if ($year && $month && $day) $daytoparse=dol_mktime(0, 0, 0, $month, $day, $year);							// this are value submited after submit of action 'submitdateselect'

if (empty($search_usertoprocessid) || $search_usertoprocessid == $user->id)
{
    $usertoprocess=$user;
	$search_usertoprocessid=$usertoprocess->id;
}
elseif (search_usertoprocessid > 0)
{
    $usertoprocess=new User($db);
    $usertoprocess->fetch($search_usertoprocessid);
	$search_usertoprocessid=$usertoprocess->id;
}
else
{
	$usertoprocess=new User($db);
}

$object=new Task($db);


/*
 * Actions
 */

// Purge criteria
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $action = '';
    $search_categ='';
    $search_usertoprocessid = '';
    $search_task_ref = '';
    $search_task_label = '';
    $search_project_ref = '';
    $search_thirdparty = '';
}
if (GETPOST("button_search_x",'alpha') || GETPOST("button_search.x",'alpha') || GETPOST("button_search",'alpha'))
{
    $action = '';
}

if (GETPOST('submitdateselect'))
{
	$daytoparse = dol_mktime(0, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));

	$action = '';
}


if ($action == 'addtime' && $user->rights->projet->lire && GETPOST('assigntask'))
{
    $action = 'assigntask';

    if ($taskid > 0)
    {
		$result = $object->fetch($taskid, $ref);
		if ($result < 0) $error++;
    }
    else
    {
    	setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Task")), '', 'errors');
    	$error++;
    }
    if (! GETPOST('type'))
    {
    	setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), '', 'errors');
    	$error++;
    }
    if (! $error)
    {
    	$idfortaskuser=$usertoprocess->id;
		$result = $object->add_contact($idfortaskuser, GETPOST("type"), 'internal');

    	if ($result >= 0 || $result == -2)	// Contact add ok or already contact of task
    	{
			// Test if we are already contact of the project (should be rare but sometimes we can add as task contact without being contact of project, like when admin user has been removed from contact of project)
    		$sql='SELECT ec.rowid FROM '.MAIN_DB_PREFIX.'element_contact as ec, '.MAIN_DB_PREFIX.'c_type_contact as tc WHERE tc.rowid = ec.fk_c_type_contact';
    		$sql.=' AND ec.fk_socpeople = '.$idfortaskuser." AND ec.element_id = '.$object->fk_project.' AND tc.element = 'project' AND source = 'internal'";
    		$resql=$db->query($sql);
    		if ($resql)
    		{
    			$obj=$db->fetch_object($resql);
    			if (! $obj)	// User is not already linked to project, so we will create link to first type
    			{
    				$project = new Project($db);
    				$project->fetch($object->fk_project);
    				// Get type
    				$listofprojcontact=$project->liste_type_contact('internal');

    				if (count($listofprojcontact))
    				{
    					$typeforprojectcontact=reset(array_keys($listofprojcontact));
    					$result = $project->add_contact($idfortaskuser, $typeforprojectcontact, 'internal');
    				}
    			}
    		}
    		else
    		{
    			dol_print_error($db);
    		}
    	}
    }

	if ($result < 0)
	{
		$error++;
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorTaskAlreadyAssigned"), null, 'warnings');
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if (! $error)
	{
		setEventMessages("TaskAssignedToEnterTime", null);
		$taskid=0;
	}

	$action='';
}

if ($action == 'addtime' && $user->rights->projet->lire)
{
    $timespent_duration=array();

    if (is_array($_POST))
    {
        foreach($_POST as $key => $time)
        {
            if (intval($time) > 0)
            {
                // Hours or minutes of duration
                if (preg_match("/([0-9]+)duration(hour|min)/",$key,$matches))
                {
                    $id = $matches[1];
    				if ($id > 0)
    				{
    	                // We store HOURS in seconds
    	                if($matches[2]=='hour') $timespent_duration[$id] += $time*60*60;

    	                // We store MINUTES in seconds
    	                if($matches[2]=='min') $timespent_duration[$id] += $time*60;
    				}
                }
            }
        }
    }

    if (count($timespent_duration) > 0)
    {
    	foreach($timespent_duration as $key => $val)
    	{
	        $object->fetch($key);
		    $object->progress = GETPOST($key.'progress', 'int');
	        $object->timespent_duration = $val;
	        $object->timespent_fk_user = $user->id;
	        $object->timespent_note = GETPOST($key.'note');
	        if (GETPOST($key."hour") != '' && GETPOST($key."hour") >= 0)	// If hour was entered
	        {
	        	$object->timespent_date = dol_mktime(GETPOST($key."hour"),GETPOST($key."min"),0,$monthofday,$dayofday,$yearofday);
	        	$object->timespent_withhour = 1;
	        }
	        else
			{
	        	$object->timespent_date = dol_mktime(12,0,0,$monthofday,$dayofday,$yearofday);
			}

			if ($object->timespent_date > 0)
			{
				$result=$object->addTimeSpent($user);
			}
			else
			{
				setEventMessages("ErrorBadDate", null, 'errors');
				$error++;
				break;
			}

			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
				break;
			}
    	}

    	if (! $error)
    	{
	    	setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');

    	    // Redirect to avoid submit twice on back
        	header('Location: '.$_SERVER["PHP_SELF"].'?'.($projectid?'id='.$projectid:'').($search_usertoprocessid?'&search_usertoprocessid='.$search_usertoprocessid:'').($mode?'&mode='.$mode:'').'&year='.$yearofday.'&month='.$monthofday.'&day='.$dayofday);
        	exit;
    	}
    }
    else
    {
   	    setEventMessages($langs->trans("ErrorTimeSpentIsEmpty"), null, 'errors');
    }
}



/*
 * View
 */

$form=new Form($db);
$formother = new FormOther($db);
$formcompany=new FormCompany($db);
$formproject=new FormProjets($db);
$projectstatic=new Project($db);
$project = new Project($db);
$taskstatic = new Task($db);
$thirdpartystatic = new Societe($db);


$prev = dol_getdate($daytoparse - (24 * 3600));
$prev_year  = $prev['year'];
$prev_month = $prev['mon'];
$prev_day   = $prev['mday'];

$next = dol_getdate($daytoparse + (24 * 3600));
$next_year  = $next['year'];
$next_month = $next['mon'];
$next_day   = $next['mday'];

$title=$langs->trans("TimeSpent");
if ($mine || ($usertoprocess->id == $user->id)) $title=$langs->trans("MyTimeSpent");

$projectsListId = $projectstatic->getProjectsAuthorizedForUser($usertoprocess,(empty($usertoprocess->id)?2:0),1);  // Return all project i have permission on. I want my tasks and some of my task may be on a public projet that is not my project

if ($id)
{
    $project->fetch($id);
    $project->fetch_thirdparty();
}

$onlyopenedproject=1;	// or -1
$morewherefilter='';
if ($search_task_ref) $morewherefilter.=natural_search("t.ref", $search_task_ref);
if ($search_task_label) $morewherefilter.=natural_search("t.label", $search_task_label);
if ($search_thirdparty) $morewherefilter.=natural_search("s.nom", $search_thirdparty);
$tasksarray=$taskstatic->getTasksArray(0, 0, ($project->id?$project->id:0), $socid, 0, $search_project_ref, $onlyopenedproject, $morewherefilter, ($search_usertoprocessid?$search_usertoprocessid:0));    // We want to see all task of opened project i am allowed to see and that match filter, not only my tasks. Later only mine will be editable later.
$projectsrole=$taskstatic->getUserRolesForProjectsOrTasks($usertoprocess, 0, ($project->id?$project->id:0), 0, $onlyopenedproject);
$tasksrole=$taskstatic->getUserRolesForProjectsOrTasks(0, $usertoprocess, ($project->id?$project->id:0), 0, $onlyopenedproject);
//var_dump($tasksarray);
//var_dump($projectsrole);
//var_dump($taskrole);


llxHeader("",$title,"");

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num, '', 'title_project');

$param='';
$param.=($mode?'&mode='.$mode:'');
$param.=($search_project_ref?'&search_project_ref='.$search_project_ref:'');
$param.=($search_userassignedid > 0?'&search_userassignedid='.$search_usertoprocessid:'');

// Show navigation bar
$nav ='<a class="inline-block valignmiddle" href="?year='.$prev_year."&amp;month=".$prev_month."&amp;day=".$prev_day.$param.'">'.img_previous($langs->trans("Previous"))."</a>\n";
$nav.=" <span id=\"month_name\">".dol_print_date(dol_mktime(0,0,0,$month,$day,$year),"day")." </span>\n";
$nav.='<a class="inline-block valignmiddle" href="?year='.$next_year."&amp;month=".$next_month."&amp;day=".$next_day.$param.'">'.img_next($langs->trans("Next"))."</a>\n";
$nav.=" &nbsp; (<a href=\"?year=".$nowyear."&amp;month=".$nowmonth."&amp;day=".$nowday.$param."\">".$langs->trans("Today")."</a>)";
$nav.='<br>'.$form->select_date(-1,'',0,0,2,"addtime",1,0,1).' ';
$nav.=' <input type="submit" name="submitdateselect" class="button valignmiddle" value="'.$langs->trans("Refresh").'">';

$picto='calendarweek';


print '<form name="addtime" method="POST" action="'.$_SERVER["PHP_SELF"].($project->id > 0 ? '?id='.$project->id : '').'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addtime">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
$tmp = dol_getdate($daytoparse);
print '<input type="hidden" name="addtimeyear" value="'.$tmp['year'].'">';
print '<input type="hidden" name="addtimemonth" value="'.$tmp['mon'].'">';
print '<input type="hidden" name="addtimeday" value="'.$tmp['mday'].'">';

$head=project_timesheet_prepare_head($mode, $usertoprocess);
dol_fiche_head($head, 'inputperday', '', -1, 'task');

// Show description of content
print '<div class="hideonsmartphone">';
if ($mine || ($usertoprocess->id == $user->id)) print $langs->trans("MyTasksDesc").($onlyopenedproject?' '.$langs->trans("OnlyOpenedProject"):'').'<br>';
else
{
	if (empty($usertoprocess->id) || $usertoprocess->id < 0)
	{
		if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").($onlyopenedproject?' '.$langs->trans("OnlyOpenedProject"):'').'<br>';
		else print $langs->trans("ProjectsPublicTaskDesc").($onlyopenedproject?' '.$langs->trans("OnlyOpenedProject"):'').'<br>';
	}
}
if ($mine || ($usertoprocess->id == $user->id))
{
	print $langs->trans("OnlyYourTaskAreVisible").'<br>';
}
else
{
	print $langs->trans("AllTaskVisibleButEditIfYouAreAssigned").'<br>';
}
print '</div>';

dol_fiche_end();

print '<div class="floatright right'.($conf->dol_optimize_smallscreen?' centpercent':'').'">'.$nav.'</div>';     // We move this before the assign to components so, the default submit button is not the assign to.

print '<div class="float valignmiddle">';
$titleassigntask = $langs->trans("AssignTaskToMe");
if ($usertoprocess->id != $user->id) $titleassigntask = $langs->trans("AssignTaskToUser", $usertoprocess->getFullName($langs));
print '<div class="taskiddiv inline-block">';
$formproject->selectTasks($socid?$socid:-1, $taskid, 'taskid', 32, 0, 1, 1);
print '</div>';
print ' ';
print $formcompany->selectTypeContact($object, '', 'type','internal','rowid', 0, 'maxwidth200');
print '<input type="submit" class="button valignmiddle" name="assigntask" value="'.dol_escape_htmltag($titleassigntask).'">';
print '</div>';

print '<div class="clearboth" style="padding-bottom: 8px;"></div>';


$moreforfilter='';

// Filter on categories
/*if (! empty($conf->categorie->enabled))
{
	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.=$langs->trans('ProjectCategories'). ': ';
	$moreforfilter.=$formother->select_categories('project', $search_categ, 'search_categ', 1, 1, 'maxwidth300');
	$moreforfilter.='</div>';
}*/

// If the user can view user other than himself
$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('ProjectsWithThisUserAsContact'). ': ';
$includeonly='hierachyme';
if (empty($user->rights->user->user->lire)) $includeonly=array($user->id);
$moreforfilter.=$form->select_dolusers($search_usertoprocessid?$search_usertoprocessid:$usertoprocess->id, 'search_usertoprocessid', $user->rights->user->user->lire?0:0, null, 0, $includeonly, null, 0, 0, 0, '', 0, '', 'maxwidth200');
$moreforfilter.='</div>';

if (! empty($moreforfilter))
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';
}


print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'" id="tablelines3">'."\n";

print '<tr class="liste_titre_filter">';
print '<td class="liste_titre"><input type="text" size="4" name="search_project_ref" value="'.dol_escape_htmltag($search_project_ref).'"></td>';
if (! empty($conf->global->PROJECT_LINES_PERDAY_SHOW_THIRDPARTY)) print '<td class="liste_titre"><input type="text" size="4" name="search_thirdparty" value="'.dol_escape_htmltag($search_thirdparty).'"></td>';
print '<td class="liste_titre"><input type="text" size="4" name="search_task_ref" value="'.dol_escape_htmltag($search_task_ref).'"></td>';
print '<td class="liste_titre"><input type="text" size="4" name="search_task_label" value="'.dol_escape_htmltag($search_task_label).'"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
// Action column
print '<td class="liste_titre nowrap" align="right">';
$searchpicto=$form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ProjectRef").'</td>';
if (! empty($conf->global->PROJECT_LINES_PERDAY_SHOW_THIRDPARTY))
{
    print '<td>'.$langs->trans("ThirdParty").'</td>';
}
print '<td>'.$langs->trans("RefTask").'</td>';
print '<td>'.$langs->trans("LabelTask").'</td>';
print '<td align="right" class="maxwidth100">'.$langs->trans("PlannedWorkload").'</td>';
print '<td align="right" class="maxwidth100">'.$langs->trans("ProgressDeclared").'</td>';
/*print '<td align="right" class="maxwidth100">'.$langs->trans("TimeSpent").'</td>';
if ($usertoprocess->id == $user->id) print '<td align="right" class="maxwidth100">'.$langs->trans("TimeSpentByYou").'</td>';
else print '<td align="right" class="maxwidth100">'.$langs->trans("TimeSpentByUser").'</td>';*/
print '<td align="right" class="maxwidth100">'.$langs->trans("TimeSpent").'<br>('.$langs->trans("Everybody").')</td>';
print '<td align="right" class="maxwidth100">'.$langs->trans("TimeSpent").'</td>';
print '<td align="center">'.$langs->trans("HourStart").'</td>';
print '<td align="center">'.$langs->trans("Duration").'</td>';
print '<td align="right">'.$langs->trans("Note").'</td>';
print '<td align="center"></td>';
print "</tr>\n";


// By default, we can edit only tasks we are assigned to
$restrictviewformytask=(empty($conf->global->PROJECT_TIME_SHOW_TASK_NOT_ASSIGNED)?1:0);

if (count($tasksarray) > 0)
{
	$j=0;
	projectLinesPerDay($j, 0, $usertoprocess, $tasksarray, $level, $projectsrole, $tasksrole, $mine, $restrictviewformytask, $daytoparse);
}
else
{
	print '<tr><td colspan="13">'.$langs->trans("NoTasks").'</td></tr>';
}
print "</table>";
print '</div>';

print '<div class="center">';
print '<input type="submit" class="button"'.($disabledtask?' disabled':'').' value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';


print '<script type="text/javascript">';
print "jQuery(document).ready(function () {\n";
print '		jQuery(".timesheetalreadyrecorded").tipTip({ maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50, content: \''.dol_escape_js($langs->trans("TimeAlreadyRecorded", $user->getFullName($langs))).'\'});';
print "});";
print '</script>';


llxFooter();

$db->close();
