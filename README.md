# Sandstorm.Newsletter

(still work in progress)

This is a newsletter sending infrastructure based on Neos. The actual newsletter-sending is handled
through Redis and a Go-Daemon, which can be found at https://github.com/sandstorm/mailer-daemon.

## Usage

* Install this package
* Create a neos page template, using the TypoScript object `Sandstorm.Newsletter:NewsletterPage`.
* all styles will be auto-inlined
