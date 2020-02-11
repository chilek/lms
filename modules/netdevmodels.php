<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

$layout['pagetitle'] = trans("Network device producers and models");
$listdata = $modellist = array();
$producerlist = $DB->GetAll('SELECT id, name FROM netdeviceproducers ORDER BY name ASC');


if (!isset($_GET['p_id'])) {
    $SESSION->restore('ndpid', $pid);
} else {
    $pid = intval($_GET['p_id']);
}
$SESSION->save('ndpid', $pid);

if (!isset($_GET['page'])) {
    $SESSION->restore('ndlpage', $_GET['page']);
}

if ($pid) {
    $producerinfo = $DB->GetRow('SELECT p.id , p.alternative_name FROM netdeviceproducers p WHERE p.id = ?', array($pid));
} else {
    $producerinfo = array();
}

$listdata['pid'] = $pid; // producer id

function cancel_producer()
{
    $obj = new xajaxResponse();

    $obj->assign("id_producer", "value", "");
    $obj->assign("id_producername", "value", "");
    $obj->assign("id_alternative_name", "value", "");
    $obj->script("removeClass(xajax.$('id_producername'),'alert');");
    $obj->script("xajax.$('div_produceredit').style.display='none';");

    return $obj;
}


function add_producer()
{
    $obj = new xajaxResponse();

    $obj->script("xajax.$('div_produceredit').style.display='';");
    $obj->script("removeClass(xajax.$('id_producername'),'alert');");
    $obj->assign("id_action_name", "innerHTML", trans('New producer'));
    $obj->assign("id_producer", "value", "");
    $obj->assign("id_producername", "value", "");
    $obj->assign("id_alternative_name", "value", "");
    $obj->script("xajax.$('id_producername').focus();");

    return $obj;
}

function edit_producer($id)
{
    global $DB;
    $obj = new xajaxResponse();

    $producer = $DB->GetRow(
        'SELECT * FROM netdeviceproducers WHERE id = ?',
        array($id)
    );

    $obj->script("xajax.$('div_produceredit').style.display='';");
    $obj->script("removeClass(xajax.$('id_producername'),'alert');");
    $obj->assign("id_action_name", "innerHTML", trans('Producer edit: $a', $producer['name']));

    $obj->assign("id_producer", "value", $producer['id']);
    $obj->assign("id_producername", "value", $producer['name']);
    $obj->assign("id_alternative_name", "value", $producer['alternative_name']);
    $obj->script("xajax.$('id_producername').focus();");

    return $obj;
}

function save_producer($forms)
{
    global $LMS;

    $DB = LMSDB::getInstance();
    $obj = new xajaxResponse();

    $form = $forms['produceredit'];
    $formid = $form['id'];
    $error = false;

    $obj->script("removeClass(xajax.$('id_producername'),'alert');");

    if (empty($form['name'])) {
        $error = true;
        $obj->setEvent("id_producername", "onmouseover", "popup('<span class=\\\"red bold\\\">" . trans("Producer name is required!") . "</span>')");
    }

    if (!$error) {
        if (!$form['id']) {
            $error = ($DB->GetOne(
                'SELECT COUNT(*) FROM netdeviceproducers WHERE name = ?',
                array(strtoupper($form['name']))
            ) ? true : false);
        } else {
            $error = ($DB->GetOne(
                'SELECT COUNT(*) FROM netdeviceproducers WHERE name = ? AND id <> ? ',
                array(strtoupper($form['name']), $form['id'])
            ) ? true : false);
        }

        if ($error) {
            $obj->setEvent("id_producername", "onmouseover", "popup('<span class=\\\"red bold\\\">" . trans("Producer already exists!") . "</span>')");
        }
    }

    $hook_data = $LMS->executeHook(
        'netdevproducer_validation_before_update',
        array(
            'netdevproducerdata' => $form,
            'error' => $error,
        )
    );
    $form = $hook_data['netdevproducerdata'];
    $error = $hook_data['error'];

    if ($error) {
        $obj->script("addClass(xajax.$('id_producername'),'alert');");
        $obj->script("xajax.$('id_producername').focus();");
    } else {
        if ($form['id']) {
            $DB->Execute(
                'UPDATE netdeviceproducers SET name = ?, alternative_name = ? WHERE id = ?',
                array($form['name'],
                    ($form['alternative_name'] ? $form['alternative_name'] : null),
                    $form['id']
                )
            );
            $obj->script("xajax_cancel_producer();");
            $obj->script("self.location.href='?m=netdevmodels&page=1&p_id=$formid';");
        } else {
            $DB->Execute(
                'INSERT INTO netdeviceproducers (name, alternative_name) VALUES (?, ?)',
                array($form['name'],
                    ($form['alternative_name'] ? $form['alternative_name'] : null)
                )
            );
            $form['id'] = $DB->getLastInsertId('netdeviceproducers');

            $obj->script("xajax_cancel_producer();");
            $obj->script("self.location.href='?m=netdevmodels&page=1&p_id=" . $form['id'] . "';");
        }

        $hook_data = $LMS->executeHook(
            'netdevproducer_after_update',
            array(
                'netdevproducerdata' => $form,
            )
        );
    }

    return $obj;
}

function delete_producer($id)
{
    global $LMS;

    $DB = LMSDB::getInstance();
    $obj = new xajaxResponse();

    $hook_data = $LMS->executeHook(
        'netdevproducer_validation_before_delete',
        array(
            'id' => $id,
            'error' => array(),
        )
    );
    $error = $hook_data['error'];

    if (!$error) {
        $DB->Execute('DELETE FROM netdeviceproducers WHERE id = ?', array($id));

        $hook_data = $LMS->executeHook(
            'netdevproducer_after_delete',
            array(
                'id' => $id,
                'error' => array(),
            )
        );

        $obj->script("self.location.href='?m=netdevmodels&page=1&p_id=';");
    } else {
        $obj->script("self.location.href='?m=netdevmodels&page=1&p_id=" . $id . "';");
    }

    return $obj;
}


function cancel_model()
{
    $obj = new xajaxResponse();

    $obj->assign("id_model", "value", "");
    $obj->assign("id_modelname", "value", "");
    $obj->assign("id_model_alternative_name", "value", "");
    $obj->script("removeClass(xajax.$('id_model_name'),'alert');");
    $obj->script("xajax.$('div_modeledit').style.display='none';");

    return $obj;
}

function add_model()
{
    $obj = new xajaxResponse();

    $obj->script("xajax.$('div_modeledit').style.display='';");
    $obj->script("removeClass(xajax.$('id_model_name'),'alert');");
    $obj->assign("id_model_action_name", "innerHTML", trans("New model"));
    $obj->assign("id_model", "value", "");
    $obj->assign("id_model_name", "value", "");
    $obj->assign("id_model_alternative_name", "value", "");
    $obj->script("xajax.$('id_model_name').focus();");

    return $obj;
}

function edit_model($id)
{
    global $LMS;

    $DB = LMSDB::getInstance();
    $SMARTY = LMSSmarty::getInstance();

    $obj = new xajaxResponse();

    $model = $DB->GetRow('SELECT * FROM netdevicemodels WHERE id = ?', array($id));

    $hook_data = $LMS->executeHook(
        'netdevmodel_edit_before_display',
        array(
            'netdevmodeldata' => $model,
            'xajaxResponse' => $obj,
        )
    );
    $model = $hook_data['netdevmodeldata'];

    $obj->script("$('#div_modeledit').show();");
    $obj->script("$('#id_model_name').removeClass('alert');");
    $obj->assign("id_model_action_name", "innerHTML", trans('Model edit: $a', $model['name']));
    $obj->assign("id_model", "value", $model['id']);
    $obj->assign("id_model_name", "value", $model['name']);
    $obj->assign("id_model_alternative_name", "value", $model['alternative_name']);
    $obj->script("$('#id_model_name').focus();");
    $SMARTY->assign('restore', 1);
    $SMARTY->assign('attachmenttype', "netdevmodelid");
    $SMARTY->assign('attachmentresourceid', $model['id']);
    $filecontainers = array(
        'netdevmodelid' => array(
            'id' => $model['id'],
            'prefix' => trans('Model attachments'),
            'containers' => $LMS->GetFileContainers('netdevmodelid', $model['id']),
        ),
    );
    $SMARTY->assign('filecontainers', $filecontainers);
    $SMARTY->assign('attachment_support_already_loaded', true);
    $obj->assign('netdevmodel-attachements', "innerHTML", $SMARTY->fetch('attachments.html'));
    $obj->call('init_titlebars', '#netdevmodel-attachements .lmsbox-titlebar');
    $obj->call('init_attachment_lists', '#netdevmodel-attachements');
    $obj->script('new lmsFileUpload("files-netdevmodelid", "upload-form-netdevmodelid")');
    $obj->script("$('#attachmentpanel-netdevmodelid').show();");

    return $obj;
}

function save_model($forms)
{
    global $LMS;

    $DB = LMSDB::getInstance();
    $obj = new xajaxResponse();

    $form = $forms['modeledit'];
    $formid = intval($form['id']);
    $pid = intval($form['pid']);
    $error = false;

    $obj->script("removeClass(xajax.$('id_model_name'),'alert');");

    if (empty($form['name'])) {
        $error = true;
        $obj->setEvent("id_model_name", "onmouseover", "popup('<span class=\\\"red bold\\\">" . trans("Model name is required!") . "</span>')");
    }

    if (!$error) {
        if (!$form['id']) {
            $error = ($DB->GetOne(
                'SELECT COUNT(*) FROM netdevicemodels WHERE netdeviceproducerid = ? AND UPPER(name) = ? ',
                array($pid, strtoupper($form['name']))
            ) ? true : false);
        } else {
            $error = ($DB->GetOne(
                'SELECT COUNT(*) FROM netdevicemodels WHERE id <> ? AND netdeviceproducerid = ? AND UPPER(name) = ?',
                array($formid, $pid, strtoupper($form['name']))
            ) ? true : false);
        }

        if ($error) {
            $obj->setEvent("id_model_name", "onmouseover", "popup('<span class=\\\"red bold\\\">" . trans("Model already exists!") . "</span>')");
        }
    }

    $hook_data = $LMS->executeHook(
        'netdevmodel_validation_before_update',
        array(
            'netdevmodeldata' => $form,
            'error' => $error,
        )
    );
    $form = $hook_data['netdevmodeldata'];
    $error = $hook_data['error'];

    if ($error) {
        $obj->script("addClass(xajax.$('id_model_name'),'alert');");
        $obj->script("xajax.$('id_model_name').focus();");
    } else {
        if ($formid) {
            $DB->Execute(
                'UPDATE netdevicemodels SET name = ?, alternative_name = ? WHERE id = ?',
                array($form['name'],
                    ($form['alternative_name'] ? $form['alternative_name'] : null),
                    $formid,
                )
            );
            $obj->script("xajax_cancel_model();");
            $obj->script("self.location.href='?m=netdevmodels&page=1&p_id=$pid';");
        } else {
            $DB->Execute(
                'INSERT INTO netdevicemodels (netdeviceproducerid, name, alternative_name) VALUES (?, ?, ?)',
                array($pid,
                    $form['name'],
                    ($form['alternative_name'] ? $form['alternative_name'] : null),
                )
            );
            $form['id'] = $DB->GetLastInsertID('netdevicemodels');

            $obj->script("xajax_cancel_model();");
            $obj->script("self.location.href='?m=netdevmodels&page=1&p_id=$pid';");
        }

        $hook_data = $LMS->executeHook(
            'netdevmodel_after_update',
            array(
                'netdevmodeldata' => $form,
            )
        );
    }

    return $obj;
}

function delete_model($id)
{
    global $LMS;

    $DB = LMSDB::getInstance();
    $obj = new xajaxResponse();

    $id = intval($id);

    $pid = $DB->GetOne(
        'SELECT p.id FROM netdevicemodels m
		JOIN netdeviceproducers p ON (p.id = m.netdeviceproducerid) WHERE m.id = ?',
        array($id)
    );

    if (!$DB->GetOne('SELECT COUNT(i.id) FROM netdevices i WHERE i.netdevicemodelid = ?', array($id))) {
        $hook_data = $LMS->executeHook(
            'netdevmodel_validation_before_delete',
            array(
                'id' => $id,
                'error' => array(),
            )
        );
        $error = $hook_data['error'];

        if (!$error) {
            $result = $DB->Execute('DELETE FROM netdevicemodels WHERE id = ?', array($id));

            if ($result) {
                $hook_data = $LMS->executeHook(
                    'netdevmodel_after_delete',
                    array(
                        'id' => $id,
                    )
                );
            }
        }
    }

    $obj->script("self.location.href='?m=netdevmodels&page=1&p_id=$pid';");

    return $obj;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array(
    'cancel_producer',
    'add_producer',
    'edit_producer',
    'save_producer',
    'delete_producer',
    'cancel_model',
    'add_model',
    'edit_model',
    'save_model',
    'delete_model',
));


function GetModelList($pid = null)
{
    global $DB;

    if (!$pid) {
        return null;
    }

    $list = $DB->GetAll(
        'SELECT m.id, m.name, m.alternative_name,
			(SELECT COUNT(i.id) FROM netdevices i WHERE i.netdevicemodelid = m.id) AS netdevcount
			FROM netdevicemodels m
			WHERE m.netdeviceproducerid = ?
			ORDER BY m.name ASC',
        array($pid)
    );

    if (!empty($list)) {
        foreach ($list as &$model) {
            $model['customlinks'] = array();
        }
        unset($model);
    }

    return $list;
}

$modellist = GetModelList($pid);

$listdata['total'] = empty($modellist) ? 0 : count($modellist);

$page = (!$_GET['page'] ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('phpui.netdevmodel_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('ndlpage', $page);

$hook_data = $LMS->executeHook(
    'netdevmodels_before_display',
    array(
        'producerlist' => $producerlist,
        'producerinfo' => $producerinfo,
        'modellist' => $modellist,
        'smarty' => $SMARTY,
    )
);
$producerlist = $hook_data['producerlist'];
$producerinfo = $hook_data['producerinfo'];
$modellist = $hook_data['modellist'];

if (isset($_GET['restore']) && isset($_GET['resourceid'])) {
    $restore = $_GET['restore'];
    $resourceid = $_GET['resourceid'];
    $SMARTY->assign('restore', $restore);
    $SMARTY->assign('resourceid', $resourceid);
}
$SESSION->save('backto', 'm=netdevmodels');

$SMARTY->assign('xajax', $LMS->RunXajax());
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('producerlist', $producerlist);
$SMARTY->assign('modellist', $modellist);
$SMARTY->assign('producerinfo', $producerinfo);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->display('netdev/netdevmodels.html');
