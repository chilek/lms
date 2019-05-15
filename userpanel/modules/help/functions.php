<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

function get_solution($id)
{
    global $DB;
    return $DB->GetRow('SELECT title,body,reference,id FROM up_help WHERE id = ?', array($id));
}

function get_first_solution()
{
    global $DB;
    return $DB->GetOne('SELECT id FROM up_help WHERE reference IS NULL');
}

function update_solution($id, $title, $body)
{
    global $DB;
    $DB->Execute('UPDATE up_help SET title=?, body=? WHERE id=?', array($title, $body, $id));
}

function add_solution($refid, $title, $body)
{
    global $DB;
    $DB->Execute(
        'INSERT INTO up_help (reference, title, body) VALUES (?, ?, ?)',
        array(empty($refid) ? null : $refid, $title, $body)
    );
}

function delete_solution($id)
{
    global $DB;
    $DB->Execute('DELETE FROM up_help WHERE id=?', array($id));
}

function get_questions($id)
{
    global $DB;
    if (empty($id)) {
        return $DB->GetAll('SELECT id,title FROM up_help WHERE reference IS NULL');
    } else {
        return $DB->GetAll('SELECT id,title FROM up_help WHERE reference = ?', array($id));
    }
}

function are_questions($id)
{
    global $DB;
    if (($DB->GetOne('SELECT id FROM up_help WHERE reference = ? LIMIT 1', array($id))) >0) {
        return true;
    } else {
        return false;
    }
}

function fetch_questions($id)
{
    $table = array();
    if ($questions = get_questions($id)) {
        foreach ($questions as $question) {
            if (are_questions($question['id'])) {
                $table[$question['id']] = $question['title'];
                $table['next'.$question['id']] = fetch_questions($question['id']);
            } else {
                $table[$question['id']] = $question['title'];
            }
        }
    }
    return $table;
}

function module_main()
{
    global $SMARTY,$_GET;
    if (isset($_GET['pr'])) {
        $problem = $_GET['pr'];
    } else {
        $problem = get_first_solution();
    }
    $solution = get_solution($problem);
    $questions = get_questions($problem);

    $SMARTY->assign('solution', $solution);
    $SMARTY->assign('questions', $questions);
    $SMARTY->display('module:help.html');
}

if (defined('USERPANEL_SETUPMODE')) {
    function module_setup()
    {
        global $SMARTY,$LMS;
        $questions = fetch_questions(0);
        $treefile = ConfigHelper::getConfig('directories.userpanel_dir').'/modules/help/templates/tree.html';
        $SMARTY->assign('tree', $questions);
        $SMARTY->assign('treefile', $treefile);
        $SMARTY->display('module:help:setup.html');
    }

    function module_edit()
    {
        global $SMARTY,$_GET;
        $solution = get_solution($_GET['nr']);
        $SMARTY->assign('solution', $solution);
        $SMARTY->display('module:help:edit.html');
    }

    function module_postedit()
    {
        global $SMARTY,$_POST;
        if ($_POST['title'] == '') {
            $error['title'] = trans('This cannot be empty');
        }
        if ($_POST['body'] == '') {
            $error['body'] = trans('This cannot be empty');
        }
        if (!$error) {
            update_solution($_POST['id'], $_POST['title'], $_POST['body']);
            header('Location: ?m=userpanel&module=help');
        } else {
            $solution['id'] = $_POST['id'];
            $solution['title'] = $_POST['title'];
            $solution['body'] = $_POST['body'];
            $SMARTY->assign('solution', $solution);
            $SMARTY->assign('error', $error);
            $SMARTY->display('module:help:edit.html');
        }
    }

    function module_delete()
    {
        global $SMARTY,$_GET;
        delete_solution($_GET['nr']);
        header('Location: ?m=userpanel&module=help');
    }

    function module_add()
    {
        global $SMARTY,$_GET;
        $solution['refid'] = !empty($_GET['refid']) ? $_GET['refid'] : 0;
        $SMARTY->assign('solution', $solution);
        $SMARTY->display('module:help:add.html');
    }

    function module_postadd()
    {
        global $SMARTY,$_POST;
        if ($_POST['refid'] == '') {
            $_POST['refid'] = 0;
        }
        if ($_POST['title'] == '') {
            $error['title'] = trans('This cannot be empty');
        }
        if ($_POST['body'] == '') {
            $error['body'] = trans('This cannot be empty');
        }
        if (!$error) {
            add_solution($_POST['refid'], $_POST['title'], $_POST['body']);
            header('Location: ?m=userpanel&module=help');
        } else {
            $solution['refid'] = $_POST['refid'];
            $solution['title'] = $_POST['title'];
            $solution['body'] = $_POST['body'];
            $SMARTY->assign('solution', $solution);
            $SMARTY->assign('error', $error);
            $SMARTY->display('module:help:add.html');
        }
    }
}
