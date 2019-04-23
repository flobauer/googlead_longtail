# Google Ad Creation Tool

Longtail Google Ad campaign tool for creating google ads with not so common words.

## Installation

1. install composer 
2. run composer
3. install tree tagger in `./tagger` [TreeTagger - a part-of-speech tagger for many languages](http://www.cis.uni-muenchen.de/~schmid/tools/TreeTagger/)
4. put PDF Files in `./pdf` that delivery the branch specific content
5. put Blacklist Files in `./blacklists` with words, you don't like
6. run `php worker.php` and check the cache files for reference