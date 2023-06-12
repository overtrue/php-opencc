# PHP OpenCC

中文简繁转换，支持词汇级别的转换、异体字转换和地区习惯用词转换（中国大陆、台湾、香港、日本新字体）。基于 [BYVoid/OpenCC](https://github.com/BYVoid/OpenCC) 数据实现。

[![Build Status](https://github.com/overtrue/php-opencc/actions/workflows/ci.yml/badge.svg)](https://github.com/overtrue/php-opencc/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/overtrue/php-opencc/v/stable)](https://packagist.org/packages/overtrue/php-opencc)
[![Total Downloads](https://poser.pugx.org/overtrue/php-opencc/downloads)](https://packagist.org/packages/overtrue/php-opencc)
[![License](https://poser.pugx.org/overtrue/php-opencc/license)](https://packagist.org/packages/overtrue/php-opencc)

## Installing

```shell
$ composer require overtrue/php-opencc -vvv
```

## Usage

```php
use Overtrue\OpenCC\OpenCC;

echo OpenCC::convert('服务器', 'SIMPLIFIED_TO_TAIWAN_WITH_PHRASE'); 
// output: 伺服器
```

## :heart: Sponsor me 

如果你喜欢我的项目并想支持它，[点击这里 :heart:](https://github.com/sponsors/overtrue)

## Project supported by JetBrains

Many thanks to Jetbrains for kindly providing a license for me to work on this and other open-source projects.

[![](https://resources.jetbrains.com/storage/products/company/brand/logos/jb_beam.svg)](https://www.jetbrains.com/?from=https://github.com/overtrue)


## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/overtrue/php-opencc/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/overtrue/php-opencc/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT
