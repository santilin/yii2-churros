window.yii.churros = (function ($) {
	var pub = {
		email: function (value, messages, options) {
			value = $.trim(value);
			if (yii.validation.isEmpty(value)) {
				return;
			}
			return yii.validation.email(value, messages, options);
		},
		dot_dot_groups: function(mask, dot) {
			const parts = mask.split(dot);
			let ret = [];
			for( i=0; i<parts.length; ++i ) {
				ret.push(parts[i].length);
			}
			return ret;
		},
		dot_dot_validate_input: function($form, attribute, messages, mask, dot, options) {
			var $input = $form.find(attribute.input);
			value = pub.dot_dot_validate($input.val(), mask, dot, options);
			if( value !== false ) {
				$input.val(value);
			} else {
				messages.push(options['message']);
			}
		},
		dot_dot_validate(value, mask, dot, options) {
			const groups = pub.dot_dot_groups(mask, dot);
			var regexp_dot;
			if( dot == '.' ) {
				regexp_dot = '\\.';
			} else {
				regexp_dot = dot;
			}
			if( groups.length == 0 ) {
				return true;
			}
			let reg_exps = [];
			for( i=0; i<groups.length; ++i ) {
				if( i==0 ) {
					reg_exps.push("[0-9]{1," + groups[i] + "}");
				} else {
					reg_exps.push(regexp_dot + "[0-9]{0," + groups[i] + "}");
				}
			}
			let re_str = '';
			for( i=0; i<reg_exps.length; ++i ) {
				if( i>0 ) {
					re_str += '|';
				}
				for( j=0; j<=i; ++j ) {
					re_str += reg_exps[j];
				}
			}
			console.log(re_str);
			var rgx = new RegExp("^(" + re_str + ")$");
			if( value.match(rgx) ) {
				var parts = value.split(dot);
				let ret = '';
				for( i=0; i<parts.length; ++i ) {
					if( i!=0 ) {
						ret += dot;
					}
					ret += parts[i].padStart(groups[i], '0')
				}
				return ret;
			} else {
				return false;
			}
		},
		inputSetSelectionRange(input, selectionStart, selectionEnd) {
			// https://stackoverflow.com/a/499158
			if (input.setSelectionRange) {
				input.setSelectionRange(selectionStart, selectionEnd);
			} else if (input.createTextRange) {
				var range = input.createTextRange();
				range.collapse(true);
				range.moveEnd('character', selectionEnd);
				range.moveStart('character', selectionStart);
				range.select();
			}
		},
		dateParseFromFormat(datestr, format) {
			// https://stackoverflow.com/questions/60759006/is-there-a-way-to-prevent-the-date-object-in-js-from-overflowing-days-months
// 			console.log("Matching datestr `" + datestr + "` against regexp `/^" + format + "$/`");
			matches = datestr.match('^' + format + '$');
			if (matches === null) {
				return false;
			}
			let today = new Date();
			if (matches.groups.year_long !== undefined) {
				year = parseInt(matches.groups.year_long);
			} else if (matches.groups.year_short !== undefined) {
				year = parseInt(matches.groups.year_short);
			} else {
				year = today.getFullYear();;
			}
			if (isNaN(year)) {
				year = today.getFullYear();
			} else if (year<100) {
				year += 2000;
			}
			if (matches.groups.month !== undefined) {
				month = parseInt(matches.groups.month);
			} else {
				month = today.getMonth() + 1;
			}
			if (matches.groups.day !== undefined) {
				day = parseInt(matches.groups.day);
			} else {
				day = today.getDate();
			}
			if (matches.groups.hour !== undefined) {
				hour = parseInt(matches.groups.hour);
			} else {
				hour = today.getHours();
			}
			if (matches.groups.minute !== undefined) {
				minute = parseInt(matches.groups.minute);
			} else {
				minute = today.getMinutes();
			}
			if (matches.groups.second !== undefined) {
				second = parseInt(matches.groups.second);
			} else {
				second = 0;
			}

			var d = new Date(year, month-1, day);
			if( d.getFullYear() != year || d.getMonth() != month-1 || d.getDate() != day ) {
				return null;
			} else {
				d.setHours(hour);
				d.setMinutes(minute);
				d.setSeconds(second);
				return d;
			}
		},
		dateInputChange(date_input, orig_id, format, saveFormat, format_as_regex, err_message, default_times) {
			if ($.trim(date_input.val()) == '') {
				var date_js = null;
			} else {
				let ds = date_input.val();
				if (default_times !== undefined ) {
					for (const prop in default_times) {
						ds = ds.replace(prop, default_times[prop]);
					}
					ds = ds.replace('_', '0');
				}
				var date_js = window.yii.churros.dateParseFromFormat(ds, format_as_regex);
			}
			let error_el = date_input.next('.invalid-feedback');
			let form_control = date_input.closest(".form-control");
			if( date_js === null ) { // empty
				$('#' + orig_id).val('');
				if (error_el) {
					error_el.text("");
				}
				if (form_control) {
					form_control.removeClass('is-invalid');
				}
				return true;
			} else if (date_js == false ) { // wrong
				$('#' + orig_id).val( date_input.val() );
				if (error_el) {
					error_el.text(err_message);
				}
				if (form_control) {
					form_control.addClass('is-invalid');
				}
				return false;
			} else {
				var fmt = new DateFormatter();
				date_input.val(fmt.formatDate(date_js, format));
				$('#' + orig_id).val(fmt.formatDate(date_js, saveFormat));
				if (error_el) {
					error_el.text("");
				}
				if (form_control) {
					form_control.removeClass('is-invalid');
				}
				return true;
			}
		},
		createODSFile(id, fileName, excludedClasses = [], excludedRows = [], excludedCols = []) {
			const table = document.getElementById(id);
			const content = this.generateContentXML(table, excludedClasses, excludedRows, excludedCols);
			const manifest = this.generateManifestXML();
			const styles = this.generateStylesXML();

			const zip = new JSZip();
			zip.file("content.xml", content);
			zip.file("META-INF/manifest.xml", manifest);
			zip.file("styles.xml", styles);
			zip.file("mimetype", "application/vnd.oasis.opendocument.spreadsheet");
			zip.generateAsync({type: "blob"})
			.then(function(content) {
				saveAs(content, `${fileName}.ods`);
			});
		},
		generateContentXML(table, excludedClasses, excludedRows, excludedCols) {
			let content = `<?xml version="1.0" encoding="UTF-8"?>
			<office:document-content
			xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
			xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
			xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">
			<office:body>
			<office:spreadsheet>
			<table:table table:name="Sheet1">`;

			for (let i = 0; i < table.rows.length; i++) {
				if (excludedRows.includes(i)) continue;
				if (excludedClasses.some(className => table.rows[i].classList.contains(className))) continue;
				content += '<table:table-row>';
				for (let j = 0; j < table.rows[i].cells.length; j++) {
					if (excludedCols.includes(j)) continue;
					const cellContent = table.rows[i].cells[j].textContent;
					content += `<table:table-cell office:value-type="string">
					<text:p>${cellContent}</text:p>
					</table:table-cell>`;
				}
				content += '</table:table-row>';
			}

			content += `
			</table:table>
			</office:spreadsheet>
			</office:body>
			</office:document-content>`;

			return content;
		},
		generateManifestXML() {
			return `<?xml version="1.0" encoding="UTF-8"?>
			<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">
			<manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.spreadsheet"/>
			<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>
			<manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/>
			</manifest:manifest>`;
		},
		generateStylesXML() {
			return `<?xml version="1.0" encoding="UTF-8"?>
			<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0">
			</office:document-styles>`;
		},
		internetDomain(value, messages, options) {
			if (options.skipOnEmpty && yii.validation.isEmpty(value)) {
				return;
			}

			var pattern = options.pattern;

			if (options.enableIDN) {
				value = punycode.toASCII(value);
			}

			if (options.clean) {
				if (value.toLowerCase().indexOf('mailto://') === 0) {
					value = value.substr(9);
					var emailParts = value.split('@');
					if (emailParts.length === 2) {
						value = emailParts[1];
					} else {
						messages.push(options.message);
						return;
					}
				} else {
					value = value.replace(/^(https?:\/\/)?(www\.)?/, '');
					var match = value.match(/^(((?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))|([a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,6}))/);
					value = match ? match[1] : value;
				}
			}

			if (!pattern.test(value)) {
				messages.push(options.message);
			}
		}
	}
	return pub;
})(window.jQuery);
