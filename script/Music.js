/* Merci à "RAD ZONE" ( http://radservebeer.free.fr ) pour l'inspiration de se script */
var P,T;
var over = -1;
var fontSize = 40;

function zoom(s)
{
	if(s!=over)
	{
		over = s;
		for(var i=0;i<T;i++)
		{
			if (Math.abs(i - s)<10)
				P[i].style.fontSize=Math.floor(fontSize / (0.3*Math.abs(i - s) + 1))+"px";
			else
				P[i].style.fontSize=Math.floor(fontSize / 4)+"px";
		}
	}
}

onload = function()
{
	P = document.getElementById("music-list").getElementsByTagName("li");
	T = P.length;
	for (var i=0;i<T;i++)
	{
		P[i].style.width = "100%";
		P[i].onmouseover=new Function("zoom("+i+");");
	}
	zoom(0);
}