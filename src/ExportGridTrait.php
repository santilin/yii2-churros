<?php
namespace santilin\churros;

use Yii;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\NotFoundHttpException;

/**
 * Exports a grid from the server, in full.
 *
 * The grid's toolbar already has a quick client-side CSV that dumps the table in
 * the DOM; that one only ever sees the page on screen. This is the other half:
 * it re-runs the search with the same filters, drops the pagination, and writes
 * every matching row.
 *
 * It is a trait rather than an Action so that actionExport() runs inside the
 * controller, exactly as actionIndex() does: createSearchModel() is protected,
 * and going through it also brings the master-model linking that embedded grids
 * need. Add the button in the grid view with
 * {@see \santilin\churros\widgets\grid\GridView::addSplitExportButton()}.
 */
trait ExportGridTrait
{
	/**
	 * @var array|callable|null attributes to export, in order. An array of
	 * attribute names, or a callable(model) returning one. Null means every
	 * safe attribute, in the table's own order.
	 */
	public $exportColumns = null;

	/** @var string|null base name of the downloaded file */
	public ?string $exportFilename = null;

	/**
	 * @var int rows per batch when walking the query. Exporting "everything"
	 * means the row count is unbounded, so the query is walked in batches
	 * instead of loading every model at once.
	 */
	public int $exportBatchSize = 200;

	/** @var array formats this action can write */
	public array $exportFormats = ['csv', 'ods', 'odt', 'pdf'];

	public function actionExport(string $format = 'csv')
	{
		$format = strtolower($format);
		if (!in_array($format, $this->exportFormats, true)) {
			throw new NotFoundHttpException("$format: unsupported export format");
		}
		[$provider, $model] = $this->createExportProvider();
		$columns = $this->resolveExportColumns($model);
		$title = $model->getModelInfo('title_plural');
		$name = $this->exportFilename ?: preg_replace('/[^\w-]+/u', '_', (string)$title);

		$headers = [];
		foreach ($columns as $attribute) {
			$headers[] = (string)$model->getAttributeLabel($attribute);
		}

		switch ($format) {
			case 'csv': return $this->sendCsv($provider, $columns, $headers, $name);
			case 'ods': return $this->sendOds($provider, $columns, $headers, $name, $title);
			case 'odt': return $this->sendOdt($provider, $columns, $headers, $name, $title);
			case 'pdf': return $this->sendPdf($provider, $columns, $headers, $name, $title);
		}
	}

	/**
	 * Rebuilds the index search with the request's filters and no pagination,
	 * so the export matches what the grid is showing.
	 */
	protected function createExportProvider(): array
	{
		// the very same call actionIndex() makes, so the export sees the same
		// rows: filters from the request, and the master model linked in for
		// embedded grids
		$model = $this->createSearchModel();
		if (!$model) {
			throw new NotFoundHttpException('Unable to create a search model to export');
		}
		$provider = $model->search($this->request->queryParams);
		if (!$provider instanceof ActiveDataProvider) {
			throw new InvalidConfigException('Export needs an ActiveDataProvider');
		}
		// every matching row, not the page the user happens to be on
		$provider->pagination = false;
		return [$provider, $model];
	}

	protected function resolveExportColumns($model): array
	{
		if (is_callable($this->exportColumns)) {
			return call_user_func($this->exportColumns, $model);
		}
		if (is_array($this->exportColumns) && $this->exportColumns) {
			return $this->exportColumns;
		}
		// safeAttributes() comes back alphabetical, which reads badly in a
		// report — the id lands in the middle and the name near the end. Take
		// the table's own order instead, keeping only the safe ones.
		$safe = array_flip($model->safeAttributes());
		return array_values(array_filter($model->attributes(),
			fn($attribute) => isset($safe[$attribute])));
	}

	/**
	 * One row of already-formatted strings.
	 *
	 * Enums are the reason this exists: the column holds 1 or 2, and only the
	 * model knows those mean "Solo información" and "Con seguimiento". Exporting
	 * the raw number would be worse than useless to whoever opens the file.
	 */
	protected function exportRowValues($model, array $columns): array
	{
		$row = [];
		foreach ($columns as $attribute) {
			$getter = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $attribute))) . 'Label';
			if (method_exists($model, $getter)) {
				$row[] = (string)$model->$getter();
				continue;
			}
			$value = $model->$attribute ?? null;
			if (is_bool($value)) {
				// the formatter is already translated; Yii::t('churros', 'Yes')
				// is not, and came out in English
				$value = Yii::$app->formatter->asBoolean($value);
			}
			$row[] = $value === null ? '' : (string)$value;
		}
		return $row;
	}

	/** Walks the query in batches, yielding one array of strings per record. */
	protected function eachExportRow($provider, array $columns): \Generator
	{
		foreach ($provider->query->each($this->exportBatchSize) as $model) {
			yield $this->exportRowValues($model, $columns);
		}
	}

	/**
	 * One CSV line as a string.
	 *
	 * $escape is passed explicitly: PHP 8.4 deprecates leaving it out, and ''
	 * is the RFC 4180 behaviour — quotes are doubled, nothing is backslashed.
	 */
	protected function csvLine(array $row): string
	{
		$handle = fopen('php://temp', 'r+');
		fputcsv($handle, $row, ',', '"', '');
		rewind($handle);
		$line = stream_get_contents($handle);
		fclose($handle);
		return $line;
	}

	protected function sendCsv($provider, array $columns, array $headers, string $name)
	{
		$response = $this->response;
		$response->format = \yii\web\Response::FORMAT_RAW;
		$response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
		$response->headers->set('Content-Disposition',
			'attachment; filename="' . $name . '.csv"');
		// A callable stream has to RETURN something iterable: Yii does
		// `foreach (call_user_func($this->stream) as $chunk)`. Echoing from
		// inside and returning null is what produced the 500.
		$response->stream = function () use ($provider, $columns, $headers) {
			return (function () use ($provider, $columns, $headers) {
				// BOM: without it spreadsheets open UTF-8 accents as mojibake
				yield "\xEF\xBB\xBF";
				yield $this->csvLine($headers);
				foreach ($this->eachExportRow($provider, $columns) as $row) {
					yield $this->csvLine($row);
				}
			})();
		};
		return $response;
	}

	protected function sendOds($provider, array $columns, array $headers, string $name, $title)
	{
		$this->requireClass(\PhpOffice\PhpSpreadsheet\Spreadsheet::class,
			'phpoffice/phpspreadsheet', 'ODS');
		$book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $book->getActiveSheet();
		$sheet->setTitle(mb_substr((string)$title, 0, 31));
		$sheet->fromArray($headers, null, 'A1');
		$sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')
			->getFont()->setBold(true);
		$line = 2;
		foreach ($this->eachExportRow($provider, $columns) as $row) {
			$sheet->fromArray($row, null, 'A' . $line++);
		}
		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Ods($book);
		return $this->sendWriter($writer, "$name.ods",
			'application/vnd.oasis.opendocument.spreadsheet');
	}

	protected function sendOdt($provider, array $columns, array $headers, string $name, $title)
	{
		$this->requireClass(\PhpOffice\PhpWord\PhpWord::class, 'phpoffice/phpword', 'ODT');
		$word = new \PhpOffice\PhpWord\PhpWord();
		$section = $word->addSection(['orientation' => 'landscape']);
		$section->addTitle((string)$title, 1);
		$table = $section->addTable(['borderSize' => 6, 'cellMargin' => 60]);
		$table->addRow();
		foreach ($headers as $header) {
			$table->addCell(2000)->addText($header, ['bold' => true]);
		}
		foreach ($this->eachExportRow($provider, $columns) as $row) {
			$table->addRow();
			foreach ($row as $value) {
				$table->addCell(2000)->addText($value);
			}
		}
		$writer = \PhpOffice\PhpWord\IOFactory::createWriter($word, 'ODText');
		return $this->sendWriter($writer, "$name.odt",
			'application/vnd.oasis.opendocument.text');
	}

	/**
	 * PDF through the same mpdf the per-record actionPdf() already uses, so a
	 * project that prints records gets its export looking the same.
	 */
	protected function sendPdf($provider, array $columns, array $headers, string $name, $title)
	{
		$this->requireClass(\kartik\mpdf\Pdf::class, 'kartik-v/yii2-mpdf', 'PDF');
		$html = Html::tag('h1', Html::encode((string)$title));
		$cells = '';
		foreach ($headers as $header) {
			$cells .= Html::tag('th', Html::encode($header));
		}
		$body = '';
		foreach ($this->eachExportRow($provider, $columns) as $row) {
			$tds = '';
			foreach ($row as $value) {
				$tds .= Html::tag('td', nl2br(Html::encode($value)));
			}
			$body .= Html::tag('tr', $tds);
		}
		$html .= Html::tag('table', Html::tag('thead', Html::tag('tr', $cells))
			. Html::tag('tbody', $body), ['class' => 'table table-bordered']);

		$pdf = new \kartik\mpdf\Pdf([
			'mode' => \kartik\mpdf\Pdf::MODE_UTF8,
			'format' => \kartik\mpdf\Pdf::FORMAT_A4,
			// a grid is wider than it is tall
			'orientation' => \kartik\mpdf\Pdf::ORIENT_LANDSCAPE,
			'destination' => \kartik\mpdf\Pdf::DEST_DOWNLOAD,
			'filename' => "$name.pdf",
			'content' => $html,
			'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
			'options' => ['title' => (string)$title],
			'methods' => [
				'setHeader' => date('Y-m-d H:i') . '|' . $title . '|{PAGENO}',
			],
		]);
		return $pdf->render();
	}

	/**
	 * Streams a PhpOffice writer without leaving a file behind.
	 *
	 * The document is built into a temporary file and the open handle is given
	 * to Yii, which reads resources in chunks. It is not true streaming, but
	 * neither ODS nor ODT can be produced incrementally: both are zip archives
	 * whose central directory is written last.
	 */
	protected function sendWriter($writer, string $filename, string $mime)
	{
		// A temporary file, not php://temp: PhpSpreadsheet's writer takes a
		// resource but PhpWord's ODText::save() is typed `string $filename`,
		// and passing it a handle is what made the ODT export a 500.
		$file = tempnam(sys_get_temp_dir(), 'churros-export-');
		$writer->save($file);
		$handle = fopen($file, 'rb');
		// unlinked while still open: on POSIX the handle stays valid and the
		// file is reclaimed on close, so nothing is left behind even if the
		// download is aborted halfway
		@unlink($file);

		$response = $this->response;
		$response->format = \yii\web\Response::FORMAT_RAW;
		$response->headers->set('Content-Type', $mime);
		$response->headers->set('Content-Disposition',
			'attachment; filename="' . $filename . '"');
		$response->stream = $handle;
		return $response;
	}

	/** A missing optional dependency should say which one, not fatal on a class. */
	protected function requireClass(string $class, string $package, string $format): void
	{
		if (!class_exists($class)) {
			throw new InvalidConfigException(
				"$format export needs the $package package: composer require $package");
		}
	}
}
