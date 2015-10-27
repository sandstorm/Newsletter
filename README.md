# Sandstorm.Newsletter

**The project has been sponsored by [Swisscom](https://www.swisscom.ch), and initiated by [Web Essentials](http://www.web-essentials.asia/). Thanks for your support!**

This is a tool which adjusts Neos in a way such that it can be used to send Newsletters.

Is is comprised of two parts:

- a [Go Daemon](https://github.com/sandstorm/mailer-daemon) which does the actual Mail-Sending
- a [Neos package](https://github.com/sandstorm/Sandstorm.Newsletter) **this package** which provides the User Interface

This is a newsletter sending infrastructure based on Neos. The actual newsletter-sending is handled
through Redis and a Go-Daemon, which can be found at https://github.com/sandstorm/mailer-daemon.

## Usage

* Install this package
* Create a neos page template, using the TypoScript object `Sandstorm.Newsletter:NewsletterPage`.
* all styles will be auto-inlined
