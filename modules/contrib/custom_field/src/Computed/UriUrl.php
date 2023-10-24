<?php

namespace Drupal\custom_field\Computed;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Url;

/**
 * A computed property for generating url from uri.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - uri source: The uri property containing the value to be computed..
 */
class UriUrl extends TypedData {

  /**
   * The computed url.
   *
   * @var string|null
   */
  protected $url = NULL;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->languageManager = \Drupal::service('language_manager');

    if ($definition->getSetting('uri source') === NULL) {
      throw new \InvalidArgumentException("The definition's 'uri source' key has to specify the name of the text property to be processed.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    /** @var \Drupal\custom_field\Plugin\Field\FieldType\CustomItem $item */
    $item = $this->getParent();
    $uri = $item->{($this->definition->getSetting('uri source'))};
    if ($this->url !== NULL) {
      return $this->url;
    }
    else {
      $lang_code = $item->getLangcode();
      $url = Url::fromUri($uri, ['language' => $this->languageManager->getLanguage($lang_code)]);

      $this->url = $url->toString();
    }

    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE): void {
    $this->url = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
