/*
 * Copyright (c) 2015. Sandstorm Media GmbH.
 * This is proprietary software.
 */

define(
[
    'emberjs',
    'Library/jquery-with-dependencies',
    'text!./SendEmailEditor.html',
    './SendNewsletterDialog'
],
function(Ember, $, template, SendNewsletterDialog) {
    return Ember.View.extend({

        classNames: ['sandstorm-newsletter-send-email-editor'],

        TextField: Ember.TextField,
        template: Ember.Handlebars.compile(template),


        // When the "value" changes, the current Document node changes; so we can re-read the dimensions from the UI.
        languages: function() {
            window.$ = $;

            var languages = [];
            var numberOfRecipients = JSON.parse($('.sandstorm-newsletter-sample-data').attr('data-newsletter-number-of-recipients'));
            $('.sandstorm-newsletter-sample-data .newsletter-dimension-menu li').each(function() {
                var $listElement = $(this);
                languages.push({
                    language: $listElement.text().trim(),
                    translationExists: !!$listElement.find('a').length,
                    count: numberOfRecipients[$listElement.attr('data-presetname')]
                });

            });

            return languages;
        }.property('value'),

        sendButtonLabel: 'Send Newsletter',

        send: function() {
            var sendingEndpoint = $('.sandstorm-newsletter-sample-data').attr('data-newsletter-sending-endpoint');
            var csrfToken = $('.sandstorm-newsletter-sample-data').attr('data-newsletter-csrf-token');
            var that = this;

            SendNewsletterDialog.create({
                languages: this.get('languages'),
                receiverGroupChosen: $('.newsletter-sample-contents').is('.is-sample-data-available'),
                sendNewsletter: function() {
                    that.set('sendButtonLabel', 'Preparing to send...');

                    $.post(sendingEndpoint, {
                        __csrfToken: csrfToken
                    }).then(function() {
                        that.set('sendButtonLabel', 'Sending...');
                    }, function() {
                        that.set('sendButtonLabel', 'Failed (See Data/Logs/System*.log)');
                    });
                    this.cancel();
                }
            });

        }
    });
});