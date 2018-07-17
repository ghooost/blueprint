# Blueprint
Is a site generator that assembles static pages with blocks, collects images
and builds CSS and JS bundles.

## Usage

```
php blueprint.php
```

## Blueprint.json
Is a map of your site. Here you configure your site pages.
```
{
  "codepage":"utf-8",     //--------- site parameters
  "pages":[               //--------- list of pages
    {
      "template":"_page", //--------- page data
      "file":"index.html",  //---------- name of file of the page
      "url":"./",         //--------- url to the page, see .htaccess

      "name":"Home page",   //--------- usually it requires for menu

      "http-title":"The page title",
      "http-description":"blabla",

      "header":[
        {"template":"header"}
      ],
      "main":[
        {"template":"section_note"},
      ],
      "footer":[
        {"template":"footer"}
      ]
    }
  ]
}

```
## Blocks
Blocks - are bricks you build sites with. Each block has it's own folder inside common blocks folder:
```
/blocks
  /footer
    /afb        //---- place images here
      1.png
      2.png
    index.js    //---- can be js.php
    index.css   //---- can be css.php
    index.html  //---- can be index.php
```
At this case footer is a block. In the footer folder we place html content (index.html), css (index.css), js (index.js) and a couple of images (1.png and 2.png).

## Dynamic vs static content
You can use php generators for each part of your blocks.
At this case we make
```
require "blocks/footer/index.php";

//instead of

readfile("blocks/footer/index.html")
```
PHP code receives:
```
$siteData=[...];

/*
whole data of your blueprint.json:
{
  "codepage":"utf-8",
  "pages":[....]
}
*/

$pageData=[...];
/*
data of page that hosts the block
{
  "template":"_page",
  "file":"index.html",
  "url":"./",

  "name":"Home page",
  ...
}
*/

$blockData=[]
/*
data of the block
{
  "template":"section_note"
  ...
}
*/
```
Also it has access to $styles, $js, $images arrays, where blueprint collects materials - css, js, images of your site.

## Menu
Menu is an obvious sample of PHP generator. Simple menu code will look like:
```
<?php
  $nav="";
  if($siteData['pages'] && count($siteData['pages']))
    foreach($siteData['pages'] as $page)
      if(empty($page['hidden'])){
        $sel=($page['url']==$pageData['url'])?' menu_a_selected':'';
        $nav.=<<<EOT
<li><a href="{$page['url']}" class="menu_a{$sel}">{$page['name']}</a></li>
EOT;
      };
  echo <<<EOT
<ul class="menu">$nav</ul>
EOT;
?>
```
All standart output of your PHP generators will be inserted into final pages.

## Build folder
Blueprint output generated files into build folder. After building it's
content will be like:
```
/build
  /afb      //---- images from block afb folders will be copied there
  .htaccess     // ---- see below
  12345678.css  // ---- CSS bundle of the site.
  23456789.js   // ---- JS bundle of the site
  index.html    // ---- html pages
  about.html
  ....
```

## CSS and JS bundles
CSS and JS code of used blocks are assembled into bundles named randomly to
prevent caching

## .htaccess
This is a common Apache config file we use to force Apache to use URLs
your defined in blueprint.json instead of names of output files. So
```
http://www.yoursite.com/mypage.html
```
will be available as
```
http://www.yoursite.com/mypageurl
```
if your pageData like
```
{
  "file":"mypage.html",
  "url":"mypageurl",
  ...
}
```
Of course, you should upload content of your build folder to www.yoursite.com first.
