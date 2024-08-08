CommunityConfiguration Example
==============================

To see the example in action, add the following to your LocalSettings.php:

```php
wfLoadExtension( 'CommunityConfigurationExample', $IP . '/extensions/CommunityConfiguration/example/example-extension.json' );
```

This will add a new provider on the CommunityConfiguration special page: "Special:CommunityConfiguration/CommunityConfigurationExample",
and it will add a special page where you can see results of the configuration: "Special:CommunityConfigurationExample".
