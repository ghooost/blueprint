<?php
if(!file_exists('blueprint.json')){
  die("blueprint.json should be at the same folder with the sitebones.php\r\n");
};

$siteData=json_decode(join("",file('blueprint.json')),true);
$filesInUse=[];

if(!$siteData){
  die("blueprint.json has no proper site structure\r\n");
};

clearDir('build');

$styles=[];
$js=[];
$images=[];
$rules=[];

if($siteData['pages'] && count($siteData['pages']))
  foreach($siteData['pages'] as $page){
    buildPage($page,$siteData,$styles,$js,$images,$rules);
  }

$f=fopen('build/index.css','w');
if($f){
  fputs($f,join("\r\n",$styles));
  fclose($f);
};

$f=fopen('build/index.js','w');
if($f){
  fputs($f,join("\r\n",$js));
  fclose($f);
};

$hta="";
foreach($rules as $url=>$file){
  $hta.='RewriteRule ^'.$file.'$ '.$url.' [L,R=301]'."\r\n";
};

if($hta){
  $f=fopen('build/.htaccess','w');
  if($f){
    fputs($f,<<<EOT
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

$hta
EOT
    );
    fclose($f);
  };
};


function buildPage($pageData,$siteData,&$styles,&$js,&$images,&$rules){
  if(!empty($pageData['url']) && $pageData['url']!=$pageData['file']){
    $rules[$pageData['url']]=$pageData['file'];
  };
  $head="";
  if($pageData['head'] && count($pageData['head']))
    foreach($pageData['head'] as $block)
      buildBlock($block,$pageData,$siteData,$head,$styles,$js,$images);

  $body="";
  if($pageData['body'] && count($pageData['body']))
    foreach($pageData['body'] as $block)
      buildBlock($block,$pageData,$siteData,$body,$styles,$js,$images);

  $fileName=$pageData['file']?$pageData['file']:md5(json_encode($pageData)).".html";
  $f=fopen('build/'.$fileName,"w");
  if($f){
    fputs($f,<<<EOT
<!DOCTYPE html>
<html>
<head>
$head
<link rel="stylesheet" href="index.css"/>
<script src="index.js"></script>
</head>
<body>
$body
</body>
</html>
EOT
    );
    fclose($f);
  }
}

function buildBlock($blockData,$pageData,$siteData,&$html,&$styles,&$js,&$images){
  global $filesInUse;

  if(!$blockData['block']) return;
  $folder='blocks/'.$blockData['block'];
  if(!file_exists($folder)) return;


  ob_start();
  if(file_exists($folder.'/index.php')){
    require $folder.'/index.php';
  } else if(file_exists($folder.'/index.html')){
    readfile($folder.'/index.html');
  };
  $html.=ob_get_contents();
  ob_end_clean();


  ob_start();
  if(file_exists($folder.'/css.php')){
    require $folder.'/css.php';
    $content=ob_get_contents();
    $key=md5($content);
    if(empty($filesInUse[$key])){
      $styles[]=$content;
      $filesInUse[$key]=TRUE;
    }
  } else if(file_exists($folder.'/index.css')){
    if(empty($filesInUse[$folder.'/index.css'])){
      readfile($folder.'/index.css');
      $filesInUse[$folder.'/index.css']=TRUE;
      $styles[]=ob_get_contents();
    }
  };
  ob_end_clean();

  ob_start();
  if(file_exists($folder.'/js.php')){
    require $folder.'/js.php';
    $content=ob_get_contents();
    $key=md5($content);
    if(empty($filesInUse[$key])){
      $js[]=$content;
      $filesInUse[$key]=TRUE;
    }
  } else if(file_exists($folder.'/index.js')){
    if(empty($filesInUse[$folder.'/index.js'])){
      readfile($folder.'/index.js');
      $filesInUse[$folder.'/index.js']=TRUE;
      $js[]=ob_get_contents();
    }
  };
  ob_end_clean();


  if (is_dir($folder.'/afb')) {
      if ($dh = opendir($folder.'/afb')) {
          while (($file = readdir($dh)) !== false)
              if($file!='.' && $file!='..')
                if(!in_array($folder.'/afb/'.$file,$images))
                  $images[]=$folder.'/afb/'.$file;

          closedir($dh);
      }
  };
}

function clearDir($dir){
  if (is_dir($dir)) {
      if ($dh = opendir($dir)) {
          while (($file = readdir($dh)) !== false)
              if($file!='.' && $file!='..')
                if(is_dir($dir.'/'.$file)){
                  clearDir($dir.'/'.$file);
                  rmdir($dir.'/'.$file);
                } else {
                  unlink($dir.'/'.$file);
                };

          closedir($dh);
      }
  };

}

?>
