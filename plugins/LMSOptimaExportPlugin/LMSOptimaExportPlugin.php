<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2015 LMS Developers
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

/**
 * LMSOptimaExportPlugin
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class LMSOptimaExportPlugin extends LMSPlugin {
	const plugin_directory_name = 'LMSOptimaExportPlugin';
	const PLUGIN_NAME = 'Optima Export';
	const PLUGIN_DESCRIPTION = 'Financial data export to optima format';
	const PLUGIN_AUTHOR = 'Tomasz Chiliński &lt;tomasz.chilinski@chilan.com&gt;';

	public function registerHandlers() {
		$this->handlers = array(
			'smarty_initialized' => array(
				'class' => 'OptimaExportInitHandler',
				'method' => 'smartyInit',
			),
			'modules_dir_initialized' => array(
				'class' => 'OptimaExportInitHandler',
				'method' => 'ModulesDirInit',
			),
			'menu_initialized' => array(
				'class' => 'OptimaExportInitHandler',
				'method' => 'menuInit',
			),
			'access_table_initialized' => array(
				'class' => 'OptimaExportInitHandler',
				'method' => 'accessTableInit',
			),
		);
	}
}

?>
