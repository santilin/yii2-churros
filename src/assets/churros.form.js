window.yii.churros = (function ($) {
	return {
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
        moveCaretToEnd(input) {
			console.log(input);
			const val = input.value;
			input.value = '';
			input.value = val;
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
		internetDomain(value, messages, options) {
			if (options.skipOnEmpty && yii.validation.isEmpty(value)) {
				return;
			}

			var pattern = options.pattern;

			if (options.enableIDN) {
				value = punycode.toASCII(value);
			}

			if (options.clean) {
				if (value.toLowerCase().indexOf('mailto:') === 0) {
					value = value.substr(7);
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
		},

		copyToClipboard: function(text_area, text) {
			try {
				// Try to use the modern clipboard API
				navigator.clipboard.writeText(text).then(function() {
				}).catch(function(err) {
					console.error('Unable to copy text: ', err);
				});
			} catch (err) {
				// Check if a textarea is provided
				if (text_area) {
					// If textarea exists, use it
					text_area.value = text;
					text_area.select();
				} else {
					// If no textarea, create a temporary one
					text_area = document.createElement("textarea");
					text_area.value = text;
					document.body.appendChild(text_area);
					text_area.select();
				}
				// Fallback to execCommand for older browsers
				try {
					var successful = document.execCommand('copy');
					var msg = successful ? 'successful' : 'unsuccessful';
				} catch (err) {
					console.error('Fallback: Unable to copy text: ', err);
				}
				// Remove the temporary textarea if we created one
				if (!text_area.parentNode) {
					document.body.removeChild(text_area);
				}
			}
		},

		persistBootstrapTabs: function (tabSelector) {
			// Derive a unique localStorage key by sanitizing the selector string
			var key = 'activeTab_' + tabSelector.replace(/[^a-z0-9]/gi, '_');

			// Activate saved tab on load
			var activeTab = localStorage.getItem(key);
			if (activeTab) {
				var triggerEl = document.querySelector(tabSelector + '[href="' + activeTab + '"]');
				if (triggerEl) {
					var tab = new bootstrap.Tab(triggerEl);
					tab.show();
				}
			}

			// Save active tab on show
			var tabLinks = document.querySelectorAll(tabSelector);
			tabLinks.forEach(function (tabLink) {
				tabLink.addEventListener('shown.bs.tab', function (event) {
					var tabId = event.target.getAttribute('href');
					localStorage.setItem(key, tabId);
				});
			});
		},
		htmlEncode : function (str) {
			return str.replace(/[&<>'"]/g, function(char) {
				const map = {
					'&': '&amp;',
					'<': '&lt;',
					'>': '&gt;',
					"'": '&#39;',
					'"': '&quot;'
				};
				return map[char];
			});
		}
	};
})(window.jQuery);


window.yii.FormController = (function() {
    // Private WeakMap to track changed state per form (avoids polluting DOM)
    const _changedForms = new WeakMap();

    function FormController(form) {
        if (typeof form === 'string') {
            form = document.getElementById(form);
        }
        if (!form) throw new Error('Form not found');
        this.form = form;
		form.controller = this;
    }

    FormController.prototype = {
        // Initialize core features (Enter as Tab, focus, etc.)
        init: function({ enterAsTab = true, setFocus = true, preventBackspace = true } = {}) {
            if (setFocus) {
                this.setFocusToFirstInput();
            }
            if (preventBackspace) {
                this.form.addEventListener('keydown', this.preventBackspaceNavigation);
            }
            if (enterAsTab) {
                this.form.addEventListener('keydown', this.handleEnterAsTab);
            }
            return this; // Allow chaining
        },

        // Track changes for this specific form
        trackChanges: function() {
            _changedForms.set(this.form, false);
            this.form.querySelectorAll('input, textarea, select').forEach(el => {
                el.addEventListener('change', () => {
                    _changedForms.set(this.form, true);
                });
            });
            return this;
        },

        hasChanged: function() {
            return !!_changedForms.get(this.form);
        },

        resetChanged: function() {
            _changedForms.set(this.form, false);
			return this;
        },

        // Disable all fields except one (by selector or element)
        disableAllExcept: function(except) {
            const exceptEl = typeof except === 'string'
                ? this.form.querySelector(except)
                : except;

            this.form.querySelectorAll('input, textarea, select, button').forEach(el => {
                el.disabled = (el !== exceptEl);
            });
            return this;
        },

        // Existing methods adapted for instance use
        setFocusToFirstInput: function() {
			if (this.form.elements.length > 0) {
				index = 0;
				while( (this.form.elements[index].type === "hidden"
					|| window.getComputedStyle(this.form.elements[index]).display === "none"
					|| this.form.elements[index].tabIndex == -1 )) {
					if (++index == this.form.elements.length ) {
						break;
					}
				}
				if (index < this.form.elements.length ) {
					this.form.elements[index].focus();
				}
			}
			return this;
		},
		formEnterAsTab: function(event) {
			if (event.keyCode === 13 && ( event.target.nodeName === 'INPUT' || event.target.nodeName === 'SELECT') ) {
				var form = event.target.form;
				var index = Array.prototype.indexOf.call(form, event.target);
				index++;
				if (form.elements.length > 0) {
					while( (form.elements[index].type === "hidden"
						|| window.getComputedStyle(form.elements[index]).display === "none"
						|| form.elements[index].tabIndex == -1 ) ) {
						if (++index == form.elements.length ) {
							break;
						}
					}
					if (index < form.elements.length ) {
						form.elements[index].focus();
					}
				}
				event.preventDefault();
				return false;
			} else {
				return true;
			}
		},

		preventBackspaceNavigation: function(event) {
			var doPrevent = false;
			if (event.keyCode === 8) {
				var d = event.srcElement || event.target;
				if ((d.tagName.toUpperCase() === 'INPUT' &&
					(d.type.toUpperCase() === 'TEXT' ||
					d.type.toUpperCase() === 'PASSWORD' ||
					d.type.toUpperCase() === 'FILE' ||
					d.type.toUpperCase() === 'SEARCH' ||
					d.type.toUpperCase() === 'EMAIL' ||
					d.type.toUpperCase() === 'NUMBER' ||
					d.type.toUpperCase() === 'DATE' )) ||
					d.tagName.toUpperCase() === 'TEXTAREA') {
					doPrevent = d.readOnly || d.disabled;
				} else {
					doPrevent = true;
				}
			}
		},

		disableAllFieldsButOne: function(exceptElement) {
			if (typeof exceptElement === 'string') exceptElement = document.getElementById(exceptElement);
			if (!exceptElement) return;

			// Select all input, select, and textarea fields in the form
			var fields = this.form.querySelectorAll('input:not([type="hidden"]), select, textarea');
			fields.forEach(function(field) {
				field.disabled = (field !== exceptElement);
			});
			return this;
		},
    };

    return FormController;
})();

