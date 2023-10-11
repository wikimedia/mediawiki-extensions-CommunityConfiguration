## Community configuration 2.0: Backend extension

This extension is a part of the [Community configuration 2.0 project](https://www.mediawiki.org/wiki/Special:MyLanguage/Community_configuration_2.0) of the Wikimedia Foundation's [Growth team](https://mediawiki.org/wiki/Special:MyLanguage/Growth). It backs Community configuration itself and is accompanied by the [CommunityConfigurationExample](https://gitlab.wikimedia.org/repos/growth/community-configuration-example) as an example usage of Community configuration in a feature. 

### Architecture
...

### Setting up locally
An end to end setup of Community configuration depends on three components:

* This extension, `CommunityConfiguration` (for validation to work, `json-validation-opis` needs to be checked out)
* The example usage in `CommunityConfigurationExample`
* Set of changes in `mediawiki/core` that are uploaded in the `sandbox/urbanecm/community-configuration` ([branch info details](https://gerrit.wikimedia.org/g/mediawiki/core/+/refs/heads/sandbox/urbanecm/community-configuration))

To set up Community configuration 2.0, please follow those instructions:

```
# checkout required MediaWiki Core changes
core $ git checkout sandbox/urbanecm/community-configuration
# clone the necessary extensions
core $ git clone https://gitlab.wikimedia.org/repos/growth/community-configuration-example extensions/CommunityConfigurationExample
core $ git clone --branch=json-validation-opis https://gitlab.wikimedia.org/repos/growth/community-configuration extensions/CommunityConfiguration
# enable extensions in LocalSettings.php
core $ echo "wfLoadExtensions( ['CommunityConfiguration', 'CommunityConfigurationExample' ] );" >> LocalSettings.php
```

Once done, you should be able to access CommunityConfiguration from `Special:CommunityConfigurationExample`. Interacting with the Community configuration interface should be also working:

```
core $ php maintenance/run.php shell
> $config = \MediaWiki\MediaWikiServices::getInstance()->get('CommunityConfiguration.ProviderFactory')->newProvider('static-example')->loadValidConfiguration()
= StatusValue {#5739
    +value: [
      "wgFooBar" => 42,
    ],
    +success: [],
    +successCount: 0,
    +failCount: 0,
  }

> $config->isOK()
= true

> $config->getValue()
= [
    "wgFooBar" => 42,
  ]

> 
```

### Resources
* [Project page – MediaWiki.org](https://www.mediawiki.org/wiki/Special:MyLanguage/Community_configuration_2.0)
* [Community configuration 2.0 Product Requirements Document – Google Docs](https://docs.google.com/document/d/1Ai7ib6h1q9ly5xClowK2cn0CKIJp3Z1KpkvwzVMk32U/edit)