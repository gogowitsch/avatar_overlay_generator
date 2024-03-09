<?php

namespace Drupal\avatar_overlay_generator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Class AvatarForm.
 */
class GenericAvatarForm extends FormBase {

  private \DOMDocument $dom;

  private string $picturePath;

  private \DOMElement $svgElement;

  /** @var bool|array */
  private $imageSize;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'avatar_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
    ];

    $form['note'] = [
      '#type' => 'markup',
      '#markup' =>
        '<br><br><small><small>' .
        $this->t('The source code for this page is available at @url.', ['@url' => Markup::create('<a href="https://github.com/gogowitsch/avatar_overlay_generator">github.com/gogowitsch/avatar_overlay_generator</a>')]) .
        '</small></small>',
    ];
    return $form;
  }

  private function buildRadioTitle($file) {
    $imageUrl = substr($file, strlen(\Drupal::root()), -3) . 'png';
    $title = $this->t('Click to open a larger example.');
    $imgTag = sprintf('<a href="%1$s" target="_blank"><img style="clear:both; float: left" width="70" src="%1$s" title="%2$s" alt="Avatar"></a> ', $imageUrl, $title);
    return $imgTag .
      substr(basename($file), 0, -4);
  }

  protected function buildOverlaySelection($dir): array {
    $radios = [
      '#type' => 'radios',
      '#title' => $this->t('Select overlay to add') . ':',
    ];
    $files = array_map('realpath', glob($dir . '/*.svg'));
    $radios['#options'] = array_combine(
      $files,
      array_map([$this, 'buildRadioTitle'], $files)
    );
    $radios['#short_name'] = array_combine(
      $files,
      array_map(static fn($file) => basename(substr($file, 0, -4)), $files)
    );
    $radios['#default_value'] = key($radios['#options']);
    return $radios;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->picturePath = $_FILES['files']['tmp_name']['source_picture'];
    if (!$this->picturePath) {
      $form_state->setError($form['source_picture'], $this->t('You have to select a picture from your computer.'));
      return;
    }
    $this->imageSize = $size = getimagesize($this->picturePath);
    if (!$size || $size[0] < 10 || $size[1] < 10) {
      $form_state->setError($form['source_picture'], $this->t('Invalid picture; please select a different picture.'));
    }
    $svgFile = $form_state->getValue('overlay_to_add');
    if (file_exists($svgFile)) {
      $this->svgElement = $this->getImageElement($svgFile);
    }
    else {
      $form_state->setError($form['overlay_to_add'], 'You have to select an overlay.');
    }

    parent::validateForm($form, $form_state);
  }

  private function getImageElement($file): \DOMElement {
    $this->dom = new \DOMDocument();
    $this->dom->load($file);
    $root = $this->dom->documentElement;
    $groups = $root->getElementsByTagName('g');
    foreach ($groups as $group) {
      /** @var \DOMElement $group */
      foreach ($group->attributes as $attribute) {
        /** @var \DOMAttr $attribute */
        if ($attribute->name == 'label' && $attribute->value == 'Foto') {
          return $group->getElementsByTagName('image')->item(0);
        }
      }
    }
    throw new \InvalidArgumentException('SVG element not found');
  }

  /**
   * {@inheritdoc}
   * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->svgElement->setAttributeNS('http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd', 'absref', $this->picturePath);
    $this->svgElement->setAttributeNS('http://www.w3.org/1999/xlink', 'href', $this->picturePath);
    /** @var \Drupal\Core\File\FileSystem $file_system */
    $file_system = \Drupal::service('file_system');
    $this->dom->save($tmp_svg_file = $file_system->tempnam($file_system->getTempDirectory(), 'svg_'));
    $tmp_png_file = $file_system->tempnam($file_system->getTempDirectory(), 'png_');

    $expandFactor = 1.164876404;
    $width = round($this->imageSize[0] * $expandFactor);
    $cmd = "inkscape '$tmp_svg_file' --export-png='$tmp_png_file' --export-width=$width";
    exec($cmd, $output, $result_code);
    if ($result_code) {
      $msg = $this->t('The avatar overlay couldn’t be added.');
      \Drupal::messenger()->addError($msg);
      \Drupal::logger('avatar_overlay_generator')->error('Failed to execute Inkscape: @error', ['@error' => $msg . ' ' . $cmd . ' ' . implode(', ', $output)]);
    }
    else {
      $this->showSuccessMessage($tmp_png_file, $form['overlay_to_add']['#short_name'], $form_state);
    }
  }

  protected function buildSelectPicture(array $form): array {
    /** @noinspection SyntaxError */
    $form['source_picture'] = [
      '#type' => 'file',
      '#title' => $this->t('Select a source picture from your computer') . ':',
      '#description' => $this->t('A square is recommended') . '.',
      '#upload_validators' => [
        'file_validate_is_image' => [],
      ],
    ];
    return $form;
  }

  private function showSuccessMessage($tmp_png_file, $shortName, FormStateInterface $form_state): void {
    $msg = $this->t(
      'Success! You can <a href=":url">download the picture here</a>.',
      [
        ':url' => \Drupal::urlGenerator()->generateFromRoute(
          'avatar_overlay_generator.download',
          [
            'file' => basename($tmp_png_file),
            'name' => preg_replace('/[^-a-z_0-9.]/i', '', $_FILES['files']['name']['source_picture']),
            'result' => $shortName[$form_state->getValue('overlay_to_add')],
          ]
        ),
      ]
    );
    \Drupal::messenger()->addStatus($msg);
  }

}
