/* See license.txt for terms of usage */

define("domplate/infoTip",["domplate/domplate","core/lib","core/trace"],function(Domplate,Lib,Trace){with(Domplate){var InfoTip=Lib.extend({listeners:[],maxWidth:100,maxHeight:80,infoTipMargin:10,infoTipWindowPadding:25,tags:domplate({infoTipTag:DIV({"class":"infoTip"})}),initialize:function(){var e=$("body");return e.bind("mouseover",Lib.bind(this.onMouseMove,this)),e.bind("mouseout",Lib.bind(this.onMouseOut,this)),e.bind("mousemove",Lib.bind(this.onMouseMove,this)),this.infoTip=this.tags.infoTipTag.append({},Lib.getBody(document))},showInfoTip:function(e,t,n,r,i,s){var o=Lib.getOverflowParent(t),u=n+(o?o.scrollLeft:0),a=Lib.dispatch2(this.listeners,"showInfoTip",[e,t,u,r,i,s]);if(a){var f=e.ownerDocument.documentElement,l=f.clientWidth,c=f.clientHeight;n+e.offsetWidth+this.infoTipMargin>l-this.infoTipWindowPadding?(e.style.left="auto",e.style.right=l-n+this.infoTipMargin+"px"):(e.style.left=n+this.infoTipMargin+"px",e.style.right="auto"),r+e.offsetHeight+this.infoTipMargin>c?(e.style.top=Math.max(0,c-(e.offsetHeight+this.infoTipMargin))+"px",e.style.bottom="auto"):(e.style.top=r+this.infoTipMargin+"px",e.style.bottom="auto"),e.setAttribute("active","true")}else this.hideInfoTip(e)},hideInfoTip:function(e){e&&e.removeAttribute("active")},onMouseOut:function(e){e.relatedTarget||this.hideInfoTip(this.infoTip)},onMouseMove:function(e){this.infoTip.setAttribute("multiline",!1);var t=e.clientX,n=e.clientY;this.showInfoTip(this.infoTip,e.target,t,n,e.rangeParent,e.rangeOffset)},populateTimingInfoTip:function(e,t){return this.tags.colorTag.replace({rgbValue:t},e),!0},addListener:function(e){this.listeners.push(e)},removeListener:function(e){Lib.remove(this.listeners,e)}});return InfoTip.initialize(),InfoTip}});