<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once(__DIR__ . '/../../config.php');

require_sesskey();

// Get current date for lastname.
$today = date('Y-m-d');

// Find the next auto-increment number for today.
global $DB;
$lastnamepattern = $DB->sql_like('lastname', ':pattern');
$sql = "SELECT lastname FROM {user} WHERE $lastnamepattern ORDER BY lastname DESC";
$params = ['pattern' => $today . '%'];
$records = $DB->get_records_sql($sql, $params, 0, 1);

$increment = 1;
if ($records) {
    $record = reset($records);
    // Extract increment from lastname like "2025-11-27-5".
    if (preg_match('/' . preg_quote($today, '/') . '-(\d+)$/', $record->lastname, $matches)) {
        $increment = intval($matches[1]) + 1;
    }
}

$lastname = $today . '-' . $increment;

// Create unique username and email with timestamp.
$timestamp = time();
$username = 'tempuser' . $timestamp;
$email = 'tempuser' . $timestamp . '@tempo.rary';

// Create the user.
$user = new stdClass();
$user->username = $username;
$user->firstname = 'Temp user';
$user->lastname = $lastname;
$user->email = $email;
$user->auth = 'manual'; // Using 'manual' as 'magic' auth plugin may not be installed.
$user->confirmed = 1;
$user->mnethostid = $CFG->mnet_localhost_id;
$user->password = hash_internal_user_password(random_string(20));
$user->timecreated = time();
$user->timemodified = time();
$user->lastlogin = 0;
$user->firstaccess = 0;
$user->lastaccess = 0;

$user->id = $DB->insert_record('user', $user);
$user = $DB->get_record('user', ['id' => $user->id]); // Get complete user object.

// Log the user in.
complete_user_login($user);

// Enroll user in course if courseid is provided.
$courseid = optional_param('courseid', 0, PARAM_INT);
if ($courseid && $courseid != SITEID) {
    // Get the course.
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    
    // Get the default student role.
    $studentrole = $DB->get_record('role', ['shortname' => 'student']);
    
    if ($studentrole) {
        // Get manual enrolment plugin instance for this course.
        $enrol = enrol_get_plugin('manual');
        
        if ($enrol) {
            // Get the manual enrolment instance for this course.
            $instance = $DB->get_record('enrol', [
                'courseid' => $courseid,
                'enrol' => 'manual'
            ], '*', IGNORE_MISSING);
            
            // If no manual enrolment instance exists, create one.
            if (!$instance) {
                $instanceid = $enrol->add_instance($course);
                $instance = $DB->get_record('enrol', ['id' => $instanceid]);
            }
            
            // Enrol the user.
            $enrol->enrol_user($instance, $user->id, $studentrole->id);
        }
    }
}

// Get return URL or default to homepage.
$returnurl = optional_param('returnurl', $CFG->wwwroot, PARAM_LOCALURL);

// Add success notification.
redirect(
    $returnurl,
    get_string('tempusercreated', 'block_conversion'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);