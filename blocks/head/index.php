<?php
$metas=[
  "description"=>"",
  "keywords"=>"",
  "author"=>"",
  "viewport"=>"width=device-width, minimum-scale=1, maximum-scale=1, initial-scale=1, user-scalable=0",
  "MobileOptimized"=>"320",
  "HandheldFriendly"=>"true",
  "skype_toolbar"=>"skype_toolbar_parser_compatible",
  "apple-mobile-web-app-capable"=>"yes",
  "og:url"=>"",
  "og:type"=>"article",
  "og:title"=>"",
  "og:description"=>"",
  "og:image"=>""
];
$metaContent="";
foreach($metas as $k=>$v){
  $value=empty($pageData[$k])?$v:$pageData[$k];
  $metaContent.=<<<EOT
<meta name="$k" content="$v">

EOT;
};

if(!empty($siteData['favicon']))
  $metaContent.=<<<EOT
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

EOT;

if($siteData['maincss'])
  $metaContent.=<<<EOT
<link rel="stylesheet" href="{$siteData['maincss']}"/>

EOT;

if($siteData['mainjs'])
  $metaContent.=<<<EOT
<script src="{$siteData['mainjs']}"></script>

EOT;

echo <<<EOT
<meta charset="{$siteData['codepage']}"/>
<title>{$pageData['http-title']}</title>
{$metaContent}
EOT;
?>
