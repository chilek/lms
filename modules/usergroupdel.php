<?php
/**
 * @author Maciej_Wawryk
 */

if (isset($_GET['is_sure'])) {
    $LMS->UsergroupDelete($_GET['id']);
}

$SESSION->redirect('?m=usergrouplist');
