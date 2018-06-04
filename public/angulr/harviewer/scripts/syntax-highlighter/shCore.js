/**
 * Code Syntax Highlighter.
 * Version 1.5.1
 * Copyright (C) 2004-2007 Alex Gorbatchev.
 * http://www.dreamprojections.com/syntaxhighlighter/
 * 
 * This library is free software; you can redistribute it and/or modify it under the terms of the GNU Lesser General 
 * Public License as published by the Free Software Foundation; either version 2.1 of the License, or (at your option) 
 * any later version.
 *
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more 
 * details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with this library; if not, write to 
 * the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA 
 */

define("syntax-highlighter/shCore",[],function(){var e={sh:{Toolbar:{},Utils:{},RegexLib:{},Brushes:{},Strings:{AboutDialog:'<html><head><title>About...</title></head><body class="dp-about"><table cellspacing="0"><tr><td class="copy"><p class="title">dp.SyntaxHighlighter</div><div class="para">Version: {V}</p><p><a href="http://www.dreamprojections.com/syntaxhighlighter/?ref=about" target="_blank">http://www.dreamprojections.com/syntaxhighlighter</a></p>&copy;2004-2007 Alex Gorbatchev.</td></tr><tr><td class="footer"><input type="button" class="close" value="OK" onClick="window.close()"/></td></tr></table></body></html>'},ClipboardSwf:null,Version:"1.5.1"}};return e.SyntaxHighlighter=e.sh,e.sh.Toolbar.Commands={ExpandSource:{label:"+ expand source",check:function(e){return e.collapse},func:function(e,t){e.parentNode.removeChild(e),t.div.className=t.div.className.replace("collapsed","")}},ViewSource:{label:"view plain",func:function(t,n){var r=e.sh.Utils.FixForBlogger(n.originalCode).replace(/</g,"&lt;"),i=window.open("","_blank","width=750, height=400, location=0, resizable=1, menubar=0, scrollbars=0");i.document.write('<textarea style="width:99%;height:99%">'+r+"</textarea>"),i.document.close()}},CopyToClipboard:{label:"copy to clipboard",check:function(){return window.clipboardData!=null||e.sh.ClipboardSwf!=null},func:function(t,n){var r=e.sh.Utils.FixForBlogger(n.originalCode).replace(/&lt;/g,"<").replace(/&gt;/g,">").replace(/&amp;/g,"&");if(window.clipboardData)window.clipboardData.setData("text",r);else if(e.sh.ClipboardSwf!=null){var i=n.flashCopier;i==null&&(i=document.createElement("div"),n.flashCopier=i,n.div.appendChild(i)),i.innerHTML='<embed src="'+e.sh.ClipboardSwf+'" FlashVars="clipboard='+encodeURIComponent(r)+'" width="0" height="0" type="application/x-shockwave-flash"></embed>'}alert("The code is in your clipboard now")}},PrintSource:{label:"print",func:function(t,n){var r=document.createElement("IFRAME"),i=null;r.style.cssText="position:absolute;width:0px;height:0px;left:-500px;top:-500px;",document.body.appendChild(r),i=r.contentWindow.document,e.sh.Utils.CopyStyles(i,window.document),i.write('<div class="'+n.div.className.replace("collapsed","")+' printing">'+n.div.innerHTML+"</div>"),i.close(),r.contentWindow.focus(),r.contentWindow.print(),alert("Printing..."),document.body.removeChild(r)}},About:{label:"?",func:function(t){var n=window.open("","_blank","dialog,width=300,height=150,scrollbars=0"),r=n.document;e.sh.Utils.CopyStyles(r,window.document),r.write(e.sh.Strings.AboutDialog.replace("{V}",e.sh.Version)),r.close(),n.focus()}}},e.sh.Toolbar.Create=function(t){var n=document.createElement("DIV");n.className="tools";for(var r in e.sh.Toolbar.Commands){var i=e.sh.Toolbar.Commands[r];if(i.check!=null&&!i.check(t))continue;n.innerHTML+='<a href="#" onclick="dp.sh.Toolbar.Command(\''+r+"',this);return false;\">"+i.label+"</a>"}return n},e.sh.Toolbar.Command=function(t,n){var r=n;while(r!=null&&r.className.indexOf("dp-highlighter")==-1)r=r.parentNode;r!=null&&e.sh.Toolbar.Commands[t].func(n,r.highlighter)},e.sh.Utils.CopyStyles=function(e,t){var n=t.getElementsByTagName("link");for(var r=0;r<n.length;r++)n[r].rel.toLowerCase()=="stylesheet"&&e.write('<link type="text/css" rel="stylesheet" href="'+n[r].href+'"></link>')},e.sh.Utils.FixForBlogger=function(t){return e.sh.isBloggerMode==1?t.replace(/<br\s*\/?>|&lt;br\s*\/?&gt;/gi,"\n"):t},e.sh.RegexLib={MultiLineCComments:new RegExp("/\\*[\\s\\S]*?\\*/","gm"),SingleLineCComments:new RegExp("//.*$","gm"),SingleLinePerlComments:new RegExp("#.*$","gm"),DoubleQuotedString:new RegExp('"(?:\\.|(\\\\\\")|[^\\""\\n])*"',"g"),SingleQuotedString:new RegExp("'(?:\\.|(\\\\\\')|[^\\''\\n])*'","g")},e.sh.Match=function(e,t,n){this.value=e,this.index=t,this.length=e.length,this.css=n},e.sh.Highlighter=function(){this.noGutter=!1,this.addControls=!0,this.collapse=!1,this.tabsToSpaces=!0,this.wrapColumn=80,this.showColumns=!0},e.sh.Highlighter.SortCallback=function(e,t){return e.index<t.index?-1:e.index>t.index?1:e.length<t.length?-1:e.length>t.length?1:0},e.sh.Highlighter.prototype.CreateElement=function(e){var t=document.createElement(e);return t.highlighter=this,t},e.sh.Highlighter.prototype.GetMatches=function(t,n){var r=0,i=null;while((i=t.exec(this.code))!=null)this.matches[this.matches.length]=new e.sh.Match(i[0],i.index,n)},e.sh.Highlighter.prototype.AddBit=function(e,t){if(e==null||e.length==0)return;var n=this.CreateElement("SPAN");e=e.replace(/ /g,"&nbsp;"),e=e.replace(/</g,"&lt;"),e=e.replace(/\n/gm,"&nbsp;<br>");if(t!=null)if(/br/gi.test(e)){var r=e.split("&nbsp;<br>");for(var i=0;i<r.length;i++)n=this.CreateElement("SPAN"),n.className=t,n.innerHTML=r[i],this.div.appendChild(n),i+1<r.length&&this.div.appendChild(this.CreateElement("BR"))}else n.className=t,n.innerHTML=e,this.div.appendChild(n);else n.innerHTML=e,this.div.appendChild(n)},e.sh.Highlighter.prototype.IsInside=function(e){if(e==null||e.length==0)return!1;for(var t=0;t<this.matches.length;t++){var n=this.matches[t];if(n==null)continue;if(e.index>n.index&&e.index<n.index+n.length)return!0}return!1},e.sh.Highlighter.prototype.ProcessRegexList=function(){for(var e=0;e<this.regexList.length;e++)this.GetMatches(this.regexList[e].regex,this.regexList[e].css)},e.sh.Highlighter.prototype.ProcessSmartTabs=function(e){function s(e,t,n){var r=e.substr(0,t),i=e.substr(t+1,e.length),s="";for(var o=0;o<n;o++)s+=" ";return r+s+i}function o(e,t){if(e.indexOf(i)==-1)return e;var n=0;while((n=e.indexOf(i))!=-1){var r=t-n%t;e=s(e,n,r)}return e}var t=e.split("\n"),n="",r=4,i="	";for(var u=0;u<t.length;u++)n+=o(t[u],r)+"\n";return n},e.sh.Highlighter.prototype.SwitchToList=function(){var t=this.div.innerHTML.replace(/<(br)\/?>/gi,"\n"),n=t.split("\n");this.addControls==1&&this.bar.appendChild(e.sh.Toolbar.Create(this));if(this.showColumns){var r=this.CreateElement("div"),i=this.CreateElement("div"),s=10,o=1;while(o<=150)o%s==0?(r.innerHTML+=o,o+=(o+"").length):(r.innerHTML+="&middot;",o++);i.className="columns",i.appendChild(r),this.bar.appendChild(i)}for(var o=0,u=this.firstLine;o<n.length-1;o++,u++){var a=this.CreateElement("LI"),f=this.CreateElement("SPAN");a.className=o%2==0?"alt":"",f.innerHTML=n[o]+"&nbsp;",a.appendChild(f),this.ol.appendChild(a)}this.div.innerHTML=""},e.sh.Highlighter.prototype.Highlight=function(t){function n(e){return e.replace(/^\s*(.*?)[\s\n]*$/g,"$1")}function r(e){return e.replace(/\n*$/,"").replace(/^\n*/,"")}function i(t){var r=e.sh.Utils.FixForBlogger(t).split("\n"),i=new Array,s=new RegExp("^\\s*","g"),o=1e3;for(var u=0;u<r.length&&o>0;u++){if(n(r[u]).length==0)continue;var a=s.exec(r[u]);a!=null&&a.length>0&&(o=Math.min(a[0].length,o))}if(o>0)for(var u=0;u<r.length;u++)r[u]=r[u].substr(o);return r.join("\n")}function s(e,t,n){return e.substr(t,n-t)}var o=0;t==null&&(t=""),this.originalCode=t,this.code=r(i(t)),this.div=this.CreateElement("DIV"),this.bar=this.CreateElement("DIV"),this.ol=this.CreateElement("OL"),this.matches=new Array,this.div.className="dp-highlighter",this.div.highlighter=this,this.bar.className="bar",this.ol.start=this.firstLine,this.CssClass!=null&&(this.ol.className=this.CssClass),this.collapse&&(this.div.className+=" collapsed"),this.noGutter&&(this.div.className+=" nogutter"),this.tabsToSpaces==1&&(this.code=this.ProcessSmartTabs(this.code)),this.ProcessRegexList();if(this.matches.length==0){this.AddBit(this.code,null),this.SwitchToList(),this.div.appendChild(this.bar),this.div.appendChild(this.ol);return}this.matches=this.matches.sort(e.sh.Highlighter.SortCallback);for(var u=0;u<this.matches.length;u++)this.IsInside(this.matches[u])&&(this.matches[u]=null);for(var u=0;u<this.matches.length;u++){var a=this.matches[u];if(a==null||a.length==0)continue;this.AddBit(s(this.code,o,a.index),null),this.AddBit(a.value,a.css),o=a.index+a.length}this.AddBit(this.code.substr(o),null),this.SwitchToList(),this.div.appendChild(this.bar),this.div.appendChild(this.ol)},e.sh.Highlighter.prototype.GetKeywords=function(e){return"\\b"+e.replace(/ /g,"\\b|\\b")+"\\b"},e.sh.BloggerMode=function(){e.sh.isBloggerMode=!0},e.sh.HighlightAll=function(t,n,r,i,s,o){function u(){var e=arguments;for(var t=0;t<e.length;t++){if(e[t]==null)continue;if(typeof e[t]=="string"&&e[t]!="")return e[t]+"";if(typeof e[t]=="object"&&e[t].value!="")return e[t].value+""}return null}function a(e,t){for(var n=0;n<t.length;n++)if(t[n]==e)return!0;return!1}function f(e,t,n){var r=new RegExp("^"+e+"\\[(\\w+)\\]$","gi"),i=null;for(var s=0;s<t.length;s++)if((i=r.exec(t[s]))!=null)return i[1];return n}function l(e,t,n){var r=document.getElementsByTagName(n);for(var i=0;i<r.length;i++)r[i].getAttribute("name")==t&&e.push(r[i])}var c=[],h=null,p={},d="innerHTML";typeof t=="string"?(l(c,t,"pre"),l(c,t,"textarea")):c.push(t);if(c.length==0)return;for(var v in e.sh.Brushes){var m=e.sh.Brushes[v].Aliases;if(m==null)continue;for(var g=0;g<m.length;g++)p[m[g]]=v}for(var g=0;g<c.length;g++){var y=c[g],b=u(y.attributes["class"],y.className,y.attributes.language,y.language),w="";if(b==null)continue;b=b.split(":"),w=b[0].toLowerCase();if(p[w]==null)continue;h=new e.sh.Brushes[p[w]],y.style.display="none",h.noGutter=n==null?a("nogutter",b):!n,h.addControls=r==null?!a("nocontrols",b):r,h.collapse=i==null?a("collapse",b):i,h.showColumns=o==null?a("showcolumns",b):o;var E=document.getElementsByTagName("head")[0];if(h.Style&&E){var S=document.createElement("style");S.setAttribute("type","text/css");if(S.styleSheet)S.styleSheet.cssText=h.Style;else{var x=document.createTextNode(h.Style);S.appendChild(x)}E.appendChild(S)}h.firstLine=s==null?parseInt(f("firstline",b,1)):s,h.Highlight(y[d]),h.source=y,y.parentNode.insertBefore(h.div,y)}},e.sh.Brushes.JScript=function(){var t="abstract boolean break byte case catch char class const continue debugger default delete do double else enum export extends false final finally float for function goto if implements import in instanceof int interface long native new null package private protected public return short static super switch synchronized this throw throws transient true try typeof var void volatile while with";this.regexList=[{regex:e.sh.RegexLib.SingleLineCComments,css:"comment"},{regex:e.sh.RegexLib.MultiLineCComments,css:"comment"},{regex:e.sh.RegexLib.DoubleQuotedString,css:"string"},{regex:e.sh.RegexLib.SingleQuotedString,css:"string"},{regex:new RegExp("^\\s*#.*","gm"),css:"preprocessor"},{regex:new RegExp(this.GetKeywords(t),"gm"),css:"keyword"}],this.CssClass="dp-c"},e.sh.Brushes.JScript.prototype=new e.sh.Highlighter,e.sh.Brushes.JScript.Aliases=["js","jscript","javascript"],e});