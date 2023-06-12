
/**
 * @file
 * Schema.org settings element behaviors.
 */

"use strict";

((Drupal, once, tabbable) => {

  /**
   * CodeMirror options.
   *
   * @type {object}
   */
  const options = {
    mode: 'yaml',
    lineNumbers: true,
    matchBrackets: true,
    extraKeys: {
      // Setting for using spaces instead of tabs.
      // @see https://github.com/codemirror/CodeMirror/issues/988
      Tab: function (cm) {
        const spaces = Array(cm.getOption('indentUnit') + 1).join(' ');
        cm.replaceSelection(spaces, 'end', '+element');
      },
      // On 'Escape' move to the next tabbable input.
      // @see http://bgrins.github.io/codemirror-accessible/
      Esc: function (cm) {
        const textarea = cm.getTextArea();
        // Must show and then textarea so that we can determine
        // its tabindex.
        textarea.classList.add('visually-hidden');
        textarea.setAttribute('style', 'display: block');
        const tabbableElements = tabbable.tabbable(document);
        const tabindex = tabbableElements.indexOf(textarea);
        textarea.setAttribute('style', 'display: none');
        textarea.classList.remove('visually-hidden');
        // Tabindex + 2 accounts for the CodeMirror's iframe.
        tabbableElements[tabindex + 2].focus();
      },
    },
  };

  /**
   * Schema.org settings element YAML behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgSettingsElementYaml = {
    attach: function attach(context) {
      if (!window.CodeMirror) {
        return;
      }

      once('schemadotorg-codemirror', '.schemadotorg-codemirror', context)
        .forEach((element) => {
          // Track closed details and open them to initialize CodeMirror.
          // @see https://github.com/codemirror/codemirror5/issues/61
          let closedDetails = [];
          let parentElement = element.parentNode;
          while (parentElement) {
            if (parentElement.tagName === 'DETAILS'
              && !parentElement.getAttribute('open')) {
              parentElement.setAttribute('open', 'open');
              closedDetails.push(parentElement);
            }
            parentElement = parentElement.parentNode
          }

          // Set mode from data attribute.
          options.mode = element.getAttribute('data-mode') || options.mode;

          // Initialize CodeMirror.
          CodeMirror.fromTextArea(element, options);

          // Close opened details.
          if (closedDetails) {
            closedDetails.forEach((element) => element.removeAttribute('open'));
          }
        });
    }
  };
})(Drupal, once, tabbable);
