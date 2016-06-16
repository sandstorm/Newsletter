# Sandstorm.Newsletter

**The project has been sponsored by [Swisscom](https://www.swisscom.ch), and initiated by [Web Essentials](http://www.web-essentials.asia/). Thanks for your support!**

This is a tool which adjusts Neos in a way such that it can be used to send Newsletters.

Is is comprised of two parts:

- a [Go Daemon](https://github.com/sandstorm/mailer-daemon) which does the actual Mail-Sending
- a [Neos package](https://github.com/sandstorm/Sandstorm.Newsletter) **this package** which provides the User Interface

This is a newsletter sending infrastructure based on Neos. The actual newsletter-sending is handled
through Redis and a Go-Daemon, which can be found at https://github.com/sandstorm/mailer-daemon.

## Features

* Performant and distributed mail sending through SMTP or Mandrill
* All Newsletter content is manageable through Neos
* Support for multiple languages
* Allowance to seggregate the recipient list arbitrarily to tightly control whom newsletters should be sent to
* Custom receiver sources

### Non-Features

The following things are *not yet implemented, but might be implemented in further projects:

* Sign Up for new Newsletters

## Prerequisites

* Install the [mailer sending daemon](https://github.com/sandstorm/mailer-daemon/releases) which is written in Go
* Install Redis

## Installation / Set Up

* Install this package: TODO through packagist
```
cd path/to/your/NeosDistribution
cd Packages/Application
git clone https://github.com/sandstorm/Newsletter.git Sandstorm.Newsletter
cd ../../
./flow flow:package:rescan
```

* Ensure you have the Routes included, so that means `Configuration/Routes.yaml` should contain the following
  *before* the Neos routes:
  ```
  -
  name: 'Newsletter'
  uriPattern: '<NewsletterSubroutes>'
  subRoutes:
    'NewsletterSubroutes':
      package: 'Sandstorm.Newsletter'

  ```

* Create a neos page template, using the TypoScript object `Sandstorm.Newsletter:NewsletterPage`.
  Also, ensure to include the `Sandstorm.Newsletter:SampleDataWidget` somewhere in your page.

  As an example, you can use the following TypoScript snippet:
  
  ```
  page = Sandstorm.Newsletter:NewsletterPage
  page.sampleDataWidget = Sandstorm.Newsletter:SampleDataWidget
  ```

## Usage in Neos

* First, create a "Receiver Source" which contains 



* all styles will be auto-inlined.
