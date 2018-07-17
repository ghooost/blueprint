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
<header>
  <ul class="menu">$nav</ul>
</header>
EOT;

?>
