window.yii.churros = (function ($) {
	return {
		round: function(value, decimals) {
			if (typeof value !== 'number' || typeof decimals !== 'number') {
				throw new TypeError('Both arguments must be numbers');
			}
			const factor = Math.pow(10, decimals);
			return Math.round(value * factor) / factor;
		},
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
			for (let i=0; i<parts.length; ++i) {
				ret.push(parts[i].length);
			}
			return ret;
		},
		dot_dot_validate_input: function($form, attribute, messages, mask, dot, options) {
			var $input = $form.find(attribute.input);
			let value = window.yii.churros.dot_dot_validate($input.val(), mask, dot, options);
			if (value !== false) {
				$input.val(value);
			} else {
				messages.push(options['message']);
			}
		},
		dot_dot_validate(value, mask, dot, options) {
			const groups = window.yii.churros.dot_dot_groups(mask, dot);
			let regexp_dot;
			if (dot == '.') {
				regexp_dot = '\\.';
			} else {
				regexp_dot = dot;
			}
			if (groups.length == 0) {
				return true;
			}
			let reg_exps = [];
			for ( let i=0; i<groups.length; ++i) {
				if (i==0) {
					reg_exps.push("[0-9]{1," + groups[i] + "}");
				} else {
					reg_exps.push(regexp_dot + "[0-9]{0," + groups[i] + "}");
				}
			}
			let re_str = '';
			for ( let i=0; i<reg_exps.length; ++i) {
				if (i>0) {
					re_str += '|';
				}
				for (let j=0; j<=i; ++j) {
					re_str += reg_exps[j];
				}
			}
			var rgx = new RegExp("^(" + re_str + ")$");
			if (value.match(rgx)) {
				var parts = value.split(dot);
				let ret = '';
				for (let i=0; i<parts.length; ++i) {
					if (i!=0) {
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
			let matches = datestr.match('^' + format + '$');
			if (matches === null) {
				return false;
			}
			let today = new Date();
			let year = null;
			let month = null;
			let day = null;
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
			if (d.getFullYear() != year || d.getMonth() != month-1 || d.getDate() != day) {
				return null;
			} else {
				d.setHours(hour);
				d.setMinutes(minute);
				d.setSeconds(second);
				return d;
			}
		},
        moveCaretToEnd(input) {
			const val = input.value;
			input.value = '';
			input.value = val;
		},
		dateInputChange(date_input, orig_id, format, saveFormat, format_as_regex, err_message, default_times) {
			if ($.trim(date_input.val()) == '') {
				var date_js = null;
			} else {
				let ds = date_input.val();
				if (default_times !== undefined) {
					for (const prop in default_times) {
						ds = ds.replace(prop, default_times[prop]);
					}
					ds = ds.replace('_', '0');
				}
				var date_js = window.yii.churros.dateParseFromFormat(ds, format_as_regex);
			}
			let error_el = date_input.next('.invalid-feedback');
			let form_control = date_input.closest(".form-control");
			if (date_js === null) { // empty
				$('#' + orig_id).val('');
				if (error_el) {
					error_el.text("");
				}
				if (form_control) {
					form_control.removeClass('is-invalid');
				}
				return true;
			} else if (date_js == false) { // wrong
				$('#' + orig_id).val( date_input.val() );
				if (error_el) {
					error_el.text(err_message);
				}
				if (form_control) {
					form_control.addClass('is-invalid');
				}
				return false;
			} else {
				// ✅ Reemplazo inline de DateFormatter
				function formatDate(date, format) {
					const pad = (n) => n.toString().padStart(2, '0');
					const year = date.getFullYear();
					const month = pad(date.getMonth() + 1);
					const day = pad(date.getDate());
					const hours = pad(date.getHours());
					const minutes = pad(date.getMinutes());
					const seconds = pad(date.getSeconds());

					return format
					.replace(/YY/g, year)
					.replace(/Y/g, year)
					.replace(/m/g, month)
					.replace(/d/g, day)
					.replace(/H/g, hours)
					.replace(/I/g, minutes)
					.replace(/s/g, seconds);
				}
				date_input.val(formatDate(date_js, format));
				$('#' + orig_id).val(formatDate(date_js, saveFormat));
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

			if (typeof punycode !== 'undefined' && options.enableIDN) {
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
            let form_element = document.getElementById(form);
			if (!form_element) {
				throw new Error('Form ' + form + ' not found');
			}
			this.form = form_element;
        } else {
			this.form = form;
		}
		form.controller = this;
    }

    FormController.prototype = {
        // Initialize core features (Enter as Tab, focus, etc.)
		init: function({ enterAsTab = true, setFocus = true, preventBackspace = true } = {}) {
			if (setFocus) {
				this.setFocusToFirstInput();
			}
			if (preventBackspace) {
				this.form.addEventListener('keydown', this.preventBackspaceNavigation.bind(this));
			}
			if (enterAsTab) {
				this.form.addEventListener('keydown', this.formEnterAsTab.bind(this));
			}
			return this;
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
				let index = 0;
				while( (this.form.elements[index].type === "hidden"
					|| window.getComputedStyle(this.form.elements[index]).display === "none"
					|| this.form.elements[index].tabIndex == -1 )) {
					if (++index == this.form.elements.length) {
						break;
					}
				}
				if (index < this.form.elements.length) {
					this.form.elements[index].focus();
				}
			}
			return this;
		},
		formEnterAsTab: function(event) {
			if (event.key !== 'Enter' || !['INPUT','SELECT'].includes(event.target.nodeName)) {
				return;
			}
			const elems = Array.from(this.form.elements);  // Convierte a array
			const index = elems.indexOf(event.target);
			if (index === -1) return;

			let nextIndex = index + 1;
			while (nextIndex < elems.length) {
				const nextEl = elems[nextIndex];
				if (nextEl.type !== 'hidden' &&
					window.getComputedStyle(nextEl).display !== 'none' &&
					nextEl.tabIndex !== -1 &&
					nextEl.offsetParent !== null) {  // Visible en DOM
						nextEl.focus();
						// ✅ FIX only if positionCaretOnTab = 'radixFocus'
						setTimeout(() => {
							const maskData = nextEl.dataset.pluginInputmask;
							if (maskData) {
								const maskConfig = window[maskData];
								if (maskConfig && maskConfig.positionCaretOnTab === 'radixFocus' && nextEl.inputmask) {
									const digits = maskConfig.digits || 2;  // 2 decimales normalmente
									const totalLength = nextEl.value.length;
									const caretPos = totalLength - digits - 1;  // Antes del "."

									window.yii.churros.inputSetSelectionRange(nextEl, caretPos, caretPos);
								}
							}
						}, 50);
						break;
					}
					nextIndex++;
			}
			event.preventDefault();
		},

		preventBackspaceNavigation: function(event) {
			var doPrevent = false;
			if (event.key === 'Backspace') {
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
				if (doPrevent) {
					event.preventDefault();
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

