/* eslint-disable strict, no-use-before-define */

/**
 * @file
 * Schema.org UI behaviors.
 */

"use strict";

(($, Drupal, debounce, once) => {
  /**
   * Schema.org UI properties filter by text.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgUiPropertiesFilterByText = {
    attach: function attach(context) {
      once('schemadotorg-ui-properties-filter-text', 'input.schemadotorg-ui-properties-filter-text', context)
        .forEach((input) => {
          // Reset.
          const reset = document.createElement('input');
          reset.setAttribute('class', 'schemadotorg-ui-properties-filter-reset');
          reset.setAttribute('type', 'button');
          reset.setAttribute('title', Drupal.t('Clear the search query.'));
          reset.setAttribute('value', '✕');
          reset.setAttribute('style', 'display: none');
          reset.addEventListener('click', () => {
            input.value = '';
            input.dispatchEvent(new Event('keyup'));
            input.focus();
          });
          input.parentNode.appendChild(reset);

          // Filter rows.
          const table = document.querySelector('table.schemadotorg-ui-properties');
          let filterRows;
          if (table) {
            filterRows = table.querySelectorAll('div.schemadotorg-ui-property');
            input.addEventListener('keyup', debounce(filterBlockList, 200));
          }

          // Make sure the filter input is alway empty when the page loads.
          setTimeout(() => {input.value = ''}, 100);

          function filterBlockList(event) {
            const query = event.target.value.toLowerCase();

            // Use CSS to hide/show matches that the hide/show mapped properties
            // state is preserved.
            if (query.length >= 2) {
              table.classList.add('schemadotorg-ui-properties-filter-matches');
              filterRows.forEach((label) => {
                const textMatch = label.innerText.toLowerCase().includes(query);
                const tableRow = label.closest('tr');
                tableRow.classList.toggle('schemadotorg-ui-properties-filter-match', textMatch);
              });

              const totalProperties = table.querySelectorAll('.schemadotorg-ui-properties-filter-match').length;
              const message = Drupal.formatPlural(totalProperties, '1 property is available in the modified list.', '@count properties are available in the modified list.');
              Drupal.announce(message);
            } else {
              table.classList.remove('schemadotorg-ui-properties-filter-matches');
              filterRows.forEach((label) => {
                const tableRow = label.closest('tr');
                tableRow.classList.remove('schemadotorg-ui-properties-filter-match');
              });
            }

            // Hide/show reset.
            reset.style.display = query.length ? 'block' : 'none';
          }
        });
    }
  };

  /**
   * Schema.org UI properties toggle behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgUiPropertiesToggle = {
    attach: function attach(context) {
      // Toggle selected/mapped properties.
      once('schemadotorg-ui-properties-toggle', 'table.schemadotorg-ui-properties', context)
        .forEach((table) => {
          // Flag form validation errors.
          table.querySelectorAll('tbody .form-item--error')
            .forEach((element) => {
              while (element) {
                if (element.tagName === 'TR') {
                  element.classList.add('color-error');
                  break;
                }
                element = element.parentNode;
              }
            });

          // Make sure the table has mapping properties before proceeding.
          if (table.querySelector('tbody tr.color-warning, tbody tr.color-success, tbody tr.color-error') === null) {
            table.style.display = 'table';
            const ignoredProperties = document.querySelector('#edit-ignored-properties');
            if (ignoredProperties) {
              ignoredProperties.style.display = 'block';
            }
            return;
          }

          const hideUnmappedLabel = Drupal.t('Hide unmapped');
          const showUnmappedLabel = Drupal.t('Show unmapped');

          const toggleKey = 'schemadotorg-ui-properties-toggle';

          // If toggle key does not exist, set its default state
          // to hide unmapped.
          if (localStorage.getItem(toggleKey) === null) {
            localStorage.setItem(toggleKey, '1');
          }

          // Create toggle button.
          const toggleButton = document.createElement('button')
          toggleButton.setAttribute('type', 'button');
          toggleButton.setAttribute('class', 'schemadotorg-ui-properties-toggle link action-link action-link--extrasmall');
          toggleButton.addEventListener('click', () => {
            const toggleState = localStorage.getItem(toggleKey);
            localStorage.setItem(toggleKey, (toggleState === '1') ? '0' : '1');
            toggleProperties();
          });

          // Prepend toggle element with wrapper the table.
          const toggle = document.createElement('div');
          toggle.setAttribute('class', 'schemadotorg-ui-properties-toggle-wrapper');
          toggle.appendChild(toggleButton);

          const ginLayerWrapper = table.closest('.gin-layer-wrapper');
          if (ginLayerWrapper) {
            ginLayerWrapper.parentNode.insertBefore(toggle, ginLayerWrapper);
          }
          else {
            table.parentNode.insertBefore(toggle, table);
          }

          // Initialize properties toggle.
          toggleProperties();

          // Show the table after it has been fully initialized.
          table.style.display = 'table';

          const ignoredProperties = document.querySelector('#edit-ignored-properties');
          if (ignoredProperties) {
            ignoredProperties.style.display = 'block';
          }

          function toggleProperties() {
            const showAll = (localStorage.getItem('schemadotorg-ui-properties-toggle') === '0');
            if (showAll) {
              toggleButton.innerText = hideUnmappedLabel;
              toggleButton.classList.remove('action-link--icon-show');
              toggleButton.classList.add('action-link--icon-hide');

              table.querySelectorAll('tbody tr')
                .forEach((tablRow) => {tablRow.style.display = 'table-row'});
            }
            else {
              toggleButton.innerText = showUnmappedLabel;
              toggleButton.classList.add('action-link--icon-show');
              toggleButton.classList.remove('action-link--icon-hide');

              table.querySelectorAll('tbody tr')
                .forEach((tablRow) => {tablRow.style.display = 'none'});
              table.querySelectorAll('tbody tr.color-warning, tbody tr.color-success, tbody tr.color-error')
                .forEach((tablRow) => {tablRow.style.display = 'table-row'});
            }
          }
        });
    }
  };

  /**
   * Schema.org UI properties status behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgUiPropertyStatus = {
    attach: function attach(context) {
      once('schemadotorg-ui-property-status', 'table.schemadotorg-ui-properties select[name$="[field][name]"]', context)
        .forEach((element) => {
          element.addEventListener('change', event => {
            const select = event.target;
            const selectedValue = select.options[select.selectedIndex].value;
            let defaultSelectedValue = '';
            select.querySelectorAll('option')
              .forEach((option) => {
                if (option.defaultSelected) {
                  defaultSelectedValue = option.value;
                }
              });

            let tr = select
            while (tr.tagName !== 'TR') {
              tr = tr.parentNode;
            }

            tr.classList.remove('color-success');
            tr.classList.remove('color-warning');
            if (selectedValue) {
              tr.classList.add( (selectedValue !== defaultSelectedValue) ? 'color-warning' : 'color-success')
            }
          })
        });
    }
  };

  /**
   * Schema.org UI property add field summary behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgUiPropertyAddFieldSummary = {
    attach: function attach(context) {
      // Set detail summary from 'data-schemadotorg-ui-summary' attribute.
      once('schemadotorg-ui-property-summary', 'details[data-schemadotorg-ui-summary]', context)
        .forEach((details) => {
          const summary = details.getAttribute('data-schemadotorg-ui-summary')
          $(details)
            .drupalSetSummary(summary)
            .trigger('summaryUpdated');
        });

      // Set details summary from the 'Add new field' elements.
      const eventTypes = {
        'checkbox': 'click',
        'text': 'keydown',
        'select': 'change',
      };
      once('schemadotorg-ui-property-summary', 'table.schemadotorg-ui-properties details.schemadotorg-ui--add-field', context)
        .forEach((details) => {
          details.querySelectorAll('select[name$="[type]"], input[name$="[label]"], input[type="checkbox"]')
            .forEach((element) => {
              const type = (element.tagName === 'INPUT')
                ? element.getAttribute('type')
                : element.tagName.toLowerCase();
              element.addEventListener(eventTypes[type], () => setPropertyAddFieldSummary(details));
            });
          setPropertyAddFieldSummary(details);
        });
    }
  };

  function setPropertyAddFieldSummary(details) {
    let summary = '';

    // Add field label to details summary.
    const labelText = details.querySelector('input[name$="[label]"]');
    summary = `${labelText.value}: `;

    // Add field type to details summary.
    const typeSelect = details.querySelector('select[name$="[type]"]');
    summary += typeSelect.options[typeSelect.selectedIndex].text;

    // Add field unlimited to details summary.
    const unlimitedCheckbox = details.querySelector('input[name$="[unlimited]"]');
    if (unlimitedCheckbox.checked) {
      summary += ` - ${Drupal.t('unlimited')}`;
    }

    // Add field required to details summary.
    const requiredCheckbox = details.querySelector('input[name$="[required]"]');
    if (requiredCheckbox.checked) {
      summary += ` - ${Drupal.t('required')}`;
    }

    $(details)
      .drupalSetSummary(summary)
      .trigger('summaryUpdated');
  }
})(jQuery, Drupal, Drupal.debounce, once);
