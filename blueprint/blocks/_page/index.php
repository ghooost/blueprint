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

  $mainjs=<<<EOT
<script src="{$siteData['mainjs']}"></script>

EOT;

echo <<<EOT
<!DOCTYPE html>
<html>
<head>
{$pieces['head']}
</head>
<body>
{$pieces['header']}
<main>{$pieces['main']}</main>
{$pieces['footer']}
{$mainjs}
</body>
</html>
EOT;
?>
