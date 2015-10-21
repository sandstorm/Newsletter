/*
 * Copyright (c) 2015. Sandstorm Media GmbH.
 * This is proprietary software.
 */

define(
[
    'emberjs',
    'Library/jquery-with-dependencies',
    'text!./TestEmailEditor.html'
],
function(Ember, $, template) {
    return Ember.View.extend({

        TextField: Ember.TextField,
        template: Ember.Handlebars.compile(template),

        // bound from view
        emailAddress: '',

        sendButtonLabel: 'Send Test Email',

        sendMailButtonDisabled: function() {
            return !this.get('emailAddress');
        }.property('emailAddress'),

        send: function() {
            var sendingEndpoint = $('.sandstorm-newsletter-sample-data').attr('data-newsletter-sending-endpoint');
            var csrfToken = $('.sandstorm-newsletter-sample-data').attr('data-newsletter-csrf-token');
            var that = this;

            this.set('sendButtonLabel', 'Sending...');

            $.post(sendingEndpoint, {
                previewEmail: this.get('emailAddress'),
                __csrfToken: csrfToken
            }).then(function() {
                that.set('sendButtonLabel', 'Sent!');
            });
        }
    });
});