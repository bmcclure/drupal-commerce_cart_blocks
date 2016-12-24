/**
 * @file
 * Defines Javascript behaviors for the commerce cart module.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.commerceCartBlocks = {
    attach: function (context) {
      var $context = $(context);
      var $cart = $context.find('.commerce-cart-block--type-button');
      var $cartButton = $context.find('.commerce-cart-block--link__expand');
      var $cartContents = $cart.find('.commerce-cart-block--contents');

      if ($cartContents.length > 0) {
        // Expand the block when the link is clicked.
        $cartButton.on('click', function (e) {
          // Prevent it from going to the cart.
          e.preventDefault();
          // Get the shopping cart width + the offset to the left.
          var windowWidth = $(window).width();
          var cartWidth = $cartContents.width() + $cart.offset().left;
          // If the cart goes out of the viewport we should align it right.
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
