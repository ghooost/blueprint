<?php
if(!file_exists('blueprint.json')){
  die("blueprint.json should be at the same folder with the sitebones.php\r\n");
};


$siteData=json_decode(join("",file('blueprint.json')),true);
$filesInUse=[];

if(!$siteData){
  die("blueprint.json has no proper site structure\r\n");
};

if(!file_exists('build')) mkdir('build',0777);
clearDir('build');

$styles=[];
$js=[];
$images=[];
$rules=[];

if(empty($siteData['maincss'])) $siteData['maincss']=mkRandomName().'.css';
if(empty($siteData['mainjs'])) $siteData['mainjs']=mkRandomName().'.js';

if($siteData['pages'] && count($siteData['pages']))
  foreach($siteData['pages'] as $page){
    buildPage($page,$siteData,$styles,$js,$images,$rules);
  }

$f=fopen('build/'.$siteData['maincss'],'w');
if($f){
  fputs($f,join("\r\n",$styles));
  fclose($f);
};

$f=fopen('build/'.$siteData['mainjs'],'w');
if($f){
  fputs($f,join("\r\n",$js));
  fclose($f);
};

$hta="";
foreach($rules as $url=>$file){
  $url=preg_replace('/\/+$/','',$url);
  $hta.='RewriteRule ^'.$url.'(\/)*$ '.$file.' [L]'."\r\n";
};

if($hta){
  $f=fopen('build/.htaccess','w');
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

if(count($images)){
  if(!file_exists('build/afb')) mkdir('build/afb',0777);
  foreach($images as $src=>$dst)
    copy($src,'build/afb/'.$dst);
};

function buildPage($pageData,$siteData,&$styles,&$js,&$images,&$rules){
  if(!empty($pageData['url']) && $pageData['url']!=$pageData['file']){
    $rules[$pageData['url']]=$pageData['file'];
  } else {
    $rules['/']=$pageData['file'];
  };

  $pageBody="";
  buildBlock($pageData,$pageData,$siteData,$pageBody,$styles,$js,$images);

  $fileName=$pageData['file']?$pageData['file']:md5(json_encode($pageData)).".html";
  $f=fopen('build/'.$fileName,"w");
  if($f){
    fputs($f,$pageBody);
    fclose($f);
  }
}

function buildBlock($blockData,$pageData,$siteData,&$html,&$styles,&$js,&$images){
  global $filesInUse;


  if(!$blockData['template']) return;
  $folder='blocks/'.$blockData['template'];
  if(!file_exists($folder)) return;

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


  if (is_dir($folder.'/afb')) {
      if ($dh = opendir($folder.'/afb')) {
          while (($file = readdir($dh)) !== false)
              if($file!='.' && $file!='..'){
                $src=$folder.'/afb/'.$file;
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

function mkRandomName($len=8){
  $ret="";
  $startstr=md5(time()/13);
  $keys=array_rand(array_fill(0,32,'.'),$len);
  foreach($keys as $key)
    $ret.=$startstr[$key];
  return $ret;
}
?>
