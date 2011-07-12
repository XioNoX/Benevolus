function changement_festival(festival_id)
{
	var url = "/festival_actif/" + festival_id + "?url=" + document.location.pathname;
	window.location.replace(url);
}