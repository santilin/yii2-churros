const ChurrosGrid = (function() {
	return {
		generateBs5Table(data, excludeKeys = []) {
			if (!data.length) return '<table class="table"><thead></thead><tbody></tbody></table>';

			// Extract headers, excluding specified keys
			const headers = Object.keys(data[0]).filter(key => !excludeKeys.includes(key));

			// Create thead
			let thead = '<thead><tr>';
			headers.forEach(header => {
				thead += `<th scope="col">${header}</th>`;
			});
			thead += '</tr></thead>';

			// Create tbody rows excluding excluded keys
			let tbody = '<tbody>';
			data.forEach(row => {
				tbody += '<tr>';
				headers.forEach(header => {
					tbody += `<td>${row[header]}</td>`;
				});
				tbody += '</tr>';
			});
			tbody += '</tbody>';

			// Compose full table
			return `<table class="table">${thead}${tbody}</table>`;
		},
		resetFilters(grid_id) {
			console.log('Resetting filters on ' + grid_id);
			const grid = document.getElementById(grid_id);
			if (!grid) return false;

			const filters = grid.querySelector('.filters');
			if (!filters) return false;

			const removeFiltersLink = filters.querySelector('.remove-filters');
			if (!removeFiltersLink) return false;

			const inputs = filters.querySelectorAll('input');
			const selects = filters.querySelectorAll('select');
			let changedElement = false;

			inputs.forEach(input => {
				if (input.value !== '' && !changedElement) {
					input.value = '';
					changedElement = input;
				} else {
					input.value = '';
				}
			});

			selects.forEach(select => {
				if (select.selectedIndex !== 0 && !changedElement) {
					select.selectedIndex = 0;
					changedElement = select;
				} else {
					select.selectedIndex = 0;
				}
			});
			if (changedElement) {
				changedElement.dispatchEvent(new Event('change', { bubbles: true }));
			}
			return true;
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
		generateContentXML(table, excludedClasses = [], excludedRows = [], excludedCols = []) {
			let content = `<?xml version="1.0" encoding="UTF-8"?>
			<office:document-content
			xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
			xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
			xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
			xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
			xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
			<office:automatic-styles>`;

			// This object will hold unique style definitions keyed by a style signature string
			const styleMap = {};
			let styleCount = 0;

			// Helper to convert rgb() or named color to hex (omitted here for brevity, you can write a helper function)

			// Function to create unique style ID and XML based on extracted styles
			function createStyleId(style) {
				const styleKey = JSON.stringify(style);
				if (styleMap[styleKey]) return styleMap[styleKey].id;

				const id = `ce${++styleCount}`;
				styleMap[styleKey] = { id, style };
				return id;
			}

			// Gather rows/cells content and extract styles
			let rowsXml = `<table:table table:name="Sheet1">`;
			for (let i = 0; i < table.rows.length; i++) {
				if (excludedRows.includes(i)) continue;
				if (excludedClasses.some(className => table.rows[i].classList.contains(className))) continue;
				rowsXml += '<table:table-row>';
				for (let j = 0; j < table.rows[i].cells.length; j++) {
					if (excludedCols.includes(j)) continue;
					const cell = table.rows[i].cells[j];
					const cellContent = cell.textContent || '';

					// Extract basic styles from inline CSS
					const style = {};
					const cs = window.getComputedStyle(cell);
					if (cs.backgroundColor && cs.backgroundColor !== 'rgba(0, 0, 0, 0)' && cs.backgroundColor !== 'transparent') {
						style.backgroundColor = this.rgbToHex(cs.backgroundColor);
					}
					if (cs.fontWeight === '700' || cs.fontWeight === 'bold') style.fontWeight = 'bold';
					if (cs.fontStyle === 'italic') style.fontStyle = 'italic';
					if (cs.color) style.color = this.rgbToHex(cs.color);
					if (cs.textAlign && cs.textAlign !== 'start') style.textAlign = cs.textAlign;
					if (cs.verticalAlign && cs.verticalAlign !== 'baseline') style.verticalAlign = cs.verticalAlign;

					// Create or get style id for this style
					const styleId = createStyleId(style);

					rowsXml += `<table:table-cell office:value-type="string" table:style-name="${styleId}"><text:p>${this.escapeXml(cellContent)}</text:p></table:table-cell>`;
				}
				rowsXml += '</table:table-row>';
			}
			rowsXml += `</table:table>`;

			content += this.buildStylesXML(styleMap);
			content += `</office:automatic-styles>`;
			content += `<office:body><office:spreadsheet>`;
			content += rowsXml;
			content += `</office:spreadsheet></office:body></office:document-content>`;

			return content;
		},
		// Build styles XML for each unique style
		buildStylesXML(styleMap) {
			let stylesXml = '';
			for (const key in styleMap) {
				const s = styleMap[key].style;

				stylesXml += `<style:style style:name="${styleMap[key].id}" style:family="table-cell">`;

				// Table cell properties like background-color, borders
				if (s.backgroundColor || s.border) {
					stylesXml += `<style:table-cell-properties`;
					if (s.backgroundColor) stylesXml += ` fo:background-color="${s.backgroundColor}"`;
					if (s.border) stylesXml += ` fo:border="${s.border}"`;
					stylesXml += `/>\n`;
				}

				// Text properties like font-weight, color
				if (s.fontWeight || s.color || s.fontStyle) {
					stylesXml += `<style:text-properties`;
					if (s.fontWeight) stylesXml += ` fo:font-weight="${s.fontWeight}"`;
					if (s.fontStyle) stylesXml += ` fo:font-style="${s.fontStyle}"`;
					if (s.color) stylesXml += ` fo:color="${s.color}"`;
					stylesXml += `/>\n`;
				}

				// Paragraph properties like text-align, vertical-align
				if (s.textAlign || s.verticalAlign) {
					stylesXml += `<style:paragraph-properties`;
					if (s.textAlign) stylesXml += ` fo:text-align="${s.textAlign}"`;
					if (s.verticalAlign) stylesXml += ` style:vertical-align="${s.verticalAlign}"`;
					stylesXml += `/>\n`;
				}

				stylesXml += `</style:style>\n`;
			}
			return stylesXml;
		},
		rgbToHex(rgb) {
			// rgb can be in format "rgb(r, g, b)" or "rgba(r, g, b, a)"
			const result = rgb.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
			if (!result) return ''; // fallback: empty string if not rgb format

			const r = parseInt(result[1], 10);
			const g = parseInt(result[2], 10);
			const b = parseInt(result[3], 10);

			// Convert each to hex and pad to two digits
			return "#" + [r, g, b].map(x => x.toString(16).padStart(2, '0')).join('');
		},
		escapeXml(unsafe) {
			return unsafe.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&apos;");
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
		}

	}
})();
