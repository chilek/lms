/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

function lmsFileUpload(elemid, formid, new_item_custom_content) {
	var elem = $("#" + elemid);
	var formelem = typeof(formid) != 'undefined' && formid.length ? $('#' + formid) : elem.closest("form");
	var formdata = new FormData(formelem.get(0));
	var dontScaleImages = elem.find('.dont-scale-images');
	var files;
	var progressbar = elem.find(".fileupload-progressbar");
	var progresslabel = progressbar.find(".fileupload-progress-label");
	var xhr;

	function upload_files() {
		var action = formelem.attr("action");
		if (action === undefined) {
			action = document.location;
		}
		action += "&ajax=1&fileupload=1";
		$("#" + elemid + "-progress-dialog").dialog("open");
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
			success: function(data) {
				elem.find(".fileupload-status").html(data.error);
				if (typeof(data) == "object" && !data.error.length) {
					elem.find(".fileupload-tmpdir").val(data.tmpdir);
					var fileupload_files = elem.find(".fileupload-files");
					var count = fileupload_files.find(".fileupload-file").length;
					$.each(data.files, function(key) {
						var size = get_size_unit(files[key].size);
						var fileListItem = $('<div class="fileupload-file">' +
							'<div class="fileupload-file-info">' +
								'<a href="#" class="file-delete"><i class="fas fa-trash"></i></a>&nbsp;' +
								(files[key].imgElem ? '<a href="#" class="file-preview"><i class="fas fa-search"></i></a>&nbsp;' : '') +
								'<a href="#" class="file-view"><i class="fas fa-eye"></i></a>&nbsp;' +
								files[key].name + ' (' + size.size + ' ' + size.unit + ')' +
								'<input type="hidden" name="fileupload[' + elemid + '][' + (count + key) + '][name]"' +
									' value="' + files[key].name + '" ' + (formid ? ' form="' + formid + '"' : '') + '>' +
								'<input type="hidden" class="fileupload-file-size" name="fileupload[' + elemid + '][' + (count + key) + '][size]"' +
									' value="' + files[key].size + '" ' + (formid ? ' form="' + formid + '"' : '') + '>' +
								'<input type="hidden" name="fileupload[' + elemid + '][' + (count + key) + '][type]"' +
									' value="' + files[key].type + '" ' + (formid ? ' form="' + formid + '"' : '') + '>' +
							'</div>' +
							(new_item_custom_content.length ? '<div class="fileupload-file-options">' +
								Base64.decode(new_item_custom_content).replaceAll('%idx%', count + key) + '</div>': '') +
						'</div>').appendTo(fileupload_files);
						fileListItem.find('.file-preview').tooltip({
							items: 'a',
							content: files[key].imgElem,
							classes: {
								'ui-tooltip' : 'documentview'
							},
							track: true
						});
						fileListItem.find('.file-view').click(function() {
							lmsFileView(files[key]);
						});
						fileListItem.find('.file-delete').click(function() {
							$(this).closest('.fileupload-file').remove();
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
				$('#' + elemid + '-progress-dialog').dialog("close");
				elem.trigger('lms:fileupload:complete');
			}
		});
	}

	function prepare_files() {
		formdata = new FormData(formelem.get(0));

		var left = files.length;
		$(files).each(function(index, file) {
			var fileReader = new FileReader();
			fileReader.onload = function(readerEvent) {
				if (file.type.match('^image/.*')) {
					var image = new Image();
					var imageUrl = readerEvent.target.result;
					image.onerror = function() {
						formdata.append(elemid + '[]', file);
						left--;
						if (!left) {
							upload_files();
						}
					};
					image.onload = function() {
						// Resize the image
						var canvas = document.createElement('canvas'),
							imgWidth = image.width,
							imgHeight = image.height,
							maxSize = 300,
							width,
							height;

						// render thumbnail
						width = imgWidth;
						height = imgHeight;
						if (width > maxSize || height > maxSize) {
							if (width > height) {
								if (width > maxSize) {
									height *= maxSize / width;
									width = maxSize;
								}
							} else {
								if (height > maxSize) {
									width *= maxSize / height;
									height = maxSize;
								}
							}
							canvas.width = width;
							canvas.height = height;
							canvas.getContext('2d').drawImage(image, 0, 0, width, height);
							file.imgElem = $('<img src="' + canvas.toDataURL(file.type) + '" alt="">');
							file.contentElem = $(image);

							width = imgWidth;
							height = imgHeight;
						} else {
							file.imgElem = $(image);
						}

						maxSize = lmsSettings.uploadedImageMaxSize;

						if (!maxSize || dontScaleImages.prop('checked') || (width <= maxSize && height <= maxSize)) {
							formdata.append(elemid + '[]', file);
							left--;
							if (!left) {
								upload_files();
							}
							return;
						}
						if (width > height) {
							if (width > maxSize) {
								height *= maxSize / width;
								width = maxSize;
							}
						} else {
							if (height > maxSize) {
								width *= maxSize / height;
								height = maxSize;
							}
						}
						canvas.width = width;
						canvas.height = height;
						canvas.getContext('2d').drawImage(image, 0, 0, width, height);
						canvas.toBlob(function(blob) {
								formdata.append(elemid + '[]', blob, file.name);
								left--;
								if (!left) {
									upload_files();
								}
							}, file.type);
					};
					image.src = imageUrl;
				} else {
					formdata.append(elemid + '[]', file);
					left--;
					if (!left) {
						upload_files();
					}
				}
			};
			fileReader.readAsDataURL(file);
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
		files = e.originalEvent.dataTransfer.files;
		prepare_files();
		formdata.delete(elemid + '[]');
	});
	elem.find('#' + elemid + '-progress-dialog').dialog({
		modal: true,
		autoOpen: false,
		resizable: false,
		draggable: false,
		minWidth: 0,
		minHeight: 0,
		dialogClass: "fileupload-progress-dialog",
		buttons: [{
			text: $t("Cancel"),
			click: function() {
				xhr.abort();
				$(this).dialog("close");
			}
		}]
	}).parent().draggable();
	progressbar.progressbar({
		value: false
	});
	elem.find("input[type=file]").change(function() {
		files = $(this).get(0).files;
		prepare_files();
		formdata.delete(elemid + '[]');
	});
	elem.find(".file-delete").click(function() {
		$(this).closest('.fileupload-file').remove();
	});
}
