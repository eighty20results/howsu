//	HYPE.documents["Howsu"]

(function(){(function k(){function l(a,b,d){var c=!1;null==window[a]&&(null==window[b]?(window[b]=[],window[b].push(k),a=document.getElementsByTagName("head")[0],b=document.createElement("script"),c=h,false==!0&&(c=""),b.type="text/javascript",b.src=c+"/"+d,a.appendChild(b)):window[b].push(k),c=!0);return c}var h="howsu.hyperesources",c="Howsu",e="howsu_hype_container";if(false==!1)try{for(var f=document.getElementsByTagName("script"),
a=0;a<f.length;a++){var b=f[a].src;if(null!=b&&-1!=b.indexOf("howsuDemo_hype_generated_script.js")){h=b.substr(0,b.lastIndexOf("/"));break}}}catch(n){}if(false==!1&&(a=navigator.userAgent.match(/MSIE (\d+\.\d+)/),a=parseFloat(a&&a[1])||null,a=l("HYPE_526","HYPE_dtl_526",!0==(null!=a&&10>a||false==!0)?"HYPE-526.full.min.js":"HYPE-526.thin.min.js"),false==!0&&(a=a||l("HYPE_w_526","HYPE_wdtl_526","HYPE-526.waypoints.min.js")),a))return;
f=window.HYPE.documents;if(null!=f[c]){b=1;a=c;do c=""+a+"-"+b++;while(null!=f[c]);for(var d=document.getElementsByTagName("div"),b=!1,a=0;a<d.length;a++)if(d[a].id==e&&null==d[a].getAttribute("HYP_dn")){var b=1,g=e;do e=""+g+"-"+b++;while(null!=document.getElementById(e));d[a].id=e;b=!0;break}if(!1==b)return}b=[];b=[{name:"textmeButton",source:"function(hypeDocument, element, event) {\t\n\t\n\tjQuery(document).on('click', '#livedemoButton', function() {\n\t\tvar state = jQuery(this).attr('class');\n\t\t\t\t\n\t\tif(state.indexOf('ani') != -1) {\n\t\t\t\t\n\t\t\tjQuery(this).addClass('ani');\n\t\t\tjQuery(this).removeClass('dem');\t\n\t\t\t\n\t\t\thypeDocument.continueTimelineNamed('demo', hypeDocument.kDirectionForward);\n\t\t\thypeDocument.continueTimelineNamed('Main Timeline', hypeDocument.kDirectionForward);\n\t\t\t\n\t\t} else {\n\t\t\n\t\t\tjQuery(this).addClass('dem');\n\t\t\tjQuery(this).removeClass('ani');\n\t\t\t\n\t\t\t//hypeDocument.pauseTimelineNamed('Main Timeline', hypeDocument.kDirectionForward);\n\t\t\thypeDocument.startTimelineNamed('demo', hypeDocument.kDirectionForward);\n\t\t}\n\t\n\t});\n\t\t\n\t\n\tjQuery(document).on('click', '#textmeButton', function() {\n\t\n\t\tvar name = jQuery('#demoname').val();\n\t\tvar phone = jQuery('#demophone').val();\n\t\t\n\t\tif(name && phone) {\n\t\t\n\t\t\tjQuery.ajax({\n\t\t\t\turl: '/wp-admin/admin-ajax.php',\n\t\t\t\ttype: 'POST',\n\t\t\t\tdata: 'action=howsu_demo&type=text&name='+name+'&number='+phone,\n\t\t\t\n\t\t\t\tsuccess: function(res) {\t\t\n\t\t\t\t\tif(res) {\n\t\t\t\t\t\thypeDocument.continueTimelineNamed('Main Timeline', hypeDocument.kDirectionForward);\n\t\t\t\t\t}\t\t\t\n\t\t\t\t}\n\t\t\t});\n\t\t} else {\n\t\t\n\t\t\tjQuery('#detailError').fadeIn().delay('800').fadeOut();\n\t\t\n\t\t}\n\t});\t\n\t\tjQuery(document).on('click', '#phonemeButton', function() {\n\t\n\t\tvar name = jQuery('#demoname').val();\n\t\tvar phone = jQuery('#demophone').val();\n\t\t\n\t\tif(name && phone) {\n\t\t\n\t\t\tjQuery.ajax({\n\t\t\t\turl: '/wp-admin/admin-ajax.php',\n\t\t\t\ttype: 'POST',\n\t\t\t\tdata: 'action=howsu_demo&type=phone&name='+name+'&number='+phone,\n\t\t\t\n\t\t\t\tsuccess: function(res) {\n\t\t\t\t\tif(res) {\n\t\t\t\t\t\thypeDocument.continueTimelineNamed('Main Timeline', hypeDocument.kDirectionForward);\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t});\n\t\t} else {\n\t\t\tjQuery('#detailError').fadeIn().delay('800').fadeOut();\n\t\t}\n\t});\n\t\n\t\n}",identifier:"11"}];d={};g={};for(a=0;a<b.length;a++)try{g[b[a].identifier]=b[a].name,d[b[a].name]=eval("(function(){return "+b[a].source+"})();")}catch(m){window.console&&window.console.log(m),
d[b[a].name]=function(){}}a=new HYPE_526(c,e,{"7":{p:1,n:"Screen%20Shot%202015-11-21%20at%2018.05.31.jpg",g:"31",t:"@1x"},"3":{p:1,n:"Screen%20Shot%202015-11-21%20at%2018.03.32.jpg",g:"27",t:"@1x"},"8":{p:1,n:"Screen%20Shot%202015-11-21%20at%2018.05.31_2x.jpg",g:"31",t:"@2x"},"4":{p:1,n:"Screen%20Shot%202015-11-21%20at%2018.03.32_2x.jpg",g:"27",t:"@2x"},"0":{p:1,n:"Pasted.png",g:"7",t:"@1x"},"5":{p:1,n:"Screen%20Shot%202015-11-21%20at%2018.05.47.jpg",g:"29",t:"@1x"},"1":{n:"jquery-2.1.4.min.js"},"6":{p:1,n:"Screen%20Shot%202015-11-21%20at%2018.05.47_2x.jpg",g:"29",t:"@2x"},"2":{p:1,n:"backg_2.jpg",g:"21",t:"@1x"}},h,[],d,[{n:"Untitled Scene",o:"1",X:[0]}],[{A:{a:[{p:4,h:"11"},{b:"12",p:3,z:false,symbolOid:"2"}]},o:"3",p:"600px",x:0,cA:false,Z:480,Y:240,c:"#FFFFFF",L:[],bY:1,d:240,U:{},T:{"12":{i:"12",n:"demo",z:15.15,b:[],a:[{f:"c",y:0,z:4.18,i:"e",e:-1,s:-1,o:"43"},{f:"c",y:0,z:13.2,i:"a",e:4,s:4,o:"42"},{f:"c",p:2,y:0,z:15.15,i:"ActionHandler",e:{a:[{b:"12",p:3,z:false,symbolOid:"2"}]},s:{a:[{b:"12",p:3,z:false,symbolOid:"2"}]},o:"12"},{f:"c",y:0,z:7,i:"e",e:0,s:0,o:"47"},{f:"c",y:0,z:10,i:"e",e:0,s:0,o:"48"},{f:"c",y:0,z:13,i:"e",e:0,s:0,o:"42"},{f:"c",y:0,z:5.05,i:"e",e:0,s:0,o:"44"},{f:"c",y:0,z:0.09,i:"e",e:1,s:0,o:"45"},{f:"c",y:0,z:9.06,i:"e",e:0,s:0,o:"49"},{f:"c",y:0.09,z:0.11,i:"e",e:1,s:1,o:"45"},{f:"c",y:0.2,z:0.09,i:"e",e:0,s:1,o:"45"},{y:0.29,i:"e",s:0,z:0,o:"45",f:"c"},{f:"c",y:1.03,z:0.2,i:"e",e:0,s:1,o:"46"},{y:1.23,i:"e",s:0,z:0,o:"46",f:"c"},{f:"c",y:2,z:11.2,i:"b",e:30,s:45,o:"42"},{f:"c",y:2.09,z:0.15,i:"e",e:0,s:1,o:"50"},{y:2.24,i:"e",s:0,z:0,o:"50",f:"c"},{f:"c",y:3.15,z:0.15,i:"e",e:0,s:1,o:"41"},{y:4,i:"e",s:0,z:0,o:"41",f:"c"},{f:"c",y:4.18,z:0.15,i:"e",e:1,s:-1,o:"43"},{y:5.03,i:"e",s:1,z:0,o:"43",f:"c"},{f:"c",y:5.05,z:0.09,i:"e",e:1,s:0,o:"44"},{f:"c",y:5.14,z:1.01,i:"e",e:1,s:1,o:"44"},{f:"c",y:6.15,z:0.08,i:"e",e:0,s:1,o:"44"},{y:6.23,i:"e",s:0,z:0,o:"44",f:"c"},{f:"c",y:7,z:0.15,i:"e",e:1,s:0,o:"47"},{f:"c",y:7.15,z:1,i:"e",e:1,s:1,o:"47"},{f:"c",y:8.15,z:0.15,i:"e",e:0,s:1,o:"47"},{y:9,i:"e",s:0,z:0,o:"47",f:"c"},{f:"c",y:9.06,z:0.09,i:"e",e:1,s:0,o:"49"},{f:"c",y:9.15,z:0.09,i:"e",e:0,s:1,o:"49"},{y:9.24,i:"e",s:0,z:0,o:"49",f:"c"},{f:"c",y:10,z:0.15,i:"e",e:1,s:0,o:"48"},{f:"c",y:10.15,z:1,i:"e",e:1,s:1,o:"48"},{f:"c",y:11.15,z:0.15,i:"e",e:0,s:1,o:"48"},{y:12,i:"e",s:0,z:0,o:"48",f:"c"},{f:"c",y:13,z:0.2,i:"e",e:1,s:0,o:"42"},{y:13.2,i:"a",s:4,z:0,o:"42",f:"c"},{y:13.2,i:"b",s:30,z:0,o:"42",f:"c"},{f:"c",y:13.2,z:1.1,i:"e",e:1,s:1,o:"42"},{f:"c",y:15,z:0.15,i:"e",e:0,s:1,o:"42"},{f:"c",p:2,y:15.15,z:0,i:"ActionHandler",s:{a:[{b:"12",p:3,z:false,symbolOid:"2"}]},o:"12"},{y:15.15,i:"e",s:0,z:0,o:"42",f:"c"}],f:30},kTimelineDefaultIdentifier:{i:"kTimelineDefaultIdentifier",n:"Main Timeline",z:1,b:[],a:[{f:"c",y:0,z:0.15,i:"e",e:1,s:0,o:"39"},{f:"c",y:0,z:0.15,i:"w",e:"Please click here to cancel",s:"Please click here for our live demo",o:"52"},{f:"c",p:2,y:0,z:0.15,i:"ActionHandler",e:{a:[{b:"kTimelineDefaultIdentifier",symbolOid:"2",p:7}]},s:{a:[{b:"kTimelineDefaultIdentifier",symbolOid:"2",p:7}]},o:"kTimelineDefaultIdentifier"},{f:"c",y:0,z:0.15,i:"g",e:"#FF2600",s:"#193356",o:"52"},{f:"c",y:0.15,z:0.15,i:"w",e:"<span style=\"color: rgb(255, 255, 255); font-family: Helvetica, Arial, sans-serif; font-size: 13px; font-style: normal; font-variant: normal; font-weight: bold; letter-spacing: normal; text-align: center; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; background-color: rgb(25, 51, 86);\">Please click here for our live demo</span><br>",s:"Please click here to cancel",o:"52"},{f:"c",y:0.15,z:0.15,i:"e",e:0,s:1,o:"39"},{f:"c",p:2,y:0.15,z:0.15,i:"ActionHandler",e:{a:[{}]},s:{a:[{b:"kTimelineDefaultIdentifier",symbolOid:"2",p:7}]},o:"kTimelineDefaultIdentifier"},{f:"c",y:0.15,z:0.15,i:"g",e:"#193356",s:"#FF2600",o:"52"},{y:1,i:"w",s:"<span style=\"color: rgb(255, 255, 255); font-family: Helvetica, Arial, sans-serif; font-size: 13px; font-style: normal; font-variant: normal; font-weight: bold; letter-spacing: normal; text-align: center; text-indent: 0px; text-transform: none; white-space: normal; word-spacing: 0px; background-color: rgb(25, 51, 86);\">Please click here for our live demo</span><br>",z:0,o:"52",f:"c"},{y:1,i:"e",s:0,z:0,o:"39",f:"c"},{y:1,i:"g",s:"#193356",z:0,o:"52",f:"c"},{f:"c",p:2,y:1,z:0,i:"ActionHandler",s:{a:[{i:0,b:"kTimelineDefaultIdentifier",p:9,symbolOid:"2"},{p:0}]},o:"kTimelineDefaultIdentifier"}],f:30}},bZ:180,O:["39","38","49","45","44","42","48","47","43","41","50","46","40","52","51"],v:{"42":{h:"31",p:"no-repeat",x:"visible",a:4,q:"100% 100%",b:45,j:"absolute",bF:"40",z:7,k:"div",c:158,d:118,r:"inline",e:1},"47":{h:"27",p:"no-repeat",x:"visible",a:0,q:"100% 100%",b:30,j:"absolute",bF:"40",z:5,k:"div",c:166,d:54,r:"inline",e:1},"43":{h:"21",p:"no-repeat",x:"visible",a:0,q:"100% 100%",b:0,j:"absolute",bF:"40",z:4,k:"div",c:202,d:271,r:"inline",e:0},"48":{h:"29",p:"no-repeat",x:"visible",a:30,q:"100% 100%",b:36,j:"absolute",bF:"40",z:6,k:"div",c:166,d:41,r:"inline",e:1},"38":{k:"div",x:"visible",c:240,d:430,z:7,r:"inline",a:0,j:"absolute",b:0},"44":{G:"#000000",aU:8,aV:8,r:"inline",e:1,s:"Verdana,Tahoma,Geneva,Sans-Serif",t:21,Y:25,Z:"break-word",v:"bold",w:"How\u2019s U? &nbsp;App",bF:"40",j:"absolute",x:"visible",yy:"nowrap",k:"div",y:"preserve",z:8,aS:8,aT:8,a:8,b:57},"49":{c:91,d:27,I:"Solid",e:1,J:"Solid",K:"Solid",L:"Solid",M:3,N:3,bF:"40",A:"#E62116",x:"visible",j:"absolute",B:"#E62116",k:"div",O:3,C:"#E62116",z:10,P:3,D:"#E62116",a:1,b:212},"40":{k:"div",x:"visible",c:202,d:271,z:6,r:"inline",a:16,j:"absolute",b:85},"50":{c:185,d:47,I:"None",e:1,J:"None",K:"None",g:"#DAE1ED",L:"None",M:0,N:0,bF:"40",A:"#D8DDE4",x:"visible",j:"absolute",B:"#D8DDE4",k:"div",O:0,C:"#D8DDE4",z:2,P:0,D:"#D8DDE4",a:11,b:106},"39":{c:203,d:273,I:"Solid",e:0,J:"Solid",K:"Solid",g:"#E8EBED",L:"Solid",M:1,w:"<div style=\"padding: 20px 5px 5px 5px;\">\n<label for=\"demoname\">Your Name:</label>\n<input style=\"height: 30px; width: 96%;\" id=\"demoname\" value=\"\" placeholder=\"ex. John Snow\">\n<p></p>\n<label for=\"demophone\">Your Number:</label>\n<input style=\"height: 30px; width: 96%;\" id=\"demophone\" value=\"\" placeholder=\"ex. +447465235465\">\n<p style=\"font-size: 11px\">Try our service for free! Enter your name and phone number, <strong>with country code</strong>. Then click which service you would perfer.</p>\n<button id=\"textmeButton\" style=\"background-color: black; height: 35px; width: 48%; color: #fff;\">TEXT</button>\n<button id=\"phonemeButton\" style=\"background-color: black; height: 35px; width: 48%; color: #fff;\">CALL</button>\n<div id=\"detailError\" style=\"text-align: center; width: 96%; height: 45px; float: left; position: relative; top: -150px; padding: 20px 5px 5px 5px; background-color: #bc1822; color: #ffffff; display: none\">We require valid details!</div>\n</div>",N:1,A:"#D8DDE4",x:"visible",j:"absolute",B:"#D8DDE4",k:"div",O:1,C:"#D8DDE4",z:3,bF:"38",D:"#D8DDE4",P:1,a:15,b:80},"45":{G:"#000000",aU:8,aV:8,r:"inline",e:1,s:"Verdana,Tahoma,Geneva,Sans-Serif",t:21,Y:25,Z:"break-word",v:"bold",w:"How\u2019s U? &nbsp;SMS",bF:"40",j:"absolute",x:"visible",yy:"nowrap",k:"div",y:"preserve",z:9,aS:8,aT:8,a:4,b:63},"51":{h:"7",p:"no-repeat",x:"visible",a:0,q:"100% 100%",b:0,j:"absolute",r:"inline",c:240,k:"div",z:1,d:430},"41":{c:194,d:86,I:"None",e:1,J:"None",K:"None",g:"#DAE1ED",L:"None",M:0,N:0,bF:"40",A:"#D8DDE4",x:"visible",j:"absolute",B:"#D8DDE4",k:"div",O:0,C:"#D8DDE4",z:3,P:0,D:"#D8DDE4",a:0,b:155},"46":{c:194,d:54,I:"None",e:1,J:"None",K:"None",g:"#DAE1ED",L:"None",M:0,N:0,bF:"40",A:"#D8DDE4",x:"visible",j:"absolute",B:"#D8DDE4",k:"div",O:0,C:"#D8DDE4",z:1,P:0,D:"#D8DDE4",a:0,b:45},"52":{b:437,z:2,K:"Solid",c:232,cP:"ani",d:25,L:"Solid",M:1,bD:"none",aS:3,N:1,aT:3,O:1,g:"#193356",aU:3,P:1,Q:0,i:"livedemoButton",aV:3,R:"#FFFFFF",j:"absolute",S:0,k:"div",T:0,A:"#A0A0A0",B:"#A0A0A0",Z:"break-word",r:"inline",C:"#A0A0A0",D:"#A0A0A0",t:13,F:"center",v:"bold",G:"#FFFFFF",aP:"pointer",w:"Please click here for our live demo",x:"visible",I:"Solid",a:0,y:"preserve",J:"Solid"}}}],{},g,{},null,false,true,-1,true,true,true,true);f[c]=a.API;document.getElementById(e).setAttribute("HYP_dn",
c);a.z_o(this.body)})();})();