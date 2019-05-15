<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2013 LMS Developers
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
 * ConfigVariable
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class ConfigVariable
{
    /**
     * @var string Variable name
     */
    protected $name;
    
    /**
     * @var string Variable value
     */
    protected $value;
    
    /**
     * @var string Variable comment
     */
    protected $comment;
    
    /**
     * Constructs config variable
     *
     * @param string $name Variable name
     * @param string $value Variable value
     * @param string $comment Variable comment
     */
    function __construct($name, $value, $comment = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->comment = $comment;
    }
    
    /**
     * Returns variable name
     *
     * @return string
     */
    public function getVariable()
    {
        return $this->name;
    }

    /**
     * Returns variable value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns variable comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }
}
