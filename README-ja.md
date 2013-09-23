ImapIdleClient
==============

pear/Net_IMAPを拡張してIMAP IDLEコマンド([RFC 2177][])用メソッドを追加したクラス。

[RFC 2177]: http://tools.ietf.org/html/rfc2177


インストール
------------

*composer.json* ファイルに以下の行を追加してください。

```json
{
    "require": {
        "noi/imap-idle": "dev-master"
    },
    "repositories": [
        {
            "type": "pear",
            "url": "pear.php.net"
        }
    ]
}
```

その後、Composerのinstallコマンドを実行します。

```sh
$ php composer.phar install
```


(2013-09-23)
現時点では、以下のようなエラーが発生するかもしれません。

> [UnexpectedValueException]
> Failed to extract PEAR package /path/to/Net_IMAP/Net_IMAP-1.1.2.tgz
> to /path/to/Net_IMAP. Reason: Invalid PEAR package.
> package.xml defines file that is not located inside tarball.

これを回避するためには、GitHubで公開されている
Net_IMAPパッケージを使うようにします。

*composer.json* ファイルには以下の行を追加してください。

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

エラーの発生原因は、PEAR公式サイトの不具合 [Bug #19730][] だと思います。

[Bug #19730]: http://pear.php.net/bugs/bug.php?id=19730

PEAR公式サイトからダウンロードできる Net_IMAP のアーカイブは、
`tar -i`(`tar --ignore-zeros`) オプションが必要な形式でした。
現時点のComposerは、
このような（壊れた？）tarアーカイブに対応していないためエラーが発生するようです。


使い方
------

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

ImapIdleClientクラスのライセンスは、MITライセンスです。
詳しくは `LICENSE` ファイルの規約を確認してください。
