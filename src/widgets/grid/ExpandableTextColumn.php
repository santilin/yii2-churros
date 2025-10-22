<?php

namespace santilin\churros\widgets\grid;

use yii\grid\DataColumn;
use yii\helpers\{Html, Url, StringHelper, Markdown, HtmlPurifier};

class ExpandableTextColumn extends DataColumn
{
    /** @var int Maximum text length before truncation */
    public $length = 40;

    /**
     * @var string Format string. Examples:
     *   'text'              → plain text
     *   'html'              → purified HTML
	 *   'markdown'          → original
     *   'markdown:gfm'      → GitHub-Flavored Markdown
     *   'markdown:extra'    → Markdown Extra
     */
    public $textFormat = 'text';

    public $captionOptions = ['class' => 'see-more-content'];
    public $contentOptions = ['class' => 'see-more-container'];
    public $modalBodyOptions = ['class' => 'p-2'];
    public $modalTitle = null;

    protected function renderDataCellContent($model, $key, $index)
    {
        if ($this->modalTitle === null) {
            $this->modalTitle = $this->getHeaderCellLabel();
        }

        $text = $this->getDataCellValue($model, $key, $index);
        if ($text === null || (is_string($text) && !trim($text))) {
            return '';
        }

        if (is_array($text)) {
            $text = print_r($text, true);
        }
        $text = trim($text);

        [$formatType, $flavor] = array_pad(explode(':', $this->textFormat, 2), 2, null);

        switch ($formatType) {
            case 'markdown':
                $html = Markdown::process($text, $flavor ?? 'original');
                $html = HtmlPurifier::process($html);
                break;
            case 'html':
                $html = HtmlPurifier::process($text);
                break;
            case 'text':
                $html = Html::encode($text);
                break;
            default:
                throw new \Exception($formatType . ': format not supported');
        }

        if ($this->length > 0) {
            $cellKey = $index . '_' . $this->attribute;
            $truncated = StringHelper::truncate($text, $this->length, '…', null, true);

            $modalHtml = Html::tag('div', $html, $this->modalBodyOptions);
            $modalId = "modalSeeMore_$cellKey";

            $modal = <<<MODAL
<div class="modal fade" id="{$modalId}" tabindex="-1" aria-labelledby="{$modalId}_title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h5 class="modal-title mb-0" id="{$modalId}_title">{$this->modalTitle}</h5>
        <div class="d-flex align-items-center">
          <button type="button" class="btn btn-primary btn-sm me-2" id="copyBtn_$cellKey" title="Copy">
            <i class="far fa-clipboard"></i>
          </button>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body">{$modalHtml}</div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('copyBtn_$cellKey');
  if (btn) {
    btn.addEventListener('click', async () => {
      const content = document.querySelector('#{$modalId} .modal-body').innerText.trim();
      async function copyToClipboard(text) {
        // Primary (modern) method
        if (window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText) {
          try {
            await navigator.clipboard.writeText(text);
            console.log('Copied via navigator.clipboard');
            return;
          } catch (e) {
            console.warn('navigator.clipboard failed, fallback triggered', e);
          }
        }
        // Fallback for HTTP, Safari, or unsupported environments
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
          document.execCommand('copy');
          console.log('Copied via execCommand');
        } catch (err) {
          console.error('Copy failed:', err);
        } finally {
          document.body.removeChild(textArea);
        }
      }

      copyToClipboard(content);
    });
  }
});
</script>
MODAL;

            $expandButton = Html::a('<i class="fas fa-book-open"></i>', '#', [
                'title' => 'Click to read more',
                'class' => 'btn btn-outline-secondary btn-sm py-0 px-1 ms-3',
                'style' => 'font-size: xx-small;',
                'data' => [
                    'bs-toggle' => 'modal',
                    'bs-target' => "#{$modalId}",
                ],
            ]);

            return $modal . Html::tag('span', Html::encode($truncated), $this->captionOptions) . $expandButton;
        }

        return $html;
    }
}
