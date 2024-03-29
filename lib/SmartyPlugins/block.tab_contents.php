<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

function smarty_block_tab_contents($params, $content, $template, $repeat)
{
    if (!$repeat) {
        $id = $params['id'] ?? null;
        $class = $params['class'] ?? null;

        return '
			<div class="lms-ui-tab-contents lms-ui-multi-check' . ($class ? ' ' . $class : '')
                . '"' . ($id ? ' id="' . $id . '"' : '') . ' style="display: none;">'
                . $content . '
			</div>
			<script>
		        (function() {
		            var state = getStorageItem("' . $id . '", "local");
		            if (state == "1") {
		                $("#' . $id . '").show()
		            } else {
                        if (getCookie("' . $id . '") == "1") {
                            $("#' . $id . '").show();
                            setCookie("' . $id . '", "0", "0");
                            setStorageItem("' . $id . '", "1", "local");
                        }
		            }
		        })();
			</script>';
    }
}
