<?php
$filesInUse="";
$options=getArgv(
  $argv,
  ['-i'=>'blueprint','-o'=>'output','-build'=>'build',"-watch"=>'watch',"-watchtime"=>'watchtime',"-mode"=>"mode","-debug"=>"debug"],
  ['blueprint'=>'','output'=>'','build'=>false,"watch"=>false,'watchtime'=>5,"mode"=>"","debug"=>false]
);

if(!$options['blueprint'] && !$options['watch']){
  die(<<<EOT
Blueprint - is a static site generator written in PHP.

Usage:
php blueprint.php -- [-i <folder>] [-o <folder>] [-build] [-watch] [-watchtime <sec>]

Options:
-i <folder>       : define folder from where blueprint.json will be loaded
-o <folder>       : output folder
-build            : build final bandles, shortcut for -mode build
-mode <mode>      : mode, can be dev, build or any other custom mode
-watch            : watch -i folder
-watchtime <sec>  : watch timeout in seconds

More info:
https://github.com/ghooost/blueprint

EOT
);
}

if($options['build']){
  $options['mode']='build';
};

if(empty($options['mode'])){
  $options['mode']='dev';
};

if($options['watch']){
  //do watch!
  if($options['blueprint']){
    $folderToWatch=$options['blueprint'];
  } else {
    $folderToWatch=".";
  };
  doWatch($folderToWatch,$options);
} else {
  doBuilding($options);
}

function doWatch($folder,$options){
  $hash="";
  while(true){
    if(!file_exists($folder)){
      die("Watch can't see ".$folder);
    };
    $newHash=md5(listFolder($folder));
    if($newHash!=$hash){
      doBuilding($options);
      $hash=md5(listFolder($folder));
    } else {
//      echo "No changes\r\n";
    };
    sleep(empty($options['watchtime'])?5:$options['watchtime']);
  }
}

function listFolder($src){
  $ret="";
  if (file_exists($src) && is_dir($src)) {
      if ($dh = opendir($src)) {
          while (($file = readdir($dh)) !== false)
              if($file!='.' && $file!='..')
                if(is_dir($src.'/'.$file)){
                  $ret.=listFolder($src.'/'.$file,$dst.'/'.$file);
                } else {
                  $f=$src.'/'.$file;
                  $ret.='|'.$f.'|'.filesize($f).'|'.filemtime($f).'|';
                };
          closedir($dh);
      }
  };
  return $ret;
}

function doBuilding($options){
  global $filesInUse;
  if(!file_exists($options['blueprint'].'/blueprint.json')){
    die($options['blueprint']."/blueprint.json not found\r\n");
  };

  echo "**************blueprints loading*************\r\n";
  $siteData=arrayBuild(
    [
      'codepage'=>'utf-8',
      'pageTemplate'=>'_page',

      'blueprint'=>$options['blueprint'],

      'folder-output'=>$options['output']?$options['output']:($options['blueprint'].'/build'),
      'folder-static'=>$options['blueprint'].'/static',

      'folder-def'=>'blueprint',
      'folder-afb'=>'afb',
      'folder-lib'=>'blocks',

      'options'=>$options,

      'maincss'=>'index.css',
      'mainjs'=>'index.js',
      'build-maincss'=>mkRandomName().'.css',
      'build-mainjs'=>mkRandomName().'.js',

      'ext-data'=>$options['blueprint'].'/blueprint.json'
    ],".");
  echo "**************loaded*************\r\n";

  $filesInUse=[];

  if(!file_exists($siteData['folder-output'])) mkdir($siteData['folder-output'],0777);
  if(file_exists($siteData['folder-output'])){

    if($options['mode']=='build') clearDir($siteData['folder-output']);

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

  $mode=$options['mode'];

  $fname=firstNotEmpty($siteData,[$mode.'-maincss','maincss']);
  $f=fopen($siteData['folder-output'].'/'.$fname,'w');
  if($f){
    $body=join("\r\n",$styles);

    $ch=firstNotEmpty($siteData,[$mode.'-css-afb-url','css-afb-url',$mode.'-afb-url','afb-url']);
    if($ch){
      $pattern=preg_replace('/\/$/','',$siteData['folder-afb']);
      $body=preg_replace('/'.preg_quote($pattern).'\//',$ch,$body);
    };
    fputs($f,$body);
    fclose($f);
  };

  $fname=firstNotEmpty($siteData,[$mode.'-mainjs','mainjs']);
  $f=fopen($siteData['folder-output'].'/'.$fname,'w');
  if($f){
    $body=join("\r\n",$js);

    $ch=firstNotEmpty($siteData,[$mode.'-js-afb-url','js-afb-url',$mode.'-afb-url','afb-url']);
    if($ch){
      $pattern=preg_replace('/\/$/','',$siteData['folder-afb']);
      $body=preg_replace('/'.preg_quote($pattern).'\//',$ch,$body);
    };
    fputs($f,$body);
    fclose($f);
  };

  if(count($rules)){
    $hta="";
    foreach($rules as $url=>$file){
      $url=preg_replace(['/^\/+/','/\/+$/'],['',''],$url);
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

  if(!empty($siteData['folder-static']))
    copyDir($siteData['folder-static'],$siteData['folder-output']);

  if(count($images)){
    if(!file_exists($siteData['folder-output'].'/'.$siteData['folder-afb'])) mkdir($siteData['folder-output'].'/'.$siteData['folder-afb'],0777);
    foreach($images as $src=>$dst)
      copyFile($src,$siteData['folder-output'].'/'.$siteData['folder-afb'].'/'.$dst);
  };

}

function buildPage($pageData,$siteData,&$styles,&$js,&$images,&$rules){
  if(empty($siteData['def-page'])) $siteData['def-page']=[];
  $pageData=arrayAssign($siteData['def-page'],$pageData);

  if($cURL && !preg_match('/\/$/',$cURL)) $cURL.='/';

  if(!empty($pageData['url']) && $pageData['url']!=$pageData['file']){
    $rules[$pageData['url']]=$pageData['file'];
  } else {
    $rules[$cURL]=$pageData['file'];
  };

  if(empty($pageData['template']))
    $pageData['template']=$siteData['pageTemplate'];

  $pageBody="";
  buildBlock($pageData,$pageData,$siteData,$pageBody,$styles,$js,$images);

  $fileName=$pageData['file']?$pageData['file']:md5(json_encode($pageData)).".html";
  $f=fopen($siteData['folder-output'].'/'.$fileName,"w");
  if($f){
    $mode=$siteData['options']['mode'];
    $ch=firstNotEmpty($siteData,[$mode.'-afb-url','afb-url']);
    if($ch){
      $pattern=preg_replace('/\/$/','',$siteData['folder-afb']);
      $pageBody=preg_replace('/'.preg_quote($pattern).'\//',$ch,$pageBody);
    };


    fputs($f,$pageBody);
    fclose($f);
  }
}

function buildBlock($blockData,$pageData,$siteData,&$html,&$styles,&$js,&$images){
  global $filesInUse;

  if(!$blockData['template']) return;
  $folder=$siteData['blueprint'].'/'.$siteData['folder-lib'].'/'.$blockData['template'];

  if(!file_exists($folder)){
    $folder=$siteData['folder-def'].'/'.$siteData['folder-lib'].'/'.$blockData['template'];
    if(!file_exists($folder)){
      die("There is no ".$folder." block\r\n");
    };
  };

  $content=getContent([
    $folder.'/'.$siteData['options']['mode'].'_css.php',
    $folder.'/'.$siteData['options']['mode'].'.css',
    $folder.'/css.php',
    $folder.'/index.css'
  ],$blockData,$pageData,$siteData,$html,$styles,$js,$images);
  $key=md5($content);
  if(empty($filesInUse[$key])){
    $styles[]=$content;
    $filesInUse[$key]=TRUE;
  };

  $content=getContent([
    $folder.'/'.$siteData['options']['mode'].'_js.php',
    $folder.'/'.$siteData['options']['mode'].'.js',
    $folder.'/js.php',
    $folder.'/index.js'
  ],$blockData,$pageData,$siteData,$html,$styles,$js,$images);
  $key=md5($content);
  if(empty($filesInUse[$key])){
    $js[]=$content;
    $filesInUse[$key]=TRUE;
  };

  $html.=getContent([
    $folder.'/'.$siteData['options']['mode'].'_html.php',
    $folder.'/'.$siteData['options']['mode'].'.html',
    $folder.'/index.php',
    $folder.'/index.html'
  ],$blockData,$pageData,$siteData,$html,$styles,$js,$images);


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

function firstNotEmpty($data,$keys,$def=''){
    foreach($keys as $key)
      if(!empty($data[$key]))
        return $data[$key];
    return $def;
}

function getContent($filenames,$blockData,$pageData,$siteData,&$html,&$styles,&$js,&$images){
  foreach($filenames as $file){
    if(file_exists($file)){
      ob_start();
      if(preg_match('/\.php$/i',$file)){
        require $file;
      } else {
        readfile($file);
      };
      $ret=ob_get_contents();
      ob_end_clean();
      return $ret;
    };
  }
  return "";
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
                  copyFile($src.'/'.$file,$dst.'/'.$file);
                };

          closedir($dh);
      }
  };
}

function copyFile($src,$dst){
  if(file_exists($dst) && filesize($src)==filesize($dst)){
    //echo $src." has no changes\r\n";
  } else {
    copy($src,$dst);
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

function chPaths($data,$path){
  $ret=[];
  foreach($data as $k=>$v)
    if(is_array($v)){
      $ret[$k]=chPaths($v,$path);
    } else {
      $ret[$k]=preg_replace('/^\.\//',$path.'/',$v);
    }
  return $ret;
}

function loadExtData($fname){
  if(file_exists($fname)){
    $path=dirname($fname);
    $data=json_decode(join("",file($fname)),true);
    return chPaths($data,$path);
  } else {
    die("No ".$fname." exists\r\n");
  }
}

function arrayBuild($obj){
  $ret=[];
  if(is_array($obj)){
    while(!empty($obj['ext-data'])){
      $fname=$obj['ext-data'];
      if(file_exists($fname)){
        echo $fname." loaded"."\r\n";
        $data=loadExtData($fname);
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


function getArgv($arr,$keys,$ret){
  $gotMM=false;
  $curKey="";
  foreach($arr as $v)
    if($v=='--'){
      $gotMM=true;
    } else if($gotMM){
      if(preg_match('/^-/',$v)){
        if(empty($keys[$v])){
          die("Unrecognized key ".$v);
        } else {
          $curKey=$v;
          $ret[$keys[$curKey]]=true;
        };
      } else {
        if(!$curKey){
          die("Unrecognized param ".$v);
        } else {
          if($ret[$keys[$curKey]]===true){
              $ret[$keys[$curKey]]=$v;
          } else {
            $ret[$keys[$curKey]].=" ".$v;
          };
        }
      }
    }
  return $ret;
}

?>
