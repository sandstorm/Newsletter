/*
 * Copyright (c) 2015. Sandstorm Media GmbH.
 * This is proprietary software.
 */

define(
    [
        'emberjs',
        'Shared/AbstractModal',
        'text!./SendNewsletterDialog.html'
    ],
    function(Ember, AbstractModal, template) {
        return AbstractModal.extend({
            template: Ember.Handlebars.compile(template),
            title: 'Send Newsletter - Confirmation',
            receiverGroupChosen: false
        });
    });