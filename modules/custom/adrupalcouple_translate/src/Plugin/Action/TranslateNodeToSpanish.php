<?php

declare(strict_types=1);

namespace Drupal\adrupalcouple_translate\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai_translate\TextExtractorInterface;
use Drupal\ai_translate\TextTranslatorInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Translates a node to Spanish using AI Translate.
 *
 * Mirrors the logic of `drush ai:translate-entity` (extract → translate each
 * field → addTranslation → save) for one node, for use as a Views Bulk
 * Operations action. Idempotent: skips nodes already translated to Spanish.
 */
#[Action(
  id: 'adrupalcouple_translate_to_spanish',
  label: new TranslatableMarkup('Translate to Spanish (AI)'),
  type: 'node',
)]
final class TranslateNodeToSpanish extends ActionBase implements ContainerFactoryPluginInterface {

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
    if (!$entity instanceof NodeInterface) {
      return;
    }

    $target = 'es';

    if (!$entity->isTranslatable()) {
      $this->messenger()->addWarning($this->t('"@title" is not translatable.', [
        '@title' => $entity->label(),
      ]));
      return;
    }

    // Translate from the node's default (original) language.
    if ($entity->hasTranslation($entity->getUntranslated()->language()->getId())) {
      $entity = $entity->getUntranslated();
    }
    $source = $entity->language()->getId();

    if ($source === $target) {
      $this->messenger()->addWarning($this->t('"@title" is already in Spanish.', [
        '@title' => $entity->label(),
      ]));
      return;
    }

    if ($entity->hasTranslation($target)) {
      $this->messenger()->addWarning($this->t('"@title" already has a Spanish translation.', [
        '@title' => $entity->label(),
      ]));
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
      $this->entityTypeManager->getStorage('node')->save($translation);

      $this->messenger()->addStatus($this->t('Translated "@title" to Spanish.', [
        '@title' => $entity->label(),
      ]));
    }
    catch (\Throwable $e) {
      $this->messenger()->addError($this->t('Failed to translate "@title": @error', [
        '@title' => $entity->label(),
        '@error' => $e->getMessage(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
