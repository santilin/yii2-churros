
function churros_extract_dot_groups(mask, dot)
{
	const parts = mask.split(dot);
	let ret = [];
	for( i=0; i<parts.length; ++i ) {
		ret.push(parts[i].length);
	}
	return ret;
}

function churros_validate_dot_dot_input($form, attribute, messages, mask, dot, options)
{
	var $input = $form.find(attribute.input);
	value = churros_validate_dot_dot($input.val(), mask, dot, options);
	if( value !== false ) {
		$input.val(value);
	} else {
		messages.push(options['message']);
	}
}

function churros_validate_dot_dot(value, mask, dot, options)
{
	const groups = churros_extract_dot_groups(mask, dot);
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
}
