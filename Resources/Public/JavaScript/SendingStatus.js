/*
 * Copyright (c) 2015. Sandstorm Media GmbH.
 * This is proprietary software.
 */



/**
 * Extension to the Content Module which directly hooks into Neos (NON-API); displaying the content module overlays.
 */
require(
// The following RequireJS Configuration is exactly the same as in ContentModuleBootstrap.js
{
    baseUrl: window.T3Configuration.neosJavascriptBasePath,
    urlArgs: window.localStorage.showDevelopmentFeatures ? 'bust=' +  (new Date()).getTime() : '',
    paths: requirePaths,
    context: 'neos'
},
[
    'Library/jquery-with-dependencies'
],
function($) {

    var timer = null;

    function setupEventListeners() {
        if ($('.sandstorm-newsletter-sample-data').length) {

            $('.sandstorm-newsletter-sample-data .collapse').on('click', function() {
                $('.sandstorm-newsletter-sample-data').toggleClass('newsletter-collapsed');
            });

            var statusEndpoint = $('.sandstorm-newsletter-sample-data').attr('data-newsletter-status-endpoint');
            var cancelEndpoint = $('.sandstorm-newsletter-sample-data').attr('data-newsletter-cancel-endpoint');
            var failuresEndpoint = $('.sandstorm-newsletter-sample-data').attr('data-newsletter-failures-endpoint');

            var $sendingStatus = $('.sandstorm-newsletter-sample-data .newsletter-sending-status');
            $.getJSON(statusEndpoint).then(function(response) {
                var emailGatewayString = '';

                if (response && response.serverConfiguration && response.serverConfiguration.EmailGateway) {
                    emailGatewayString = response.serverConfiguration.EmailGateway;
                }

                // 2 seconds after the end of the response, we trigger a new one.
                if (timer) {
                    window.clearTimeout(timer);
                }
                timer = window.setTimeout(setupEventListeners, 2000);

                $sendingStatus.removeClass('is-error');
                $sendingStatus.removeClass('is-not-sent');
                $sendingStatus.removeClass('sending-or-sent');

                if (response.error) {
                    $sendingStatus.html('<b>Error:</b> Newsletter Sender is offline. Please contact your server administrator to re-start the backend services.');
                    $sendingStatus.addClass('is-error');
                    return;
                }

                if (response.Summary.NumberOfRecipients == 0) {
                    $sendingStatus.addClass('is-not-sent');
                    $sendingStatus.html('Newsletter is not yet sent. (Server: ' + emailGatewayString + ')');
                    return;
                }

                if (response.Summary.NumberOfRecipients == response.Summary.NumberOfSentMails) {
                    $sendingStatus.addClass('sending-or-sent');
                    $sendingStatus.html('<b>Success</b>: Newsletter fully sent. (' + response.Summary.NumberOfSentMails + ' of ' + response.Summary.NumberOfRecipients + ' sent. Errors: ' + response.Summary.NumberOfSendingFailures + ') (Server: ' + emailGatewayString + ') <button class="sandstorm-newsletter-cancel">Delete Status Information</button>  <button class="sandstorm-newsletter-receiverfailures">Download Failures</button>');
                    return;
                } else {
                    $sendingStatus.addClass('sending-or-sent');
                    // Currently sending email
                    $sendingStatus.html('<b>In Progress:</b> Sending out newsletter. (' + response.Summary.NumberOfSentMails + ' of ' + response.Summary.NumberOfRecipients + ' sent. Errors: ' + response.Summary.NumberOfSendingFailures + ') (Server: ' + emailGatewayString + ') <button class="sandstorm-newsletter-cancel">Cancel Sending</button> <button class="sandstorm-newsletter-receiverfailures">Download Failures</button>');
                    return;
                }
            });

            $sendingStatus.off('click', 'button.sandstorm-newsletter-cancel');
            $sendingStatus.on('click', 'button.sandstorm-newsletter-cancel', function() {
                $('button.sandstorm-newsletter-cancel').text('Working...');
                var csrfToken = $('.sandstorm-newsletter-sample-data').attr('data-newsletter-csrf-token');
                $.post(cancelEndpoint, {
                    __csrfToken: csrfToken
                }).then(function(response) {
                    $('button.sandstorm-newsletter-cancel').text('Done!');
                }, function(error) {
                    $('button.sandstorm-newsletter-cancel').text('!!! Error !!!');
                    console.log("ERROR CANCELLING: ", arguments);
                })
            });

            $sendingStatus.off('click', 'button.sandstorm-newsletter-receiverfailures');
            $sendingStatus.on('click', 'button.sandstorm-newsletter-receiverfailures', function() {
                window.location.href = failuresEndpoint;
            });
        }
    }

    setupEventListeners();


    if (typeof document.addEventListener === 'function') {
        document.addEventListener('Neos.PageLoaded', function(event) {
            setupEventListeners();
        }, false);
    }
});