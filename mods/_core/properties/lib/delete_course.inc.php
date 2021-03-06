<?php
/************************************************************************/
/* ATutor																*/
/************************************************************************/
/* Copyright (c) 2002-2010                                              */
/* Inclusive Design Institute                                           */
/* http://atutor.ca                                                     */
/* This program is free software. You can redistribute it and/or        */
/* modify it under the terms of the GNU General Public License          */
/* as published by the Free Software Foundation.                        */
/************************************************************************/
// $Id$


function delete_course($course, $material) {
	global $db, $moduleFactory;

	$delete_groups = FALSE; // whether or not to delete the groups as well

	$groups = array();

	//unset s_cid var
	if ($material === TRUE) {
		unset($_SESSION['s_cid']);
		$delete_groups = TRUE;
		// get a list of groups in an array to send to module::delete()
		// get groups
		$sql	= "SELECT G.group_id FROM %sgroups G INNER JOIN %sgroups_types T USING (type_id) WHERE T.course_id=%d";
		$group_rows = queryDB($sql, array(TABLE_PREFIX, TABLE_PREFIX, $course));
		
        foreach($group_rows as $group_row){
			$groups[] = $group_row['group_id'];
		}
	}

	$module_list = $moduleFactory->getModules(AT_MODULE_STATUS_ENABLED | AT_MODULE_STATUS_DISABLED);
	$keys = array_keys($module_list);

	//loop through mods and call delete function
	foreach ($keys as $module_name) {
		if ($module_name == '_core/groups') {
			continue;
		}
		if ($module_name == '_core/enrolment') {
			continue;
		}
		$module = $module_list[$module_name];

		if (($material === TRUE) || isset($material[$module_name])) {
			$module->delete($course, $groups);  ////// Breaks here
		}
	}

	// groups and enrollment must be deleted last because that info is used by other modules

	if (($material === TRUE) || isset($material['_core/groups'])) {
		$module =& $moduleFactory->getModule('_core/groups');
		$module->delete($course, $groups);
	}
	if (($material === TRUE) || isset($material['_core/enrolment'])) {
		$module =& $moduleFactory->getModule('_core/enrolment');
		$module->delete($course, $groups);
	}

	if ($material === TRUE) {
		// delete actual course
		$sql = "DELETE FROM %scourses WHERE course_id=%d";
		$result = queryDB($sql, array(TABLE_PREFIX, $course));
		global $sqlout;
		write_to_log(AT_ADMIN_LOG_DELETE, 'courses', $result, $sqlout);
	}
}
?>