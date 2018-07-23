(function(){
  window.addEventListener('scroll', onScroll, true);
  window.addEventListener('resize', onResize, true);
  window.addEventListener('load', checkMenu, true);
  var header=null;
  var main=null;

  function onScroll(e){
    checkMenu();
  }

  function onResize(e){
    checkMenu();
  }

  function checkMenu(){
    var headerClasses=[];
    var top=0;
    var wh=window.innerHeight;
    if(!header) header=document.querySelector("header");
    if(wh<767){
      headerClasses.push('scrollable');
    };
    if(!main) main=document.querySelector('main');
    var r=main.getBoundingClientRect();
    if(r.top<155){
      headerClasses.push('menu');
    }
    header.className=headerClasses.join(" ");
  }
}());
