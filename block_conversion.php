<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

class block_conversion extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_conversion');
    }

    public function get_content() {
        global $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Check if current user is a temporary user.
        $istempuser = isloggedin() && !isguestuser() && strpos($USER->username, 'tempuser') === 0;

        if ($istempuser) {
            // Display conversion form for temporary users.
            $this->display_temp_user_conversion();
            return $this->content;
        }

        // Show nothing for logged in users (excluding guests).
        if (isloggedin() && !isguestuser()) {
            return $this->content;
        }

        // Display message and button for non-logged in users and guests.
        $this->display_login_form();

        return $this->content;
    }

    /**
     * Display the login form for non-logged in users.
     */
    private function display_login_form() {
        $url = new moodle_url('/blocks/conversion/create_temp_user.php');
        
        $this->content->text = html_writer::tag('p', 
            get_string('loginmessage', 'block_conversion')
        );
        
        $this->content->text .= html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $url->out()
        ]);
        
        $this->content->text .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey()
        ]);
        
        // Add course ID if we're in a course context.
        if ($this->page->course && $this->page->course->id != SITEID) {
            $this->content->text .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'courseid',
                'value' => $this->page->course->id
            ]);
        }
        
        // Add return URL to redirect back to current page.
        $this->content->text .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'returnurl',
            'value' => $this->page->url->out(false)
        ]);
        
        $this->content->text .= html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('loginbutton', 'block_conversion'),
            'class' => 'btn btn-primary'
        ]);
        
        $this->content->text .= html_writer::end_tag('form');
    }

    /**
     * Display the conversion form for temporary users.
     */
    private function display_temp_user_conversion() {
        $url = new moodle_url('/blocks/conversion/convert_temp_user.php');
        
        $this->content->text = html_writer::tag('p', 
            get_string('tempusermessage', 'block_conversion')
        );
        
        $this->content->text .= html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $url->out(),
            'class' => 'block-conversion-form'
        ]);
        
        $this->content->text .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey()
        ]);
        
        // Add return URL to redirect back to current page.
        $this->content->text .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'returnurl',
            'value' => $this->page->url->out(false)
        ]);
        
        // Email input field.
        $this->content->text .= html_writer::start_div('form-group');
        $this->content->text .= html_writer::empty_tag('input', [
            'type' => 'email',
            'name' => 'email',
            'placeholder' => get_string('emailplaceholder', 'block_conversion'),
            'required' => 'required',
            'class' => 'form-control d-inline-block',
            'style' => 'width: auto; display: inline-block;'
        ]);
        
        // Submit button.
        $this->content->text .= ' ';
        $this->content->text .= html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('convertbutton', 'block_conversion'),
            'class' => 'btn btn-primary'
        ]);
        $this->content->text .= html_writer::end_div();
        
        $this->content->text .= html_writer::end_tag('form');
    }

    public function applicable_formats() {
        return [
            'course-view' => true,
            'site' => true,
            'my' => false
        ];
    }

    public function has_config() {
        return false;
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function instance_can_be_hidden() {
        return false;
    }

    public function hide_header() {
        return false;
    }
}