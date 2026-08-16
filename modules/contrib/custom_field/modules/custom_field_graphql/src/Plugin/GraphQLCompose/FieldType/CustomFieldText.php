<?php

declare(strict_types=1);

namespace Drupal\custom_field_graphql\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "custom_field_text",
 *   type_sdl = "Text",
 * )
 */
class CustomFieldText extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface {

  use FieldProducerTrait;

  /**
   * The filter format repository service.
   *
   * @var \Drupal\filter\FilterFormatRepositoryInterface
   */
  protected FilterFormatRepositoryInterface $filterFormatRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->filterFormatRepository = $container->get(FilterFormatRepositoryInterface::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, FieldContext $context) {
    $property = (string) $context->getContextValue('property_name');
    $settings = $context->getContextValue('settings');
    $format = $settings['default_format'] ?? $this->filterFormatRepository->getFallbackFormatId();
    $processed = [
      '#type' => 'processed_text',
      '#text' => $item->{$property},
      '#format' => $format,
    ];
    return [
      'format' => $format,
      'value' => $item->{$property},
      'processed' => $processed,
    ];
  }

}
