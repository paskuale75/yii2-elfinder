<?php

namespace simialbi\yii2\elfinder\widgets;

use simialbi\yii2\widgets\InputWidget;
use Yii;
use yii\bootstrap\Html;
use yii\bootstrap\Modal;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;

/**
 * ElFinderInput widget renders an bootstrap form group with input group button which opens an elfinder modal for
 * selecting files.
 *
 * ```php
 * <?php echo ElFinderInput::widget([
 *      'name' => 'my-file',
 *      'value' => '/path/to/my/file.ext',
 *      'instanceName' => 'default'
 * ]); ?>
 * ```
 * or with model usage
 * ```php
 * <?php echo $form->field($model, 'my-file')->widget(
 *      ElFinderInput::className(),
 *      [
 *          'instanceName' => 'default'
 *      ]
 * ); ?>
 * ```
 *
 * @property-write string $icon Elfinder button icon
 * @property-write string $modalIcon Icon in modal header
 * @property-write array $previewOptions Preview button options
 *
 * @author Simon Karlen <simi.albi@gmail.com>
 * @since 1.1
 */
class ElFinderInput extends InputWidget
{
    /**
     * @var array the HTML attributes (name-value pairs) for the field container tag.
     * The values will be HTML-encoded using [[Html::encode()]].
     * If a value is `null`, the corresponding attribute will not be rendered.
     * The following special options are recognized:
     *
     * - `tag`: the tag name of the container element. Defaults to `div`. Setting it to `false` will not render a container tag.
     *   See also [[\yii\helpers\Html::tag()]].
     *
     * If you set a custom `id` for the container element, you may need to adjust the [[$selectors]] accordingly.
     *
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = ['class' => 'form-control'];
    /**
     * @var string label text. It will NOT be HTML-encoded. Therefore you can pass in HTML code
     * such as an image tag. If this is is coming from end users, you should [[encode()]]
     * it to prevent XSS attacks.
     */
    public string $label;
    /**
     * @var string name of the instance used in module configuration (defaults to default)
     */
    public string $instanceName = 'default';
    /**
     * @var array ElFinder widget configuration options
     */
    public array $elfinderOptions = [];
    /**
     * @var boolean add an image cropper widget (requires demi/cropper)
     */
    public bool $addImageCrop = false;
    /**
     * @var array options for image cropper
     */
    public array $cropperOptions = [];
    /**
     * @var boolean add a preview button
     */
    public bool $addPreview = false;
    /**
     * @var string Preview button content
     */
    public string $previewContent = '<span class="glyphicon glyphicon-eye-open"></span>';
    /**
     * @var array Preview button options
     */
    public array $previewButtonOptions = [
        'class' => ['btn', 'btn-default'],
        'target' => '_blank'
    ];
    /**
     * @var array Open elfinder button options
     */
    public array $openButtonOptions = [
        'class' => ['btn', 'btn-default'],
    ];
    /**
     * @var array modal options
     * @see \yii\bootstrap\Modal
     */
    public array $modalOptions = [];
    /**
     * @var boolean return only path or full url
     */
    public bool $onlyPath = false;

    /**
     * {@inheritdoc}
     * @throws \ReflectionException
     */
    public function init(): void
    {
        if (!isset($this->options['id'])) {
            if ($this->hasModel()) {
                $this->options['id'] = Html::getInputId($this->model, $this->attribute);
            } else {
                $this->options['id'] = $this->getId();
            }
        }

        $this->registerTranslations();

        parent::init();
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function run(): string
    {
        $options = $this->options;
        $cropperCallback = '';

        $label = Yii::t('simialbi/elfinder/input-widget', 'Choose file');
        if ($this->hasModel()) {
            $label = $this->model->getAttributeLabel($this->attribute);
        }
        $html = Html::beginTag('div', [
            'class' => 'input-group'
        ]);
        if ($this->hasModel()) {
            $html .= Html::activeTextInput($this->model, $this->attribute, $options);
        } else {
            $html .= Html::textInput($this->name, $this->value, $options);
        }
        $html .= Html::beginTag('div', ['class' => 'input-group-btn']);
        if ($this->addPreview) {
            $value = $this->hasModel() ? $this->model->{$this->attribute} : $this->value;
            $html .= Html::a($this->previewContent, $value, $this->previewButtonOptions);
        }
        if ($this->addImageCrop && class_exists('simialbi\yii2\crop\Cropper')) {
            $cropperOptions = ArrayHelper::merge([
                'type' => 'modal',
                'image' => $this->hasModel() ? $this->model->{$this->attribute} : $this->value,
                'options' => ['id' => $options['id'] . '-crop']
            ], $this->cropperOptions);
            $force = ArrayHelper::remove($cropperOptions, 'force', false);
            $html .= call_user_func(['simialbi\yii2\crop\Cropper', 'widget'], $cropperOptions);
            $cropperCallback = "jQuery('#{$options['id']}-crop > img').prop('src', file.url);";
            if ($force) {
                $cropperCallback .= "\njQuery(document).one('hidden.bs.modal', '#{$options['id']}-modal', function () {\n";
                $cropperCallback .= "jQuery('#{$cropperOptions['options']['id']}-target').modal('show');\n";
                $cropperCallback .= "});\n";
            }
        }
        $buttonOptions = ArrayHelper::merge([
            'id' => $options['id'] . '-btn',
            'data' => [
                'toggle' => 'modal',
                'target' => '#' . $options['id'] . '-modal'
            ]
        ], $this->openButtonOptions);
        $icon = ArrayHelper::remove($buttonOptions, 'icon', '<span class="glyphicon glyphicon-option-horizontal"></span>');
        $html .= Html::button($icon, $buttonOptions);
        $html .= Html::endTag('div'); // <!-- input-group-btn -->
        $html .= Html::endTag('div'); // <!-- input-group -->

        ob_start();
        $modalOptions = $this->modalOptions;
        $icon = ArrayHelper::remove($modalOptions, 'icon', '<span class="glyphicon glyphicon-folder-open"></span>');
        ArrayHelper::setValue($modalOptions, 'id', $options['id'] . '-modal');
        Modal::begin(ArrayHelper::merge([
            'size' => Modal::SIZE_LARGE,
            'header' => $icon . Html::tag('h4', $label, ['class' => 'modal-title']),
            'footer' => Html::button(Yii::t('simialbi/elfinder/input-widget', 'Close'), [
                    'class' => ['btn', 'btn-default'],
                    'data-dismiss' => 'modal'
                ]) . Html::button(Yii::t('simialbi/elfinder/input-widget', 'Save'), [
                    'class' => ['btn', 'btn-primary', 'pull-right'],
                    'data-dismiss' => 'modal'
                ])
        ], $modalOptions));
        $fullUrl = $this->onlyPath ? 'false' : 'true';
        $elfinderOptions = $this->elfinderOptions;
        $elfinderOptions['sound'] = false;
        $elfinderOptions['instanceName'] = $this->instanceName;
        $elfinderOptions['getFileCallback'] = new JsExpression("function (file) {
//			var fullUrl = $fullUrl,
//				parser = document.createElement('a');
//			parser.href = file.url;
			jQuery('#{$options['id']}').val(file.url).trigger('change');
			$cropperCallback
		}");
        if (!isset($elfinderOptions['id'])) {
            $elfinderOptions['id'] = $this->options['id'] . '-elfinder';
        }
        echo ElFinder::widget($elfinderOptions);
        Modal::end();

        $html .= ob_get_clean();

        $this->view->registerJs("jQuery(document).one('shown.bs.modal', '#{$options['id']}-modal', function () {
			jQuery('#{$elfinderOptions['id']}').trigger('resize');
		});");

        return $html;
    }

    /**
     * Icon setter for backwards compatibility
     *
     * @param string $icon
     * @deprecated
     */
    public function setIcon(string $icon): void
    {
        ArrayHelper::setValue($this->openButtonOptions, 'icon', $icon);
    }

    /**
     * Modal icon setter for backwards compatibility
     *
     * @param string $modalIcon
     * @deprecated
     */
    public function setModalIcon(string $modalIcon): void
    {
        ArrayHelper::setValue($this->modalOptions, 'icon', $modalIcon);
    }

    /**
     * Preview button options setter for backwards compatibility
     *
     * @param array $previewOptions
     * @deprecated
     */
    public function setPreviewOptions(array $previewOptions): void
    {
        $this->previewButtonOptions = $previewOptions;
    }
}
