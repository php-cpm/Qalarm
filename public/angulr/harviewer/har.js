/* See license.txt for terms of usage */

(function(){function t(t){var n=t.className.replace(e," ");t.className=n.replace(/^\s*|\s*$/g,"")}function n(){var t=[];if(document.getElementsByClassName)t=document.getElementsByClassName("har");else if(document.getElementsByTagName){var n=document.getElementsByTagName("div");for(var r=0;r<n.length;r++){var i=n[r].className;i&&i.match(e)&&t.push(n[r])}}var s=[];for(var r=0;r<t.length;r++)s.push(t[r]);return s}function r(e,t,n,r){r=r||!1,e.addEventListener?e.addEventListener(t,n,r):e.attachEvent("on"+t,n)}window.harInitialize=function(){var e=document.getElementById("har"),r=e.src.lastIndexOf("/"),i=e.src.substr(0,r+1),s=n();for(var o=0;o<s.length;o++){var u=s[o],a=u.getAttribute("data-har");if(!a)continue;var f=u.getAttribute("data-callback"),l=u.getAttribute("width"),c=u.getAttribute("height"),h=u.getAttribute("expand"),p=u.getAttribute("validate"),d="?"+(a.indexOf("http:")==0?"inputUrl":"path")+"="+encodeURIComponent(a);h!="false"&&(d+="&expand="+(h?h:"true")),p=="false"&&(d+="&validate=false"),f&&(d+="&callback="+f);var v=document.createElement("iframe");v.setAttribute("style","border: 1px solid lightgray;"),v.setAttribute("frameborder","0"),v.setAttribute("width",l?l:"100%"),v.setAttribute("height",c?c:"150px"),v.setAttribute("src",i+"preview.php"+d),u.appendChild(v),t(u)}};var e=new RegExp("(^|\\s)har(\\s|$)","g");harInitialize(),r(window,"load",harInitialize,!1)})();