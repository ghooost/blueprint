<?php
$pieces=[
  "head"=>"",
  "header"=>"",
  "main"=>"",
  "footer"=>""
];

foreach($pieces as $k=>$v){
  $content="";
  if($pageData[$k] && count($pageData[$k]))
    foreach($pageData[$k] as $block)
      buildBlock($block,$pageData,$siteData,$content,$styles,$js,$images);
  $pieces[$k]=$content;
}

echo <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="{$siteData['codepage']}"/>
<link rel="stylesheet" href="{$siteData['maincss']}"/>
<script src="{$siteData['mainjs']}"></script>
{$pieces['head']}
</head>
<body>
{$pieces['header']}
<main>{$pieces['main']}</main>
{$pieces['footer']}
</body>
</html>
EOT;
?>
