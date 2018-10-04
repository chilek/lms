<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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
					. (isset($error_tip_params) ? Utils::tip($error_tip_params, $template) : '') . '> ' . trans("Select files") . '</button>
				<INPUT name="' . $id . '[]" type="file" multiple class="fileupload-select-btn" style="display: none;">
			</div>
			<div class="fileupload-files">';
	if (!empty($fileupload) && isset($fileupload[$id]))
		foreach ($fileupload[$id] as $fileidx => $file)
			$result .= '<div>
					<a href="#" class="fileupload-file"><i class="fas fa-trash"></i>
						' . $file['name'] . ' (' . $file['sizestr'] . ')
					</a>
					<input type="hidden" name="fileupload[' . $id . '][' . $fileidx . '][name]" value="' . $file['name'] . '">
					<input type="hidden" class="fileupload-file-size" name="fileupload[' . $id . '][' . $fileidx . '][size]" value="' . $file['size'] . '">
					<input type="hidden" name="fileupload[' . $id . '][' . $fileidx . '][type]" value="' . $file['type'] . '">
				</div>';
	$result .= '</div>
			<div class="fileupload-status alert bold">
			</div>
			<input type="hidden" class="fileupload-tmpdir" name="fileupload[' . $id . '-tmpdir]" value="' . $fileupload[$id . '-tmpdir'] . '">
		</div>';
	$result .= '<script type="text/javascript">
		<!--
			$(function() {
				var elemid = "' . $id . '";
				var elem = $("#" + elemid);
				var progressbar = elem.find(".fileupload-progressbar");
				var progresslabel = progressbar.find(".fileupload-progress-label");
				var xhr;

				function upload_files(form, formdata) {
					var action = form.attr("action");
					if (action === undefined)
						action = document.location;
					action += "&ajax=1";
					$("#' . $id . '-progress-dialog").dialog("open");
					xhr = $.ajax(action, {
						type: "POST",
						contentType: false,
						data: formdata,
						processData: false,
						xhr: function() {
							var myXhr = $.ajaxSettings.xhr();
							if (myXhr.upload)
								myXhr.upload.addEventListener("progress", function(e) {
									if (e.lengthComputable) {
										progressbar.progressbar("option", "value", e.loaded)
											.progressbar("option", "max", e.total);
										$(progresslabel).text(((e.loaded / e.total) * 100).toFixed() + "%");
									}
								}, false);
							return myXhr;
						},
						success: function(data, textStatus, jqXHR) {
							elem.find(".fileupload-status").html(data.error);
							if (typeof(data) == "object" && !data.error.length) {
								elem.find(".fileupload-tmpdir").val(data.tmpdir);
								var fileupload_files = elem.find(".fileupload-files");
								var count = fileupload_files.find(".fileupload-file").length;
								$.each(data.files, function(key, file) {
									var size = get_size_unit(file.size);
									fileupload_files.append(\'<div><a href="#" class="fileupload-file"><i class="fas fa-trash"></i>&nbsp;\'
										+ file.name + \' (\' + size.size + \' \' + size.unit + \')</a>\'
										+ \'<input type="hidden" name="fileupload[\' + elemid + \'][\' + (count + key) + \'][name]" value="\' + file.name + \'">\'
										+ \'<input type="hidden" class="fileupload-file-size" name="fileupload[\' + elemid + \'][\' + (count + key) + \'][size]" value="\' + file.size + \'">\'
										+ \'<input type="hidden" name="fileupload[\' + elemid + \'][\' + (count + key) + \'][type]" value="\' + file.type + \'">\'
										+ "</div>");
									elem.find(".fileupload-file").on("click", function() {
										$(this).parent().remove();
									});
								});
							}
						},
						error: function(jqXHR, textStatus, errorThrown) {
							if (errorThrown != "abort")
								elem.find(".fileupload-status").html(errorThrown);
						},
						complete: function(jqXHR, textStatus) {
							elem.find("input[type=file]").replaceWith(
								elem.find("input[type=file]").clone(true));
							$("#' . $id . '-progress-dialog").dialog("close");
						}
					});
				}

				elem.find("button").on("click", function() {
					$(this).siblings("input[type=file]").val("").click();
				}).on("dragover", function(e) {
					e.preventDefault();
					e.stopPropagation();
					$(this).addClass("lms-ui-fileupload-dropzone");
				}).on("dragleave", function(e) {
					e.preventDefault();
					e.stopPropagation();
					$(this).removeClass("lms-ui-fileupload-dropzone");
				}).on("drop", function(e) {
					e.preventDefault();
					e.stopPropagation();
					$(this).removeClass("lms-ui-fileupload-dropzone");
					var files = e.originalEvent.dataTransfer.files;
					var form = $(this).closest("form");
					var formdata = new FormData(form.get(0));
					formdata.delete("' . $id . '[]");
					$(files).each(function(index, file) {
						var fileReader = new FileReader();
						fileReader.readAsDataURL(file);
						formdata.append("' . $id . '[]", file);
						delete fileReader;
					});
					upload_files(form, formdata);
				});
				elem.find("#' . $id . '-progress-dialog").dialog({
					modal: true,
					autoOpen: false,
					resizable: false,
					draggable: false,
					minWidth: 0,
					minHeight: 0,
					dialogClass: "fileupload-progress-dialog",
					buttons: [{
						text: "' . trans("Cancel") . '",
						click: function() {
							xhr.abort();
							$(this).dialog("close");
						}
					}]
				}).parent().draggable();
				progressbar.progressbar({
					value: false
				});
				elem.find("input[type=file]").on("change", function() {
					var form = $(this).closest("form");
					var formdata = new FormData(form.get(0));
					upload_files(form, formdata);
				});
				elem.find(".fileupload-file").on("click", function() {
					$(this).parent().remove();
				});
			});
		//-->
		</script>';

	return $result;
}

?>
