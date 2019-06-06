(function ($, Drupal, drupalSettings, window) {
  'use strict';

  Drupal.behaviors.commerceCartBlocksAddToCartForm = {
    attach: function (context, settings) {
      Drupal.AjaxCommands.prototype.commerceCartBlocksRefreshPage = function (ajax, response) {
        window.location.reload();
      };

      if (settings.commerce_cart_blocks.ajax.refresh_page_after_modal) {
        $('.added-to-cart-dialog').not('.processed').on('dialogclose', function () {
          window.location.reload();
        }).addClass('processed');
      }
    }
  };

}(jQuery, Drupal, drupalSettings, window));
