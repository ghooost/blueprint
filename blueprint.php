<?php
if(!file_exists('blueprint.json')){
  die("blueprint.json should be at the same folder with the blueprint.php\r\n");
};


echo "**************blueprints loading*************\r\n";
$siteData=arrayBuild(
  [
    'codepage'=>'utf-8',
    'pageTemplate'=>'_page',
    'folder-output'=>'build',
    'folder-static'=>'static',
    'folder-afb'=>'afb',
    'folder-lib'=>'blocks',
    'maincss'=>mkRandomName().'.css',
    'mainjs'=>mkRandomName().'.js',
    'folder-current'=>'.',
    'ext-data'=>'blueprint.json'
  ]
);
echo "**************loaded*************\r\n";

$filesInUse=[];

if(!file_exists($siteData['folder-output'])) mkdir($siteData['folder-output'],0777);
if(file_exists($siteData['folder-output'])){
  clearDir($siteData['folder-output']);
  echo "Set output to: ".$siteData['folder-output']."\r\n";
} else {
  die("Can't create ".$siteData['folder-output']."\r\n");
};

$styles=[];
$js=[];
$images=[];
$rules=[];


if(!empty($siteData['pages']) && count($siteData['pages']))
    foreach($siteData['pages'] as $page)
      buildPage($page,$siteData,$styles,$js,$images,$rules);


$f=fopen($siteData['folder-output'].'/'.$siteData['maincss'],'w');
if($f){
  fputs($f,join("\r\n",$styles));
  fclose($f);
};

$f=fopen($siteData['folder-output'].'/'.$siteData['mainjs'],'w');
if($f){
  fputs($f,join("\r\n",$js));
  fclose($f);
};

if(count($rules)){
  $hta="";
  foreach($rules as $url=>$file){
    $url=preg_replace('/\/+$/','',$url);
    $hta.='RewriteRule ^'.$url.'(\/)*$ '.$file.' [L]'."\r\n";
  };

  if($hta){
    $f=fopen($siteData['folder-output'].'/.htaccess','w');
    if($f){
      fputs($f,<<<EOT
  AddDefaultCharset {$siteData['codepage']}

  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d

  $hta
EOT
      );
      fclose($f);
    };
  };
}

if(!empty($siteData['folder-current'].'/'.$siteData['folder-static']))
  copyDir($siteData['folder-current'].'/'.$siteData['folder-static'],$siteData['folder-output']);

if(count($images)){
  if(!file_exists($siteData['folder-output'].'/'.$siteData['folder-afb'])) mkdir($siteData['folder-output'].'/'.$siteData['folder-afb'],0777);
  foreach($images as $src=>$dst)
    copy($src,$siteData['folder-output'].'/'.$siteData['folder-afb'].'/'.$dst);
};



function buildPage($pageData,$siteData,&$styles,&$js,&$images,&$rules){
  if(empty($siteData['def-page'])) $siteData['def-page']=[];
  $pageData=arrayAssign($siteData['def-page'],$pageData);

  if(!empty($pageData['url']) && $pageData['url']!=$pageData['file']){
    $rules[$pageData['url']]=$pageData['file'];
  } else {
    $rules['/']=$pageData['file'];
  };

  if(empty($pageData['template']))
    $pageData['template']=$siteData['pageTemplate'];

  $pageBody="";
  buildBlock($pageData,$pageData,$siteData,$pageBody,$styles,$js,$images);

  $fileName=$pageData['file']?$pageData['file']:md5(json_encode($pageData)).".html";
  $f=fopen($siteData['folder-output'].'/'.$fileName,"w");
  if($f){
    fputs($f,$pageBody);
    fclose($f);
  }
}


function buildBlock($blockData,$pageData,$siteData,&$html,&$styles,&$js,&$images){
  global $filesInUse;

  if(!$blockData['template']) return;
  $folder=$siteData['folder-current'].'/'.$siteData['folder-lib'].'/'.$blockData['template'];
  if(!file_exists($folder)){
    $folder=$siteData['folder-lib'].'/'.$blockData['template'];
    if(!file_exists($folder)){
      die("There is no ".$folder." block\r\n");
    };
  };

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

  ob_start();
  if(file_exists($folder.'/index.php')){
    require $folder.'/index.php';
  } else if(file_exists($folder.'/index.html')){
    readfile($folder.'/index.html');
  };
  $html.=ob_get_contents();
  ob_end_clean();


  if (is_dir($folder.'/'.$siteData['folder-afb'])) {
      if ($dh = opendir($folder.'/'.$siteData['folder-afb'])) {
          while (($file = readdir($dh)) !== false)
              if($file!='.' && $file!='..'){
                $src=$folder.'/'.$siteData['folder-afb'].'/'.$file;
                $dst=$file;
                if(empty($images[$src]))
                  $images[$src]=$dst;
              };

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

function copyDir($src,$dst){
  if (file_exists($src) && is_dir($src)) {
      if(!file_exists($dst)) mkdir($dst);
      if ($dh = opendir($src)) {
          while (($file = readdir($dh)) !== false)
              if($file!='.' && $file!='..')
                if(is_dir($src.'/'.$file)){
                  copyDir($src.'/'.$file,$dst.'/'.$file);
                } else {
                  copy($src.'/'.$file,$dst.'/'.$file);
                };

          closedir($dh);
      }
  };
}

function mkRandomName($len=8){
  $ret="";
  $startstr=md5(time()/13);
  $keys=array_rand(array_fill(0,32,'.'),$len);
  foreach($keys as $key)
    $ret.=$startstr[$key];
  return $ret;
}

function arrayAssign(...$args){
  $ret=[];
  foreach($args as $obj)
    if(is_array($obj)){
      foreach($obj as $k=>$v)
        if(is_array($v)){
          $ret[$k]=arrayAssign($v);
        } else {
          $ret[$k]=$v;
        };
    }
  return $ret;
}

function arrayBuild($obj){
  $ret=[];
  if(is_array($obj)){
    while(!empty($obj['ext-data'])){
      $fname=$obj['ext-data'];
      if(file_exists($fname)){
        echo $fname." loaded"."\r\n";
        $data=json_decode(join("",file($fname)),true);
        unset($obj['ext-data']);
        $obj=arrayAssign($obj,$data);
      } else {
        die("No file exists: ".$obj['ext-data']."\r\n");
      };
    };
    foreach($obj as $k=>$v)
      if(is_array($v)){
        $ret[$k]=arrayBuild($v);
      } else {
        $ret[$k]=$v;
      }
  };
  return $ret;
}
?>
