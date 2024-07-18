var display_event = 0;
var display_list = 0;
var name = "";
var Mouse = {"x":0,"y":0};

// Demande de gestion de l'evenement à NetScape
if(navigator.appName.substring(0,3) == "Net") {
	document.captureEvents(Event.MOUSEMOVE);
}
 
// Gestion de l'evenement
var OnMouseMoveEventHandler=function() {}
var OnMouseMove = function (e)
{
   Mouse.x = (navigator.appName.substring(0,3) == "Net") ? e.pageX : event.x+document.body.scrollLeft;
   Mouse.y = (navigator.appName.substring(0,3) == "Net") ? e.pageY : event.y+document.body.scrollTop;
   if (Mouse.x < 0) {Mouse.x=0;}
   if (Mouse.y < 0) {Mouse.y=0;}
   OnMouseMoveEventHandler(e)
}
 
try {
   document.attachEvent("onmousemove", OnMouseMove, true);
}
catch (ex) {
   document.addEventListener("mousemove", OnMouseMove, true);
}

function getXhr()
{
	var xhr = null; 
	if(window.XMLHttpRequest) // Firefox et autres
		xhr = new XMLHttpRequest(); 
		else if(window.ActiveXObject) // Internet Explorer 
		{
			try
			{
				xhr = new ActiveXObject("Msxml2.XMLHTTP");
			}
			catch (e)
			{
				xhr = new ActiveXObject("Microsoft.XMLHTTP");
			}
		}
		else // XMLHttpRequest non supporté par le navigateur 
		{
			alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest..."); 
			xhr = false; 
		}
	return xhr
}

function ajax(obj)
{
	if (display_event==0 || name!=obj.getAttribute("name"))
	{
		var xhr = getXhr()
		// On défini ce qu'on va faire quand on aura la réponse
		xhr.onreadystatechange = function()
		{
			// On ne fait quelque chose que si on a tout reçu et que le serveur est ok
			if(xhr.readyState == 4 && xhr.status == 200)
			{
				leselect=xhr.responseText;
				var div = document.getElementById('event');
				div.innerHTML = leselect;
				div.style.display = 'block';
				var height = div.offsetHeight;
				if(navigator.appName == "Microsoft Internet Explorer")
				{
					div.style.top = (Mouse.y-height)+"px";
					div.style.left = (Mouse.x)+"px";
				}
				else
				{
					div.style.top = (Mouse.y-height-(document.getElementById('page').offsetTop))+"px";
					div.style.left = (Mouse.x-(document.getElementById('page').offsetLeft))+"px";
				}
				display_event=1;
			}
		}
		xhr.open("POST","ajax.php",true);
		xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		name = obj.getAttribute("name");
		xhr.send("page="+name.split('|')[1]+"&action=ajax&date="+name.split('|')[0]);
	}
	else
	{
		document.getElementById('event').style.display = 'none';
		display_event=0;
	}
}

function calendar()
{
	if (display_list==0)
	{
		document.getElementById('months').style.display = 'block';
		display_list=1;
	}
	else
	{
		document.getElementById('months').style.display = 'none';
		display_list=0;
	}
}