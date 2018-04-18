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
 * Novitus HD Fiskal Printer Plugin
 *
 * @author Marcin Romanowski <marcin@nicram.net>
 *
 */

class LMSNovitusHDPlugin extends LMSPlugin
{
	const PLUGIN_NAME = 'Novitus HD Fiskal Printer Plugin';
	const PLUGIN_DESCRIPTION = 'Plugin Fiskal Printer Novitus HD';
	const PLUGIN_AUTHOR = 'Marcin Romanowski <marcin@nicram.net>';
	const PLUGIN_DIRECTORY_NAME = 'LMSNovitusHDPlugin';
	const PLUGIN_MODULES_DIRECTORY = 'modules';
	const PLUGIN_DBVERSION = '2018032700';

	/**
	 * Registers hooks handlers
	 */
	function registerHandlers()
	{
		$this->handlers = [
			'modules_dir_initialized' => array(
				'class' => 'InitHandler',
				'method' => 'modulesDirInit'
			),
			'smarty_initialized' => array(
				'class' => 'InitHandler',
				'method' => 'smartyInit'
			),
			'menu_initialized' => array(
				'class' => 'InitHandler',
				'method' => 'menuEntry'
			),
		];
	}
}