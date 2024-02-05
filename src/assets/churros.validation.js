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
			console.log("Ya tenía el foco", input.selectionStart, input.selectionEnd);
			return;
			if (document.activeElement == input) {
				return;
			}
			if (input.setSelectionRange) {
				input.setSelectionRange(selectionStart, selectionEnd);
			} else if (input.createTextRange) {
				var range = input.createTextRange();
				range.collapse(true);
				range.moveEnd('character', selectionEnd);
				range.moveStart('character', selectionStart);
				range.select();
			}
		}
	}
	return pub;
})(window.jQuery);
