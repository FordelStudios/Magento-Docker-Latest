<?php
declare(strict_types=1);

namespace Formula\Wati\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Formula\Wati\Model\TemplateVariables;

/**
 * Admin block to display available template variables reference
 */
class VariableReference extends Field
{
    /**
     * @var TemplateVariables
     */
    protected $templateVariables;

    /**
     * @param Context $context
     * @param TemplateVariables $templateVariables
     * @param array $data
     */
    public function __construct(
        Context $context,
        TemplateVariables $templateVariables,
        array $data = []
    ) {
        $this->templateVariables = $templateVariables;
        parent::__construct($context, $data);
    }

    /**
     * Render the variable reference HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $variablesByCategory = $this->templateVariables->getVariablesByCategory();

        $html = '<div class="wati-variable-reference" style="max-height: 600px; overflow-y: auto;">';

        // Add copy instruction
        $html .= '<div style="background: #fffbdd; border: 1px solid #f0c36d; padding: 12px; margin-bottom: 15px; border-radius: 4px;">';
        $html .= '<strong style="color: #735c0f;">Important:</strong> ';
        $html .= 'Use these exact variable names when creating templates in Wati dashboard. ';
        $html .= 'Click on any variable name to copy it. Variables must match exactly (case-sensitive).';
        $html .= '</div>';

        foreach ($variablesByCategory as $category => $variables) {
            $html .= '<div class="variable-category" style="margin-bottom: 20px;">';
            $html .= '<h4 style="background: #f5f5f5; padding: 10px; margin: 0 0 10px 0; border-left: 4px solid #eb5202;">';
            $html .= htmlspecialchars($category) . ' Variables';
            $html .= '</h4>';

            $html .= '<table class="admin__table-primary" style="width: 100%; border-collapse: collapse;">';
            $html .= '<thead>';
            $html .= '<tr style="background: #fafafa;">';
            $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #e3e3e3; width: 25%;">Variable Name</th>';
            $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #e3e3e3; width: 50%;">Description</th>';
            $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #e3e3e3; width: 25%;">Example</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach ($variables as $name => $info) {
                $html .= '<tr>';
                $html .= '<td style="padding: 10px; border: 1px solid #e3e3e3; vertical-align: top;">';
                $html .= '<code class="copyable-variable" ';
                $html .= 'style="background: #f0f0f0; padding: 4px 8px; border-radius: 3px; cursor: pointer; display: inline-block; font-family: monospace; font-size: 13px;" ';
                $html .= 'onclick="copyVariable(this)" ';
                $html .= 'title="Click to copy">';
                $html .= '{{' . htmlspecialchars($name) . '}}';
                $html .= '</code>';
                $html .= '</td>';
                $html .= '<td style="padding: 10px; border: 1px solid #e3e3e3; vertical-align: top;">';
                $html .= htmlspecialchars($info['description']);
                $html .= '</td>';
                $html .= '<td style="padding: 10px; border: 1px solid #e3e3e3; vertical-align: top; color: #666; font-style: italic;">';
                $html .= htmlspecialchars($info['example']);
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
        }

        $html .= '</div>';

        // Add JavaScript for copy functionality
        $html .= $this->getCopyScript();

        return $html;
    }

    /**
     * Get JavaScript for copy functionality
     *
     * @return string
     */
    protected function getCopyScript(): string
    {
        return <<<'SCRIPT'
<script>
function copyVariable(element) {
    var text = element.innerText;

    // Create temporary textarea
    var textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.select();

    try {
        document.execCommand('copy');

        // Visual feedback
        var originalBg = element.style.background;
        var originalText = element.innerText;
        element.style.background = '#4caf50';
        element.style.color = '#fff';
        element.innerText = 'Copied!';

        setTimeout(function() {
            element.style.background = originalBg;
            element.style.color = '';
            element.innerText = originalText;
        }, 1000);
    } catch (err) {
        console.error('Copy failed:', err);
    }

    document.body.removeChild(textarea);
}
</script>
SCRIPT;
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
