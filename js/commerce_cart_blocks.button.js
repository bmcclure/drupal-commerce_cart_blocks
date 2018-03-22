/**
 * @file
 * Defines Javascript behaviors for the commerce cart module.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.commerceCartBlocksButton = {
    attach: function (context) {
      var $context = $(context);
      var $cart = $context.find('.commerce-cart-block--type-button');
      var $cartButton = $context.find('.commerce-cart-block--link__expand');
      var $cartContents = $cart.find('.commerce-cart-block--contents');

      if ($cartContents.length > 0) {
        $cartButton.on('click', function (e) {
          e.preventDefault();
          var windowWidth = $(window).width();
          var cartWidth = $cartContents.width() + $cart.offset().left;

          if (cartWidth > windowWidth) {
            $cartContents.addClass('is-outside-horizontal');
          }

          $cartButton.toggleClass('commerce-cart-block--link__open');

          // Toggle the expanded class.
          $cartContents
            .toggleClass('commerce-cart-block--contents__expanded')
            .slideToggle();

          if ($cartContents.hasClass('commerce-cart-block--contents__expanded')) {
            $(document).on('click.commerceCartButtons', function (event) {
              if (!$(event.target).closest($cart).length) {
                $cartButton.removeClass('commerce-cart-block--link__open');

                $cartContents
                  .removeClass('commerce-cart-block--contents__expanded')
                  .slideUp();

                $(document).off('click.commerceCartButtons');
              }
            });
          }
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
