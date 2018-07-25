# Blueprint

Is a site generator that assembles static pages with blocks, collects images
and builds CSS and JS bundles.

## Usage

```
php blueprint.php -- [-i <folder>] [-build] [-watch] [-watchtime <sec>]
```

## Command line parameters

**-i FOLDER** define folder from where blueprint.json will be loaded

**-mode MODE** set mode, can be build, dev or any other custom mode, see modes

**-build** build final bundles, shortcut for **-mode build**

**-watch** watch -i folder

**-watchtime SEC** watch timeout in seconds


## Blueprint.json

Is a map of your site. Here you configure your site pages.
```
{
  "folder-output":"mysite/build", //- folder to write assembled files to
  "folder-static":"mysite/static", //- folder with static content, see static section
  "site-home":"/", //- site home URL
  "pages":[ //- array of pages
    {
      "file":"index.html",
      "url":"/",
      "name":"Home",
      "http-title":"The first page",
      "ext-data":"mysite/index.json" //- external json file, see ext-data section
    }
  ]
}
```

## Page structure

```
{
  "file":"index.html", //- name of file that the page will be saved to
  "url":"/",
  "name":"Home", //- name, usually for menus
  "http-title":"The first page", //- HTTP title
//page structure  
  "head":[  //- blocks to include into <HEAD> tag
    {"template":"head"},
    {"template":"localstyles"}
  ],
  "header":[ //- blocks to include into HEADER tag
    {"template":"header"}
  ],
  "main":[ //- blocks to include into MAIN tag
    {"template":"about"},
    {"template":"features"},
    {"template":"advantages"},
    {"template":"prices"}
  ],
  "footer":[ //- blocks to include into FOOTER tag
    {"template":"footer"}
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
    index.js    
    index.css   
    index.html  
```
At this case footer is a block. In the footer folder we place html content (index.html), css (index.css), js (index.js) and a couple of images (1.png and 2.png).

## Modes

Mode defines how blueprint will seek for files in your folders.

The order is like:

```
1. [mode]_[type].php
2. [mode].[type]
3. [type].php
4. index.[type]
```
So, in build mode blueprint will seek for build_css.php, if no one - for build.css, then for css.php, and if no build_css.php, build.css and css.php found - for index.css.

Thus, you can place different block materials for different modes.

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

## Static folder

If you define a static folder (folder-static option in blueprint.json) -
it's content will be copied to build folder.

```
mysite/
  static/
    fonts/
      f.font
    1.png
    1.png
```
will be copied to the build folder as:
```
mysite/
  build/
    fonts/
      f.font
    1.png
    1.png
```

## Buld is a special mode

In build mode blueprint.php removes everything from the output folder before the building.

It copies all required files into output folder and [by default] generates main CSS/main JS
with random names to avoid possible cache problems.

Build mode can be switched on with **-build** option or with **-mode build**.

## Watch mode

In watch state blueprint.php reviews working folder every N seconds and
rebuild a site if something inside the watched folders was changed.

Watch mode can be switched on with **-watch** option.

You can define timeout to review changes with **-watchtime** option. Default is 5 sec.
