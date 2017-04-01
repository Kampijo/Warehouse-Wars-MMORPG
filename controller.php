stage=null;
interval=null;
mobileInterval=null;
score=0;
socket = null;
direction= "":

function alertStuff(data){ alert(JSON.stringify(data)); }
function startGame(){
	if(interval == null){
		interval = setInterval(step, 1000);
	} if (mobileInterval == null){
		mobileInterval = setInterval(sendMobile, 100);
	}
}
function resetGame(){
	clearInterval(interval);
	clearInterval(mobileInterval);
	interval=null;
	mobileInterval=null;
	score=0;
}

function playGame(){
	resetGame();
	startGame();
	connectSocket();
	$("#LoginPage").hide();
	$("#ProfilePage").hide();
	$("#RegisterPage").hide();
	$("#game").show();
}
function showProfile(){
	resetGame();
	closeSocket();
	$("#game").hide();	
	$("#greeting").html("Hello there, "+sessionStorage.getItem('user')+"!");
	getScores();
	$("#ProfilePage").show();
}
function updatePassword(){
	var input = {"password":$("#newpasswd").val()};
    var params = {
               method: "POST",
               url: "update",
               data: JSON.stringify(input),
			   contentType: 'application/json; charset=UTF-8',
			   dataType: "json",
               headers: { "Authorization": "Basic " + btoa(sessionStorage.getItem('user')+":"+sessionStorage.getItem('pass'))}
              };
          $.ajax(params).done(function(data){
              alert(data["status"]);
			  sessionStorage.setItem('pass', $("#newpasswd").val());
          }).fail(function(data){
              var response = JSON.parse(data["responseText"]);
              alert(response["status"]);
          });
}
function loginFunction(){
	var params = {
              method: "GET",
              url: "authenticate",
              headers: { "Authorization": "Basic " + btoa($("#loginuser").val() + ":" + $("#loginpasswd").val())}
          };
	$.ajax(params).done(function(data){
		alert(data["status"]);	
		sessionStorage.setItem('user',$("#loginuser").val());
		sessionStorage.setItem('pass',$("#loginpasswd").val());
		$("#links").show();
        	playGame();
	}).fail(function(data){
		var response = JSON.parse(data["responseText"]);
		alert(response["status"]);
		$("#loginuser").css('background-color', 'red');
        $("#loginpasswd").css('background-color', 'red');
	});
}
function logoutFunction(){
	closeSocket();
	sessionStorage.clear();
	window.location.reload();	
}
function deleteAccount(){
	var params = {
			method: "DELETE",
			url: "delete/"+sessionStorage.getItem("user"),
			headers: { "Authorization": "Basic " + btoa(sessionStorage.getItem("user") + ":" + sessionStorage.getItem("pass")) }
		};
	$.ajax(params).done(function(data){
		alert(data["status"]);
		logoutFunction();
	}).fail(function(data){
		var response = JSON.parse(data["responseText"]);
		alert(response["status"]);
	});

}
function registerFunction(){
	var input = { "user": $("#registeruser").val(), "password": $("#registerpasswd").val()};
	var params = {
			method: "PUT",
			url: "register",
			data: JSON.stringify(input),
			contentType: 'application/json; charset=UTF-8',
			dataType: "json"
		};
	$.ajax(params).done(function(data){
		alert(data["status"]);
		sessionStorage.setItem('user', $("#registeruser").val());
		sessionStorage.setItem('pass', $("#registerpasswd").val());
		$('#links').show();
		playGame();
	}).fail(function(data){
		var response = JSON.parse(data["responseText"]);
		alert(response["status"]);
		$("#registeruser").css('background-color', 'red');
	});
}
function putScore(){
	var input = {"score":score};
	var params = {
			method: "PUT",
			url: "insertScore",
			data: JSON.stringify(input),
			contentType: 'application/json; charset=UTF-8',
			dataType:"json",
			headers: { "Authorization": "Basic " + btoa(sessionStorage.getItem('user') + ":" + sessionStorage.getItem('pass'))}
		};
	$.ajax(params).done();
}
function getScores(){
	var params = {
			method: "GET",
			url: sessionStorage.getItem('user')+"/hiScores",
			headers: { "Authorization": "Basic " + btoa(sessionStorage.getItem('user') + ":" + sessionStorage.getItem('pass'))}
		};
	$.ajax(params).done(function(data){
		var scores = data["response"];		
		var scoreHTML = "<tr><td>Score</td></tr>";
		for(var i = 0; i < scores.length; i++){
			scoreHTML = scoreHTML+"<tr><td>"+scores[i]+"</td></tr>";
		}
		$("#userscorestable").html(scoreHTML);
	});
}
function getHiscores(){
	var params = {
			method: "GET",
			url: "hiScores"
	};
	$.ajax(params).done(function(data){
		var hiscores = data["response"];
		var scoreHTML = "<tr><td>User</td><td>Score</td></tr>";
		for(var i = 0; i < hiscores.length; i++){
			var row = hiscores[i];
			scoreHTML = scoreHTML+"<tr><td>"+row.user+"</td><td>"+row.score+"</td></tr>";
		}
		$("#hiscorestable").html(scoreHTML);
	});
}
function movePlayer(direction){
	if(interval != null){
		socket.send(JSON.stringify({'direction':direction, 'id':sessionStorage.getItem('user')}));
	}
}
function readKeyboard(event){

	if(interval != null){
		input = String.fromCharCode(event.keyCode);
		input = input.toLowerCase();

		if(input == "w") {
	    	return "N";
	    }
	    if(input == "s") {
	    	return "S";
	    }
	    if(input == "d") {
	    	return "E";
	    }
	    if(input == "a") {
	    	return "W";
	    }
	    if(input == "q") {
	    	return "NW";
	    }
	    if(input == "e") {
	    	return "NE";
	    }
	    if(input == "z") {
	    	return "SW";
	    }
	    if(input == "c") {
	        return "SE";
	    }
	}
}
function handleOrientation(event){
  	x  = event.beta;
  	y  = event.gamma;

  	if(y >= 25){
  		direction = "E";
  	} if (y <= -25){
  		direction = "W";
  	} if (x <= 20) {
  		direction = "N";
  	} if (x >= 75) {
  		direction = "S";
  	} else {
  		direction = "";
  	}
}
function step(){
	score++;
	$('#score').html(score);
}
function sendMobile(){
	socket.send(JSON.stringify({'direction': direction, 'id': sessionStorage.getItem('user')}));
}
$(function(){

	$('#LoginPage').show();
	$('#RegisterPage').hide();
	$('#ProfilePage').hide();
	$('#game').hide();
	$('#links').hide();
	$('#score').html(score);

	getHiscores();

	$('#showRegister').click(function(){ 
		$('#LoginPage').hide();
		$('#RegisterPage').show(); 

	});
	$('#Login').click(function(){
		loginFunction();
	});
	$('#Register').click(function(){
		if($('#registeruser')[0].checkValidity() && $('#registerpasswd')[0].checkValidity()){
			registerFunction();
		}
	});
	$('#updatePassword').click(function(){
		if($('#newpasswd')[0].checkValidity()){
			updatePassword();
		}
	});
	$('#deleteAccount').click(function(){
		if(confirm("Are you sure? You cannot undo these changes.")){
			deleteAccount();
		} 
	});
});
	function connectSocket(){
		socket = new WebSocket("ws://cslinux.utm.utoronto.ca:10551/"+sessionStorage.getItem('user'));
		socket.onopen = function (event) {
			console.log("connected");
		};
		socket.onclose = function (event) {
			console.log("disconnected");
		};
		socket.onmessage = function (event) {
			var res = JSON.parse(event.data);
			if(res.type == "render"){ 
				document.getElementById(res.id).innerHTML = res.data;
			} else if(res.type == "update"){
				document.getElementById(res.id).src = res.data;		
			} else if(res.type == "death"){
				if(res.id == sessionStorage.getItem('user')){
					socket.close();
					putScore();
					resetGame();
				}
			} else if(res.type == "users") {
				usersHTML = "<tr><td>Users</td></tr>";		
				for(var i = 0; i < res.users.length; i++){
					usersHTML += "<tr><td>"+res.users[i]+"</td></tr>";
				}
				$('#currentUsers').html(usersHTML);
			} else if (res.type == "player" && res.id == sessionStorage.getItem('user')) {
				document.getElementById(res.prevCoord).style.backgroundColor = "";
				document.getElementById(res.coord).style.backgroundColor = "red";
			}
		}
		document.addEventListener('keydown', function(event) { 
			socket.send(JSON.stringify({'direction': readKeyboard(event), 'id': sessionStorage.getItem('user')})); 
		});
		window.addEventListener('deviceorientation', handleOrientation);
	}
	function closeSocket(){
		socket.close();
	}
