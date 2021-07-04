<?php

namespace Drupal\avatar_overlay_generator\Form;

use Drupal\Core\Form\FormStateInterface;

class AugustRiseUpAvatarForm extends GenericAvatarForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#markup'] = <<<'HTML'
        <p id="explain">Am 16.08.2021 beginnt das <a href="https://augustriseup.de/mitmachen/">RiseUp in Berlin</a>.
        Zeige deine Unterstützung, indem du bis dahin dein eigenes Social Media-Bildchen („Avatar“) anpasst.
        Mit diesem Generator kannst du einen Schriftzug auf dein Foto legen. Es ist geeignet für die meisten Social Media-Plattformen, z.B. Twitter, Signal, Telegram, LinkedIn, Element, Mattermost und Instagram.</p><hr>
      HTML;

    $form = $this->buildSelectPicture($form);

    $form ['overlay_to_add'] = $this->buildOverlaySelection(__DIR__ . '/../../overlays/AugustRiseUp');

    return parent::buildForm($form, $form_state);
  }

}
