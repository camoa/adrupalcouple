<?php

namespace Drupal\geolocation_address\Plugin\geolocation\DataProvider;

use CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Utility\Token;
use Drupal\geolocation\DataProviderBase;
use Drupal\geolocation\DataProviderInterface;
use Drupal\geolocation\GeocoderInterface;
use Drupal\geolocation\GeocoderManager;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides default address field.
 *
 * @DataProvider(
 *   id = "geolocation_address_field_provider",
 *   name = @Translation("Address Field"),
 *   description = @Translation("Address Field."),
 * )
 */
class AddressFieldProvider extends DataProviderBase implements DataProviderInterface {

  /**
   * Geocoder for address resolution.
   *
   * @var \Drupal\geolocation\GeocoderInterface
   */
  protected GeocoderInterface $geocoder;

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityFieldManagerInterface $entity_field_manager,
    ModuleHandlerInterface $moduleHandler,
    Token $token,
    protected GeocoderManager $geocoderManager,
    protected AddressFormatRepositoryInterface $addressFormatRepository,
    protected CountryRepositoryInterface $countryRepository,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager, $moduleHandler, $token);
    if (!empty($configuration['geocoder'])) {
      $this->geocoder = $this->geocoderManager->createInstance($configuration['geocoder']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): DataProviderInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('module_handler'),
      $container->get('token'),
      $container->get('plugin.manager.geolocation.geocoder'),
      $container->get('address.address_format_repository'),
      $container->get('address.country_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isViewsGeoOption(FieldPluginBase $viewsField): bool {
    if ($viewsField instanceof EntityField) {

      /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
      $entityFieldManager = \Drupal::service('entity_field.manager');

      $field_map = $entityFieldManager->getFieldMap();

      if (
        !empty($field_map)
        &&!empty($viewsField->configuration['entity_type'])
        && !empty($viewsField->configuration['field_name'])
        && !empty($field_map[$viewsField->configuration['entity_type']])
        && !empty($field_map[$viewsField->configuration['entity_type']][$viewsField->configuration['field_name']])
      ) {
        if ($field_map[$viewsField->configuration['entity_type']][$viewsField->configuration['field_name']]['type'] == 'address') {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldGeoOption(FieldDefinitionInterface $fieldDefinition): bool {
    return ($fieldDefinition->getType() == 'address');
  }

  /**
   * {@inheritdoc}
   */
  public function getPositionsFromItem(FieldItemInterface $fieldItem): array {
    if (!($fieldItem instanceof AddressItem)) {
      return [];
    }

    if (empty($this->geocoder)) {
      return [];
    }

    $address_format = str_replace(["\r", "\n"], ' ', $this->addressFormatRepository->get($fieldItem->getCountryCode())->getFormat());

    $formatted_address = new FormattableMarkup(str_replace('%', ':', $address_format), [
      ':givenName' => $fieldItem->getGivenName(),
      ':familyName' => $fieldItem->getFamilyName(),
      ':organization' => $fieldItem->getOrganization(),
      ':addressLine1' => $fieldItem->getAddressLine1(),
      ':addressLine2' => $fieldItem->getAddressLine2(),
      ':dependentLocality' => $fieldItem->getDependentLocality(),
      ':locality' => $fieldItem->getLocality(),
      ':administrativeArea' => $fieldItem->getAdministrativeArea(),
      ':postalCode' => $fieldItem->getPostalCode(),
      ':sortingCode' => $fieldItem->getSortingCode(),
    ]);

    $address = (string) $formatted_address;
    $address = trim($address);
    $address = $address . ' ' . $this->countryRepository->get($fieldItem->getCountryCode())->getName();

    $coordinates = $this->geocoder->geocode($address);
    return !empty($coordinates['location']) ? [$coordinates['location']] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = []): array {
    $element = parent::getSettingsForm($settings, $parents);

    $geocoder_options = [];
    foreach ($this->geocoderManager->getDefinitions() as $geocoder_id => $geocoder_definition) {
      if (empty($geocoder_definition['locationCapable'])) {
        continue;
      }
      $geocoder_options[$geocoder_id] = $geocoder_definition['name'];
    }

    if (empty($geocoder_options)) {
      return [
        '#markup' => $this->t('No geocoder option found'),
      ];
    }

    $element['geocoder'] = [
      '#type' => 'select',
      '#title' => $this->t('Geocoder'),
      '#options' => $geocoder_options,
      '#default_value' => empty($settings['geocoder']) ? key($geocoder_options) : $settings['geocoder'],
      '#description' => $this->t('Choose plugin to geocode address into coordinates.'),
      '#weight' => -1,
    ];

    return $element;
  }

}
