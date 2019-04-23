<?php
 
// Include Composer autoloader if not already done.
include 'vendor/autoload.php';

use Spatie\PdfToText\Pdf;

// loop through PDF Folder

$path    = './pdfs';
$pdftotext = '/usr/local/bin/pdftotext';

// optional
$campaign_name = "";
$ad_group = "";

$files = array_diff(scandir($path), array('.', '..'));

// empty pdf text file
fclose(fopen('cache_pdf.txt','w'));

// write text to pdf cache
foreach($files as $file) {
  // get PDF text
  $text = (new Pdf($pdftotext))
      ->setPdf("$path/$file")
      ->text();

  // write into file
  $file = fopen('cache_pdf.txt', 'a');
  fwrite($file, $text);
  fclose($file);
}

// container for keywords
$keywords = [];

// run tre-tagger for proper keywords
$result = exec("cat cache_pdf.txt | ./tagger/cmd/tree-tagger-german > cache_treetagger.txt");


// load blacklist
$blacklist_path = "./blacklists";
$blacklist = [];

$files = array_diff(scandir($blacklist_path), array('.', '..'));
foreach($files as $file) {
  $file = fopen("$blacklist_path/$file", 'r');
  while(!feof($file)) {
    $words = preg_split('/\s+/', fgets($file), -1, PREG_SPLIT_NO_EMPTY);
    if(count($words)>1){
      // we use second parameter
      $e = mb_strtolower(trim($words[1]), 'UTF-8');
    }  else {
      $e = mb_strtolower(trim($words[0]), 'UTF-8');
    }
    $blacklist[$e] = true;
  }
  fclose($file);
}

// stream from file
$file = fopen('cache_treetagger.txt', 'r');
// read file line per line
while(!feof($file))
{
  // Only interested in nouns
  $word = explode("NN", fgets($file));

  // was it a noun
  if(count($word) > 1) {

    // are they an abbreviation, use the word family
    if(trim($word[1]) === "<unknown>" || trim($word[2]) === "") {
      $e = mb_strtolower(trim($word[0]), 'UTF-8');
    } else {
      $e = mb_strtolower(trim($word[1]), 'UTF-8');
    }

    // additional checks
    if(mb_strlen($e, 'UTF-8') < 3) continue;
    if(isset($blacklist[$e])) continue;
    if(strpos($e, 'www.') !== false) continue;
    if(strpos($e, '©') !== false) continue;
    if(strpos($e, '´') !== false) continue;
    if(strpos($e, '>') !== false) continue;
    if(strpos($e, '<') !== false) continue;

    // interesting keyword
    // do we already have it
    if(isset($keywords[$e])) {
      $keywords[$e]++;
    } else {
      $keywords[$e] = 1;
    }
  }
}
fclose($file);

// sort by key
arsort($keywords);

// save result as csv
print_r($keywords);

// cache results
$file = fopen("cache_keywords.csv","w");
foreach($keywords as $key=>$value) {
  fwrite($file, "$key: $value\n\r");
}
fclose($file);

// prepare for google Ads

if($campaign_name !== "" && $ad_group !== "") {
  $file = fopen("google_csv.csv","w");

  //headlines
  $headlines = [
    'Campaign',
    'Campaign Daily Budget',
    'Languages',
    'Location ID',
    'Location',
    'Networks',
    'Ad Group',
    'Max CPC',
    'Flexible Reach',
    'Keyword',
    'Type',
    'Bid adjustment',
    'Headline',
    'Description Line 1',
    'Description Line 2',
    'Sitelink text',
    'Display URL',
    'Final URL',
    'Platform targeting',
    'Device Preference',
    'Ad Schedule'
  ];
  fputcsv($file, $headlines);

  // entries
  foreach($keywords as $key=>$value) {
    fputcsv($file, [$campaign_name, $ad_group, $key]);
  }
  fclose($file);
}


echo "Keyword Count: ".count($keywords)."\n\r";
