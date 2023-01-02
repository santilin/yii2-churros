
function churros_dot_dot_groups(mask, dot)
{
	const parts = mask.split(dot);
	let ret = [];
	for( i=0; i<parts.length; ++i ) {
		ret.push(parts[i].length);
	}
	return ret;
}

function churros_dot_dot_taxon_values(taxonomy, values, level)
{
	const levels = taxonomy.levels;
	let options = taxonomy.items;
	let ret = { '0': 'Elige...' };
	for( l=0; l<level; ++l ) {
		options = options[values[l]].items;
	}
	for( const v in options ) {
		ret[v] = options[v].title;
	}
	console.log(ret);
	return ret;
}

function churros_dot_dot_validate_input($form, attribute, messages, mask, dot, options)
{
	var $input = $form.find(attribute.input);
	value = churros_dot_dot_validate($input.val(), mask, dot, options);
	if( value !== false ) {
		$input.val(value);
	} else {
		messages.push(options['message']);
	}
}

function churros_dot_dot_validate(value, mask, dot, options)
{
	const groups = churros_dot_dot_groups(mask, dot);
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
