<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once(__DIR__ . '/../../config.php');

require_login();
require_sesskey();

global $DB, $USER;

// Check if user is a temporary user.
if (strpos($USER->username, 'tempuser') !== 0) {
	redirect($CFG->wwwroot, get_string('error'), null, \core\output\notification::NOTIFY_ERROR);
}

// Get the submitted email.
$email = required_param('email', PARAM_EMAIL);
$returnurl = optional_param('returnurl', $CFG->wwwroot, PARAM_LOCALURL);

// Validate email format.
if (!validate_email($email)) {
	redirect($returnurl, get_string('invalidemail', 'block_conversion'), null, \core\output\notification::NOTIFY_ERROR);
}

// Check if email already exists.
$emailexists = $DB->record_exists('user', ['email' => $email]);
if ($emailexists) {
	redirect($returnurl, get_string('emailexists', 'block_conversion'), null, \core\output\notification::NOTIFY_ERROR);
}

// Use full email as username (guaranteed to be unique since we checked email doesn't exist).
$username = $email;

// Parse firstname and lastname from email local part (before @).
$localpart = strstr($email, '@', true);
$localpart = clean_param($localpart, PARAM_TEXT);

// Check if local part contains a dot (firstname.lastname pattern).
if (strpos($localpart, '.') !== false) {
	$parts = explode('.', $localpart, 2);
	$firstname = ucfirst($parts[0]);
	$lastname = ucfirst($parts[1]);
} else {
	// No dot found, use entire local part as firstname, keep original lastname.
	$firstname = ucfirst($localpart);
	$lastname = $USER->lastname; // Keep the date-based lastname.
}

// Update the user's email, username, firstname, and lastname.
$USER->email = $email;
$USER->username = $username;
$USER->firstname = $firstname;
$USER->lastname = $lastname;

$DB->set_field('user', 'email', $email, ['id' => $USER->id]);
$DB->set_field('user', 'username', $username, ['id' => $USER->id]);
$DB->set_field('user', 'firstname', $firstname, ['id' => $USER->id]);
$DB->set_field('user', 'lastname', $lastname, ['id' => $USER->id]);

// Redirect back with success message.
redirect(
	$returnurl,
	get_string('accountconverted', 'block_conversion'),
	null,
	\core\output\notification::NOTIFY_SUCCESS
);