# Facebook Photos Archiver

All facebook albums can be downloaded after facebook authenticaton as well as can be archived to google drive after google authentication.

<<<<<<< HEAD
##getting Started

First, You need to add your own facebook developer configurations (app id, app secret,redirect url) in fbconfig.php file. then You need to add your own google developer configurations (id, secret,redirect url) in googleConfig.php


=======
## getting Started

First, You need to add your own facebook developer configurations (app id, app secret,redirect url) in fbconfig.php file. then You need to add your own google developer configurations (id, secret,redirect url) in googleConfig.php

>>>>>>> cb05c9fbd144f2cc5b69310c251f902859f488c0
## APIs Used

### Facebook Graph API

Facebook Graph API is used to get all the albums as well as all photographs of authenticated user. for this api you need facebook-php-sdk to communicate with facebook.

### Google Drive API

Google Drive API is used to access google drive and manage folders and files of authenticated used. for this api you need google-php-sdk to communicate with google.

<<<<<<< HEAD



=======
## Third party libraries

### Bootstrap

bootstrap version v3.3.7

### jQuery

jquery version v3.2.1

###  Jquery-prettyPhoto

jquery-prettyPhoto used for jquery slider

### Google APIs Client Library for PHP

The Google API Client Library enables you to work with Google APIs such as Google+, Drive, or YouTube on your server.

#### Requirements

[PHP 5.4.0 or higher](http://www.php.net/)

#### Developer Documentation

http://developers.google.com/api-client-library/php

### Facebook SDK for PHP (v5)

open source PHP SDK that allows you to access the Facebook Platform from your PHP app.

#### Installation

The Facebook PHP SDK can be installed with Composer. Run this command:

```
composer require facebook/graph-sdk
```

#### Requirements

[PHP 5.4.0 or higher](http://www.php.net/)

## Demo Link

http://devikpatel.cuccfree.com/index.php

### prerequisites

You need to contact me to access the albums of facebook from above link. You are not able to archive your albums from above link without tester rights.

## Code Quality 

Run the PHPUnit tests with PHPUnit. You can configure an API key and token in googleConfig.php to run all calls, but this will require some setup on the Google Developer Console.

## Coding Style (PHP CodeSniffer)

To check for coding style violations, run

```
phpcs src --standard=style/ruleset.xml -np
```

To automatically fix (fixable) coding style violations, run

```
phpcbf src --standard=style/ruleset.xml
```

## Security Vulnerabilities

If you have found a security issue, please contact the maintainer directly at pateldevik@gmail.com.
>>>>>>> cb05c9fbd144f2cc5b69310c251f902859f488c0
