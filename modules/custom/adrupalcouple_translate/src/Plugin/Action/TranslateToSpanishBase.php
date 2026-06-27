<?php

declare(strict_types=1);

namespace Drupal\adrupalcouple_translate\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ai_translate\TextExtractorInterface;
use Drupal\ai_translate\TextTranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base for "translate this entity to Spanish" VBO actions.
 *
 * Works on any translatable content entity (node, taxonomy_term, block_content,
 * menu_link_content, …). Mirrors `drush ai:translate-entity`: extract → translate
 * each field → addTranslation → save. Idempotent — skips entities already in
 * Spanish or already translated. Subclasses only declare the #[Action] attribute
 * (id + entity type); the logic lives here.
 */
abstract class TranslateToSpanishBase extends ActionBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly TextExtractorInterface $textExtractor,
    protected readonly TextTranslatorInterface $textTranslator,
    protected readonly LanguageManagerInterface $languageManager,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ai_translate.text_extractor'),
      $container->get('ai_translate.text_translator'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    $target = 'es';

    if (!$entity->isTranslatable()) {
      $this->messenger()->addWarning($this->t('"@label" is not translatable.', ['@label' => $entity->label()]));
      return;
    }

    // Always translate from the original (default) translation.
    $entity = $entity->getUntranslated();
    $source = $entity->language()->getId();

    if ($source === $target) {
      $this->messenger()->addWarning($this->t('"@label" is already in Spanish.', ['@label' => $entity->label()]));
      return;
    }
    if ($entity->hasTranslation($target)) {
      $this->messenger()->addWarning($this->t('"@label" already has a Spanish translation.', ['@label' => $entity->label()]));
      return;
    }

    try {
      $langNames = $this->languageManager->getNativeLanguages();
      $textMetadata = $this->textExtractor->extractTextMetadata($entity);

      foreach ($textMetadata as &$singleField) {
        foreach ($singleField['_columns'] as $column) {
          $singleField['translated'][$column] = '';
          if (!empty($singleField[$column])) {
            $singleField['translated'][$column] = $this->textTranslator->translateContent(
              $singleField[$column],
              $langNames[$target],
              $langNames[$source] ?? NULL,
            );
          }
        }
        foreach ($singleField['translated'] as &$translated_text_item) {
          $translated_text_item = html_entity_decode($translated_text_item);
        }
      }

      $translation = $entity->addTranslation($target, $entity->toArray());
      $this->textExtractor->insertTextMetadata($translation, $textMetadata);
      $translation->save();

      $this->messenger()->addStatus($this->t('Translated "@label" to Spanish.', ['@label' => $entity->label()]));
    }
    catch (\Throwable $e) {
      $this->messenger()->addError($this->t('Failed to translate "@label": @error', [
        '@label' => $entity->label(),
        '@error' => $e->getMessage(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $object */
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
