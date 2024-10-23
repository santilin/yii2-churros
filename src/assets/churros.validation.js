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
		fnGridExcelExport(id, nombre, excluded_rows, excluded_cols) {
			if (typeof(excluded_rows)==='undefined') excluded_rows = null;
			if (typeof(excluded_cols)==='undefined') excluded_cols = null;
			var uri = 'data:application/vnd.ms-excel;base64,';
			base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) }
			format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) }
			var tab_text="<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns='http://www.w3.org/TR/REC-html40'><meta http-equiv='content-type' content='application/vnd.ms-excel; charset=UTF-8'><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Resumen</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body><table>";

			var textRange; var j=0, i=0;
			var tab = document.getElementById(id); // id of table
			if(excluded_rows !== null || excluded_cols !== null){
				for(j = 0 ; j < tab.rows.length ; j++){
					if(excluded_rows.includes(j) == false){//Si el valor de j no está incluido en el array pasado como parámetro
						tab_text = tab_text + "\n<tr>";
						let row = tab.rows[j];
						if( !row.classList.contains('no-export') ) {
							for(i = 0 ; i < row.cells.length ; i++) {
								if(excluded_cols.includes(i) == false){//Si el valor de i no está incluido en el array pasado como parámetro
									tab_text=tab_text + "\n<td>" + row.cells[i].innerHTML.replace(/(<a.*?>)|(<\/a>)/ig, "") + "</td>";
								}
							}
						}
						tab_text = tab_text + "</tr>";
					}
				}
			} else {
				for(j = 0 ; j < tab.rows.length ; j++) {
					let row = tab.rows[j];
					if( !row.classList.contains('no-export') ) {
						tab_text=tab_text + "\n<tr>" + row.innerHTML.replace(/(<a.*?>)|(<\/a>)/ig, "") + "</tr>";
					}
				}
			}

			tab_text=tab_text+"\n</table></body></html>";

			var table = document.getElementById(id)

			var ua = window.navigator.userAgent;
			var msie = ua.indexOf('MSIE ');
			if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))      // If Internet Explorer
			{
				txtArea1.document.open('txt/html','replace');
				txtArea1.document.write(tab_text);
				txtArea1.document.close();
				txtArea1.focus();
				sa=txtArea1.document.execCommand('SaveAs',true,nombre+'.xls');
			} else {           //other browser not tested on IE 11
				var ctx = {worksheet: nombre || 'Worksheet', table: table.innerHTML}
				var btn = document.getElementById("btn-"+id);
				btn.href = uri + base64(format(tab_text, ctx));
				btn.download = nombre+".xls";
				return true;
			}
		}

	}
	return pub;
})(window.jQuery);
