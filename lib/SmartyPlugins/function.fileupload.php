<?php

/*
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

function smarty_function_fileupload($params, $template) {
	static $vars = array('id', 'fileupload');

	$result = '';
	foreach ($vars as $var)
		if (array_key_exists($var, $params))
			$$var = $params[$var];
		else
			return $result;

	$form = isset($params['form']) ? $params['form'] : null;

	// special treatment of file upload errors marked in error associative array
	$tmpl = $template->getTemplateVars('error');
	if (isset($tmpl[$id . '_button']))
		$error_variable = $id . '_button';
	elseif (isset($tmpl['files']))
		$error_variable = 'files';
	if (isset($error_variable))
		$error_tip_params = array(
			'text' => $tmpl[$error_variable],
			'trigger' => $id . '_button'
		);

	$result = '<div class="fileupload" id="' . $id . '">
			<div class="fileupload" id="' . $id . '-progress-dialog" title="' . trans("Uploading files ...") . '" style="display: none;">
				<div style="padding: 10px;">' . trans("Uploading files to server ...") . '</div>
				<div class="fileupload-progressbar"><div class="fileupload-progress-label"></div></div>
			</div>
			<div class="lms-ui-button-fileupload-container">
				<button type="button" class="lms-ui-button-fileupload lms-ui-button' . (isset($error_tip_params) ? ' alert' : '') . '" id="' . $id . '_button" '
					. (isset($error_tip_params) ? Utils::tip($error_tip_params, $template) : '') . '><i></i> ' . trans("Select files") . '</button>
				<INPUT name="' . $id . '[]" type="file" multiple class="fileupload-select-btn" style="display: none;" ' . ($form ? ' form="' . $form . '"' : '') . '>
			</div>
			<div class="fileupload-files">';
	if (!empty($fileupload) && isset($fileupload[$id]))
		foreach ($fileupload[$id] as $fileidx => $file)
			$result .= '<div>
					<a href="#" class="fileupload-file"><i class="fas fa-trash"></i>
						' . $file['name'] . ' (' . $file['sizestr'] . ')
					</a>
					<input type="hidden" name="fileupload[' . $id . '][' . $fileidx . '][name]" value="' . $file['name'] . '" ' . ($form ? ' form="' . $form . '"' : '') . '>
					<input type="hidden" class="fileupload-file-size" name="fileupload[' . $id . '][' . $fileidx . '][size]" value="' . $file['size'] . '" ' . ($form ? ' form="' . $form . '"' : '') . '>
					<input type="hidden" name="fileupload[' . $id . '][' . $fileidx . '][type]" value="' . $file['type'] . '" ' . ($form ? ' form="' . $form . '"' : '') . '>
				</div>';
	$result .= '</div>
			<div class="fileupload-status alert bold">
			</div>
			<input type="hidden" class="fileupload-tmpdir" name="fileupload[' . $id . '-tmpdir]" value="' . $fileupload[$id . '-tmpdir'] . '" ' . ($form ? ' form="' . $form . '"' : '') . '>
		</div>';
	$result .= '<script>
			$(function() {
				new lmsFileUpload("' . $id . '", "' . $form . '");
			});
		</script>';

	return $result;
}

?>
