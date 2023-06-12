/* eslint-disable strict */

/**
 * @file
 * Schema.org form behaviors.
 */

"use strict";

((Drupal, once) => {
  /**
   * Schema.org form behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgFormSubmitOnce = {
    attach: function attach(context) {
      once('schemadotorg-submit-once', 'form.js-schemadotorg-submit-once', context)
        .forEach((form) => {
          const submit = form.querySelector('.form-actions input[type="submit"]');

          // Track which button is clicked.
          submit.addEventListener('click', () => submit.classList.add('js-schemadotorg-submit-clicked'));

          // Disable the submit button, remove the cancel link,
          // and display a progress throbber.
          form.addEventListener('submit', () => {
            submit.disabled = true;

            const cancelLink = submit.parentNode.querySelector('#edit-cancel');
            cancelLink && cancelLink.remove();

            const throbber = Drupal.theme.ajaxProgressThrobber();
            submit.insertAdjacentHTML('afterend', throbber);

          });
        });
    }
  };
})(Drupal, once);
