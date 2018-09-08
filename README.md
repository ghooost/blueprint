# Blueprint

Is a site generator that assembles static pages with blocks, collects images
and builds CSS and JS bundles.

## Usage

```

php blueprint.php -- [-i <folder>] [-o <folder>] [-build] [-watch] [-watchtime <sec>] [-revers] [-f <folder>] [-c <css file>]

Options:


php blueprint.php -- [-i <folder>] [-build] [-watch] [-watchtime <sec>]
```

## Command line parameters

### Construction mode:

**-i <folder>**       : define folder from where blueprint.json will be loaded

**-o <folder>**       : output folder

**-build**            : build final bandles, shortcut for -mode build

**-mode <mode>**      : mode, can be dev, build, revers or any other custom mode

**-watch**            : watch -i folder

**-watchtime <sec>**  : watch timeout in seconds

###Revers mode:

**-revers**          : set revers mode

**-f <folder>**      : define folder with block html

**-c <css file, css file....>**    : css file[s] for deconstruction


## Blueprint.json

Is a data of your site and pages.
```
{
  "folder-output":"folder_to_write_bundles_to",
  "folder-static":"folder with static content",
  "pages":[
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
It contains both your site and pages data. Please notice **ext-data** fields.
These are link to an external json file that will be loaded and placed instead of **ext-data**.
```
/* users/jane.json */
{
  "name":"Jane",
  "age":"42",
  "photo":"./photos/jane.png"
}
/* Include at */
{
  "userid":1,
  "ext-data":"users/jane.json"
}
/* And finally */
{
  "userid":1,
  "name":"Jane",
  "age":"42"
  "photo":"users/photos/jane.png"
}
```
Fields values started with "./" will be patched with path of file that contain them.


## Site data

All fields except **pages** are optional.

### Important fields

**pages** - is an array of pages of your site

### Optional fields

**codepage** - codepage of the site pages. By defaul is utf-8 and I don't see any reason to change it

**pageTemplate** - is a "root" page generator, that takes page data - all these
head, footer, main, header sections and form a html page from them. I provide
a default generator, but of course you can change it.

**blueprint** - that's comes from -blueprint command line option

**folder-output** - folder to output bundles, default is **blueprint**/build

**folder-static** - folder with static content, default is **blueprint**/static

**folder-afb** - folder with static content inside blocks, default is afb

**folder-lib** - folder with blocks, default is block

**maincss** - name of final CSS bundle, default is index.css, you can use **mode-maincss** to set separate name for a mode

**mainjs** - name of final JS bundle, default is index.js, you can use
**mode-maincss** to set separate name for a mode

**afb-url** - real URL to afb folder, all afb/ substrings inside your files will be patched with this URL, also can be in form **mode-afb-url** to set the URL for a mode

**css-afb-url** - real URL to afb folder from the main CSS, all afb/ substrings inside your CSS will be patched with this URL, also can be in form **mode-css-afb-url** to set the URL for a mode

## Page data

It's something like:

```
{
  "file":"page_file_name.html",
  "url":"/url_to_page",
  "name":"Page name you can in menu, so on",
  "http-title":"HTTP title",

  // blocks to include into <HEAD> tag

  "head":[  
    {"template":"head"},
    {"template":"localstyles"}
  ],

  // blocks to include before <MAIN> tag

  "header":[
    {"template":"header"}
  ],

  // blocks to include into <MAIN> tag

  "main":[
    {"template":"about"},
    {"template":"features"}
  ],

  // blocks to include after <MAIN> tag

  "footer":[
    {"template":"footer"}
  ]
}
```

In general - you can put there any data you need and process it inside your blocks and pages. But out of box blueprint configures to understand following
fields:

### Important fields

**file** - name of the page file, index.html f.e.

**url** - url to the page, /index/ f.e.. It will be included into .htaccess to map your urls and files

etc.


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
rebuilds a site if something inside the watched folders was changed.

Watch mode can be switched on with **-watch** option.

You can define timeout to review changes with **-watchtime** option. Default is 5 sec.

##Reverse mode

This mode is for collect classes and images used in a html code.

You can create a folder (block), put there index.html with the block HTML and
run blueprint.php in reverse mode. It will collect clases and images for the block.

Then you can easily use this creature as a block for your site.
