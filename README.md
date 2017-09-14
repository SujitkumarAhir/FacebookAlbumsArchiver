## Getting Started

First, You need to add your own facebook developer configurations (app id, app secret,redirect url) in fbconfig.php file. then You need to add your own google developer configurations (id, secret,redirect url) in googleConfig.php

## APIs Used

### Facebook Graph API

Facebook Graph API is used to get all the albums as well as all photographs of authenticated user. for this api you need facebook-php-sdk to communicate with facebook.

### Google Drive API

Google Drive API is used to access google drive and manage folders and files of authenticated used. for this api you need google-php-sdk to communicate with google.

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

http://www.devik.online/index.php

## About Background Jobs

On download click, server will inititate background job to zip the albums. you can download this zip file after compeletion.

On Move to Google Drive click, server will inititate background job to move the Facebook albums to your Google Drive

Once background job is initiated, you can turn off PC or internet connection. It will run in server.

At a time only 1 download process(zipping of Facebook albums) per user can be run on the server and other processes will be in queue. When running process finishes its task then next processes will start executing in background one by one.

You can run 2-5 background processes simultaneously to move your Facebook albums to your google drive. These processes are running simultaneously depending on number of users accessing the website at a time. When running process finishes its task then next processes will start executing in background one by one.

You can abort running/waiting processes. After abort if any process is available in queue then it will be started.

If any process is already initiated and it is in running or waiting state then new copy of the same process cannot be started or initiated. In this case you should first abort running or waiting process then only you can start new one.

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

