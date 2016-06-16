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
* Allow to replace arbitrary content in the newsletter with custom recipient-specific fields.
* Recipient-specific fields can be previewed in the `Desktop` preview mode.
* Support for multiple languages
* Allowance to seggregate the recipient list arbitrarily to tightly control whom newsletters should be sent to, using the [jq query language](https://stedolan.github.io/jq/manual/)
* Custom receiver sources possible
* Supports Unsubscribe lists in a privacy-preserving manner, not storing email addresses but their hashes
* Parallel email sending possible by starting multiple Go daemons simultaneously
* Auto-Inline CSS style sheets for maximum compatibility

### Non-Features

The following things are *not yet implemented, but might be implemented in further projects:

* Sign Up for new Newsletters

## Prerequisites

* Install the [mailer sending daemon](https://github.com/sandstorm/mailer-daemon/releases) which is written in Go
* Install Redis
* Install [jq](https://stedolan.github.io/jq/) and ensure it exists on the PATH.

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
  uriPattern: 'neos/newsletter/<NewsletterSubroutes>'
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

## Receiver Sources and Receiver Groups

Internally, the system uses files for representing receiver lists. Each line in a file represents a *single receiver*,
and is a JSON object containing arbitrary properties (where one must be at least the email address, of course).

* A *receiver source* is essentially one source for receivers. Currently, we support both line-by-line JSON and CSV files;
  but you might want to create your own Receiver Source lateron.
  
* If you use multiple languages, the *receiver source* also contains a rule how the lines are segregated into the different languages.

* The *receiver group* is an additional subset of receivers inside a *receiver source*, so you could create a "male" or
  "female" receiver source if you like.


## Usage in Neos

* First, go to the *Newsletter Receiver Management* module and create a new receiver source; in our example choose the type *CSV Upload**[]: 

* Then, upload the `Documentation/example.csv` file. It defines the fields `firstName`, `lastName`, `email`, `gender` and `language` and contains just three receivers.

* If you have a languages content dimension configured, set the correct filters; e.g:

  * German: `language == "de"`
  * French: `language == "fr"`
  * (others): `false` (as the input file does not contain these languages)

* (optionally) create a receiver group for "Male", with the filter `gender == "male"`, and vice versa for `female`.

* Now, in the Content module of Neos, create a new Document of type `Newsletter`. In the inspector, first select a *Receiver Group*. Optionally define email subjects etc;
  and create your content as you like.
  
* If your content e.g. includes `{firstName}`, this will be replaced with the actual first name of the email recipient. You can preview this by switching to the `Desktop` mode
  in preview central.
  
* Enjoy!


## Getting Help

If you get stuck, feel free to contact @sebastian or @christoph.daehne in Slack at slack.neos.io.

