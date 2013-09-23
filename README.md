ImapIdleClient
==============

Extends pear/Net_IMAP and provides support for the IMAP IDLE command ([RFC 2177][]).

[RFC 2177]: http://tools.ietf.org/html/rfc2177


Installation
------------

Add the following lines to your *composer.json* file:

```json
{
    "require": {
        "noi/imap-idle": "*"
    },
    "repositories": [
        {
            "type": "pear",
            "url": "pear.php.net"
        }
    ]
}
```

and run Composer install:

```sh
$ php composer.phar install
```


(23-Sep-2013)
If you get something similar to the following error:

> [UnexpectedValueException]
> Failed to extract PEAR package /path/to/Net_IMAP/Net_IMAP-1.1.2.tgz
> to /path/to/Net_IMAP. Reason: Invalid PEAR package.
> package.xml defines file that is not located inside tarball.

To get beyond this error, you need to add the following lines instead:

```json
{
    "require": {
        "noi/imap-idle": "dev-master"
    },
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "pear-pear/Net_IMAP",
                "version": "1.1.2",
                "source": {
                    "type": "git",
                    "url": "https://github.com/pear/Net_IMAP.git",
                    "reference": "1.1.2"
                },
                "require": {
                    "pear-pear/Net_Socket": ">=1.0.8"
                },
                "autoload": {
                    "classmap": ["./Net/"]
                },
                "include-path": ["./"]
            }
        },
        {
            "type": "pear",
            "url": "pear.php.net"
        }
    ]
}
```

It seems that this error is caused by [Bug #19730][].

[Bug #19730]: http://pear.php.net/bugs/bug.php?id=19730


Usage
-----

```php
<?php
require 'vendor/autoload.php';

$imap = new \Noi\Util\Mail\ImapIdleClient('your.imap.host', 993);
$imap->login('username', 'password');
$imap->selectMailbox('INBOX');

while (!$imap->idle(300) instanceof \PEAR_Error) {
    $mails = $imap->search('UNSEEN');
    foreach ($imap->getMessages($mails) as $mail) {
        echo '==== New Message ====', "\n";
        echo $mail, "\n";
    }
}
```


License
-------

ImapIdleClient is licensed under the MIT License - see the `LICENSE` file for details.
