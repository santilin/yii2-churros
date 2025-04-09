const ChurrosGrid = (function() {
	return {
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
		}

	}
})();
