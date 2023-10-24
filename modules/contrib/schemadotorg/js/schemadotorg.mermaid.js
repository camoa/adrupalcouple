/* eslint-disable no-undef */

/**
 * @file
 * Schema.org mermaid behaviors.
 */

((Drupal, mermaid, once) => {
  /**
   * Schema.org mermaid behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgMermaid = {
    attach: function attach(context) {
      const mermaids = once('mermaid', '.mermaid', context);
      if (!mermaids.length) {
        return;
      }

      const closedDetails = [];
      mermaids.forEach((element) => {
        // Track closed details and open them to after diagram is rendered.
        let parentElement = element.parentNode;
        while (parentElement) {
          // eslint-disable-next-line
          if (parentElement.tagName === 'DETAILS' && !parentElement.getAttribute('open')) {
            parentElement.setAttribute('open', 'open');
            closedDetails.push(parentElement);
          }
          parentElement = parentElement.parentNode;
        }
      });

      // Display mermaid containers after they're rendered.
      mermaid.initialize();

      // Close opened details.
      if (closedDetails) {
        setTimeout(() => {
          closedDetails.forEach((element) => element.removeAttribute('open'));
        }, 1000);
      }
    },
  };
})(Drupal, mermaid, once);
