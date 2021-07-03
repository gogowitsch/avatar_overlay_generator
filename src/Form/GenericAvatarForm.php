<?php

namespace Drupal\avatar_overlay_generator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AvatarForm.
 */
class GenericAvatarForm extends FormBase {

  private $dom;

  private $picturePath;

  private $svgElement;

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
    $form = $this->buildSelectPicture($form);

    $form['overlay_to_add'] = $this->buildOverlaySelection(__DIR__ . '/../..');

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
    ];
    return $form;
  }

  private function buildRadioTitle($file) {
    $imageUrl = substr($file, strlen(\Drupal::root()), -3) . 'png';
    $imgTag = sprintf('<img style="clear:both; float: left" width="30" src="%s" title="%s"> ', $imageUrl, $this->t('Example'));
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
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->svgElement->setAttributeNS('http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd', 'absref', $this->picturePath);
    $this->svgElement->setAttributeNS('http://www.w3.org/1999/xlink', 'href', $this->picturePath);
    /** @var \Drupal\Core\File\FileSystem $file_system */
    $file_system = \Drupal::service('file_system');
    $this->dom->save($tmp_svg_file = $file_system->tempnam($file_system->getTempDirectory(), 'svg_'));
    $tmp_png_file = $file_system->tempnam($file_system->getTempDirectory(), 'png_');

    $expandFactor = 1.164876404;
    $messenger = \Drupal::messenger();
    $width = round($this->imageSize[0] * $expandFactor);
    $cmd = "inkscape '$tmp_svg_file' --export-png='$tmp_png_file' --export-width=$width";
    exec($cmd, $output, $result_code);
    if ($result_code) {
      $msg = $this->t('The avatar overlay couldn’t be added.');
      $messenger->addError($msg);
      \Drupal::logger('avatar_overlay_generator')->error('Failed to execute Inkscape: @error', ['@error' => $msg . ' ' . $cmd . ' ' . implode(', ', $output)]);
    }
    else {
      $messenger->addStatus(
        $this->t(
          'Success! You can <a href=":url">download the picture here</a>.',
          [
            ':url' => \Drupal::urlGenerator()->generateFromRoute(
              'avatar_overlay_generator.download',
              [
                'file' => basename($tmp_png_file),
                'name' => preg_replace('/[^-a-z_0-9.]/i', '', $_FILES['files']['name']['source_picture']),
                'result' => $form['overlay_to_add']['#short_name'][$form_state->getValue('overlay_to_add')],
              ]
            ),
          ]
        )
      );
    }
  }

  protected function buildSelectPicture(array $form): array {
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

}
