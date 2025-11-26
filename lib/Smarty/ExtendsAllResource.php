<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2025 LMS Developers
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

namespace Lms\Smarty;

use Smarty\Smarty;
use Smarty\Resource\CustomPlugin;

/**
 * Resource "extendsall:" dla Smarty 5, zgodny z PHP 7.4.
 *
 * Odpowiednik:
 *   class Smarty_Resource_Extendsall extends Smarty_Internal_Resource_Extends
 *
 * Użycie:
 *   {extends file="extendsall:page.tpl"}
 *   {include file="extendsall:box.tpl"}
 */
class ExtendsAllResource extends CustomPlugin
{
    /**
     * @var Smarty
     */
    private $smarty;

    /**
     * @param Smarty $smarty
     */
    public function __construct(Smarty $smarty)
    {
        $this->smarty = $smarty;
    }

    /**
     * Generuje „wirtualny” szablon, który tak naprawdę tylko robi:
     * {extends file="extends:file:[k]name.tpl|..."}.
     *
     * @param string $name   np. "page.tpl"
     * @param string $source wynikowy kod szablonu
     * @param int    $mtime  timestamp wirtualnego szablonu
     */
    protected function fetch($name, &$source, &$mtime)
    {
        $templateDirs = $this->smarty->getTemplateDir();
        $components   = array();
        $timestamp    = 0;

        foreach ($templateDirs as $key => $directory) {
            $directory = rtrim($directory, '/\\');
            $file      = $directory . DIRECTORY_SEPARATOR . $name;

            if (!is_file($file)) {
                continue;
            }

            // bracket-syntax: file:[key]name.tpl
            $components[] = 'file:[' . $key . ']' . $name;

            $t = @filemtime($file);
            if ($t !== false && $t > $timestamp) {
                $timestamp = $t;
            }
        }

        if (!$components) {
            // brak jakiegokolwiek wariantu – szablon logicznie „nie istnieje”
            $source = '';
            $mtime  = time();
            return;
        }

        // zachowujemy tę samą zmianę kolejności, co w Twoim oryginale
        $components = array_reverse($components, true);

        // budujemy nazwę dla wbudowanego resource "extends:"
        $extendsName = 'extends:' . implode('|', $components);

        // tworzymy mini-szablon, który tylko robi {extends}
        $source = '{extends file=\'' . addslashes($extendsName) . '\'}';

        $mtime = $timestamp ?: time();
    }

    /**
     * Timestamp wirtualnego szablonu extendsall:name
     *
     * @param string $name
     * @return int
     */
    protected function fetchTimestamp($name)
    {
        $templateDirs = $this->smarty->getTemplateDir();
        $timestamp    = 0;

        foreach ($templateDirs as $directory) {
            $directory = rtrim($directory, '/\\');
            $file      = $directory . DIRECTORY_SEPARATOR . $name;

            if (!is_file($file)) {
                continue;
            }

            $t = @filemtime($file);
            if ($t !== false && $t > $timestamp) {
                $timestamp = $t;
            }
        }

        return $timestamp ?: time();
    }
}
